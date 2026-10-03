<?php
namespace App\Http\Controllers;
use App\Models\Employee;
use App\Models\Designation;
use App\Models\ActivityLog;
use App\Services\Access;
use App\Services\Pay;
use App\Services\Documents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class EmployeeController extends Controller
{
    private function workforce() { return request()->route('workforce') ?? 'rental'; }
    private function routePrefix() { return $this->workforce() === 'own' ? 'own-employees' : 'employees'; }
    private function check(Employee $employee) { Access::employee($employee); abort_unless($employee->employment_type === $this->workforce(), 404); }
    public function index(Request $request)
    {
        $filters = $request->validate(['search' => 'nullable|string|max:100', 'company_id' => 'nullable|integer', 'status' => 'nullable|in:active,inactive']);
        $workforce = $this->workforce(); $routePrefix = $this->routePrefix();
        $query = Access::employees()->where('employment_type', $workforce)->with(['company', 'designation']);
        if ($request->filled('search')) { $query->where(function ($q) use ($request) { foreach (['name', 'iqama_number', 'passport_number', 'phone'] as $field) { $q->orWhere($field, 'like', '%'.$request->search.'%'); } }); }
        if ($request->filled('company_id')) { $query->where('company_id', $request->company_id); }
        if ($request->filled('status')) { $query->where('status', $request->status); }
        $employees = $query->latest()->paginate(12)->withQueryString();
        $companies = Access::companies()->orderBy('name')->get();
        return view('employees.index', compact('employees', 'companies', 'workforce', 'routePrefix'));
    }
    public function create() { return $this->form(new Employee(['status' => 'active', 'salary_type' => $this->workforce() === 'own' ? 'monthly' : 'hourly', 'overtime_multiplier_units' => 150, 'joined_on' => now()])); }
    public function edit(Employee $employee) { $this->check($employee); return $this->form($employee); }
    private function form($employee)
    {
        $companies = Access::companies()->where(function ($q) use ($employee) { $q->where('is_active', true)->orWhere('companies.id', $employee->company_id ?? 0); })->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->orWhere('id', $employee->designation_id ?? 0)->orderBy('name')->get();
        $workforce = $this->workforce(); $routePrefix = $this->routePrefix();
        return view('employees.form', compact('employee', 'companies', 'designations', 'workforce', 'routePrefix'));
    }
    public function store(Request $request) { return $this->save($request, new Employee); }
    public function update(Request $request, Employee $employee) { $this->check($employee); return $this->save($request, $employee); }
    private function save(Request $request, Employee $employee)
    {
        $request->merge(['salary_type' => $this->workforce() === 'own' ? 'monthly' : 'hourly']);
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'photo' => ($employee->exists ? 'nullable' : 'required').'|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=5000,max_height=5000',
            'document' => 'nullable|file|mimes:pdf|mimetypes:application/pdf|max:10240',
            'remove_document' => 'nullable|boolean',
            'iqama_number' => ['required', 'regex:/^[0-9]{10}$/', Rule::unique('employees')->ignore($employee->id)],
            'passport_number' => ['required', 'regex:/^[A-Za-z0-9-]{5,30}$/', Rule::unique('employees')->ignore($employee->id)],
            'phone' => ['required', 'regex:/^\+?[0-9 ()-]{7,25}$/'],
            'company_id' => 'required|exists:companies,id', 'designation_id' => 'required|exists:designations,id',
            'previous_experience' => 'nullable|array|max:5', 'previous_experience.*' => 'nullable|string|max:2000',
            'blood_group' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'salary_type' => 'required|in:hourly,monthly',
            'hourly_rate' => ['required', 'numeric', 'min:0.01', 'max:99999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'monthly_salary' => ['required_if:salary_type,monthly', 'nullable', 'numeric', 'min:0.01', 'max:999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
            'overtime_multiplier' => ['required', 'numeric', 'min:1', 'max:5', 'regex:/^\d+(\.\d{1,2})?$/'],
            'joined_on' => 'required|date_format:Y-m-d|before_or_equal:today', 'status' => 'required|in:active,inactive',
        ]);
        Access::company($data['company_id']);
        if ($request->hasFile('document')) {
            try { Documents::validateAttachment($request->file('document')->getRealPath()); }
            catch (\Throwable $e) { return back()->withErrors(['document' => 'Upload an unencrypted PDF with 1 to 30 pages. If this PDF uses unsupported compression, print it to PDF and upload the new copy.'])->withInput($request->except('photo', 'document')); }
        }
        $company = \App\Models\Company::findOrFail($data['company_id']);
        $designation = Designation::findOrFail($data['designation_id']);
        if ((!$company->is_active && $employee->company_id != $company->id) || (!$designation->is_active && $employee->designation_id != $designation->id)) { return back()->withErrors(['company_id' => 'Choose an active company and designation.'])->withInput(); }
        $data['hourly_rate_cents'] = Pay::units($data['hourly_rate']);
        $data['employment_type'] = $this->workforce();
        $data['monthly_salary_cents'] = $data['salary_type'] === 'monthly' ? Pay::units($data['monthly_salary']) : 0;
        $data['overtime_multiplier_units'] = Pay::units($data['overtime_multiplier']);
        $data['previous_experience'] = array_values(array_filter($data['previous_experience'] ?? [], function ($item) { return trim((string) $item) !== ''; }));
        unset($data['photo'], $data['document'], $data['remove_document'], $data['hourly_rate'], $data['monthly_salary'], $data['overtime_multiplier']);
        $oldPhoto = $employee->photo_path;
        $oldDocument = $employee->document_path;
        $newDocument = $request->hasFile('document') ? $request->file('document')->store('employee-documents', 'local') : null;
        if ($newDocument) { $data['document_path'] = $newDocument; }
        elseif ($request->boolean('remove_document')) { $data['document_path'] = null; }
        $newPhoto = $request->hasFile('photo') ? $request->file('photo')->store('employee-photos', 'local') : null;
        if ($newPhoto) { $data['photo_path'] = $newPhoto; }
        if (!$employee->exists) { $data['created_by'] = auth()->id(); }
        $creating = !$employee->exists;
        try {
            DB::transaction(function () use ($employee, $data, $creating) {
                $employee->fill($data)->save();
                ActivityLog::record($creating ? 'Added employee' : 'Updated employee', 'Employee #'.$employee->id.' · '.$employee->name);
            });
        } catch (\Throwable $e) { if ($newPhoto) { Storage::disk('local')->delete($newPhoto); } if ($newDocument) { Storage::disk('local')->delete($newDocument); } throw $e; }
        if ($newPhoto && $oldPhoto) { Storage::disk('local')->delete($oldPhoto); }
        if ($oldDocument && ($newDocument || $request->boolean('remove_document'))) { Storage::disk('local')->delete($oldDocument); }
        return redirect()->route($this->routePrefix().'.show', $employee)->with('success', 'Employee saved.');
    }
    public function show(Employee $employee)
    {
        $this->check($employee); $employee->load(['company', 'designation']);
        $timesheets = $employee->timesheets()->latest('work_date')->limit(10)->get();
        $salaryQuery = $employee->payrolls()->latest('month');
        if (!auth()->user()->isAdmin()) { $salaryQuery->whereIn('company_id', auth()->user()->companies()->select('companies.id')); }
        $payrolls = $salaryQuery->limit(6)->get();
        $workforce = $this->workforce(); $routePrefix = $this->routePrefix();
        return view('employees.show', compact('employee', 'timesheets', 'payrolls', 'workforce', 'routePrefix'));
    }
    public function photo(Employee $employee)
    {
        Access::employee($employee);
        abort_unless(Storage::disk('local')->exists($employee->photo_path), 404);
        return response()->file(Storage::disk('local')->path($employee->photo_path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
    public function destroy(Employee $employee)
    {
        $this->check($employee);
        $employee->update(['status' => 'inactive']);
        ActivityLog::record('Archived employee', 'Employee #'.$employee->id.' · '.$employee->name);
        return back()->with('success', 'Employee archived. Historical timesheets and payroll remain available.');
    }
    public function cv(Employee $employee)
    {
        $this->check($employee);
        try { $bytes = Documents::cv($employee); }
        catch (\Throwable $e) { report($e); return back()->withErrors(['document' => 'CV generation failed. Check or re-upload the supporting PDF, then try again.']); }
        return Documents::download($bytes, 'employee-'.$employee->id.'-cv.pdf');
    }
}
