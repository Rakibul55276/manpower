<?php
namespace App\Modules\SafetyShop\Stock\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
class Movement extends Model
{
    protected $table = 'safety_shop_movements';
    protected $guarded = ['id'];
    protected $casts = ['movement_date'=>'date','quantity'=>'integer','balance_after'=>'integer','destination_balance_after'=>'integer'];
    public function product() { return $this->belongsTo(Product::class); }
    public function location() { return $this->belongsTo(Master::class, 'location_id'); }
    public function destination() { return $this->belongsTo(Master::class, 'destination_id'); }
    public function supplier() { return $this->belongsTo(Master::class, 'supplier_id'); }
    public function creator() { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
}
