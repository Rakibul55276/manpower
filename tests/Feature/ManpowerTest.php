<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\User;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\Payroll;
use App\Services\Documents;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
class ManpowerTest extends TestCase
{
    use RefreshDatabase;
    private $admin;
    private $manager;
    private $company;
    private $other;
    private $designation;
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        // Simulate a fresh sign-in when switching roles within one test client.
        $this->withSession(['password_hash_'.($guard ?? 'web') => $user->getAuthPassword()]);
        return parent::actingAs($user, $guard);
    }
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::create(['name' => 'Test Admin', 'email' => 'admin@test.local', 'password' => Hash::make('A-secure-password'), 'role' => 'super_admin', 'is_active' => true]);
        $this->manager = User::create(['name' => 'Test Manager', 'email' => 'manager@test.local', 'password' => Hash::make('A-secure-password'), 'role' => 'manager', 'is_active' => true]);
        $this->company = Company::create(['name' => 'Assigned Alpha']);
        $second = Company::create(['name' => 'Assigned Beta']);
        $this->other = Company::create(['name' => 'Restricted Company']);
        $this->manager->companies()->attach([$this->company->id, $second->id]);
        $this->designation = Designation::create(['name' => 'Electrician']);
    }
    private function employee($overrides = [])
    {
        static $sequence = 1000000000; $sequence++;
        return Employee::create(array_merge(['name' => 'Employee '.$sequence, 'photo_path' => 'employee-photos/test.png', 'iqama_number' => (string) $sequence, 'passport_number' => 'P'.$sequence, 'phone' => '+966 501234567', 'company_id' => $this->company->id, 'designation_id' => $this->designation->id, 'blood_group' => 'O+', 'previous_experience' => ['Worked as an electrician for five years.'], 'employment_type' => 'rental', 'salary_type' => 'hourly', 'hourly_rate_cents' => 1234, 'monthly_salary_cents' => 0, 'overtime_multiplier_units' => 150, 'joined_on' => now()->subYear()->format('Y-m-d'), 'status' => 'active', 'created_by' => $this->admin->id], $overrides));
    }
    private function entry(Employee $employee, $overrides = [])
    {
        return Timesheet::create(array_merge(['employee_id' => $employee->id, 'work_date' => now()->format('Y-m-d'), 'regular_units' => 850, 'overtime_units' => 125, 'hourly_rate_cents' => $employee->hourly_rate_cents, 'overtime_multiplier_units' => $employee->overtime_multiplier_units, 'status' => 'approved', 'created_by' => $this->manager->id], $overrides));
    }
    private function payload($overrides = [])
    {
        return array_merge(['name' => 'New Worker', 'photo' => UploadedFile::fake()->image('photo.jpg'), 'iqama_number' => '2123456789', 'passport_number' => 'AB123456', 'phone' => '+966501234567', 'company_id' => $this->company->id, 'designation_id' => $this->designation->id, 'blood_group' => 'A+', 'previous_experience' => ['Experience one', 'Experience two'], 'hourly_rate' => '20.00', 'overtime_multiplier' => '1.50', 'joined_on' => now()->subMonth()->format('Y-m-d'), 'status' => 'active'], $overrides);
    }
    private function generate(Employee $employee, $prefix = 'payrolls', $overrides = [])
    {
        return $this->actingAs($this->admin)->post(route($prefix.'.store'), array_merge(['employee_id' => $employee->id, 'month' => now()->format('Y-m'), 'allowance' => '1.00', 'deduction' => '0.50'], $overrides));
    }
    public function test_login_and_disabled_accounts()
    {
        $this->get(route('login'))->assertOk();
        $this->post(route('login.submit'), ['email' => $this->manager->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post(route('login.submit'), ['email' => $this->manager->email, 'password' => 'A-secure-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->manager);
        $this->manager->update(['is_active' => false]);
        $this->actingAs($this->manager->fresh());
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
    public function test_all_primary_screens_render_for_both_roles()
    {
        foreach ([$this->admin, $this->manager] as $user) {
            $this->actingAs($user);
            foreach (['dashboard', 'employees.index', 'employees.create', 'own-employees.index', 'own-employees.create', 'timesheets.index', 'timesheets.create', 'attendance.index', 'attendance.create', 'payrolls.index', 'salaries.index', 'companies.index', 'designations.index', 'profile'] as $route) { $this->get(route($route))->assertOk(); }
        }
        $this->actingAs($this->admin);
        foreach (['users.index', 'users.create', 'activity'] as $route) { $this->get(route($route))->assertOk(); }
    }
    public function test_manager_has_multiple_companies_but_cannot_access_others()
    {
        $visible = $this->employee(['name' => 'Visible Alpha Worker']);
        $second = $this->employee(['name' => 'Visible Beta Worker', 'company_id' => $this->manager->companies()->where('name', 'Assigned Beta')->first()->id]);
        $hidden = $this->employee(['name' => 'Hidden Worker', 'company_id' => $this->other->id]);
        $this->actingAs($this->manager)->get(route('employees.index'))->assertSee($visible->name)->assertSee($second->name)->assertDontSee($hidden->name);
        foreach (['employees.show', 'employees.edit', 'employees.photo', 'employees.cv'] as $route) { $this->get(route($route, $hidden))->assertForbidden(); }
        $this->put(route('employees.update', $hidden), $this->payload())->assertForbidden();
        $this->delete(route('employees.destroy', $hidden))->assertForbidden();
        $this->post(route('employees.store'), $this->payload(['company_id' => $this->other->id]))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->post(route('companies.store'), ['name' => 'Unauthorized'])->assertForbidden();
    }
    public function test_employee_upload_and_experience_validation_and_workforce_separation()
    {
        $this->actingAs($this->manager)->post(route('employees.store'), $this->payload(['previous_experience' => array_fill(0, 6, 'Experience')]))->assertSessionHasErrors('previous_experience');
        $this->post(route('employees.store'), $this->payload(['photo' => null]))->assertSessionHasErrors('photo');
        $this->post(route('employees.store'), $this->payload())->assertSessionHasNoErrors();
        $rental = Employee::where('name', 'New Worker')->firstOrFail();
        $this->assertEquals('rental', $rental->employment_type); $this->assertEquals('hourly', $rental->salary_type);
        Storage::disk('local')->assertExists($rental->photo_path);
        $this->get(route('employees.photo', $rental))->assertOk();
        $this->get(route('own-employees.show', $rental))->assertNotFound();
        $this->post(route('own-employees.store'), $this->payload(['name' => 'Own Team Member', 'iqama_number' => '3123456789', 'passport_number' => 'CD123456', 'monthly_salary' => '5000.00']))->assertSessionHasNoErrors();
        $own = Employee::where('name', 'Own Team Member')->firstOrFail();
        $this->assertEquals('own', $own->employment_type); $this->assertEquals('monthly', $own->salary_type); $this->assertEquals(500000, $own->monthly_salary_cents);
        $this->get(route('employees.index'))->assertDontSee($own->name);
        $this->get(route('own-employees.index'))->assertSee($own->name)->assertDontSee($rental->name);
        $this->get(route('employees.edit', $rental))->assertOk();
        $this->get(route('own-employees.show', $own))->assertOk();
        $this->get(route('own-employees.edit', $own))->assertOk();
        $this->post(route('employees.store'), $this->payload())->assertSessionHasErrors(['iqama_number', 'passport_number']);
    }
    public function test_manager_submits_hours_and_admin_reviews_them()
    {
        $employee = $this->employee(); $payload = ['employee_id' => $employee->id, 'work_date' => now()->format('Y-m-d'), 'regular_hours' => '8.50', 'overtime_hours' => '1.25'];
        $this->actingAs($this->manager)->post(route('timesheets.store'), array_merge($payload, ['regular_hours' => 24, 'overtime_hours' => 1]))->assertSessionHasErrors('regular_hours');
        $this->post(route('timesheets.store'), $payload)->assertSessionHasNoErrors();
        $entry = Timesheet::firstOrFail(); $this->assertEquals('pending', $entry->status); $this->assertEquals(850, $entry->regular_units);
        $this->get(route('timesheets.edit', $entry))->assertOk();
        $this->post(route('timesheets.store'), $payload)->assertStatus(302)->assertSessionHasErrors('work_date');
        $this->post(route('timesheets.review', $entry), ['status' => 'approved'])->assertForbidden();
        $this->actingAs($this->admin)->post(route('timesheets.review', $entry), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertEquals('approved', $entry->fresh()->status);
        $this->actingAs($this->manager)->put(route('timesheets.update', $entry), $payload)->assertSessionHasErrors('work_date');
        $this->actingAs($this->admin)->post(route('timesheets.review', $entry), ['status' => 'pending', 'review_note' => 'Correct daily hours'])->assertSessionHasNoErrors();
        $this->assertEquals('pending', $entry->fresh()->status);
    }
    public function test_timesheet_scope_dates_and_employee_type_are_enforced()
    {
        $hidden = $this->employee(['company_id' => $this->other->id]); $own = $this->employee(['employment_type' => 'own', 'salary_type' => 'monthly', 'monthly_salary_cents' => 500000]);
        $payload = ['employee_id' => $hidden->id, 'work_date' => now()->format('Y-m-d'), 'regular_hours' => '8', 'overtime_hours' => '0'];
        $this->actingAs($this->manager)->post(route('timesheets.store'), $payload)->assertForbidden();
        $payload['employee_id'] = $own->id;
        $this->post(route('timesheets.store'), $payload)->assertForbidden();
        $this->post(route('attendance.store'), array_merge($payload, ['work_date' => now()->addDay()->format('Y-m-d')]))->assertSessionHasErrors('work_date');
        $this->post(route('attendance.store'), array_merge($payload, ['work_date' => now()->subYears(2)->format('Y-m-d')]))->assertSessionHasErrors('work_date');
        $this->post(route('attendance.store'), $payload)->assertSessionHasNoErrors();
    }
    public function test_hourly_pay_uses_rate_snapshots_exact_cents_and_locks_month()
    {
        $employee = $this->employee(); $entry = $this->entry($employee);
        $employee->update(['hourly_rate_cents' => 9000]);
        $this->generate($employee)->assertSessionHasNoErrors();
        $payroll = Payroll::firstOrFail();
        $this->assertEquals(10489, $payroll->regular_pay_cents); $this->assertEquals(2314, $payroll->overtime_pay_cents); $this->assertEquals(12853, $payroll->net_pay_cents);
        $this->assertEquals($payroll->id, $entry->fresh()->payroll_id);
        $this->generate($employee)->assertSessionHasErrors('month');
        $this->actingAs($this->manager)->post(route('timesheets.store'), ['employee_id' => $employee->id, 'work_date' => now()->subDay()->format('Y-m-d'), 'regular_hours' => 8, 'overtime_hours' => 0])->assertSessionHasErrors('work_date');
        $this->post(route('payrolls.store'), ['employee_id' => $employee->id])->assertForbidden();
        $this->get(route('payrolls.show', $payroll))->assertOk();
        $this->get(route('payrolls.export', ['month' => now()->format('Y-m')]))->assertOk()->assertDownload();
    }
    public function test_pending_hours_and_excessive_deductions_block_payroll()
    {
        $employee = $this->employee(); $entry = $this->entry($employee, ['status' => 'pending']);
        $this->generate($employee)->assertSessionHasErrors('month');
        $entry->update(['status' => 'approved']);
        $this->generate($employee, 'payrolls', ['deduction' => '99999.00'])->assertSessionHasErrors('deduction');
        $this->assertDatabaseCount('payrolls', 0);
    }
    public function test_monthly_salary_is_separate_and_does_not_use_regular_hours()
    {
        $own = $this->employee(['name' => 'Monthly Worker', 'employment_type' => 'own', 'salary_type' => 'monthly', 'monthly_salary_cents' => 500000]);
        $this->entry($own);
        $this->generate($own, 'salaries')->assertSessionHasNoErrors();
        $payroll = Payroll::firstOrFail(); $this->assertEquals(500000, $payroll->regular_pay_cents); $this->assertEquals(502364, $payroll->net_pay_cents);
        $this->get(route('salaries.show', $payroll))->assertOk();
        $this->get(route('payrolls.show', $payroll))->assertNotFound();
        $this->get(route('payrolls.index'))->assertDontSee('Monthly Worker');
        $this->get(route('salaries.index'))->assertSee('Monthly Worker');
    }
    public function test_draft_can_be_voided_but_paid_salary_is_immutable()
    {
        $employee = $this->employee(); $entry = $this->entry($employee); $this->generate($employee);
        $payroll = Payroll::firstOrFail();
        $this->delete(route('payrolls.destroy', $payroll))->assertSessionHasNoErrors();
        $this->assertNull($entry->fresh()->payroll_id);
        $this->generate($employee); $payroll = Payroll::firstOrFail();
        $this->post(route('payrolls.paid', $payroll))->assertSessionHasNoErrors();
        $this->put(route('payrolls.update', $payroll), ['allowance' => 0, 'deduction' => 0])->assertForbidden();
        $this->delete(route('payrolls.destroy', $payroll))->assertForbidden();
        $this->post(route('payrolls.paid', $payroll))->assertForbidden();
    }
    public function test_payroll_details_pdf_and_csv_are_scoped_to_company()
    {
        $employee = $this->employee(['name' => 'Restricted Salary Person', 'company_id' => $this->other->id]); $this->entry($employee); $this->generate($employee); $payroll = Payroll::firstOrFail();
        $this->actingAs($this->manager)->get(route('payrolls.show', $payroll))->assertForbidden();
        $this->get(route('payrolls.pdf', $payroll))->assertForbidden();
        $this->get(route('payrolls.index'))->assertDontSee($employee->name);
        $csv = $this->get(route('payrolls.export', ['month' => now()->format('Y-m')]))->streamedContent();
        $this->assertStringNotContainsString($employee->name, $csv);
    }
    public function test_admin_can_assign_multiple_companies_and_cannot_disable_self()
    {
        $data = ['name' => 'New Manager', 'email' => 'new@test.local', 'password' => 'Long-password-123', 'password_confirmation' => 'Long-password-123', 'role' => 'manager', 'is_active' => 1, 'companies' => [$this->company->id, $this->other->id]];
        $this->actingAs($this->admin)->post(route('users.store'), $data)->assertSessionHasNoErrors();
        $user = User::where('email', 'new@test.local')->firstOrFail(); $this->assertEquals(2, $user->companies()->count());
        $this->get(route('users.edit', $user))->assertOk();
        $this->put(route('users.update', $this->admin), ['name' => 'Admin', 'email' => $this->admin->email, 'role' => 'manager', 'is_active' => 0])->assertSessionHasErrors('role');
    }
    public function test_lookup_management_preserves_employee_references()
    {
        $this->employee();
        $this->actingAs($this->admin)->delete(route('companies.destroy', $this->company))->assertSessionHasErrors('name');
        $this->put(route('companies.update', $this->company), ['name' => 'Updated Company', 'is_active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($this->company->fresh()->is_active);
        $this->actingAs($this->manager)->post(route('designations.store'), ['name' => 'Welder'])->assertSessionHasNoErrors();
        $designation = Designation::where('name', 'Welder')->firstOrFail();
        $this->put(route('designations.update', $designation), ['name' => 'Senior Welder', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->delete(route('designations.destroy', $designation))->assertSessionHasNoErrors();
    }
    public function test_cv_merges_supporting_pdf_and_payslip_is_valid_pdf()
    {
        $attachment = new \FPDF; $attachment->AddPage(); $attachment->SetFont('Arial', '', 14); $attachment->Cell(0, 15, 'Supporting certificate - TEST ONLY');
        $bytes = $attachment->Output('S');
        $file = UploadedFile::fake()->createWithContent('certificate.pdf', $bytes);
        $this->actingAs($this->manager)->post(route('employees.store'), $this->payload(['document' => $file]))->assertSessionHasNoErrors();
        $employee = Employee::where('name', 'New Worker')->firstOrFail();
        Storage::disk('local')->assertExists($employee->document_path);
        $response = $this->get(route('employees.cv', $employee))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $parser = new Fpdi; $this->assertEquals(2, $parser->setSourceFile(StreamReader::createByString($response->getContent())));
        $this->entry($employee); $this->generate($employee); $payroll = Payroll::firstOrFail();
        $payslip = $this->get(route('payrolls.pdf', $payroll))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertEquals(1, $parser->setSourceFile(StreamReader::createByString($payslip->getContent())));
        if (!is_dir(base_path('tmp/pdfs'))) { mkdir(base_path('tmp/pdfs'), 0755, true); }
        file_put_contents(base_path('tmp/pdfs/cv-qa.pdf'), $response->getContent());
        file_put_contents(base_path('tmp/pdfs/payslip-qa.pdf'), $payslip->getContent());
    }
    public function test_invalid_supporting_document_is_rejected_and_archive_preserves_history()
    {
        $this->actingAs($this->manager)->post(route('employees.store'), $this->payload(['document' => UploadedFile::fake()->createWithContent('broken.pdf', '%PDF-1.4 invalid')]))->assertSessionHasErrors('document');
        $employee = $this->employee(); $entry = $this->entry($employee);
        $this->delete(route('employees.destroy', $employee))->assertSessionHasNoErrors();
        $this->assertEquals('inactive', $employee->fresh()->status); $this->assertDatabaseHas('timesheets', ['id' => $entry->id]);
    }
    public function test_demo_command_creates_exactly_100_employees_without_duplicates()
    {
        $this->manager->update(['email' => 'manager@manpower.local']);
        $this->artisan('manpower:demo')->assertExitCode(0);
        $this->assertDatabaseCount('employees', 100);
        $this->assertEquals(80, Employee::where('employment_type', 'rental')->count());
        $this->assertEquals(20, Employee::where('employment_type', 'own')->count());
        $this->assertDatabaseCount('payrolls', 100);
        $hoursCount = Timesheet::count();
        $this->artisan('manpower:demo')->assertExitCode(0);
        $this->assertDatabaseCount('employees', 100);
        $this->assertDatabaseCount('payrolls', 100);
        $this->assertEquals($hoursCount, Timesheet::count());
    }
    public function test_long_cv_experience_and_full_month_payslip_paginate()
    {
        $text = str_repeat('Worked on electrical installation, site maintenance, safety inspections, and daily project reporting. ', 19);
        $employee = $this->employee(['name' => 'Long Experience Demo', 'previous_experience' => array_fill(0, 5, $text)]);
        $bytes = Documents::cv($employee); $parser = new Fpdi;
        $this->assertGreaterThan(1, $parser->setSourceFile(StreamReader::createByString($bytes)));
        $this->assertLessThanOrEqual(8, $parser->setSourceFile(StreamReader::createByString($bytes)));
        file_put_contents(base_path('tmp/pdfs/long-cv-qa.pdf'), $bytes);
        $start = now()->subMonthNoOverflow()->startOfMonth();
        for ($date = $start->copy(); $date->month === $start->month; $date->addDay()) { $this->entry($employee, ['work_date' => $date->format('Y-m-d')]); }
        $this->generate($employee, 'payrolls', ['month' => $start->format('Y-m')])->assertSessionHasNoErrors();
        $payroll = Payroll::firstOrFail(); $bytes = Documents::payslip($payroll);
        $this->assertGreaterThanOrEqual(2, $parser->setSourceFile(StreamReader::createByString($bytes)));
        file_put_contents(base_path('tmp/pdfs/full-month-payslip-qa.pdf'), $bytes);
    }
    public function test_imported_account_password_can_be_set_interactively()
    {
        $this->manager->update(['is_active' => false]);
        $this->artisan('manpower:reset-password', ['email' => $this->manager->email, '--activate' => true])
            ->expectsQuestion('New password (at least 10 characters)', 'New-strong-password')
            ->expectsQuestion('Confirm new password', 'New-strong-password')->assertExitCode(0);
        $this->assertTrue($this->manager->fresh()->is_active);
        $this->assertTrue(Hash::check('New-strong-password', $this->manager->fresh()->password));
    }
}
