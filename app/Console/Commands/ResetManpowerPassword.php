<?php
namespace App\Console\Commands;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
class ResetManpowerPassword extends Command
{
    protected $signature = 'manpower:reset-password {email} {--activate : Activate the account after setting its password}';
    protected $description = 'Set an account password interactively without placing it in command arguments';
    public function handle()
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (!$user) { $this->error('Account not found.'); return 1; }
        $password = $this->secret('New password (at least 10 characters)');
        if (strlen((string) $password) < 10) { $this->error('Use at least 10 characters.'); return 1; }
        $confirm = $this->secret('Confirm new password');
        if (!hash_equals($password, (string) $confirm)) { $this->error('Passwords do not match.'); return 1; }
        $user->password = Hash::make($password); $user->remember_token = null;
        if ($this->option('activate')) { $user->is_active = true; }
        $user->save(); $this->info('Password updated'.($this->option('activate') ? ' and account activated.' : '.'));
        return 0;
    }
}
