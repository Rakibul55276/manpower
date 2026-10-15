<?php
namespace App\Console\Commands;

use App\Jobs\SubmitZatcaDocument;
use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Models\ZatcaDocument;
use Illuminate\Console\Command;

class ZatcaMonitor extends Command
{
    protected $signature='zatca:monitor {--dispatch}';protected $description='Check certificate expiry and optionally dispatch due ZATCA retries';
    public function handle(): int
    {
        $expiring=ZatcaDevice::whereNotNull('certificate_expires_at')->where('certificate_expires_at','<=',now()->addDays(30))->get();foreach($expiring as $device)$this->warn('Company '.$device->company_id.' certificate expires '.$device->certificate_expires_at->toDateString());
        if($this->option('dispatch'))ZatcaDocument::where('status','failed')->where('next_retry_at','<=',now())->pluck('id')->each(fn($id)=>SubmitZatcaDocument::dispatch($id));
        $this->info('Devices expiring within 30 days: '.$expiring->count());return self::SUCCESS;
    }
}
