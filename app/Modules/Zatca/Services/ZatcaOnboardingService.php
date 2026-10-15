<?php
namespace App\Modules\Zatca\Services;

use App\Models\Company;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use App\Modules\Zatca\Models\ZatcaDevice;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class ZatcaOnboardingService
{
    public function generate(ZatcaDevice $device): ZatcaDevice
    {
        $seller=ReceiptSetting::forCompany($device->company_id)->firstOrFail();$company=Company::findOrFail($device->company_id);
        if(!preg_match('/^3\d{13}3$/',(string)$seller->vat_number))$this->fail('vat_number','A valid Saudi VAT number is required.');
        $serial=$device->egs_serial_number?:'1-'.Str::upper(Str::random(8)).'|2-'.Str::upper(substr(preg_replace('/[^A-Za-z0-9]/','',$seller->company_name),0,8)).'|3-'.Str::uuid();$dir=storage_path('app/zatca/tmp/'.bin2hex(random_bytes(8)));if(!is_dir($dir))mkdir($dir,0700,true);$keyPath=$dir.'/private.pem';$csrPath=$dir.'/request.pem';$configPath=$dir.'/csr.cnf';
        $config="[ req ]\nprompt = no\nutf8 = yes\nstring_mask = utf8only\ndistinguished_name = dn\nreq_extensions = v3_req\n[ dn ]\nC = SA\nO = ".$this->safe($seller->company_name)."\nOU = ".$this->safe($company->name)."\nCN = ".$this->safe($device->solution_unit_name)."\nserialNumber = ".$this->safe(str_replace('|','-',$serial))."\norganizationIdentifier = ".$seller->vat_number."\n[ v3_req ]\nbasicConstraints = CA:FALSE\nkeyUsage = digitalSignature, nonRepudiation\n1.3.6.1.4.1.311.20.2 = ASN1:PRINTABLESTRING:PREZATCA-Code-Signing\nsubjectAltName = dirName:alt_names\n[ alt_names ]\nSN = ".$this->safe(str_replace('|','-',$serial))."\nUID = ".$seller->vat_number."\ntitle = 1100\nregisteredAddress = ".$this->safe(collect([$seller->address,$seller->city,$seller->postal_code])->filter()->implode(', '))."\nbusinessCategory = Industry\n";file_put_contents($configPath,$config);
        $keyProcess=new Process([config('zatca.openssl_binary'),'ecparam','-name','secp256k1','-genkey','-noout','-out',$keyPath]);$keyProcess->run();if(!$keyProcess->isSuccessful())$this->cleanup($dir);if(!$keyProcess->isSuccessful())$this->fail('key','OpenSSL key generation failed: '.trim($keyProcess->getErrorOutput()));
        $csrProcess=new Process([config('zatca.openssl_binary'),'req','-new','-sha256','-key',$keyPath,'-config',$configPath,'-out',$csrPath]);$csrProcess->run();if(!$csrProcess->isSuccessful()){$error=trim($csrProcess->getErrorOutput());$this->cleanup($dir);$this->fail('csr','OpenSSL CSR generation failed: '.$error);}$privatePem=file_get_contents($keyPath);$csrPem=file_get_contents($csrPath);$this->cleanup($dir);
        $device->update(['egs_serial_number'=>$serial,'private_key_encrypted'=>Crypt::encryptString($privatePem),'csr'=>$csrPem,'status'=>'csr_ready','last_error'=>null]);return $device->fresh();
    }
    public function installCompliance(ZatcaDevice $device,array $response): ZatcaDevice{$certificate=$response['binarySecurityToken']??null;$secret=$response['secret']??null;$requestId=$response['requestID']??$response['requestId']??null;if(!$certificate||!$secret||!$requestId)$this->fail('otp','ZATCA did not return a complete Compliance CSID response.');$device->update(['compliance_certificate'=>$certificate,'compliance_secret_encrypted'=>Crypt::encryptString($secret),'compliance_request_id'=>$requestId,'status'=>'compliance_ready','last_connected_at'=>now(),'last_error'=>null]);return $device->fresh();}
    public function installProduction(ZatcaDevice $device,array $response): ZatcaDevice{$certificate=$response['binarySecurityToken']??null;$secret=$response['secret']??null;$requestId=$response['requestID']??$response['requestId']??null;if(!$certificate||!$secret)$this->fail('device','ZATCA did not return a complete Production CSID response.');$device->update(['production_certificate'=>$certificate,'production_secret_encrypted'=>Crypt::encryptString($secret),'production_request_id'=>$requestId,'status'=>'production_ready','last_connected_at'=>now(),'last_error'=>null]);return $device->fresh();}
    private function fail(string $field,string $message): void{throw ValidationException::withMessages([$field=>$message]);}
    private function safe(?string $value): string{return preg_replace('/[\r\n=]/',' ',trim((string)$value));}
    private function cleanup(string $dir): void{foreach(glob($dir.'/*')?:[] as $file)@unlink($file);@rmdir($dir);}
}
