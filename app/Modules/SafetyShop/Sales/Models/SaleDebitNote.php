<?php
namespace App\Modules\SafetyShop\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDebitNote extends Model
{
    protected $table='safety_shop_debit_notes';
    protected $guarded=['id'];
    protected $casts=['issue_date'=>'date','subtotal_cents'=>'integer','tax_rate_units'=>'integer','tax_cents'=>'integer','total_cents'=>'integer'];
    public function sale(){return $this->belongsTo(Sale::class);}
    public function location(){return $this->belongsTo(\App\Modules\SafetyShop\Shared\Models\Master::class,'location_id');}
    public function creator(){return $this->belongsTo(\App\Models\User::class,'created_by');}
    public function lines(){return $this->hasMany(SaleDebitNoteLine::class,'debit_note_id');}
    public function zatcaDocument(){return $this->hasOne(\App\Modules\Zatca\Models\ZatcaDocument::class,'source_id')->where('source_type','safety_shop_debit_note');}
    public function getDebitNoteNumberAttribute(): string{return 'DN-'.str_pad((string)$this->id,8,'0',STR_PAD_LEFT);}
}
