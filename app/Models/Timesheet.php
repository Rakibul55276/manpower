<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Services\Pay;
class Timesheet extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['work_date' => 'date', 'reviewed_at' => 'datetime'];
    public function setWorkDateAttribute($value) { $this->attributes['work_date'] = \Carbon\Carbon::parse($value)->format('Y-m-d'); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function payroll() { return $this->belongsTo(Payroll::class); }
    public function regularPay() { return Pay::rounded($this->regular_units * $this->hourly_rate_cents, 100); }
    public function overtimePay() { return $this->overtime_rate_cents ? Pay::rounded($this->overtime_units * $this->overtime_rate_cents, 100) : Pay::rounded($this->overtime_units * $this->hourly_rate_cents * $this->overtime_multiplier_units, 10000); }
}
