<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasVoucherRedemption extends Model
{
    public function getConnectionName() { return \App\Services\TenantDatabase::active() ? 'central' : parent::getConnectionName(); }
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['previous_expiry'=>'date', 'new_expiry'=>'date', 'redeemed_at'=>'datetime'];
    public function voucher() { return $this->belongsTo(SaasVoucher::class, 'voucher_id'); }
    public function company() { return $this->belongsTo(Company::class); }
}
