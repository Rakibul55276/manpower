<?php
namespace App\Modules\SafetyShop\Stock\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
class Stock extends Model
{
    protected $table = 'safety_shop_stocks';
    protected $fillable = ['product_id','location_id','quantity'];
    protected $casts = ['quantity'=>'integer'];
    public function product() { return $this->belongsTo(Product::class); }
    public function location() { return $this->belongsTo(Master::class, 'location_id'); }
}
