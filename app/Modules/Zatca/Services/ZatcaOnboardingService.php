<?php
namespace App\Modules\Zatca\Services;

use App\Models\Company;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use App\Modules\Zatca\Models\ZatcaDevice;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\DB;

class ZatcaOnboardingService
{
    public function generate(ZatcaDevice $device): ZatcaDevice
    {
        $seller=ReceiptSetting::forCompany($device->company_id)->firstOrFail();$company=Company::findOrFail($device->company_id);
        if(!preg_match('/^3\d{13}3$/',(string)$seller->vat_number))$this->fail('vat_number','A valid Saudi VAT number is required.');
        $serial=$device->egs_serial_number?:'1-'.Str::upper(Str::random(8)).'|2-'.Str::upper(substr(preg_replace('/[^A-Za-z0-9]/','',$seller->company_name),0,8)).'|3-'.Str::uuid();
        $dir=storage_path('app/zatca/tmp/'.bin2hex(random_bytes(8)));if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))$this->fail('csr','Unable to create the protected SDK workspace.');
        $keyPath=$dir.'/private.pem';$csrPath=$dir.'/request.pem';$configPath=$dir.'/csr.properties';
        $location=collect([$seller->address,$seller->city,$seller->postal_code])->filter()->implode(', ');
        $properties=[
            'csr.common.name'=>$device->solution_unit_name.'-'.$seller->vat_number,
            'csr.serial.number'=>$serial,
            'csr.organization.identifier'=>$seller->vat_number,
            // ZATCA SDK requires the organization unit to be the first 10 digits of the TIN.
            'csr.organization.unit.name'=>substr((string)$seller->vat_number,0,10),
            // SDK 3.0.8 validates both organization fields as the 10-digit TIN segment.
            'csr.organization.name'=>substr((string)$seller->vat_number,0,10),
            'csr.country.name'=>'SA',
            'csr.invoice.type'=>'1100',
            'csr.location.address'=>$location,
            'csr.industry.business.category'=>'Safety equipment and stock sales',
        ];
        file_put_contents($configPath,collect($properties)->map(fn($value,$key)=>$key.'='.$this->property($value))->implode("\n")."\n",LOCK_EX);
        $sdkHome=rtrim((string)config('zatca.sdk_home'),'/\\');$apps=$sdkHome.DIRECTORY_SEPARATOR.'Apps';$jar=$apps.DIRECTORY_SEPARATOR.'cli-'.config('zatca.sdk_version','3.0.8').'-jar-with-dependencies.jar';
        if(!is_file($jar)||!is_file((string)config('zatca.java_binary'))){$this->cleanup($dir);$this->fail('csr','The official ZATCA SDK or Java runtime is not configured.');}
        $command=[config('zatca.java_binary'),'-Djdk.module.illegalAccess=deny','-Djdk.sunec.disableNative=false','-Dfile.encoding=UTF-8','-jar',$jar,'--globalVersion',(string)config('zatca.sdk_version','3.0.8'),'-certpassword',(string)config('zatca.sdk_certificate_password','123456789'),'-csr','-csrConfig',$configPath,'-privateKey',$keyPath,'-generatedCsr',$csrPath,'-pem'];
        if($device->environment!=='production')$command[]='-nonprod';
        $process=new Process($command,$sdkHome,['FATOORA_HOME'=>$apps,'SDK_CONFIG'=>$sdkHome.DIRECTORY_SEPARATOR.'Configuration'.DIRECTORY_SEPARATOR.'config.json']);$process->setTimeout(120);$process->run();
        if(!$process->isSuccessful()||!is_file($keyPath)||!is_file($csrPath)){$error=trim($process->getErrorOutput()."\n".$process->getOutput());$this->cleanup($dir);$this->fail('csr','Official ZATCA SDK CSR generation failed: '.mb_substr($error,0,1000));}
        $privatePem=file_get_contents($keyPath);$csrPem=file_get_contents($csrPath);$this->cleanup($dir);
        $device->update(['egs_serial_number'=>$serial,'private_key_encrypted'=>Crypt::encryptString($privatePem),'csr'=>$csrPem,'status'=>'csr_ready','last_error'=>null]);return $device->fresh();
    }
    public function installCompliance(ZatcaDevice $device,array $response): ZatcaDevice{$certificate=$response['binarySecurityToken']??null;$secret=$response['secret']??null;$requestId=$response['requestID']??$response['requestId']??null;if(!$certificate||!$secret||!$requestId)$this->fail('otp','ZATCA did not return a complete Compliance CSID response.');$device->update(['compliance_certificate'=>$certificate,'compliance_secret_encrypted'=>Crypt::encryptString($secret),'compliance_request_id'=>$requestId,'certificate_expires_at'=>$this->certificateExpiry($certificate),'status'=>'compliance_ready','last_connected_at'=>now(),'last_error'=>null]);return $device->fresh();}
    public function installProduction(ZatcaDevice $device,array $response): ZatcaDevice{$certificate=$response['binarySecurityToken']??null;$secret=$response['secret']??null;$requestId=$response['requestID']??$response['requestId']??null;if(!$certificate||!$secret)$this->fail('device','ZATCA did not return a complete Production CSID response.');$device->update(['production_certificate'=>$certificate,'production_secret_encrypted'=>Crypt::encryptString($secret),'production_request_id'=>$requestId,'certificate_expires_at'=>$this->certificateExpiry($certificate),'status'=>'production_ready','last_connected_at'=>now(),'last_error'=>null]);return $device->fresh();}
    public function generateRenewal(ZatcaDevice $device): ZatcaDevice
    {
        return DB::transaction(function()use($device){$device=ZatcaDevice::whereKey($device->id)->lockForUpdate()->firstOrFail();if(!filled($device->production_certificate))$this->fail('device','Install a Production CSID before renewal.');$oldKey=$device->private_key_encrypted;$oldCsr=$device->csr;$generated=$this->generate($device);$device->update(['renewal_private_key_encrypted'=>$generated->private_key_encrypted,'renewal_csr'=>$generated->csr,'private_key_encrypted'=>$oldKey,'csr'=>$oldCsr,'status'=>'renewal_csr_ready']);return $device->fresh();},3);
    }
    public function installRenewal(ZatcaDevice $device,array $response): ZatcaDevice
    {
        $certificate=$response['binarySecurityToken']??null;$secret=$response['secret']??null;$requestId=$response['requestID']??$response['requestId']??null;
        if(!$certificate||!$secret||!filled($device->renewal_private_key_encrypted)||!filled($device->renewal_csr))$this->fail('device','ZATCA did not return a complete renewal credential set.');
        $device->update(['production_certificate'=>$certificate,'production_secret_encrypted'=>Crypt::encryptString($secret),'production_request_id'=>$requestId,'private_key_encrypted'=>$device->renewal_private_key_encrypted,'csr'=>$device->renewal_csr,'renewal_private_key_encrypted'=>null,'renewal_csr'=>null,'certificate_expires_at'=>$this->certificateExpiry($certificate),'status'=>'production_ready','last_connected_at'=>now(),'last_error'=>null]);return $device->fresh();
    }
    private function fail(string $field,string $message): void{throw ValidationException::withMessages([$field=>$message]);}
    private function safe(?string $value): string{return preg_replace('/[\r\n=]/',' ',trim((string)$value));}
    private function property(?string $value): string{return str_replace(['\\',"\r","\n"],['\\\\',' ',' '],trim((string)$value));}
    private function certificateExpiry(string $token){$decoded=base64_decode($token,true);$pem=$decoded!==false?$decoded:$token;if(!str_contains($pem,'BEGIN CERTIFICATE'))$pem="-----BEGIN CERTIFICATE-----\n".chunk_split(base64_encode($pem),64,"\n")."-----END CERTIFICATE-----\n";$parsed=openssl_x509_parse($pem);return isset($parsed['validTo_time_t'])?now()->setTimestamp($parsed['validTo_time_t']):null;}
    private function cleanup(string $dir): void{foreach(glob($dir.'/*')?:[] as $file)@unlink($file);@rmdir($dir);}
}
