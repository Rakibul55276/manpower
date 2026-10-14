<?php
namespace Tests\Feature;

use App\Models\{Branch,Company,Designation,Employee,Payroll,Timesheet,User};
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Sales\Models\Customer;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Shared\Models\ReceiptSetting;
use App\Modules\DocumentLibrary\Models\LibraryDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;
    private $company; private $otherCompany; private $branch; private $otherBranch; private $foreignBranch; private $manager; private $admin; private $designation;

    protected function setUp(): void
    {
        parent::setUp();
        config(['invoicing.enabled'=>true, 'safety_shop.enabled'=>true, 'manpower.enabled'=>true]);
        $this->company=Company::create(['name'=>'Tenant Alpha','location'=>'Riyadh']);
        $this->otherCompany=Company::create(['name'=>'Tenant Beta','location'=>'Jeddah']);
        $this->branch=Branch::create(['company_id'=>$this->company->id,'name'=>'North','code'=>'NORTH','location'=>'Riyadh','is_active'=>true]);
        $this->otherBranch=Branch::create(['company_id'=>$this->company->id,'name'=>'South','code'=>'SOUTH','location'=>'Riyadh','is_active'=>true]);
        $this->foreignBranch=Branch::create(['company_id'=>$this->otherCompany->id,'name'=>'West','code'=>'WEST','location'=>'Jeddah','is_active'=>true]);
        $this->manager=$this->user('branch_manager','manager',$this->company->id,$this->branch->id);
        $this->admin=$this->user('company_admin','admin',$this->company->id,null);
        $this->designation=Designation::create(['name'=>'Technician','is_active'=>true]);
    }

    public function test_manager_reads_only_its_branch_and_direct_urls_are_blocked()
    {
        $own=$this->employee('Own Branch Worker',$this->company,$this->branch);
        $sibling=$this->employee('Sibling Branch Worker',$this->company,$this->otherBranch);
        $foreign=$this->employee('Foreign Company Worker',$this->otherCompany,$this->foreignBranch);
        $this->actingAs($this->manager)->get(route('employees.index'))->assertOk()->assertSee($own->name)->assertDontSee($sibling->name)->assertDontSee($foreign->name);
        $this->get(route('employees.show',$sibling))->assertForbidden();
        $this->get(route('employees.show',$foreign))->assertForbidden();
        $this->get(route('employees.photo',$sibling))->assertForbidden();
    }

    public function test_modules_have_separate_login_pages_and_session_boundaries()
    {
        $this->get(route('manpower.login'))->assertOk()->assertSee('Sign in to Manpower')->assertSee(route('manpower.login.submit'),false);
        $this->get(route('safety-shop.login'))->assertOk()->assertSee('Sign in to Safety Shop')->assertSee(route('safety-shop.login.submit'),false);
        $this->withSession(['active_module'=>'manpower'])->actingAs($this->manager)->get(route('manpower.index'))->assertOk();
        $this->get(route('safety-shop.index'))->assertForbidden();
        $this->post(route('manpower.logout'))->assertRedirect(route('manpower.login'));
        $this->withSession(['active_module'=>'safety-shop'])->actingAs($this->manager)->get(route('safety-shop.index'))->assertOk();
        $this->get(route('manpower.index'))->assertForbidden();
        $this->post(route('safety-shop.logout'))->assertRedirect(route('safety-shop.login'));
    }

    public function test_module_assignments_and_document_libraries_are_isolated()
    {
        $this->manager->moduleAccesses()->create(['company_id'=>$this->company->id,'module'=>'manpower','branch_id'=>$this->branch->id,'role'=>'manager','is_active'=>true]);
        $this->manager->moduleAccesses()->create(['company_id'=>$this->company->id,'module'=>'safety-shop','branch_id'=>$this->otherBranch->id,'role'=>'issuer','is_active'=>true]);
        $manpower=LibraryDocument::create(['company_id'=>$this->company->id,'module'=>'manpower','branch_id'=>$this->branch->id,'owner_id'=>$this->manager->id,'uploaded_by'=>$this->manager->id,'title'=>'Manpower branch file','original_name'=>'manpower.pdf','storage_path'=>'documents/manpower.pdf','mime_type'=>'application/pdf','size_bytes'=>100]);
        $safety=LibraryDocument::create(['company_id'=>$this->company->id,'module'=>'safety-shop','branch_id'=>$this->otherBranch->id,'owner_id'=>$this->manager->id,'uploaded_by'=>$this->manager->id,'title'=>'Safety branch file','original_name'=>'safety.pdf','storage_path'=>'documents/safety.pdf','mime_type'=>'application/pdf','size_bytes'=>100]);

        $this->withSession(['active_module'=>'manpower'])->actingAs($this->manager)->get(route('manpower.documents.index'))->assertOk()->assertSee($manpower->title)->assertDontSee($safety->title);
        $this->flushSession()->withSession(['active_module'=>'safety-shop'])->actingAs($this->manager)->get(route('safety-shop.documents.index'))->assertOk()->assertSee($safety->title)->assertDontSee($manpower->title);
        $this->get(route('safety-shop.documents.show',$manpower))->assertForbidden();

        $this->flushSession()->withSession(['active_module'=>'manpower'])->actingAs($this->admin)->get(route('manpower.documents.index'))->assertOk()->assertSee($manpower->title)->assertDontSee($safety->title);
    }

    public function test_opening_other_module_login_clears_current_module_session()
    {
        $this->withSession(['active_module'=>'manpower'])->actingAs($this->manager)
            ->get(route('safety-shop.login'))
            ->assertOk()
            ->assertSee('Sign in to Safety Shop');
        $this->assertGuest();
    }

    public function test_manager_cannot_forge_company_or_branch_on_employee_write()
    {
        $payload=$this->employeePayload($this->company->id,$this->otherBranch->id);
        $this->actingAs($this->manager)->post(route('employees.store'),$payload)->assertForbidden();
        $payload=$this->employeePayload($this->otherCompany->id,$this->foreignBranch->id);
        $this->post(route('employees.store'),$payload)->assertForbidden();
        $this->assertDatabaseMissing('employees',['iqama_number'=>'1234567890']);
    }

    public function test_company_admin_sees_all_own_branches_but_not_another_company()
    {
        $first=$this->employee('First Branch',$this->company,$this->branch);
        $second=$this->employee('Second Branch',$this->company,$this->otherBranch);
        $foreign=$this->employee('Hidden Tenant',$this->otherCompany,$this->foreignBranch);
        $this->actingAs($this->admin)->get(route('employees.index'))->assertOk()->assertSee($first->name)->assertSee($second->name)->assertDontSee($foreign->name);
        $this->get(route('employees.show',$foreign))->assertForbidden();
    }

    public function test_timesheet_and_payroll_routes_exports_and_bulk_ids_are_scoped()
    {
        $own=$this->employee('Own Payroll',$this->company,$this->branch);
        $sibling=$this->employee('Sibling Payroll',$this->company,$this->otherBranch);
        $ownTime=$this->timesheet($own); $siblingTime=$this->timesheet($sibling);
        $ownPay=$this->payroll($own); $siblingPay=$this->payroll($sibling);
        $this->actingAs($this->manager)->get(route('timesheets.edit',$siblingTime))->assertForbidden();
        $this->get(route('payrolls.show',$siblingPay))->assertForbidden();
        $csv=$this->get(route('payrolls.export',['month'=>now()->format('Y-m')]))->assertOk()->streamedContent();
        $this->assertStringContainsString($ownPay->employee_name,$csv); $this->assertStringNotContainsString($siblingPay->employee_name,$csv);
        $foreignTime=$this->timesheet($this->employee('Foreign',$this->otherCompany,$this->foreignBranch));
        $this->flushSession()->actingAs($this->admin)->post(route('timesheets.bulk.approve'),['timesheet_ids'=>[$foreignTime->id]])->assertForbidden();
        $this->assertSame('pending',$ownTime->fresh()->status);
    }

    public function test_company_roles_get_company_scoped_safety_shop_without_cross_company_leakage()
    {
        $ownCategory=Master::create(['company_id'=>$this->company->id,'type'=>'category','name'=>'Alpha protection','sku_prefix'=>'ALPHA','is_active'=>true]);
        $foreignCategory=Master::create(['company_id'=>$this->otherCompany->id,'type'=>'category','name'=>'Beta private protection','sku_prefix'=>'BETA','is_active'=>true]);
        $ownProduct=Product::create(['company_id'=>$this->company->id,'sku'=>'ALPHA-0001','barcode'=>'ALPHA001','name'=>'Alpha helmet','category_id'=>$ownCategory->id,'unit'=>'piece','is_active'=>true]);
        $foreignProduct=Product::create(['company_id'=>$this->otherCompany->id,'sku'=>'BETA-0001','barcode'=>'BETA001','name'=>'Beta private helmet','category_id'=>$foreignCategory->id,'unit'=>'piece','is_active'=>true]);
        Customer::create(['company_id'=>$this->otherCompany->id,'name'=>'Beta private customer','customer_type'=>'retail','country_code'=>'SA','is_active'=>true,'approval_status'=>'approved']);
        $this->actingAs($this->admin)->get(route('safety-shop.stock.index'))->assertOk();
        $this->get(route('invoicing.index'))->assertForbidden();
        $this->get(route('safety-shop.index'))->assertOk();
        $this->get(route('safety-shop.products.index'))->assertOk()->assertSee($ownProduct->name)->assertDontSee($foreignProduct->name);
        $this->get(route('safety-shop.categories.index'))->assertOk()->assertSee($ownCategory->name)->assertDontSee($foreignCategory->name);
        $this->get(route('safety-shop.customers.index'))->assertOk()->assertDontSee('Beta private customer');
        $this->get(route('safety-shop.products.show',$foreignProduct))->assertNotFound();
        $this->get(route('safety-shop.categories.edit',$foreignCategory))->assertNotFound();
        $this->flushSession()->actingAs($this->manager)->get(route('safety-shop.index'))->assertOk()->assertSee($ownProduct->name)->assertDontSee($foreignProduct->name);
        $issuer=$this->user('shop_issuer','issuer',$this->company->id,$this->branch->id);
        $this->flushSession()->actingAs($issuer)->get(route('safety-shop.index'))->assertOk()->assertSee($ownProduct->name)->assertDontSee($foreignProduct->name);
        $this->get(route('safety-shop.stock.create'))->assertOk();
        $this->get(route('safety-shop.reports.index'))->assertForbidden();
        $this->get(route('companies.branches.index',$this->company))->assertForbidden();
    }

    public function test_company_admin_has_one_combined_analytics_and_audit_center()
    {
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('Safety Shop summary')->assertSee('Six-month operating trend');
        foreach(['activity','employees','timesheets','salaries','shop_sales','shop_returns','stock_movements'] as $report){
            $this->get(route('audit.index',['report'=>$report]))->assertOk();
        }
        $this->get(route('audit.csv',['report'=>'shop_sales']))->assertOk()->assertHeader('Content-Type','text/csv; charset=UTF-8');
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('safety-shop.reports.index'));
        $this->flushSession()->actingAs($this->manager)->get(route('safety-shop.reports.index'))->assertOk();
        $this->get(route('audit.index',['report'=>'shop_sales']))->assertForbidden();
    }

    public function test_system_owner_can_manage_platform_but_cannot_open_company_operations()
    {
        $super=$this->user('system_owner','super_admin',null,null);
        $this->actingAs($super)->get(route('companies.index'))->assertOk();
        $this->get(route('superadmin.index'))->assertRedirect(route('companies.index'));
        $this->get(route('users.index'))->assertOk();
        $this->get(route('dashboard'))->assertRedirect(route('superadmin.index'));
        $this->get(route('employees.index'))->assertForbidden();
        $this->get(route('timesheets.index'))->assertForbidden();
        $this->get(route('payrolls.index'))->assertForbidden();
        $this->get(route('safety-shop.index'))->assertForbidden();
        $this->get(route('invoicing.index'))->assertForbidden();
        $this->get(route('document-library.index'))->assertForbidden();
        $this->assertStringContainsString('/superadmin/companies',route('companies.index'));
        $this->assertStringContainsString('/superadmin/users',route('users.index'));
        $this->get('/companies')->assertNotFound();
        $this->get('/users')->assertNotFound();
    }

    public function test_system_owner_uses_separate_superadmin_login_portal()
    {
        $super=$this->user('portal_owner','super_admin',null,null);
        $this->get(route('superadmin.index'))->assertRedirect(route('superadmin.login'));
        $this->get(route('superadmin.login'))->assertOk()->assertSee('Superadmin');
        $this->post(route('login.submit'),['username'=>$super->username,'password'=>'StrongPass1!'])->assertSessionHasErrors('company_code');
        $this->assertGuest();
        $this->post(route('superadmin.login.submit'),['username'=>$super->username,'password'=>'StrongPass1!'])->assertRedirect(route('companies.index'));
        $this->assertAuthenticatedAs($super);
        $this->post(route('superadmin.logout'))->assertRedirect(route('superadmin.login'));
        $this->assertGuest();
    }

    public function test_manager_approves_branch_entries_and_issuer_is_entry_only()
    {
        $issuer=$this->user('branch_issuer','issuer',$this->company->id,$this->branch->id);
        $employee=$this->employee('Issuer Worker',$this->company,$this->branch);
        $entry=$this->timesheet($employee);
        $this->actingAs($issuer)->get(route('timesheets.index'))->assertOk()->assertSee('Issuer Worker');
        $this->post(route('timesheets.review',$entry),['status'=>'approved'])->assertForbidden();
        $this->flushSession()->actingAs($this->manager)->post(route('timesheets.review',$entry),['status'=>'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved',$entry->fresh()->status);
    }

    public function test_company_admin_can_edit_and_delete_approved_unlocked_hours()
    {
        $employee=$this->employee('Admin Correction Worker',$this->company,$this->branch);
        $entry=$this->timesheet($employee); $entry->update(['status'=>'approved','reviewed_by'=>$this->manager->id,'reviewed_at'=>now()]);
        $this->actingAs($this->admin)->get(route('timesheets.edit',$entry))->assertOk();
        $this->put(route('timesheets.update',$entry),['employee_id'=>$employee->id,'work_date'=>$entry->work_date->format('Y-m-d'),'regular_hours'=>'7.50','overtime_hours'=>'0.50','notes'=>'Admin correction'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('timesheets',['id'=>$entry->id,'regular_units'=>750,'overtime_units'=>50,'status'=>'pending']);
        $entry->update(['status'=>'approved']);
        $this->delete(route('timesheets.destroy',$entry))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('timesheets',['id'=>$entry->id]);
    }

    public function test_company_admin_can_reopen_or_delete_any_salary_status()
    {
        $employee=$this->employee('Admin Salary Worker',$this->company,$this->branch);
        $payroll=$this->payroll($employee); $payroll->update(['status'=>'paid','approved_by'=>$this->admin->id,'approved_at'=>now(),'paid_by'=>$this->admin->id,'paid_at'=>now()]);
        $this->actingAs($this->admin)->put(route('payrolls.update',$payroll),['allowance'=>'20.00','deduction'=>'5.00','notes'=>'Corrected by company admin'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payrolls',['id'=>$payroll->id,'status'=>'pending','allowance_cents'=>2000,'deduction_cents'=>500,'approved_by'=>null,'paid_by'=>null]);
        $payroll->update(['status'=>'paid','approved_by'=>$this->admin->id,'approved_at'=>now(),'paid_by'=>$this->admin->id,'paid_at'=>now()]);
        $this->delete(route('payrolls.destroy',$payroll))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('payrolls',['id'=>$payroll->id]);
    }

    public function test_company_admin_can_manage_all_documents_in_own_company_only()
    {
        $own=LibraryDocument::create(['owner_id'=>$this->manager->id,'uploaded_by'=>$this->manager->id,'title'=>'Own company contract','original_name'=>'own.pdf','storage_path'=>'documents/own.pdf','mime_type'=>'application/pdf','size_bytes'=>100]);
        $foreignOwner=$this->user('foreign_document_owner','manager',$this->otherCompany->id,$this->foreignBranch->id);
        $foreign=LibraryDocument::create(['owner_id'=>$foreignOwner->id,'uploaded_by'=>$foreignOwner->id,'title'=>'Foreign private contract','original_name'=>'foreign.pdf','storage_path'=>'documents/foreign.pdf','mime_type'=>'application/pdf','size_bytes'=>100]);
        $this->actingAs($this->admin)->get(route('document-library.index'))->assertOk()->assertSee($own->title)->assertDontSee($foreign->title);
        $this->get(route('document-library.edit',$own))->assertOk();
        $this->get(route('document-library.edit',$foreign))->assertForbidden();
        $this->delete(route('document-library.destroy',$own))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('document_library_files',['id'=>$own->id]);
    }

    public function test_branch_allows_one_manager_and_multiple_issuers()
    {
        $admin=$this->admin;
        foreach (['issuer_one','issuer_two'] as $username) {
            $this->actingAs($admin)->post(route('company-users.store'),['name'=>$username,'username'=>$username,'password'=>'StrongPass1!','password_confirmation'=>'StrongPass1!','role'=>'issuer','is_active'=>1,'company_id'=>$this->company->id,'branch_id'=>$this->branch->id])->assertSessionHasNoErrors();
        }
        $this->assertSame(2,User::where('role','issuer')->where('branch_id',$this->branch->id)->count());
    }

    public function test_company_admin_manages_only_manager_and_issuers_in_own_company()
    {
        $this->actingAs($this->admin)->get(route('company-users.create'))->assertOk()->assertDontSee('Global (System Owner only)')->assertSee('type="hidden" value="'.$this->company->id.'"',false);
        $payload=['name'=>'New Issuer','username'=>'new_issuer','password'=>'StrongPass1!','password_confirmation'=>'StrongPass1!','role'=>'issuer','is_active'=>1,'company_id'=>$this->otherCompany->id,'branch_id'=>$this->branch->id];
        $this->post(route('company-users.store'),$payload)->assertSessionHasNoErrors();
        $created=User::where('username','new_issuer')->firstOrFail();
        $this->assertSame($this->company->id,$created->company_id);
        $this->assertSame($this->branch->id,$created->branch_id);
        $payload['username']='forged_admin'; $payload['role']='admin';
        $this->post(route('company-users.store'),$payload)->assertSessionHasErrors('role');
        $foreign=$this->user('foreign_issuer','issuer',$this->otherCompany->id,$this->foreignBranch->id);
        $this->get(route('company-users.edit',$foreign))->assertForbidden();
    }

    public function test_superadmin_can_create_operational_users_with_module_scopes()
    {
        $super=$this->user('full_control_owner','super_admin',null,null);
        $this->actingAs($super)->post(route('users.store'),[
            'name'=>'Safety Operator','username'=>'safety_operator','password'=>'StrongPass1!','password_confirmation'=>'StrongPass1!',
            'role'=>'issuer','is_active'=>1,'company_id'=>$this->company->id,
            'safety_shop_enabled'=>1,'safety_shop_role'=>'issuer','safety_shop_branch_id'=>$this->otherBranch->id,
        ])->assertSessionHasNoErrors();
        $created=User::where('username','safety_operator')->firstOrFail();
        $this->assertDatabaseHas('user_module_access',['user_id'=>$created->id,'module'=>'safety-shop','branch_id'=>$this->otherBranch->id,'role'=>'issuer']);
        $this->assertDatabaseMissing('user_module_access',['user_id'=>$created->id,'module'=>'manpower']);
    }

    public function test_company_branding_screen_is_removed_in_favour_of_fixed_letterhead()
    {
        $this->actingAs($this->admin)->get('/company-branding')->assertNotFound();
        $this->assertFileExists(config('documents.letterhead_pdf'));
    }

    public function test_employee_advance_is_tenant_scoped_and_deducted_from_payroll()
    {
        $employee=$this->employee('Advance Worker',$this->company,$this->branch);
        $foreign=$this->employee('Other Branch Worker',$this->company,$this->otherBranch);
        $this->actingAs($this->manager)->post(route('employees.advances.store',$foreign),['advance_date'=>now()->format('Y-m-d'),'amount'=>'500','installment'=>'200'])->assertForbidden();
        $this->post(route('employees.advances.store',$employee),['advance_date'=>now()->format('Y-m-d'),'amount'=>'500','installment'=>'20','reference'=>'ADV-001'])->assertSessionHasNoErrors();
        $this->timesheet($employee)->update(['status'=>'approved']);
        $this->post(route('payrolls.store'),['employee_id'=>$employee->id,'month'=>now()->format('Y-m'),'allowance'=>'0','deduction'=>'0'])->assertSessionHasNoErrors();
        $payroll=Payroll::where('employee_id',$employee->id)->firstOrFail();
        $this->assertSame(2000,(int)$payroll->advance_deduction_cents);
        $this->assertSame(6000,(int)$payroll->net_pay_cents);
        $this->assertDatabaseHas('employee_advance_repayments',['payroll_id'=>$payroll->id,'amount_cents'=>2000]);
    }

    public function test_role_owners_enforce_one_company_admin_and_one_manager_per_branch()
    {
        $super=$this->user('super_scope','super_admin',null,null);
        $base=['name'=>'Duplicate','username'=>'duplicate','password'=>'StrongPass1!','password_confirmation'=>'StrongPass1!','role'=>'admin','is_active'=>1,'company_id'=>$this->company->id];
        $this->actingAs($super)->post(route('users.store'),$base)->assertSessionHasErrors('company_id');
        $base['role']='manager'; $base['branch_id']=$this->branch->id;
        $this->post(route('users.store'),$base)->assertSessionHasErrors('role');
        $this->flushSession()->actingAs($this->admin)->post(route('company-users.store'),$base)->assertSessionHasErrors('branch_id');
        $base['branch_id']=$this->foreignBranch->id;
        $this->post(route('company-users.store'),$base)->assertSessionHasErrors('branch_id');
        $this->assertDatabaseMissing('users',['username'=>'duplicate']);
    }

    public function test_manpower_feature_flag_hides_and_blocks_the_module()
    {
        config(['manpower.enabled' => false]);

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Rental manpower')
            ->assertDontSee('Active workforce');
        $this->get(route('employees.index'))->assertNotFound();
        $this->get(route('timesheets.index'))->assertNotFound();
        $this->get(route('payrolls.index'))->assertNotFound();
        $this->get(route('designations.index'))->assertNotFound();
        $this->get(route('audit.index', ['report' => 'employees']))->assertNotFound();
        $this->get(route('audit.index', ['report' => 'activity']))->assertOk();
        $this->get(route('safety-shop.index'))->assertOk();
    }

    private function user($username,$role,$companyId,$branchId){return User::create(['name'=>$username,'username'=>$username,'email'=>$username.'@test.local','password'=>Hash::make('StrongPass1!'),'role'=>$role,'company_id'=>$companyId,'branch_id'=>$branchId,'is_active'=>true]);}
    private function employee($name,Company $company,Branch $branch){static $n=1000000000;$n++;return Employee::create(['name'=>$name,'photo_path'=>'none','iqama_number'=>(string)$n,'passport_number'=>'P'.$n,'phone'=>'+966500000000','designation_id'=>$this->designation->id,'company_id'=>$company->id,'branch_id'=>$branch->id,'blood_group'=>'O+','hourly_rate_cents'=>1000,'overtime_rate_cents'=>1000,'regular_hours_units'=>800,'salary_type'=>'hourly','employment_type'=>'rental','monthly_salary_cents'=>0,'overtime_multiplier_units'=>100,'joined_on'=>now()->subMonth(),'status'=>'active','created_by'=>$this->manager->id]);}
    private function timesheet(Employee $e){return Timesheet::create(['employee_id'=>$e->id,'company_id'=>$e->company_id,'branch_id'=>$e->branch_id,'work_date'=>now()->format('Y-m-d'),'regular_units'=>800,'overtime_units'=>0,'hourly_rate_cents'=>1000,'overtime_rate_cents'=>1000,'overtime_multiplier_units'=>100,'status'=>'pending','created_by'=>$this->manager->id]);}
    private function payroll(Employee $e){return Payroll::create(['employee_id'=>$e->id,'company_id'=>$e->company_id,'branch_id'=>$e->branch_id,'month'=>now()->format('Y-m'),'salary_type'=>'hourly','employment_type'=>'rental','employee_name'=>$e->name,'company_name'=>$e->company->name,'designation_name'=>'Technician','iqama_number'=>$e->iqama_number,'regular_units'=>800,'overtime_units'=>0,'regular_pay_cents'=>8000,'overtime_pay_cents'=>0,'net_pay_cents'=>8000,'status'=>'pending','created_by'=>$this->manager->id]);}
    private function employeePayload($company,$branch){return ['name'=>'Forged Worker','photo'=>\Illuminate\Http\UploadedFile::fake()->image('photo.jpg'),'iqama_number'=>'1234567890','passport_number'=>'PASS123','phone'=>'+966500000001','company_id'=>$company,'branch_id'=>$branch,'designation_id'=>$this->designation->id,'blood_group'=>'O+','hourly_rate'=>'10','overtime_rate'=>'10','po_rate'=>'18','company_cost'=>'500','regular_hours'=>'8','joined_on'=>now()->format('Y-m-d'),'status'=>'active'];}
}
