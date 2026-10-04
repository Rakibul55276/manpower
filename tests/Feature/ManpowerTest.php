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
    private $approver;
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
        $this->admin = User::create(['name' => 'Test Admin', 'username' => 'admin', 'email' => 'admin@test.local', 'password' => Hash::make('A-secure-password'), 'role' => 'super_admin', 'is_active' => true]);
        $this->manager = User::create(['name' => 'Test Manager', 'username' => 'manager', 'email' => 'manager@test.local', 'password' => Hash::make('A-secure-password'), 'role' => 'manager', 'is_active' => true]);
        $this->approver = User::create(['name' => 'Test Approver', 'username' => 'approver', 'email' => 'approver@test.local', 'password' => Hash::make('A-secure-password'), 'role' => 'admin', 'is_active' => true]);
        $this->company = Company::create(['name' => 'Assigned Alpha']);
        $second = Company::create(['name' => 'Assigned Beta']);
        $this->other = Company::create(['name' => 'Restricted Company']);
        $this->manager->companies()->attach([$this->company->id, $second->id]);
        $this->designation = Designation::create(['name' => 'Electrician']);
    }
    private function employee($overrides = [])
    {
        static $sequence = 1000000000; $sequence++;
        return Employee::create(array_merge(['name' => 'Employee '.$sequence, 'photo_path' => 'employee-photos/test.png', 'iqama_number' => (string) $sequence, 'passport_number' => 'P'.$sequence, 'phone' => '+966 501234567', 'nationality' => 'Saudi Arabia', 'personal_email' => 'employee'.$sequence.'@test.local', 'professional_summary' => 'Experienced professional.', 'education' => 'Technical diploma.', 'skills' => 'Safety, teamwork', 'company_id' => $this->company->id, 'designation_id' => $this->designation->id, 'blood_group' => 'O+', 'previous_experience' => [['company_name' => 'Previous Employer', 'position' => 'Electrician', 'duration' => '5 years', 'responsibilities' => 'Electrical installation and maintenance.']], 'employment_type' => 'rental', 'salary_type' => 'hourly', 'hourly_rate_cents' => 1234, 'monthly_salary_cents' => 0, 'overtime_multiplier_units' => 150, 'joined_on' => now()->subYear()->format('Y-m-d'), 'status' => 'active', 'created_by' => $this->admin->id], $overrides));
    }
    private function entry(Employee $employee, $overrides = [])
    {
        return Timesheet::create(array_merge(['employee_id' => $employee->id, 'work_date' => now()->format('Y-m-d'), 'regular_units' => 850, 'overtime_units' => 125, 'hourly_rate_cents' => $employee->hourly_rate_cents, 'overtime_multiplier_units' => $employee->overtime_multiplier_units, 'status' => 'approved', 'created_by' => $this->manager->id], $overrides));
    }
    private function payload($overrides = [])
    {
        return array_merge(['name' => 'New Worker', 'photo' => UploadedFile::fake()->image('photo.jpg'), 'iqama_number' => '2123456789', 'passport_number' => 'AB123456', 'phone' => '+966501234567', 'nationality' => 'Saudi Arabia', 'personal_email' => 'new.worker@test.local', 'professional_summary' => 'Experienced and reliable worker.', 'education' => 'Technical diploma.', 'skills' => 'Safety, teamwork, communication', 'company_id' => $this->company->id, 'designation_id' => $this->designation->id, 'blood_group' => 'A+', 'previous_experience' => [['company_name' => 'Previous Employer', 'position' => 'Worker', 'duration' => '2 years', 'responsibilities' => 'Site operations and reporting.']], 'hourly_rate' => '20.00', 'regular_hours' => '8.00', 'overtime_multiplier' => '1.50', 'joined_on' => now()->subMonth()->format('Y-m-d'), 'status' => 'active'], $overrides);
    }
    private function generate(Employee $employee, $prefix = 'payrolls', $overrides = [])
    {
        return $this->actingAs($this->admin)->post(route($prefix.'.store'), array_merge(['employee_id' => $employee->id, 'month' => now()->format('Y-m'), 'allowance' => '1.00', 'deduction' => '0.50'], $overrides));
    }
    public function test_login_and_disabled_accounts()
    {
        $this->get(route('login'))->assertOk();
        $this->post(route('login.submit'), ['username' => $this->manager->username, 'password' => 'wrong'])->assertSessionHasErrors('username');
        $this->post(route('login.submit'), ['username' => $this->manager->username, 'password' => 'A-secure-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->manager);
        $this->manager->update(['is_active' => false]);
        $this->actingAs($this->manager->fresh());
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_every_successful_transaction_has_an_activity_trail()
    {
        $this->actingAs($this->manager)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->manager->id,
            'action' => 'Logout',
            'subject' => 'POST logout',
        ]);
    }
    public function test_create_update_and_delete_are_attributed_to_the_acting_user()
    {
        $this->actingAs($this->admin)->post(route('companies.store'), ['name' => 'Audit Trail Company', 'location' => 'Riyadh, Saudi Arabia'])->assertSessionHasNoErrors();
        $company = Company::where('name', 'Audit Trail Company')->firstOrFail();

        $this->put(route('companies.update', $company), ['name' => 'Audited Company', 'location' => 'Riyadh, Saudi Arabia', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->delete(route('companies.destroy', $company))->assertSessionHasNoErrors();

        foreach (['Created companies', 'Updated companies', 'Deleted companies'] as $action) {
            $this->assertDatabaseHas('activity_logs', [
                'user_id' => $this->admin->id,
                'action' => $action,
                'subject' => $action === 'Created companies' ? 'Audit Trail Company' : 'Audited Company',
            ]);
        }
    }
    public function test_company_master_requires_location_and_stores_optional_details()
    {
        $this->actingAs($this->admin)->post(route('companies.store'), ['name' => 'Missing Location'])->assertSessionHasErrors('location');
        $this->post(route('companies.store'), [
            'name' => 'Complete Company',
            'location' => 'Dammam, Saudi Arabia',
            'registration_number' => 'CR-100200',
            'contact_person' => 'Operations Manager',
            'phone' => '+966500000000',
            'email' => 'operations@example.test',
            'address' => 'Industrial Area, Dammam',
            'notes' => 'Preferred workforce customer.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('companies', [
            'name' => 'Complete Company',
            'location' => 'Dammam, Saudi Arabia',
            'registration_number' => 'CR-100200',
            'email' => 'operations@example.test',
        ]);
    }
    public function test_filtered_timesheet_report_downloads_as_pdf()
    {
        $employee = $this->employee();
        $this->entry($employee, ['status' => 'pending']);

        $response = $this->actingAs($this->manager)->get(route('timesheets.pdf', [
            'month' => now()->format('Y-m'),
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $pdf = new Fpdi;
        $this->assertSame(1, $pdf->setSourceFile(StreamReader::createByString($response->getContent())));
    }
    public function test_admin_can_bulk_approve_pending_timesheets()
    {
        $first = $this->entry($this->employee(), ['status' => 'pending']);
        $second = $this->entry($this->employee(), ['status' => 'pending']);

        $this->actingAs($this->admin)->post(route('timesheets.bulk.approve'), [
            'timesheet_ids' => [$first->id, $second->id],
        ])->assertSessionHasNoErrors()->assertRedirect();

        foreach ([$first, $second] as $entry) {
            $this->assertDatabaseHas('timesheets', [
                'id' => $entry->id,
                'status' => 'approved',
                'reviewed_by' => $this->admin->id,
            ]);
        }
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->admin->id,
            'action' => 'Bulk approved hours',
        ]);
    }
    public function test_timesheet_landing_lists_employees_and_name_opens_details()
    {
        $employee = $this->employee(['name' => 'Directory Worker']);
        $entry = $this->entry($employee, ['status' => 'pending']);

        $this->actingAs($this->manager)->get(route('timesheets.index', ['month' => $entry->work_date->format('Y-m'), 'status' => 'pending']))
            ->assertOk()
            ->assertSee('Rental employee timesheets')
            ->assertSee('Directory Worker')
            ->assertSee('Rental hour rate')
            ->assertSee('SAR 12.34')
            ->assertSee('data-employee-search', false)
            ->assertSee(route('timesheets.index', ['month' => $entry->work_date->format('Y-m'), 'employee_id' => $employee->id]));

        $this->get(route('timesheets.index', ['month' => $entry->work_date->format('Y-m'), 'employee_id' => $employee->id]))
            ->assertOk()
            ->assertSee('Directory Worker')
            ->assertSee($entry->work_date->format('d M Y'));
    }
    public function test_only_super_admin_can_delete_or_reopen_records()
    {
        $employee = $this->employee(); $entry = $this->entry($employee, ['status' => 'pending']);
        $this->actingAs($this->approver)->delete(route('timesheets.destroy', $entry))->assertForbidden();
        $this->delete(route('employees.destroy', $employee))->assertForbidden();
        $this->delete(route('companies.destroy', $this->other))->assertForbidden();
        $this->post(route('timesheets.review', $entry), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->post(route('timesheets.review', $entry), ['status' => 'pending', 'review_note' => 'Correction'])->assertForbidden();
        $this->actingAs($this->admin)->post(route('timesheets.review', $entry), ['status' => 'pending', 'review_note' => 'Correction'])->assertSessionHasNoErrors();
    }
    public function test_employee_timesheet_pdf_supports_multiple_months_and_orientation()
    {
        $employee = $this->employee();
        $first = $this->entry($employee, ['work_date' => now()->subMonthNoOverflow()->startOfMonth(), 'status' => 'approved']);
        $second = $this->entry($employee, ['work_date' => now()->startOfMonth(), 'status' => 'approved']);
        $months = [$first->work_date->format('Y-m'), $second->work_date->format('Y-m')];
        $this->actingAs($this->manager)->get(route('timesheets.employee.pdf.form', $employee))->assertOk()->assertSee('Portrait')->assertSee('Landscape');
        $response = $this->post(route('timesheets.employee.pdf', $employee), ['months' => $months, 'orientation' => 'landscape']);
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $pdf = new Fpdi; $this->assertSame(2, $pdf->setSourceFile(StreamReader::createByString($response->getContent())));
        if (!is_dir(base_path('tmp/pdfs'))) { mkdir(base_path('tmp/pdfs'), 0755, true); }
        file_put_contents(base_path('tmp/pdfs/employee-timesheet-qa.pdf'), $response->getContent());
    }
    public function test_bulk_hours_save_exact_total_and_protect_company_access_and_existing_dates()
    {
        $employee = $this->employee();
        $first = now()->subDays(3)->format('Y-m-d');
        $second = now()->subDays(2)->format('Y-m-d');
        $payload = ['employee_id' => $employee->id, 'total_hours' => '15.25', 'entries' => [
            ['work_date' => $first, 'regular_hours' => '10', 'overtime_hours' => '0'],
            ['work_date' => $second, 'regular_hours' => '5', 'overtime_hours' => '0.25'],
        ]];
        $this->actingAs($this->manager)->get(route('timesheets.bulk'))->assertOk()->assertSee('Total-hours target');
        $this->post(route('timesheets.bulk.store'), array_merge($payload, ['total_hours' => 20]))->assertSessionHasErrors('total_hours');
        $this->assertEquals(0, Timesheet::count());
        $this->post(route('timesheets.bulk.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals(2, Timesheet::count());
        $this->assertDatabaseHas('timesheets', ['employee_id' => $employee->id, 'work_date' => $second, 'regular_units' => 500, 'overtime_units' => 25, 'status' => 'pending', 'hourly_rate_cents' => $employee->hourly_rate_cents]);
        // A new date preceding a duplicate must roll back with the entire batch.
        $payload['entries'][0]['work_date'] = now()->subDays(4)->format('Y-m-d');
        $this->post(route('timesheets.bulk.store'), $payload)->assertSessionHasErrors('work_date');
        $this->assertEquals(2, Timesheet::count());
        $payload['employee_id'] = $this->employee(['company_id' => $this->other->id])->id;
        $this->post(route('timesheets.bulk.store'), $payload)->assertForbidden();
        $payload['employee_id'] = $this->employee(['employment_type' => 'own', 'salary_type' => 'monthly'])->id;
        $this->post(route('timesheets.bulk.store'), $payload)->assertForbidden();
        $this->post(route('attendance.bulk.store'), $payload)->assertSessionHasNoErrors();
        $this->assertEquals(4, Timesheet::count());
    }
    public function test_bulk_hours_reject_duplicate_dates_future_dates_and_locked_month_atomically()
    {
        $employee = $this->employee();
        $date = now()->format('Y-m-d');
        $row = ['work_date' => $date, 'regular_hours' => 10, 'overtime_hours' => 0];
        $payload = ['employee_id' => $employee->id, 'entries' => [$row, $row]];
        $this->actingAs($this->manager)->post(route('timesheets.bulk.store'), $payload)->assertSessionHasErrors('entries.0.work_date');
        $payload['entries'] = [array_merge($row, ['work_date' => now()->addDay()->format('Y-m-d')])];
        $this->post(route('timesheets.bulk.store'), $payload)->assertSessionHasErrors('entries.0.work_date');
        $payload['entries'] = [array_merge($row, ['overtime_hours' => 20])];
        $this->post(route('timesheets.bulk.store'), $payload)->assertSessionHasErrors('entries');
        $this->entry($employee);
        $this->generate($employee)->assertSessionHasNoErrors();
        $payload['entries'] = [array_merge($row, ['work_date' => now()->subMonth()->startOfMonth()->format('Y-m-d')]), $row];
        $this->actingAs($this->manager)->post(route('timesheets.bulk.store'), $payload)->assertSessionHasErrors('work_date');
        $this->assertEquals(1, Timesheet::count());
    }
    public function test_all_primary_screens_render_for_both_roles()
    {
        foreach ([$this->admin, $this->manager] as $user) {
            $this->actingAs($user);
            foreach (['dashboard', 'employees.index', 'employees.create', 'own-employees.index', 'own-employees.create', 'timesheets.index', 'timesheets.create', 'attendance.index', 'attendance.create', 'payrolls.index', 'salaries.index', 'companies.index', 'designations.index', 'profile'] as $route) { $this->get(route($route))->assertOk(); }
        }
        $this->actingAs($this->admin);
        foreach (['users.index', 'users.create', 'audit.index'] as $route) { $this->get(route($route))->assertOk(); }
        $this->get(route('audit.csv'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->get(route('audit.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
        foreach (['employees', 'timesheets', 'salaries'] as $report) {
            $this->get(route('audit.index', ['report' => $report]))->assertOk();
            $this->get(route('audit.csv', ['report' => $report]))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
            $this->get(route('audit.pdf', ['report' => $report]))->assertOk()->assertHeader('content-type', 'application/pdf');
        }
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
        $this->actingAs($this->manager)->post(route('employees.store'), $this->payload(['previous_experience' => array_fill(0, 6, ['position' => 'Worker', 'duration' => '1 year', 'responsibilities' => 'Experience'])]))->assertSessionHasErrors('previous_experience');
        $this->post(route('employees.store'), $this->payload(['photo' => null]))->assertSessionHasErrors('photo');
        $this->post(route('employees.store'), $this->payload())->assertSessionHasNoErrors();
        $rental = Employee::where('name', 'New Worker')->firstOrFail();
        $this->assertEquals('rental', $rental->employment_type); $this->assertEquals('hourly', $rental->salary_type);
        Storage::disk('local')->assertExists($rental->photo_path);
        $this->get(route('employees.photo', $rental))->assertOk();
        $this->get(route('own-employees.show', $rental))->assertNotFound();
        $this->post(route('own-employees.store'), $this->payload(['name' => 'Own Team Member', 'iqama_number' => '3123456789', 'passport_number' => 'CD123456', 'personal_email' => 'own.worker@test.local', 'monthly_salary' => '5000.00',
            'directorate' => 'Operations', 'department' => 'General Affairs', 'meal_allowance' => '300.00', 'transportation_allowance' => '250.00', 'housing_allowance' => '400.00',
            'medical_allowance' => '200.00', 'retirement_insurance' => '75.00', 'tax' => '25.00']))->assertSessionHasNoErrors();
        $own = Employee::where('name', 'Own Team Member')->firstOrFail();
        $this->assertEquals('own', $own->employment_type); $this->assertEquals('monthly', $own->salary_type); $this->assertEquals(500000, $own->monthly_salary_cents);
        $this->assertSame('Operations', $own->directorate); $this->assertSame('General Affairs', $own->department);
        $this->assertSame(30000, $own->meal_allowance_cents); $this->assertSame(40000, $own->housing_allowance_cents); $this->assertSame(7500, $own->retirement_insurance_cents); $this->assertSame(2500, $own->tax_cents);
        $this->get(route('employees.index'))->assertDontSee($own->name);
        $this->get(route('own-employees.index'))->assertSee($own->name)->assertDontSee($rental->name);
        $this->get(route('employees.edit', $rental))->assertOk();
        $this->get(route('own-employees.show', $own))->assertOk();
        $this->get(route('own-employees.edit', $own))->assertOk();
        $this->post(route('employees.store'), $this->payload())->assertSessionHasErrors(['iqama_number', 'passport_number']);
    }
    public function test_optional_cv_fields_can_be_left_blank()
    {
        $payload = $this->payload(['nationality' => null, 'personal_email' => null, 'professional_summary' => null, 'education' => null, 'skills' => null, 'previous_experience' => []]);
        $this->actingAs($this->manager)->post(route('employees.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employees', ['name' => 'New Worker', 'personal_email' => null]);
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
        $this->post(route('payrolls.store'), ['employee_id' => $employee->id])->assertSessionHasErrors(['month', 'allowance', 'deduction']);
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
        $own = $this->employee(['name' => 'Monthly Worker', 'employment_type' => 'own', 'salary_type' => 'monthly', 'monthly_salary_cents' => 500000,
            'directorate' => 'Operations', 'department' => 'General Affairs', 'meal_allowance_cents' => 3000, 'transportation_allowance_cents' => 2000, 'housing_allowance_cents' => 4000,
            'medical_allowance_cents' => 1000, 'retirement_insurance_cents' => 1000, 'tax_cents' => 500]);
        $this->entry($own);
        $this->generate($own, 'salaries')->assertSessionHasNoErrors();
        $payroll = Payroll::firstOrFail(); $this->assertEquals(500000, $payroll->regular_pay_cents); $this->assertEquals(510864, $payroll->net_pay_cents);
        $this->assertSame('Operations', $payroll->directorate); $this->assertSame(3000, $payroll->meal_allowance_cents); $this->assertSame(4000, $payroll->housing_allowance_cents); $this->assertSame(1000, $payroll->retirement_insurance_cents);
        $this->get(route('salaries.show', $payroll))->assertOk();
        $this->get(route('payrolls.show', $payroll))->assertNotFound();
        $this->get(route('payrolls.index'))->assertDontSee('Monthly Worker');
        $this->get(route('salaries.index'))->assertSee('Monthly Worker');
        $this->post(route('salaries.approve', $payroll))->assertSessionHasNoErrors();
        $pdfResponse = $this->get(route('salaries.pdf', $payroll))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $pdf = new Fpdi; $this->assertSame(1, $pdf->setSourceFile(StreamReader::createByString($pdfResponse->getContent())));
        $template = $pdf->importPage(1); $size = $pdf->getTemplateSize($template); $this->assertSame('L', $size['orientation']);
        if (!is_dir(base_path('tmp/pdfs'))) { mkdir(base_path('tmp/pdfs'), 0755, true); }
        file_put_contents(base_path('tmp/pdfs/own-employee-payslip-qa.pdf'), $pdfResponse->getContent());
    }
    public function test_pending_can_be_voided_and_approved_salary_can_be_paid()
    {
        $employee = $this->employee(); $entry = $this->entry($employee); $this->generate($employee);
        $payroll = Payroll::firstOrFail();
        $this->delete(route('payrolls.destroy', $payroll))->assertSessionHasNoErrors();
        $this->assertNull($entry->fresh()->payroll_id);
        $this->generate($employee); $payroll = Payroll::firstOrFail();
        $this->actingAs($this->approver)->post(route('payrolls.approve', $payroll))->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('payrolls.paid', $payroll))->assertSessionHasNoErrors();
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
        $data = ['name' => 'New Manager', 'username' => 'new_manager', 'email' => 'new@test.local', 'password' => 'Long-password-123', 'password_confirmation' => 'Long-password-123', 'role' => 'manager', 'is_active' => 1, 'companies' => [$this->company->id, $this->other->id]];
        $this->actingAs($this->admin)->post(route('users.store'), $data)->assertSessionHasNoErrors();
        $user = User::where('username', 'new_manager')->firstOrFail(); $this->assertEquals(2, $user->companies()->count());
        $this->get(route('users.edit', $user))->assertOk();
        $this->put(route('users.update', $this->admin), ['name' => 'Admin', 'username' => $this->admin->username, 'email' => $this->admin->email, 'role' => 'manager', 'is_active' => 0])->assertSessionHasErrors('role');
    }
    public function test_lookup_management_preserves_employee_references()
    {
        $this->employee();
        $this->actingAs($this->admin)->delete(route('companies.destroy', $this->company))->assertSessionHasErrors('name');
        $this->put(route('companies.update', $this->company), ['name' => 'Updated Company', 'location' => 'Riyadh, Saudi Arabia', 'is_active' => 0])->assertSessionHasNoErrors();
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
        $this->post(route('payrolls.approve', $payroll))->assertSessionHasNoErrors();
        $payroll->refresh(); $this->assertSame('approved', $payroll->status); $this->assertSame($this->admin->id, $payroll->approved_by);
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
        $this->actingAs($this->admin)->delete(route('employees.destroy', $employee))->assertSessionHasNoErrors();
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
    public function test_long_cv_paginates_and_payslip_stays_one_page_without_timesheets()
    {
        $text = str_repeat('Worked on electrical installation, site maintenance, safety inspections, and daily project reporting. ', 19);
        $employee = $this->employee(['name' => 'Long Experience Demo', 'previous_experience' => array_fill(0, 5, ['position' => 'Senior Technician', 'duration' => '5 years', 'responsibilities' => $text])]);
        $bytes = Documents::cv($employee); $parser = new Fpdi;
        $this->assertGreaterThan(1, $parser->setSourceFile(StreamReader::createByString($bytes)));
        $this->assertLessThanOrEqual(8, $parser->setSourceFile(StreamReader::createByString($bytes)));
        file_put_contents(base_path('tmp/pdfs/long-cv-qa.pdf'), $bytes);
        $start = now()->subMonthNoOverflow()->startOfMonth();
        for ($date = $start->copy(); $date->month === $start->month; $date->addDay()) { $this->entry($employee, ['work_date' => $date->format('Y-m-d')]); }
        $this->generate($employee, 'payrolls', ['month' => $start->format('Y-m')])->assertSessionHasNoErrors();
        $payroll = Payroll::firstOrFail(); $bytes = Documents::payslip($payroll);
        $this->assertSame(1, $parser->setSourceFile(StreamReader::createByString($bytes)));
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
    public function test_company_and_designation_directories_use_separate_add_pages()
    {
        $this->actingAs($this->admin)->get(route('companies.index'))
            ->assertOk()
            ->assertSee(route('companies.create'), false)
            ->assertSee('+ Add company')
            ->assertDontSee('name="registration_number"', false);
        $this->get(route('companies.create'))
            ->assertOk()
            ->assertSee('New company')
            ->assertSee('name="registration_number"', false);

        $this->actingAs($this->manager)->get(route('designations.index'))
            ->assertOk()
            ->assertSee(route('designations.create'), false)
            ->assertSee('+ Add designation');
        $this->get(route('designations.create'))
            ->assertOk()
            ->assertSee('New designation');
    }
    public function test_authenticated_layout_has_collapsible_navigation()
    {
        $this->actingAs($this->manager)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-menu-toggle', false)
            ->assertSee('aria-controls="sidebar"', false)
            ->assertSee(asset('css/navigation.css'), false);
    }
    public function test_timesheet_screens_do_not_show_pay_rates()
    {
        $employee = $this->employee();
        $this->entry($employee, ['status' => 'pending']);

        $this->actingAs($this->manager)->get(route('timesheets.index'))
            ->assertOk()
            ->assertDontSee('Regular rate')
            ->assertDontSee('Rate snapshot');
        $this->get(route('timesheets.index', ['employee_id' => $employee->id, 'month' => now()->format('Y-m')]))
            ->assertOk()
            ->assertDontSee('Rate snapshot')
            ->assertDontSee('Estimated pay');
    }
    public function test_all_filtered_pages_have_a_clear_action()
    {
        $this->actingAs($this->admin);
        foreach (['dashboard', 'employees.index', 'own-employees.index', 'timesheets.index', 'attendance.index', 'payrolls.index', 'salaries.index', 'audit.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Clear filter');
        }
        $this->get(route('timesheets.index'))->assertSee('data-clear-employee-search', false);
    }
    public function test_rental_payroll_can_be_searched_by_employee_details()
    {
        $first = $this->employee(['name' => 'Searchable Payroll Worker', 'iqama_number' => '2999999991']);
        $second = $this->employee(['name' => 'Different Payroll Worker', 'iqama_number' => '2999999992']);
        $this->entry($first); $this->entry($second);
        $this->generate($first)->assertSessionHasNoErrors();
        $this->generate($second)->assertSessionHasNoErrors();
        $secondPayroll = Payroll::where('employee_id', $second->id)->firstOrFail();

        $this->actingAs($this->manager)->get(route('payrolls.index', ['month' => now()->format('Y-m'), 'search' => '2999999991']))
            ->assertOk()
            ->assertSee('Search payroll')
            ->assertSee('Searchable Payroll Worker')
            ->assertSee('Rental hour rate')
            ->assertSee('Regular hours')
            ->assertSee('Overtime hours')
            ->assertSee('8.50')
            ->assertSee('1.25')
            ->assertDontSee(route('payrolls.show', $secondPayroll), false);
    }
    public function test_rental_employee_directory_shows_rate_without_rental_hours_column()
    {
        $employee = $this->employee(['name' => 'Hours Directory Worker']);
        $this->entry($employee, ['regular_units' => 800, 'overtime_units' => 150, 'status' => 'approved']);

        $this->actingAs($this->manager)->get(route('employees.index', ['search' => 'Hours Directory Worker']))
            ->assertOk()
            ->assertSee('Rental hour rate')
            ->assertSee('SAR 12.34')
            ->assertDontSee('Rental hours')
            ->assertDontSee('9.50 h');

        $this->get(route('own-employees.index'))->assertOk()->assertDontSee('Rental hours');
    }
    public function test_employee_regular_hours_are_saved_and_available_to_timesheet_form()
    {
        $this->actingAs($this->manager)->post(route('employees.store'), $this->payload([
            'name' => 'Seven Hour Worker',
            'iqama_number' => '2888888881',
            'passport_number' => 'SEVEN001',
            'personal_email' => 'seven.hours@test.local',
            'regular_hours' => '7.50',
        ]))->assertSessionHasNoErrors();

        $employee = Employee::where('iqama_number', '2888888881')->firstOrFail();
        $this->assertSame(750, $employee->regular_hours_units);
        $this->get(route('timesheets.create', ['employee_id' => $employee->id]))
            ->assertOk()
            ->assertSee('data-regular-hours="7.50"', false);
    }
}
