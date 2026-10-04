<?php
namespace App\Console\Commands;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\Payroll;
use App\Models\User;
use App\Services\Pay;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class DemoManpower extends Command
{
    protected $signature = 'manpower:demo';
    protected $description = 'Add 100 sample employees with hours and salary history (idempotent)';
    public function handle()
    {
        $admin = User::where('role', 'super_admin')->firstOrFail();
        $manager = User::where('username', 'manager')->firstOrFail();
        $companyLocations = ['Gulf Construction' => 'Dammam, Saudi Arabia', 'Riyadh Facilities' => 'Riyadh, Saudi Arabia', 'Eastern Engineering' => 'Al Khobar, Saudi Arabia', 'Jeddah Logistics' => 'Jeddah, Saudi Arabia', 'Desert Industrial' => 'Jubail, Saudi Arabia'];
        $companies = collect($companyLocations)->map(function ($location, $name) { return Company::firstOrCreate(['name' => $name], ['location' => $location]); })->values();
        $designations = collect(['General Worker', 'Electrician', 'Plumber', 'Welder', 'Driver', 'Supervisor', 'Accountant'])->map(function ($name) { return Designation::firstOrCreate(['name' => $name]); });
        $manager->companies()->syncWithoutDetaching($companies->take(3)->pluck('id')->all());
        $firstNames = ['Ahmed', 'Mohammed', 'Abdul', 'Omar', 'Hassan', 'Imran', 'Rakib', 'Karim', 'Yusuf', 'Ali', 'Bilal', 'Naeem', 'Rafiq', 'Sajid', 'Faisal', 'Salman', 'Ibrahim', 'Khalid', 'Farhan', 'Tariq'];
        $lastNames = ['Khan', 'Rahman', 'Hossain', 'Ahmed', 'Islam'];
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $month = now()->subMonthNoOverflow()->format('Y-m');
        $start = now()->subMonthNoOverflow()->startOfMonth(); $end = $start->copy()->endOfMonth();
        $created = 0;
        DB::transaction(function () use ($admin, $manager, $companies, $designations, $firstNames, $lastNames, $bloodGroups, $month, $start, $end, &$created) {
            for ($index = 1; $index <= 100; $index++) {
                $name = $firstNames[($index - 1) % 20].' '.$lastNames[intdiv($index - 1, 20)];
                $own = $index > 80; $rate = (1500 + (($index % 7) * 350));
                $photoPath = 'employee-photos/demo-'.$index.'.png';
                if (!Storage::disk('local')->exists($photoPath)) { $this->avatar($name, $index, $photoPath); }
                $documentPath = null;
                if ($index % 10 === 0) {
                    $documentPath = 'employee-documents/demo-'.$index.'.pdf';
                    if (!Storage::disk('local')->exists($documentPath)) {
                        $pdf = new \FPDF; $pdf->AddPage(); $pdf->SetFont('Arial', 'B', 22); $pdf->SetTextColor(16, 45, 50); $pdf->Cell(0, 25, 'DEMO - Supporting Certificate', 0, 1);
                        $pdf->SetFont('Arial', '', 12); $pdf->MultiCell(0, 8, "Sample document for ".$name.".\n\nThis is fictional demonstration content for testing the CV document merge. It is not a real qualification or certificate.");
                        Storage::disk('local')->put($documentPath, $pdf->Output('S'));
                    }
                }
                $employee = Employee::firstOrCreate(['iqama_number' => (string) (2900000000 + $index)], [
                    'name' => ''.$name, 'photo_path' => $photoPath, 'document_path' => $documentPath, 'passport_number' => 'DEMO'.str_pad($index, 6, '0', STR_PAD_LEFT),
                    'phone' => '+966500'.str_pad($index, 6, '0', STR_PAD_LEFT), 'company_id' => $companies[($index - 1) % 5]->id, 'designation_id' => $designations[($index - 1) % 7]->id,
                    'blood_group' => $bloodGroups[($index - 1) % 8], 'employment_type' => $own ? 'own' : 'rental', 'salary_type' => $own ? 'monthly' : 'hourly', 'hourly_rate_cents' => $rate,
                    'monthly_salary_cents' => $own ? 350000 + (($index % 8) * 50000) : 0, 'overtime_rate_cents' => (int) round($rate * 1.5), 'overtime_multiplier_units' => 100,
                    'nationality' => 'Saudi Arabia', 'personal_email' => 'employee'.$index.'@example.test',
                    'professional_summary' => 'Reliable '.$designations[($index - 1) % 7]->name.' experienced in safe site operations, teamwork, and daily reporting.',
                    'education' => 'Technical training and workplace safety certification.', 'skills' => 'Safety awareness, teamwork, communication, daily reporting',
                    'previous_experience' => [['company_name' => 'Previous Employer', 'location' => 'Riyadh, Saudi Arabia', 'position' => $designations[($index - 1) % 7]->name, 'start_date' => now()->subYears(2 + ($index % 8))->format('Y-m-d'), 'end_date' => now()->subYear()->format('Y-m-d'), 'responsibilities' => 'Commercial construction and facility maintenance, safety checks, teamwork, and daily reporting.']],
                    'joined_on' => now()->subYear()->startOfYear()->addDays($index)->format('Y-m-d'), 'status' => $index > 95 ? 'inactive' : 'active', 'created_by' => $admin->id,
                ]);
                if ($employee->wasRecentlyCreated) { $created++; }
                $creatorId = $manager->canAccessCompany($employee->company_id) ? $manager->id : $admin->id;
                if ($employee->payrolls()->where('month', $month)->exists()) { continue; }
                for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                    if (in_array($day->dayOfWeek, [5, 6])) { continue; }
                    Timesheet::firstOrCreate(['employee_id' => $employee->id, 'work_date' => $day->format('Y-m-d')], [
                        'regular_units' => 800, 'overtime_units' => ($index + $day->day) % 4 === 0 ? 200 : 0, 'hourly_rate_cents' => $rate, 'overtime_rate_cents' => (int) round($rate * 1.5), 'overtime_multiplier_units' => 100,
                        'status' => 'approved', 'notes' => 'Daily shift completed.', 'created_by' => $creatorId, 'reviewed_by' => $admin->id, 'reviewed_at' => now(),
                    ]);
                }
                $approved = $employee->timesheets()->where('work_date', 'like', $month.'%')->where('status', 'approved')->get();
                $base = $own ? $employee->monthly_salary_cents : $approved->sum(function ($entry) { return $entry->regularPay(); });
                $overtime = $approved->sum(function ($entry) { return $entry->overtimePay(); });
                $allowance = $index % 3 === 0 ? 15000 : 0; $deduction = $index % 4 === 0 ? 5000 : 0;
                $paid = $index % 10 < 7;
                $payroll = Payroll::create(['employee_id' => $employee->id, 'company_id' => $employee->company_id, 'month' => $month, 'employment_type' => $employee->employment_type, 'salary_type' => $employee->salary_type,
                    'employee_name' => $employee->name, 'company_name' => $employee->company->name, 'designation_name' => $employee->designation->name, 'iqama_number' => $employee->iqama_number,
                    'regular_units' => $approved->sum('regular_units'), 'overtime_units' => $approved->sum('overtime_units'), 'regular_pay_cents' => $base, 'overtime_pay_cents' => $overtime,
                    'allowance_cents' => $allowance, 'deduction_cents' => $deduction, 'net_pay_cents' => $base + $overtime + $allowance - $deduction,
                    'status' => $paid ? 'paid' : 'draft', 'paid_at' => $paid ? now() : null, 'paid_by' => $paid ? $admin->id : null, 'created_by' => $admin->id, 'notes' => 'Fictional salary record. No real payment has been made.']);
                Timesheet::whereIn('id', $approved->pluck('id'))->update(['payroll_id' => $payroll->id]);
            }
            // Add current-month examples once, leaving some pending for the review workflow.
            $demoEmployees = Employee::whereBetween('iqama_number', ['2900000001', '2900000100'])->where('status', 'active')->get();
            $date = now()->startOfMonth();
            foreach ($demoEmployees as $employee) {
                if ($employee->payrolls()->where('month', $date->format('Y-m'))->exists()) { continue; }
                Timesheet::firstOrCreate(['employee_id' => $employee->id, 'work_date' => $date->format('Y-m-d')], [
                    'regular_units' => 800, 'overtime_units' => $employee->id % 4 === 0 ? 100 : 0, 'hourly_rate_cents' => $employee->hourly_rate_cents, 'overtime_rate_cents' => $employee->overtime_rate_cents, 'overtime_multiplier_units' => 100,
                    'status' => 'pending', 'notes' => 'Sample current-month shift awaiting review.', 'created_by' => $manager->canAccessCompany($employee->company_id) ? $manager->id : $admin->id,
                ]);
            }
        });
        $this->info($created.' demo employees added. Demo workforce: 80 rental and 20 own employees.');
        $this->info('Salary history is available for '.$month.'. The initial Manager has three demo companies in addition to the original assignment.');
        return 0;
    }
    private function avatar($name, $index, $path)
    {
        $palette = [[227, 239, 231], [222, 234, 241], [241, 232, 214], [234, 227, 241], [222, 238, 235]];
        $image = imagecreatetruecolor(300, 340); $rgb = $palette[$index % 5];
        $background = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]); $ink = imagecolorallocate($image, 16, 65, 63);
        imagefill($image, 0, 0, $background); imagefilledellipse($image, 150, 135, 115, 115, $ink); imagefilledellipse($image, 150, 340, 245, 265, $ink);
        $white = imagecolorallocate($image, 255, 255, 255); imagestring($image, 5, 130, 129, strtoupper(substr($name, 0, 1)), $white);
        ob_start(); imagepng($image); $bytes = ob_get_clean(); imagedestroy($image); Storage::disk('local')->put($path, $bytes);
    }
}
