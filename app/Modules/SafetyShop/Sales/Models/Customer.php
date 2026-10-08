<?php
namespace App\Modules\SafetyShop\Sales\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model
{
    protected $table='safety_shop_customers';
    protected $guarded=['id'];
    protected $casts=['purchase_count'=>'integer','lifetime_value_cents'=>'integer','last_purchase_at'=>'datetime','approved_at'=>'datetime','is_active'=>'boolean'];
    public function sales(){return $this->hasMany(Sale::class);}
}
