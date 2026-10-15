<?php
namespace App\Modules\Zatca\Services;

use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Models\ZatcaDocument;

class ZatcaIssuanceGate
{
    public function assertPrintable(int $companyId,string $invoiceType,?ZatcaDocument $document,bool $draft=false): void
    {
        if($draft||!config('zatca.enabled'))return;
        $device=ZatcaDevice::where('company_id',$companyId)->first();
        if(!$device||$device->environment!=='production')return;
        abort_unless($document,409,'Prepare, sign and submit this ZATCA document before issuing its customer PDF.');
        if($invoiceType==='standard')abort_unless($document->status==='cleared',409,'A standard tax document cannot be issued until ZATCA clearance is CLEARED.');
        abort_unless(filled($document->signed_xml)&&data_get($document->validation_results,'zatca_sdk.status')==='passed',409,'A simplified tax document must be signed and officially validated before issue.');
    }
}
