<?php

namespace App\Modules\Zatca\Services;

use App\Modules\Zatca\Models\ZatcaDocument;
use DOMDocument;
use RuntimeException;
use Symfony\Component\Process\Process;

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
        return $this->run($document->unsigned_xml, ['-invoice','{input}','-sign','-signedInvoice','{output}']);
    }

    private function run(string $xml,array $arguments): array
    {
        $directory=storage_path('app/zatca/tmp/'.bin2hex(random_bytes(8)));
        if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory)) throw new RuntimeException('Unable to create the protected ZATCA SDK workspace.');
        $input=$directory.DIRECTORY_SEPARATOR.'input.xml';$output=$directory.DIRECTORY_SEPARATOR.'output.xml';$config=$directory.DIRECTORY_SEPARATOR.'config.json';
        file_put_contents($input,$xml,LOCK_EX);file_put_contents($config,json_encode($this->runtimeConfig(),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);
        $command=[$this->javaBinary(),'-Djdk.module.illegalAccess=deny','-Dfile.encoding=UTF-8','-jar',$this->jarPath(),'--globalVersion',$this->sdkVersion(),'-certpassword',(string)config('zatca.sdk_certificate_password','123456789')];
        foreach($arguments as $argument)$command[]=str_replace(['{input}','{output}'],[$input,$output],$argument);
        $process=new Process($command,$this->sdkHome(),['SDK_CONFIG'=>$config,'FATOORA_HOME'=>$this->sdkHome().DIRECTORY_SEPARATOR.'Apps']);$process->setTimeout(120);$process->run();
        $stdout=trim($process->getOutput());$stderr=trim($process->getErrorOutput());$combined=trim($stdout."\n".$stderr);
        $passed=$process->isSuccessful()&&!preg_match('/(?:ERROR|FAILED|invalid invoice)/i',$combined);$signed=is_file($output)?file_get_contents($output):null;
        foreach([$input,$output,$config] as $file)if(is_file($file))@unlink($file);@rmdir($directory);
        return ['status'=>$passed?'passed':'failed','exit_code'=>$process->getExitCode(),'output'=>mb_substr($stdout,0,30000),'error'=>mb_substr($stderr,0,30000),'xml'=>$signed,'sdk_version'=>$this->sdkVersion(),'validated_at'=>now()->toIso8601String()];
    }

    private function runtimeConfig(): array
    {
        $home=str_replace('\\','/',$this->sdkHome());
        return ['xsdPath'=>$home.'/Data/Schemas/xsds/UBL2.1/xsd/maindoc/UBL-Invoice-2.1.xsd','enSchematron'=>$home.'/Data/Rules/schematrons/CEN-EN16931-UBL.xsl','zatcaSchematron'=>$home.'/Data/Rules/schematrons/20210819_ZATCA_E-invoice_Validation_Rules.xsl','certPath'=>$home.'/Data/Certificates/cert.pem','privateKeyPath'=>$home.'/Data/Certificates/ec-secp256k1-priv-key.pem','pihPath'=>$home.'/Data/PIH/pih.txt','certPassword'=>(string)config('zatca.sdk_certificate_password','123456789'),'inputPath'=>$home.'/Data/Input','usagePathFile'=>$home.'/Configuration/usage.txt'];
    }
    private function sdkHome(): string{return rtrim((string)config('zatca.sdk_home'),'/\\');}
    private function sdkVersion(): string{return (string)config('zatca.sdk_version','3.0.8');}
    private function jarPath(): string{return $this->sdkHome().DIRECTORY_SEPARATOR.'Apps'.DIRECTORY_SEPARATOR.'cli-'.$this->sdkVersion().'-jar-with-dependencies.jar';}
    private function javaBinary(): string{return (string)config('zatca.java_binary');}
}
