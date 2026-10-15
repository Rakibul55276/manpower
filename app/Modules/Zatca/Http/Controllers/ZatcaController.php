<?php
namespace App\Modules\Zatca\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceSetting;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use App\Modules\SafetyShop\Sales\Models\SaleDebitNote;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Models\ZatcaDocument;
use App\Modules\Zatca\Services\ZatcaInvoiceService;
use App\Modules\Zatca\Services\FatooraClient;
use App\Modules\Zatca\Services\ZatcaOnboardingService;
use App\Modules\Zatca\Services\ZatcaSdkService;
use App\Models\ActivityLog;
use App\Jobs\SubmitZatcaDocument;
use Illuminate\Http\Request;

class ZatcaController extends Controller
{
    public function index()
    {
        $settings = InvoiceSetting::firstOrFail();
        $stats = [
            'connected' => false,
            'compliance_csid' => false,
            'production_csid' => false,
            'submitted' => Invoice::whereNotIn('zatca_status', ['not_connected', 'not_submitted'])->count(),
        ];
        return view('zatca.index', compact('settings', 'stats'));
    }

    public function safetyShop(Request $request)
    {
        $companyId=(int)$request->user()->company_id;
        abort_unless($companyId>0,403,'Select a company account to manage its ZATCA integration.');
        $device=ZatcaDevice::firstOrCreate(['company_id'=>$companyId],['environment'=>'disabled','status'=>'not_configured']);
        $seller=ReceiptSetting::forCompany($companyId)->first();
        $documents=ZatcaDocument::where('company_id',$companyId)->latest()->paginate(25);
        $baseSales=Sale::whereHas('location',fn($q)=>$q->where('company_id',$companyId))->where('status','finalized');
        $legacyCount=(clone $baseSales)->whereNull('invoice_sequence_number')->count();
        $eligible=(clone $baseSales)->whereNotNull('invoice_sequence_number')->whereDoesntHave('zatcaDocument')->latest()->limit(25)->get();
        $stats=['prepared'=>ZatcaDocument::where('company_id',$companyId)->where('status','prepared')->count(),'accepted'=>ZatcaDocument::where('company_id',$companyId)->whereIn('status',['reported','cleared'])->count(),'failed'=>ZatcaDocument::where('company_id',$companyId)->where('status','failed')->count()];
        return view('zatca.safety-shop',compact('device','seller','documents','eligible','legacyCount','stats'));
    }

    public function prepare(Request $request,Sale $sale,ZatcaInvoiceService $service)
    {
        abort_unless((int)$sale->location()->value('company_id')===(int)$request->user()->company_id,404);
        $document=$service->prepare($sale);
        return redirect()->route('safety-shop.zatca.index')->with('success','ZATCA XML prepared for '.$sale->invoice_number.'. Sign and validate it with the official SDK before compliance testing or submission.');
    }
    public function prepareReturn(Request $request,SaleReturn $return,ZatcaInvoiceService $service)
    {
        abort_unless((int)$return->location()->value('company_id')===(int)$request->user()->company_id,404);$service->prepareReturn($return);return redirect()->route('safety-shop.zatca.index')->with('success','ZATCA credit-note XML prepared. Sign and validate it with the official SDK before compliance testing or submission.');
    }
    public function prepareDebitNote(Request $request,SaleDebitNote $debitNote,ZatcaInvoiceService $service)
    {
        abort_unless((int)$debitNote->location()->value('company_id')===(int)$request->user()->company_id,404);$service->prepareDebitNote($debitNote);return redirect()->route('safety-shop.zatca.index')->with('success','ZATCA debit-note XML prepared. Sign and validate it before submission.');
    }

    public function xml(Request $request,ZatcaDocument $document)
    {
        abort_unless((int)$document->company_id===(int)$request->user()->company_id,404);
        return response($document->cleared_xml?:($document->signed_xml?:$document->unsigned_xml),200,['Content-Type'=>'application/xml; charset=UTF-8','Content-Disposition'=>'attachment; filename="'.$document->uuid.'.xml"']);
    }

    public function configure(Request $request)
    {
        $data=$request->validate(['environment'=>'required|in:disabled,simulation,production','solution_unit_name'=>'required|string|max:150']);
        if($data['environment']==='production'&&!$request->boolean('confirm_production'))return redirect()->route('safety-shop.zatca.index')->with('error','Tick “I explicitly authorize production mode” before activating the production environment.');
        $device=ZatcaDevice::firstOrCreate(['company_id'=>$request->user()->company_id]);
        if($device->environment!==$data['environment']&&(filled($device->compliance_certificate)||filled($device->production_certificate)))return redirect()->route('safety-shop.zatca.index')->with('error','The environment is locked after a CSID is installed. Use a controlled re-onboarding procedure with credential revocation instead of switching this device.');
        $device->update($data);ActivityLog::record('Updated ZATCA EGS configuration',$data['environment']);return redirect()->route('safety-shop.zatca.index')->with('success','EGS configuration updated.');
    }

    public function generateCsr(Request $request,ZatcaOnboardingService $service)
    {
        $device=ZatcaDevice::where('company_id',$request->user()->company_id)->firstOrFail();
        if(filled($device->compliance_certificate)||filled($device->production_certificate))return redirect()->route('safety-shop.zatca.index')->with('error','Key regeneration is locked because a CSID is already installed. Replacing the key requires controlled re-onboarding.');
        $service->generate($device);ActivityLog::record('Generated ZATCA EGS key and CSR',$device->solution_unit_name);return redirect()->route('safety-shop.zatca.index')->with('success','The private key and CSR were generated. The private key is encrypted at rest.');
    }

    public function complianceCsid(Request $request,FatooraClient $client,ZatcaOnboardingService $onboarding)
    {
        $device=ZatcaDevice::where('company_id',$request->user()->company_id)->firstOrFail();
        $data=$request->validate(['otp'=>['nullable','digits:6']]);
        try {
            $onboarding->installCompliance($device,$client->complianceCsid($device,$data['otp']??null));
            ActivityLog::record('Installed ZATCA Compliance CSID',$device->solution_unit_name);
            return redirect()->route('safety-shop.zatca.index')->with('success','Compliance CSID installed. Run all compliance document checks next.');
        } catch (\Illuminate\Http\Client\RequestException $exception) {
            $status=$exception->response?->status();
            $message=data_get($exception->response?->json(),'errorMessage')?:data_get($exception->response?->json(),'message')?:data_get($exception->response?->json(),'errors.0.message');
            $device->update(['last_error'=>trim('HTTP '.($status?:'error').' '.($message?:'ZATCA rejected the request.'))]);
            return redirect()->route('safety-shop.zatca.index')->with('error','ZATCA rejected the Compliance CSID request'.($status?' (HTTP '.$status.')':'.').($message?' '.$message:''));
        } catch (\Illuminate\Http\Client\ConnectionException $exception) {
            report($exception);
            return redirect()->route('safety-shop.zatca.index')->with('error','The ZATCA sandbox could not be reached. Check the server internet, firewall and sandbox URL, then try again.');
        }
    }

    public function signValidate(Request $request,ZatcaDocument $document,ZatcaSdkService $sdk)
    {
        abort_unless((int)$document->company_id===(int)$request->user()->company_id,404);$document->load('device');
        abort_unless(filled($document->device?->compliance_certificate)||filled($document->device?->production_certificate),422,'Install a Compliance or Production CSID first.');
        $signed=$sdk->sign($document);abort_unless($signed['status']==='passed'&&filled($signed['xml']),422,'Official SDK signing failed: '.trim(($signed['error']??'').' '.($signed['output']??'')));
        $document->signed_xml=$signed['xml'];$validation=$sdk->validate($document);$results=$document->validation_results?:[];$results['zatca_sdk']=$validation;
        $changes=['signed_xml'=>$signed['xml'],'validation_results'=>$results,'last_error'=>$validation['status']==='passed'?null:trim(($validation['error']??'').' '.($validation['output']??''))];
        if(filled($signed['invoice_hash_hex']??null))$changes['invoice_hash']=$signed['invoice_hash_hex'];
        $document->update($changes);
        if($validation['status']==='passed'&&filled($signed['invoice_hash_base64']??null)&&$document->device&&$document->device->last_icv===$document->icv)$document->device->update(['previous_invoice_hash'=>$signed['invoice_hash_base64']]);
        ActivityLog::record('Signed and SDK-validated ZATCA document',$document->uuid);
        return back()->with($validation['status']==='passed'?'success':'error',$validation['status']==='passed'?'Official ZATCA SDK validation passed.':'SDK validation failed. Review the document errors.');
    }

    public function complianceCheck(Request $request,ZatcaDocument $document,FatooraClient $client)
    {
        abort_unless((int)$document->company_id===(int)$request->user()->company_id,404);$document->load('device');abort_unless(filled($document->signed_xml),422,'Sign and validate this document first.');abort_unless(data_get($document->validation_results,'zatca_sdk.status')==='passed',422,'Official SDK validation must pass first.');
        $response=$client->complianceCheck($document->device,$document);$status=strtoupper((string)($response['reportingStatus']??$response['clearanceStatus']??$response['validationResults']['status']??''));$passed=in_array($status,['REPORTED','CLEARED','PASS','PASSED'],true)&&empty($response['validationResults']['errorMessages']??[]);
        $document->update(['status'=>$passed?'compliance_passed':'failed','response_payload'=>$response,'last_error'=>$passed?null:json_encode($response['validationResults']['errorMessages']??$response)]);ActivityLog::record('Ran ZATCA compliance check',$document->uuid);
        return back()->with($passed?'success':'error',$passed?'ZATCA compliance check passed.':'ZATCA compliance check failed. Review the response.');
    }

    public function submitDocument(Request $request,ZatcaDocument $document)
    {
        abort_unless((int)$document->company_id===(int)$request->user()->company_id,404);
        $document->load('device');
        abort_unless($document->device?->environment==='production'&&filled($document->device?->production_certificate),422,'Production mode and a Production CSID are required.');
        abort_unless(filled($document->signed_xml),422,'Sign and validate this document first.');
        abort_unless(data_get($document->validation_results,'zatca_sdk.status')==='passed',422,'Official SDK validation must pass before submission.');
        abort_if(in_array($document->status,['reported','cleared'],true),422,'This document has already been accepted by ZATCA.');
        try {
            SubmitZatcaDocument::dispatchSync($document->id);
            ActivityLog::record('Submitted ZATCA document',$document->uuid);
            return back()->with('success','ZATCA accepted the document.');
        } catch (\Throwable $exception) {
            report($exception);
            $document->refresh()->update(['status'=>'failed','last_error'=>mb_substr($exception->getMessage(),0,2000),'next_retry_at'=>now()->addHour()]);
            return back()->with('error','ZATCA submission failed. The document is preserved for a controlled retry. Review the response details.');
        }
    }

    public function productionCsid(Request $request,FatooraClient $client,ZatcaOnboardingService $onboarding)
    {
        $device=ZatcaDevice::where('company_id',$request->user()->company_id)->firstOrFail();
        $passed=ZatcaDocument::where('company_id',$device->company_id)->where('status','compliance_passed')->get(['document_kind','invoice_type'])->map(fn($document)=>$document->invoice_type.':'.$document->document_kind)->unique();
        $required=collect(['standard:invoice','simplified:invoice','standard:credit_note','simplified:credit_note','standard:debit_note','simplified:debit_note']);$missing=$required->diff($passed);
        if($missing->isNotEmpty())return redirect()->route('safety-shop.zatca.index')->with('error','Production CSID is locked. Missing compliance checks: '.$missing->implode(', ').'.');
        try{$onboarding->installProduction($device,$client->productionCsid($device));ActivityLog::record('Installed ZATCA Production CSID',$device->solution_unit_name);return redirect()->route('safety-shop.zatca.index')->with('success','Production CSID installed. Keep production disabled until final acceptance testing is signed off.');}catch(\Throwable $exception){report($exception);return redirect()->route('safety-shop.zatca.index')->with('error','The Production CSID request failed. Review the ZATCA response log and compliance checks.');}
    }
    public function renewalCsr(Request $request,ZatcaOnboardingService $onboarding)
    {
        $device=ZatcaDevice::where('company_id',$request->user()->company_id)->firstOrFail();$onboarding->generateRenewal($device);ActivityLog::record('Generated ZATCA renewal CSR',$device->solution_unit_name);return back()->with('success','Renewal key and CSR generated securely. Obtain a renewal OTP in FATOORA, then complete renewal below.');
    }
    public function renewProductionCsid(Request $request,FatooraClient $client,ZatcaOnboardingService $onboarding)
    {
        $data=$request->validate(['otp'=>'required|digits:6']);$device=ZatcaDevice::where('company_id',$request->user()->company_id)->firstOrFail();
        try{$onboarding->installRenewal($device,$client->renewProductionCsid($device,$data['otp']));ActivityLog::record('Renewed ZATCA Production CSID',$device->solution_unit_name);return back()->with('success','Production CSID renewed and the pending private key activated.');}catch(\Throwable $exception){report($exception);return back()->with('error','Production CSID renewal failed. The current production credential remains active; review the ZATCA response and retry.');}
    }
    public function csv(Request $request)
    {
        $companyId=(int)$request->user()->company_id;$rows=ZatcaDocument::where('company_id',$companyId)->orderBy('icv')->get();return response()->streamDownload(function()use($rows){$safe=fn($value)=>is_string($value)&&preg_match('/^[=+\-@\t\r]/',$value)?"'".$value:$value;$out=fopen('php://output','w');fputcsv($out,['ICV','UUID','Document','Invoice type','Mode','Hash','Status','Attempts','Submitted','Accepted','Last error']);foreach($rows as $d)fputcsv($out,array_map($safe,[$d->icv,$d->uuid,$d->document_kind,$d->invoice_type,$d->submission_mode,$d->invoice_hash,$d->status,$d->attempt_count,optional($d->submitted_at)->toIso8601String(),optional($d->accepted_at)->toIso8601String(),$d->last_error]));fclose($out);},'zatca-audit-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv']);
    }
}
