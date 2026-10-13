<?php
namespace App\Modules\SafetyShop\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $table='safety_shop_sale_payments';
    protected $guarded=['id'];
    protected $casts=['payment_date'=>'date','amount_cents'=>'integer','reversed_at'=>'datetime'];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function creator() { return $this->belongsTo(\App\Models\User::class,'created_by'); }
    public function reversedBy() { return $this->belongsTo(\App\Models\User::class,'reversed_by'); }
}
