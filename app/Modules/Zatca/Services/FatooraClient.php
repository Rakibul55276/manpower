<?php
namespace App\Modules\Zatca\Services;

use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Models\ZatcaDocument;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FatooraClient
{
    public function readiness(ZatcaDevice $device): array
    {
        return ['environment_enabled'=>in_array($device->environment,['simulation','production'],true),'certificate_installed'=>filled($device->production_certificate),'secret_installed'=>filled($device->production_secret_encrypted),'private_key_installed'=>filled($device->private_key_encrypted),'certificate_valid'=>!$device->certificate_expires_at||$device->certificate_expires_at->isFuture()];
    }

    public function complianceCsid(ZatcaDevice $device,string $otp): array
    {
        $this->assertEnvironment($device);if(!filled($device->csr))$this->fail('csr','Generate the EGS key and CSR first.');
        return $this->plain($device)->withHeaders(['OTP'=>$otp])->post('/compliance',['csr'=>base64_encode($device->csr)])->throw()->json();
    }
    public function complianceCheck(ZatcaDevice $device,ZatcaDocument $document): array{return $this->authorized($device,false)->post('/compliance/invoices',$this->invoicePayload($document))->throw()->json();}
    public function productionCsid(ZatcaDevice $device): array{if(!filled($device->compliance_request_id))$this->fail('device','Compliance CSID is missing.');return $this->authorized($device,false)->post('/production/csids',['compliance_request_id'=>$device->compliance_request_id])->throw()->json();}
    public function submit(ZatcaDevice $device,ZatcaDocument $document): array{$ready=$this->readiness($device);if(in_array(false,$ready,true))$this->fail('device','The production EGS credential set is incomplete or expired.');$path=$document->submission_mode==='clearance'?'/invoices/clearance/single':'/invoices/reporting/single';return $this->authorized($device,true)->post($path,$this->invoicePayload($document))->throw()->json();}
    private function invoicePayload(ZatcaDocument $document): array{return ['invoiceHash'=>base64_encode(hex2bin($document->invoice_hash)),'uuid'=>$document->uuid,'invoice'=>base64_encode($document->signed_xml?:$document->unsigned_xml)];}
    private function plain(ZatcaDevice $device): PendingRequest{return Http::baseUrl($this->baseUrl($device))->acceptJson()->asJson()->connectTimeout(config('zatca.connect_timeout'))->timeout(config('zatca.request_timeout'))->withHeaders(['Accept-Version'=>'V2']);}
    private function authorized(ZatcaDevice $device,bool $production): PendingRequest{$certificate=$production?$device->production_certificate:$device->compliance_certificate;$encrypted=$production?$device->production_secret_encrypted:$device->compliance_secret_encrypted;if(!filled($certificate)||!filled($encrypted))$this->fail('device',($production?'Production':'Compliance').' CSID is missing.');return $this->plain($device)->withBasicAuth($certificate,Crypt::decryptString($encrypted));}
    private function baseUrl(ZatcaDevice $device): string{$this->assertEnvironment($device);return rtrim(config('zatca.endpoints.'.$device->environment),'/');}
    private function assertEnvironment(ZatcaDevice $device): void{if(!in_array($device->environment,['simulation','production'],true))$this->fail('environment','Select simulation or production before calling FATOORA.');}
    private function fail(string $field,string $message): void{throw ValidationException::withMessages([$field=>$message]);}
}
