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
    protected $casts = ['subtotal_cents'=>'integer','discount_cents'=>'integer','taxable_cents'=>'integer','tax_rate_units'=>'integer','tax_cents'=>'integer','total_cents'=>'integer','paid_cents'=>'integer','cash_cents'=>'integer','card_cents'=>'integer','bank_cents'=>'integer','payment_received'=>'boolean','cancelled_at'=>'datetime'];
    public function lines() { return $this->hasMany(SaleLine::class); }
    public function returns() { return $this->hasMany(SaleReturn::class); }
    public function payments() { return $this->hasMany(SalePayment::class); }
    public function customerRecord() { return $this->belongsTo(Customer::class,'customer_id'); }
    public function location() { return $this->belongsTo(Master::class,'location_id'); }
    public function creator() { return $this->belongsTo(\App\Models\User::class,'created_by'); }
    public function cancelledBy() { return $this->belongsTo(\App\Models\User::class,'cancelled_by'); }
    public function replaces() { return $this->belongsTo(self::class,'replaces_sale_id'); }
    public function replacement() { return $this->hasOne(self::class,'replaces_sale_id'); }
    public function getPaymentStatusAttribute(): string
    {
        if ($this->paid_cents <= 0) return 'unpaid';
        if ($this->paid_cents < $this->total_cents) return 'partially_paid';
        return 'paid';
    }

    public function getBalanceDueCentsAttribute(): int
    {
        return max(0, $this->total_cents - $this->paid_cents);
    }
}
