<?php

namespace App\Console\Commands;

use App\Modules\Zatca\Models\ZatcaDevice;
use App\Modules\Zatca\Services\ZatcaSdkService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ZatcaReadiness extends Command
{
    protected $signature = 'zatca:readiness {--company=}';
    protected $description = 'Audit technical and EGS prerequisites for ZATCA simulation or production';

    public function handle(ZatcaSdkService $sdk): int
    {
        $checks = [
            ['Application key configured', filled(config('app.key'))],
            ['Database connection', $this->databaseAvailable()],
            ['Official ZATCA SDK and Java available', $sdk->available()],
            ['Queue is not sync for production', config('queue.default') !== 'sync', 'Required for resilient production retry; sync is acceptable for local simulation.'],
            ['HTTPS application URL', str_starts_with((string) config('app.url'), 'https://'), 'Required for production deployment; localhost simulation may use HTTP.'],
            ['Secure session cookie', (bool) config('session.secure'), 'Required behind production HTTPS; keep false for localhost HTTP.'],
        ];

        $query = ZatcaDevice::query();
        if ($company = $this->option('company')) $query->where('company_id', $company);
        $devices = $query->get();

        foreach ($devices as $device) {
            $prefix = 'Company '.$device->company_id.' / '.$device->environment;
            $checks[] = [$prefix.' CSR generated', filled($device->csr)];
            $checks[] = [$prefix.' Compliance CSID installed', filled($device->compliance_certificate) && filled($device->compliance_secret_encrypted)];
            $checks[] = [$prefix.' Production CSID installed', filled($device->production_certificate) && filled($device->production_secret_encrypted), 'Not required while performing simulation compliance tests.'];
            $checks[] = [$prefix.' certificate not expired', ! $device->certificate_expires_at || $device->certificate_expires_at->isFuture()];
        }

        $this->table(['Check', 'Result', 'Note'], array_map(fn ($check) => [$check[0], $check[1] ? 'PASS' : 'FAIL', $check[2] ?? ''], $checks));

        $hardFailures = collect($checks)->filter(function ($check) {
            if ($check[1]) return false;
            return ! str_contains($check[2] ?? '', 'simulation') && ! str_contains($check[2] ?? '', 'Not required');
        })->count();

        if ($hardFailures) {
            $this->error($hardFailures.' blocking readiness check(s) failed.');
            return self::FAILURE;
        }

        $this->info('Core ZATCA readiness checks passed. Environment-specific warnings are shown above.');
        return self::SUCCESS;
    }

    private function databaseAvailable(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
