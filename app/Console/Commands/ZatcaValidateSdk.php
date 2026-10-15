<?php

namespace App\Console\Commands;

use App\Modules\Zatca\Models\ZatcaDocument;
use App\Modules\Zatca\Services\ZatcaSdkService;
use Illuminate\Console\Command;

class ZatcaValidateSdk extends Command
{
    protected $signature='zatca:sdk-validate {document? : ZATCA document ID} {--all : Revalidate every document}';
    protected $description='Validate prepared ZATCA XML using the official local SDK and store the evidence';

    public function handle(ZatcaSdkService $sdk): int
    {
        if(!$sdk->available()){$this->error('The official ZATCA SDK or Java runtime is unavailable. Check ZATCA_SDK_HOME and ZATCA_JAVA_BINARY.');return self::FAILURE;}
        $query=ZatcaDocument::query()->orderBy('id');
        if($id=$this->argument('document'))$query->whereKey($id);
        elseif(!$this->option('all'))$query->where(fn($q)=>$q->whereNull('validation_results')->orWhere('validation_results','not like','%\"status\":\"passed\"%'));
        $documents=$query->get();if($documents->isEmpty()){$this->info('No ZATCA documents require SDK validation.');return self::SUCCESS;}
        $failed=0;
        foreach($documents as $document){$result=$sdk->validate($document);$validation=$document->validation_results?:[];$validation['zatca_sdk']=$result;$document->forceFill(['validation_results'=>$validation])->save();$this->line(sprintf('[%s] document %d, ICV %d: %s',strtoupper($result['status']),$document->id,$document->icv,$document->document_kind));if($result['status']!=='passed'){$failed++;$details=trim(($result['output']??'')."\n".($result['error']??''));if($details!=='')$this->warn($details);}}
        return $failed===0?self::SUCCESS:self::FAILURE;
    }
}
