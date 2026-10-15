<?php
namespace App\Modules\Zatca\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceSetting;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Models\ZatcaDocument;
use App\Modules\Zatca\Services\ZatcaInvoiceService;
use App\Modules\Zatca\Services\FatooraClient;
use App\Modules\Zatca\Services\ZatcaOnboardingService;
use App\Models\ActivityLog;
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
        return redirect()->route('safety-shop.zatca.index')->with('success','ZATCA XML prepared and locally validated for '.$sale->invoice_number.'. Live submission remains locked.');
    }
    public function prepareReturn(Request $request,SaleReturn $return,ZatcaInvoiceService $service)
    {
        abort_unless((int)$return->location()->value('company_id')===(int)$request->user()->company_id,404);$service->prepareReturn($return);return redirect()->route('safety-shop.zatca.index')->with('success','ZATCA credit-note XML prepared. Live submission remains controlled by the EGS environment.');
    }

    public function xml(Request $request,ZatcaDocument $document)
    {
        abort_unless((int)$document->company_id===(int)$request->user()->company_id,404);
        return response($document->cleared_xml?:($document->signed_xml?:$document->unsigned_xml),200,['Content-Type'=>'application/xml; charset=UTF-8','Content-Disposition'=>'attachment; filename="'.$document->uuid.'.xml"']);
    }

    public function configure(Request $request)
    {
        $data=$request->validate(['environment'=>'required|in:disabled,simulation,production','solution_unit_name'=>'required|string|max:150']);
        if($data['environment']==='production')abort_unless($request->boolean('confirm_production'),422,'Production activation requires explicit confirmation.');
        $device=ZatcaDevice::firstOrCreate(['company_id'=>$request->user()->company_id]);$device->update($data);ActivityLog::record('Updated ZATCA EGS configuration',$data['environment']);return back()->with('success','EGS configuration updated.');
    }

    public function generateCsr(Request $request,ZatcaOnboardingService $service)
    {
        $device=ZatcaDevice::where('company_id',$request->user()->company_id)->firstOrFail();$service->generate($device);ActivityLog::record('Generated ZATCA EGS key and CSR',$device->solution_unit_name);return back()->with('success','The private key and CSR were generated. The private key is encrypted at rest.');
    }

    public function complianceCsid(Request $request,FatooraClient $client,ZatcaOnboardingService $onboarding)
    {
        $data=$request->validate(['otp'=>'required|digits:6']);$device=ZatcaDevice::where('company_id',$request->user()->company_id)->firstOrFail();$onboarding->installCompliance($device,$client->complianceCsid($device,$data['otp']));ActivityLog::record('Installed ZATCA Compliance CSID',$device->solution_unit_name);return back()->with('success','Compliance CSID installed. Run all compliance document checks next.');
    }

    public function productionCsid(Request $request,FatooraClient $client,ZatcaOnboardingService $onboarding)
    {
        $device=ZatcaDevice::where('company_id',$request->user()->company_id)->firstOrFail();$required=ZatcaDocument::where('company_id',$device->company_id)->where('status','compliance_passed')->distinct()->count('document_kind');abort_if($required<2,422,'Pass invoice and credit/debit-note compliance checks before requesting Production CSID.');$onboarding->installProduction($device,$client->productionCsid($device));ActivityLog::record('Installed ZATCA Production CSID',$device->solution_unit_name);return back()->with('success','Production CSID installed. Keep production disabled until final acceptance testing is signed off.');
    }
    public function csv(Request $request)
    {
        $companyId=(int)$request->user()->company_id;$rows=ZatcaDocument::where('company_id',$companyId)->orderBy('icv')->get();return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['ICV','UUID','Document','Invoice type','Mode','Hash','Status','Attempts','Submitted','Accepted','Last error']);foreach($rows as $d)fputcsv($out,[$d->icv,$d->uuid,$d->document_kind,$d->invoice_type,$d->submission_mode,$d->invoice_hash,$d->status,$d->attempt_count,optional($d->submitted_at)->toIso8601String(),optional($d->accepted_at)->toIso8601String(),$d->last_error]);fclose($out);},'zatca-audit-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv']);
    }
}
