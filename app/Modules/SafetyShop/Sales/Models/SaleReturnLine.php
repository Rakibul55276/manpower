<?php
namespace App\Modules\SafetyShop\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturnLine extends Model
{
    protected $table = 'safety_shop_return_lines';
    protected $guarded = ['id'];
    protected $casts = ['quantity'=>'integer','cost_cents'=>'integer','refund_cents'=>'integer'];

    public function saleReturn() { return $this->belongsTo(SaleReturn::class, 'return_id'); }
    public function saleLine() { return $this->belongsTo(SaleLine::class); }
    public function product() { return $this->belongsTo(\App\Modules\SafetyShop\Products\Models\Product::class); }
}
