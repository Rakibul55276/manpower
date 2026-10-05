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
    protected $casts = ['total_cents'=>'integer','paid_cents'=>'integer'];
    public function lines() { return $this->hasMany(SaleLine::class); }
    public function location() { return $this->belongsTo(Master::class,'location_id'); }
    public function creator() { return $this->belongsTo(\App\Models\User::class,'created_by'); }
}
