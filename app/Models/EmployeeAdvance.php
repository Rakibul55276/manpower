<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class EmployeeAdvance extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['advance_date' => 'date'];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function repayments() { return $this->hasMany(EmployeeAdvanceRepayment::class); }
    public function getRepaidCentsAttribute() { return (int) $this->repayments->sum('amount_cents'); }
    public function getBalanceCentsAttribute() { return max(0, (int) $this->amount_cents - $this->repaid_cents); }
}
