<?php
namespace App\Console\Commands;

use App\Jobs\SubmitZatcaDocument;
use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Models\ZatcaDocument;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use App\Modules\SafetyShop\Sales\Models\SaleDebitNote;
use App\Modules\Zatca\Services\ZatcaLifecycleService;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;

class ZatcaMonitor extends Command
{
    protected $signature='zatca:monitor {--dispatch}';protected $description='Check certificate expiry and optionally dispatch due ZATCA retries';
    public function handle(ZatcaLifecycleService $lifecycle): int
    {
        $expiring=ZatcaDevice::whereNotNull('certificate_expires_at')->where('certificate_expires_at','<=',now()->addDays(30))->get();foreach($expiring as $device){$message='Company '.$device->company_id.' ZATCA certificate expires '.$device->certificate_expires_at->toDateString();$this->warn($message);Log::warning($message);}
        if($this->option('dispatch')){
            Sale::where('status','finalized')->whereNotNull('invoice_sequence_number')->whereDoesntHave('zatcaDocument')->orderBy('id')->limit(25)->get()->each(fn($sale)=>$lifecycle->processSale($sale));
            SaleReturn::whereDoesntHave('zatcaDocument')->orderBy('id')->limit(25)->get()->each(fn($return)=>$lifecycle->processReturn($return));
            SaleDebitNote::whereDoesntHave('zatcaDocument')->orderBy('id')->limit(25)->get()->each(fn($note)=>$lifecycle->processDebitNote($note));
            ZatcaDocument::where('status','failed')->where('next_retry_at','<=',now())->pluck('id')->each(function($id){try{SubmitZatcaDocument::dispatchSync($id);}catch(\Throwable $exception){report($exception);}});
        }
        $overdue=ZatcaDocument::where('invoice_type','simplified')->whereNotIn('status',['reported','cleared'])->where('created_at','<=',now()->subHours(20))->count();if($overdue){$message=$overdue.' simplified ZATCA document(s) are approaching or exceeding the reporting deadline.';$this->error($message);Log::critical($message);}
        $this->info('Devices expiring within 30 days: '.$expiring->count().'; simplified documents requiring attention: '.$overdue);return $overdue?self::FAILURE:self::SUCCESS;
    }
}
