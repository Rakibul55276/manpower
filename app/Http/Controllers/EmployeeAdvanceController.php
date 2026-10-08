<?php
namespace App\Http\Controllers;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\ActivityLog;
use App\Services\Access;
use App\Services\Pay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class EmployeeAdvanceController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        Access::employee($employee);
        $data = $request->validate([
            'advance_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'amount' => ['required','numeric','min:0.01','max:999999.99','regex:/^\d+(\.\d{1,2})?$/'],
            'installment' => ['required','numeric','min:0.01','max:999999.99','regex:/^\d+(\.\d{1,2})?$/'],
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);
        if (Pay::units($data['installment']) > Pay::units($data['amount'])) return back()->withErrors(['installment'=>'Payroll deduction cannot exceed the advance amount.'])->withInput();
        DB::transaction(function () use ($employee, $data) {
            $advance = EmployeeAdvance::create(['employee_id'=>$employee->id,'company_id'=>$employee->company_id,'branch_id'=>$employee->branch_id,'advance_date'=>$data['advance_date'],'amount_cents'=>Pay::units($data['amount']),'installment_cents'=>Pay::units($data['installment']),'reference'=>$data['reference']??null,'notes'=>$data['notes']??null,'created_by'=>auth()->id()]);
            ActivityLog::record('Recorded employee advance', 'Advance #'.$advance->id.' · '.$employee->name);
        });
        return back()->with('success', 'Employee advance recorded. It will be deducted from payroll until fully recovered.');
    }
}
