<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['previous_experience' => 'array', 'joined_on' => 'date'];
    public function company() { return $this->belongsTo(Company::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function designation() { return $this->belongsTo(Designation::class); }
    public function timesheets() { return $this->hasMany(Timesheet::class); }
    public function payrolls() { return $this->hasMany(Payroll::class); }
    public function advances() { return $this->hasMany(EmployeeAdvance::class); }
}
