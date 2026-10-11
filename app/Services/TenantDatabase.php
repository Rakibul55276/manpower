<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use PDO;
use RuntimeException;
use Throwable;

class TenantDatabase
{
    private static bool $active=false;
    private const REQUIRED_TABLES = [
        'migrations', 'companies', 'users', 'branches', 'employees',
        'timesheets', 'payrolls', 'activity_logs',
    ];

    public function verify(array $credentials): void
    {
        $host = trim((string) $credentials['db_host']);
        $port = (int) $credentials['db_port'];
        $database = trim((string) $credentials['db_name']);
        $username = trim((string) $credentials['db_username']);
        $password = (string) ($credentials['db_password'] ?? '');

        try {
            $pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]
            );
            $placeholders = implode(',', array_fill(0, count(self::REQUIRED_TABLES), '?'));
            $statement = $pdo->prepare(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name IN ({$placeholders})"
            );
            $statement->execute(array_merge([$database], self::REQUIRED_TABLES));
            $present = array_map('strtolower', $statement->fetchAll(PDO::FETCH_COLUMN));
            $missing = array_values(array_diff(self::REQUIRED_TABLES, $present));
            if ($missing) {
                throw new RuntimeException('Missing required tables: '.implode(', ', $missing).'. Import database/company-customer-template.sql first.');
            }
            foreach (['designations'=>['company_id'],'safety_shop_receipt_settings'=>['company_id','header_image_path','footer_image_path']] as $table=>$columns) {
                $columnStatement=$pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema = ? AND table_name = ?');
                $columnStatement->execute([$database,$table]);
                $presentColumns=array_map('strtolower',$columnStatement->fetchAll(PDO::FETCH_COLUMN));
                if(array_diff($columns,$presentColumns)) throw new RuntimeException('The company database template is outdated. Re-import database/company-customer-template.sql.');
            }
        } catch (RuntimeException $error) {
            throw $error;
        } catch (Throwable $error) {
            report($error);
            throw new RuntimeException('The company database connection could not be verified. Check host, port, database name, username, password, and database-user privileges.');
        }
    }

    public function encryptedPassword(string $password): string
    {
        return Crypt::encryptString($password);
    }

    public function decryptedPassword(?string $password): string
    {
        return $password ? Crypt::decryptString($password) : '';
    }

    public function activate(Company $company): void
    {
        if (!$company->db_verified_at || !$company->db_host || !$company->db_name || !$company->db_username) throw new RuntimeException('The company database is not configured.');
        config(['database.connections.tenant'=>array_merge(config('database.connections.tenant'),[
            'host'=>$company->db_host,'port'=>$company->db_port,'database'=>$company->db_name,
            'username'=>$company->db_username,'password'=>$this->decryptedPassword($company->db_password_encrypted),
        ])]);
        DB::purge('tenant');
        DB::connection('tenant')->getPdo();
        DB::setDefaultConnection('tenant');
        self::$active=true;
    }

    public function deactivate(): void
    {
        DB::setDefaultConnection(config('database.default'));
        self::$active=false;
    }

    public static function active(): bool { return self::$active; }

    public function provision(Company $company, array $admin): void
    {
        $this->activate($company);
        try {
        $connection=DB::connection('tenant');
        $connection->transaction(function() use($connection,$company,$admin){
            $connection->table('companies')->updateOrInsert(['id'=>$company->id],[
                'name'=>$company->name,'company_code'=>$company->company_code,'location'=>$company->location,
                'registration_number'=>$company->registration_number,'contact_person'=>$company->contact_person,
                'phone'=>$company->phone,'email'=>$company->email,'address'=>$company->address,'notes'=>$company->notes,
                'is_active'=>1,'subscription_status'=>$company->subscription_status,
                'subscription_started_at'=>$company->subscription_started_at,'subscription_expires_at'=>$company->subscription_expires_at,
                'subscription_grace_until'=>$company->subscription_grace_until,'created_at'=>now(),'updated_at'=>now(),
            ]);
            $connection->table('users')->insert([
                'name'=>$admin['admin_name'],'username'=>$admin['admin_username'],'email'=>$admin['admin_email'],
                'password'=>Hash::make($admin['admin_password']),'role'=>'admin','is_active'=>1,
                'company_id'=>$company->id,'branch_id'=>null,'created_at'=>now(),'updated_at'=>now(),
            ]);
        });
        } finally { $this->deactivate(); }
    }
}
