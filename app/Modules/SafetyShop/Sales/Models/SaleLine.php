<?php
namespace App\Modules\SafetyShop\Sales\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Sales\Models\Sale;
class SaleLine extends Model
{
    protected $table = 'safety_shop_sale_lines';
    protected $guarded = ['id'];
    protected $casts = ['quantity'=>'integer','cost_cents'=>'integer','price_cents'=>'integer','total_cents'=>'integer'];
    public function sale() { return $this->belongsTo(Sale::class); }
    public function returnLines() { return $this->hasMany(SaleReturnLine::class); }
}
