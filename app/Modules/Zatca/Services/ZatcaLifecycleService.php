<?php
namespace App\Modules\Zatca\Services;

use App\Jobs\SubmitZatcaDocument;
use App\Models\ActivityLog;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use App\Modules\SafetyShop\Sales\Models\SaleDebitNote;
use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Models\ZatcaDocument;
use Throwable;

class ZatcaLifecycleService
{
    public function __construct(private ZatcaInvoiceService $xml,private ZatcaSdkService $sdk){}

    public function processSale(Sale $sale): bool{return $this->process('sale',$sale);}
    public function processReturn(SaleReturn $return): bool{return $this->process('return',$return);}
    public function processDebitNote(SaleDebitNote $note): bool{return $this->process('debit_note',$note);}

    private function process(string $type,$source): bool
    {
        $companyId=(int)$source->location()->value('company_id');
        $device=ZatcaDevice::where('company_id',$companyId)->first();
        if(!$device||$device->environment!=='production'||$device->status!=='production_ready'||!filled($device->production_certificate))return false;
        $document=null;
        try{
            $document=$type==='sale'?$this->xml->prepare($source):($type==='return'?$this->xml->prepareReturn($source):$this->xml->prepareDebitNote($source));
            $document->load('device');
            if(!filled($document->signed_xml)){
                $signed=$this->sdk->sign($document);
                if($signed['status']!=='passed'||!filled($signed['xml']))throw new \RuntimeException('Official SDK signing failed: '.trim(($signed['error']??'').' '.($signed['output']??'')));
                $document->signed_xml=$signed['xml'];
                $validation=$this->sdk->validate($document);
                $results=$document->validation_results?:[];$results['zatca_sdk']=$validation;
                if($validation['status']!=='passed')throw new \RuntimeException('Official SDK validation failed: '.trim(($validation['error']??'').' '.($validation['output']??'')));
                $changes=['signed_xml'=>$signed['xml'],'validation_results'=>$results,'last_error'=>null];
                if(filled($signed['invoice_hash_hex']??null))$changes['invoice_hash']=$signed['invoice_hash_hex'];
                $document->update($changes);
                if(filled($signed['invoice_hash_base64']??null)&&$document->device->last_icv===$document->icv)$document->device->update(['previous_invoice_hash'=>$signed['invoice_hash_base64']]);
            }
            if(!in_array($document->status,['cleared','reported'],true))SubmitZatcaDocument::dispatchSync($document->id);
            ActivityLog::record('Automatically processed ZATCA document',$document->uuid);
            return true;
        }catch(Throwable $exception){
            report($exception);
            if($document)$document->update(['status'=>'failed','last_error'=>mb_substr($exception->getMessage(),0,2000),'next_retry_at'=>now()->addMinutes(15)]);
            return false;
        }
    }
}
