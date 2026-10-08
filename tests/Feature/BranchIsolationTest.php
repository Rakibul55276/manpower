<?php
namespace Tests\Feature;

use App\Models\{Branch,Company,Designation,Employee,Payroll,Timesheet,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;
    private $company; private $otherCompany; private $branch; private $otherBranch; private $foreignBranch; private $manager; private $admin; private $designation;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function test_global_unscoped_modules_are_not_exposed_to_company_accounts()
    {
        $this->actingAs($this->admin)->get(route('invoicing.index'))->assertForbidden();
        $this->get(route('safety-shop.index'))->assertForbidden();
        $this->flushSession()->actingAs($this->manager)->get(route('companies.branches.index',$this->company))->assertForbidden();
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

    public function test_super_admin_enforces_one_company_admin_and_one_manager_per_branch()
    {
        $super=$this->user('super_scope','super_admin',null,null);
        $base=['name'=>'Duplicate','username'=>'duplicate','password'=>'StrongPass1!','password_confirmation'=>'StrongPass1!','role'=>'admin','is_active'=>1,'company_id'=>$this->company->id];
        $this->actingAs($super)->post(route('users.store'),$base)->assertSessionHasErrors('company_id');
        $base['role']='manager'; $base['branch_id']=$this->branch->id;
        $this->post(route('users.store'),$base)->assertSessionHasErrors('branch_id');
        $base['branch_id']=$this->foreignBranch->id;
        $this->post(route('users.store'),$base)->assertSessionHasErrors('branch_id');
        $this->assertDatabaseMissing('users',['username'=>'duplicate']);
    }

    private function user($username,$role,$companyId,$branchId){return User::create(['name'=>$username,'username'=>$username,'email'=>$username.'@test.local','password'=>Hash::make('StrongPass1!'),'role'=>$role,'company_id'=>$companyId,'branch_id'=>$branchId,'is_active'=>true]);}
    private function employee($name,Company $company,Branch $branch){static $n=1000000000;$n++;return Employee::create(['name'=>$name,'photo_path'=>'none','iqama_number'=>(string)$n,'passport_number'=>'P'.$n,'phone'=>'+966500000000','designation_id'=>$this->designation->id,'company_id'=>$company->id,'branch_id'=>$branch->id,'blood_group'=>'O+','hourly_rate_cents'=>1000,'overtime_rate_cents'=>1000,'regular_hours_units'=>800,'salary_type'=>'hourly','employment_type'=>'rental','monthly_salary_cents'=>0,'overtime_multiplier_units'=>100,'joined_on'=>now()->subMonth(),'status'=>'active','created_by'=>$this->manager->id]);}
    private function timesheet(Employee $e){return Timesheet::create(['employee_id'=>$e->id,'company_id'=>$e->company_id,'branch_id'=>$e->branch_id,'work_date'=>now()->format('Y-m-d'),'regular_units'=>800,'overtime_units'=>0,'hourly_rate_cents'=>1000,'overtime_rate_cents'=>1000,'overtime_multiplier_units'=>100,'status'=>'pending','created_by'=>$this->manager->id]);}
    private function payroll(Employee $e){return Payroll::create(['employee_id'=>$e->id,'company_id'=>$e->company_id,'branch_id'=>$e->branch_id,'month'=>now()->format('Y-m'),'salary_type'=>'hourly','employment_type'=>'rental','employee_name'=>$e->name,'company_name'=>$e->company->name,'designation_name'=>'Technician','iqama_number'=>$e->iqama_number,'regular_units'=>800,'overtime_units'=>0,'regular_pay_cents'=>8000,'overtime_pay_cents'=>0,'net_pay_cents'=>8000,'status'=>'pending','created_by'=>$this->manager->id]);}
    private function employeePayload($company,$branch){return ['name'=>'Forged Worker','photo'=>\Illuminate\Http\UploadedFile::fake()->image('photo.jpg'),'iqama_number'=>'1234567890','passport_number'=>'PASS123','phone'=>'+966500000001','company_id'=>$company,'branch_id'=>$branch,'designation_id'=>$this->designation->id,'blood_group'=>'O+','hourly_rate'=>'10','overtime_rate'=>'10','po_rate'=>'18','company_cost'=>'500','regular_hours'=>'8','joined_on'=>now()->format('Y-m-d'),'status'=>'active'];}
}
