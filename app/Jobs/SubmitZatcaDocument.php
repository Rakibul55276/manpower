<?php
namespace App\Jobs;

use App\Modules\Zatca\Models\ZatcaDocument;
use App\Modules\Zatca\Services\FatooraClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SubmitZatcaDocument implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
    public $tries=8;public $backoff=[60,300,900,3600];
    public function __construct(public int $documentId){}
    public function handle(FatooraClient $client): void
    {
        $document=ZatcaDocument::with('device')->findOrFail($this->documentId);if(in_array($document->status,['reported','cleared'],true))return;
        if(!$document->signed_xml)throw new \RuntimeException('Officially signed XML is required before submission.');
        $document->increment('attempt_count');$response=$client->submit($document->device,$document);$cleared=$response['clearedInvoice']??null;
        $document->update(['status'=>$document->submission_mode==='clearance'?'cleared':'reported','response_payload'=>$response,'cleared_xml'=>$cleared?base64_decode($cleared):null,'submitted_at'=>now(),'accepted_at'=>now(),'next_retry_at'=>null,'last_error'=>null]);
    }
    public function failed(Throwable $e): void{if($d=ZatcaDocument::find($this->documentId))$d->update(['status'=>'failed','last_error'=>mb_substr($e->getMessage(),0,2000),'next_retry_at'=>now()->addHour()]);}
}
