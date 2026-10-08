<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasVoucher extends Model
{
    protected $guarded = [];
    protected $casts = ['valid_until'=>'date', 'redeemed_at'=>'datetime'];
    public function assignedCompany() { return $this->belongsTo(Company::class, 'assigned_company_id'); }
    public function redeemedCompany() { return $this->belongsTo(Company::class, 'redeemed_company_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
