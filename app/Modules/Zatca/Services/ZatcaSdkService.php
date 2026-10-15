<?php

namespace App\Modules\Zatca\Services;

use App\Modules\Zatca\Models\ZatcaDocument;
use DOMDocument;
use RuntimeException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Crypt;

class ZatcaSdkService
{
    public function available(): bool
    {
        return is_file($this->jarPath()) && is_file($this->javaBinary());
    }

    public function validate(ZatcaDocument $document): array
    {
        $xml = $document->signed_xml ?: $document->unsigned_xml;
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $wellFormed = $dom->loadXML($xml, LIBXML_NONET);
        $xmlErrors = collect(libxml_get_errors())->map(fn ($error) => trim($error->message))->values()->all();
        libxml_clear_errors();
        if (! $wellFormed) return ['status'=>'failed','errors'=>$xmlErrors,'output'=>'','error'=>implode("\n",$xmlErrors)];
        if (! $this->available()) return ['status'=>'unavailable','errors'=>[],'output'=>'','error'=>'Official ZATCA SDK or Java runtime is not configured.'];
        libxml_use_internal_errors(true);
        $schemaPassed=$dom->schemaValidate($this->schemaPath($xml));
        $schemaErrors=collect(libxml_get_errors())->map(fn($error)=>trim($error->message))->reject(fn($message)=>str_contains($message,"X509SerialNumber")&&str_contains($message,"xs:integer"))->values()->all();libxml_clear_errors();
        if($schemaPassed===false&&$schemaErrors===[])$schemaPassed=true;
        if(!$schemaPassed)return ['status'=>'failed','errors'=>$schemaErrors,'output'=>'','error'=>implode("\n",$schemaErrors),'stage'=>'xsd'];
        return $this->run($xml, ['-invoice','{input}','-validate']);
    }

    public function validateXml(string $xml): array
    {
        $document=new ZatcaDocument();$document->unsigned_xml=$xml;
        return $this->validate($document);
    }

    public function sign(ZatcaDocument $document): array
    {
        if (! $this->available()) throw new RuntimeException('Configure the official ZATCA SDK and Java runtime first.');
        return $this->run($document->unsigned_xml, ['-invoice','{input}','-sign','-signedInvoice','{output}'],$document);
    }

    private function run(string $xml,array $arguments,?ZatcaDocument $document=null): array
    {
        $directory=storage_path('app/zatca/tmp/'.bin2hex(random_bytes(8)));
        if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory)) throw new RuntimeException('Unable to create the protected ZATCA SDK workspace.');
        $input=$directory.DIRECTORY_SEPARATOR.'input.xml';$output=$directory.DIRECTORY_SEPARATOR.'output.xml';$config=$directory.DIRECTORY_SEPARATOR.'config.json';$security=$directory.DIRECTORY_SEPARATOR.'java.security';
        $credentials=$this->credentialFiles($document,$directory);file_put_contents($input,$xml,LOCK_EX);file_put_contents($config,json_encode($this->runtimeConfig($xml,$credentials),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);file_put_contents($security,"security.provider.1=org.bouncycastle.jce.provider.BouncyCastleProvider\n",LOCK_EX);
        $command=[$this->javaBinary(),'-Djdk.module.illegalAccess=deny','-Djdk.sunec.disableNative=false','-Dfile.encoding=UTF-8','-Djava.security.properties='.$security,'-jar',$this->jarPath(),'--globalVersion',$this->sdkVersion(),'-certpassword',(string)config('zatca.sdk_certificate_password','123456789')];
        foreach($arguments as $argument)$command[]=str_replace(['{input}','{output}'],[$input,$output],$argument);
        $process=new Process($command,$this->sdkHome(),['SDK_CONFIG'=>$config,'FATOORA_HOME'=>$this->sdkHome().DIRECTORY_SEPARATOR.'Apps']);$process->setTimeout(120);$process->run();
        $stdout=trim($process->getOutput());$stderr=trim($process->getErrorOutput());$combined=trim($stdout."\n".$stderr);
        $signed=is_file($output)?file_get_contents($output):null;$isSigning=in_array('-sign',$arguments,true);
        $passed=$isSigning?($signed!==null&&str_contains($combined,'signed successfully')):str_contains($combined,'GLOBAL VALIDATION RESULT = PASSED');
        foreach(array_filter([$input,$output,$config,$security,$credentials['cert']??null,$credentials['key']??null]) as $file)if(is_file($file))@unlink($file);@rmdir($directory);
        $hashBase64=preg_match('/INVOICE HASH\s*=\s*([A-Za-z0-9+\/=]+)/',$combined,$matches)?$matches[1]:null;
        return ['status'=>$passed?'passed':'failed','exit_code'=>$process->getExitCode(),'output'=>mb_substr($stdout,0,30000),'error'=>mb_substr($stderr,0,30000),'xml'=>$signed,'invoice_hash_base64'=>$hashBase64,'invoice_hash_hex'=>$hashBase64?bin2hex(base64_decode($hashBase64)):null,'sdk_version'=>$this->sdkVersion(),'validated_at'=>now()->toIso8601String()];
    }

    private function runtimeConfig(string $xml,array $credentials=[]): array
    {
        $home=str_replace('\\','/',$this->sdkHome());
        return ['xsdPath'=>str_replace('\\','/',$this->schemaPath($xml)),'enSchematron'=>$home.'/Data/Rules/schematrons/CEN-EN16931-UBL.xsl','zatcaSchematron'=>$home.'/Data/Rules/schematrons/20210819_ZATCA_E-invoice_Validation_Rules.xsl','certPath'=>str_replace('\\','/',$credentials['cert']??($home.'/Data/Certificates/cert.pem')),'privateKeyPath'=>str_replace('\\','/',$credentials['key']??($home.'/Data/Certificates/ec-secp256k1-priv-key.pem')),'pihPath'=>$home.'/Data/PIH/pih.txt','certPassword'=>(string)config('zatca.sdk_certificate_password','123456789'),'inputPath'=>$home.'/Data/Input','usagePathFile'=>$home.'/Configuration/usage.txt'];
    }
    private function credentialFiles(?ZatcaDocument $document,string $directory): array
    {
        if(!$document)return [];$device=$document->relationLoaded('device')?$document->device:$document->device()->first();if(!$device||!filled($device->private_key_encrypted))return [];
        $certificate=$device->production_certificate?:$device->compliance_certificate;if(!filled($certificate))return [];
        $key=$directory.DIRECTORY_SEPARATOR.'egs-private.pem';$cert=$directory.DIRECTORY_SEPARATOR.'egs-certificate.pem';$decoded=base64_decode($certificate,true);$certificateBody=$decoded!==false?$decoded:$certificate;
        if(!str_contains($certificateBody,'BEGIN CERTIFICATE'))$certificateBody="-----BEGIN CERTIFICATE-----\n".chunk_split(base64_encode($certificateBody),64,"\n")."-----END CERTIFICATE-----\n";
        file_put_contents($key,Crypt::decryptString($device->private_key_encrypted),LOCK_EX);file_put_contents($cert,$certificateBody,LOCK_EX);return ['key'=>$key,'cert'=>$cert];
    }
    private function schemaPath(string $xml): string{$type=str_contains($xml,'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2')?'CreditNote':(str_contains($xml,'urn:oasis:names:specification:ubl:schema:xsd:DebitNote-2')?'DebitNote':'Invoice');$candidate=$this->sdkHome().DIRECTORY_SEPARATOR.'Data'.DIRECTORY_SEPARATOR.'Schemas'.DIRECTORY_SEPARATOR.'xsds'.DIRECTORY_SEPARATOR.'UBL2.1'.DIRECTORY_SEPARATOR.'xsd'.DIRECTORY_SEPARATOR.'maindoc'.DIRECTORY_SEPARATOR.'UBL-'.$type.'-2.1.xsd';return is_file($candidate)?$candidate:$this->sdkHome().DIRECTORY_SEPARATOR.'Data'.DIRECTORY_SEPARATOR.'Schemas'.DIRECTORY_SEPARATOR.'xsds'.DIRECTORY_SEPARATOR.'UBL2.1'.DIRECTORY_SEPARATOR.'xsd'.DIRECTORY_SEPARATOR.'maindoc'.DIRECTORY_SEPARATOR.'UBL-Invoice-2.1.xsd';}
    private function sdkHome(): string{return rtrim((string)config('zatca.sdk_home'),'/\\');}
    private function sdkVersion(): string{return (string)config('zatca.sdk_version','3.0.8');}
    private function jarPath(): string{return $this->sdkHome().DIRECTORY_SEPARATOR.'Apps'.DIRECTORY_SEPARATOR.'cli-'.$this->sdkVersion().'-jar-with-dependencies.jar';}
    private function javaBinary(): string{return (string)config('zatca.java_binary');}
}
