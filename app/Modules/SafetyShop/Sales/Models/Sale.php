<?php
namespace App\Modules\SafetyShop\Sales\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Sales\Models\SaleLine;
class Sale extends Model
{
    protected $table = 'safety_shop_sales';
    protected $guarded = ['id'];
    protected $casts = ['subtotal_cents'=>'integer','discount_cents'=>'integer','taxable_cents'=>'integer','tax_rate_units'=>'integer','tax_cents'=>'integer','total_cents'=>'integer','paid_cents'=>'integer','cash_cents'=>'integer','card_cents'=>'integer','bank_cents'=>'integer'];
    public function lines() { return $this->hasMany(SaleLine::class); }
    public function returns() { return $this->hasMany(SaleReturn::class); }
    public function customerRecord() { return $this->belongsTo(Customer::class,'customer_id'); }
    public function location() { return $this->belongsTo(Master::class,'location_id'); }
    public function creator() { return $this->belongsTo(\App\Models\User::class,'created_by'); }
}
