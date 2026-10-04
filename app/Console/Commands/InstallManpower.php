<?php
namespace App\Console\Commands;
use App\Models\User;
use App\Models\Company;
use App\Models\Designation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class InstallManpower extends Command
{
    protected $signature = 'manpower:install';
    protected $description = 'Create initial Super Admin and Manager accounts without overwriting existing users';
    public function handle()
    {
        if (User::where('role', 'super_admin')->exists()) { $this->info('A Super Admin already exists. No accounts were changed.'); return 0; }
        $credentials = DB::transaction(function () {
            $company = Company::firstOrCreate(['name' => 'Manpower Operations'], ['location' => 'Riyadh, Saudi Arabia']);
            foreach (['General Worker', 'Electrician', 'Plumber', 'Welder', 'Driver', 'Supervisor', 'Accountant'] as $name) { Designation::firstOrCreate(['name' => $name]); }
            $lines = ['MANPOWER LOCAL SETUP', 'Sign in: '.config('app.url').'/login', 'Change these initial passwords after signing in.', ''];
            foreach (['super_admin' => ['Super Admin', 'superadmin', 'superadmin@manpower.local'], 'admin' => ['Admin Approver', 'admin', 'admin@manpower.local'], 'manager' => ['Manager', 'manager', 'manager@manpower.local']] as $role => $account) {
                if (User::where('username', $account[1])->orWhere('email', $account[2])->exists()) { throw new \RuntimeException('Initial account already exists. Create an administrator manually instead.'); }
                $password = Str::random(20);
                $user = User::create(['name' => $account[0], 'username' => $account[1], 'email' => $account[2], 'password' => Hash::make($password), 'role' => $role, 'is_active' => true]);
                if ($role === 'manager') { $user->companies()->attach($company); }
                $lines[] = $account[0].' username: '.$account[1]; $lines[] = 'Password: '.$password; $lines[] = '';
            }
            return implode(PHP_EOL, $lines);
        });
        Storage::disk('local')->put('setup-credentials.txt', $credentials);
        $this->info('Initial accounts created. Private credentials: storage/app/setup-credentials.txt');
        return 0;
    }
}
