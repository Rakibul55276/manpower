<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmployeeAdvanceRepayment extends Model
{
    protected $guarded = ['id'];
    public function advance() { return $this->belongsTo(EmployeeAdvance::class, 'employee_advance_id'); }
    public function payroll() { return $this->belongsTo(Payroll::class); }
}
