<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payroll extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['paid_at' => 'datetime', 'approved_at' => 'datetime'];
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function timesheets() { return $this->hasMany(Timesheet::class); }
    public function advanceRepayments() { return $this->hasMany(EmployeeAdvanceRepayment::class); }
}
