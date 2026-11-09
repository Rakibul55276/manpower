<?php
namespace App\Modules\SafetyShop\Products\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Stock\Models\Movement;
class Product extends Model
{
    protected $table = 'safety_shop_products';
    protected $fillable = ['company_id','barcode','sku','source_sku','name','category_id','brand','size','variant','unit','safety_standard','reorder_level','cost_cents','price_cents','price_includes_vat','tax_category','tax_rate_units','tax_exemption_reason_code','tax_exemption_reason','is_active','notes','image_path'];
    protected $casts = ['is_active'=>'boolean','price_includes_vat'=>'boolean','cost_cents'=>'integer','price_cents'=>'integer','tax_rate_units'=>'integer','reorder_level'=>'integer'];
    public function category() { return $this->belongsTo(Master::class, 'category_id'); }
    public function stocks() { return $this->hasMany(Stock::class); }
    public function movements() { return $this->hasMany(Movement::class); }
}
