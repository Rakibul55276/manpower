<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckManpower extends Command
{
    protected $signature = 'manpower:check';
    protected $description = 'Check deployment, database, storage, and administrator readiness';

    public function handle()
    {
        $checks = [
            ['Application key', (bool) config('app.key'), 'APP_KEY is missing'],
            ['Production debug disabled', !app()->environment('production') || !config('app.debug'), 'APP_DEBUG must be false in production'],
            ['Storage writable', is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), 'storage or bootstrap/cache is not writable'],
        ];
        try { DB::select('SELECT 1'); $checks[] = ['Database connection', true, '']; }
        catch (\Throwable $e) { $checks[] = ['Database connection', false, $e->getMessage()]; }
        try { $checks[] = ['Active Super Admin', User::where('role', 'super_admin')->where('is_active', true)->exists(), 'No active Super Admin exists']; }
        catch (\Throwable $e) { $checks[] = ['Active Super Admin', false, 'Users table is unavailable']; }

        $failed = false;
        foreach ($checks as [$label, $passed, $message]) {
            $this->line(($passed ? '<info>PASS</info>' : '<error>FAIL</error>').'  '.$label.($passed ? '' : ' — '.$message));
            $failed = $failed || !$passed;
        }
        return $failed ? 1 : 0;
    }
}
