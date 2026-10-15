<?php
namespace App\Modules\SafetyShop\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $table = 'safety_shop_returns';
    protected $guarded = ['id'];
    protected $casts = ['refund_cents'=>'integer','gross_refund_cents'=>'integer','discount_refund_cents'=>'integer','tax_refund_cents'=>'integer','cost_reversal_cents'=>'integer'];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function lines() { return $this->hasMany(SaleReturnLine::class, 'return_id'); }
    public function location() { return $this->belongsTo(\App\Modules\SafetyShop\Shared\Models\Master::class, 'location_id'); }
    public function creator() { return $this->belongsTo(\App\Models\User::class, 'created_by'); }
    public function zatcaDocument() { return $this->hasOne(\App\Modules\Zatca\Models\ZatcaDocument::class,'source_id')->where('source_type','safety_shop_return'); }
    public function getCreditNoteNumberAttribute(): string
    {
        return 'CN-'.str_pad((string)$this->id, 8, '0', STR_PAD_LEFT);
    }
}
