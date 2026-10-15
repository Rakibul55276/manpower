<?php
namespace App\Modules\SafetyShop\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDebitNoteLine extends Model
{
    protected $table='safety_shop_debit_note_lines';
    protected $guarded=['id'];
    protected $casts=['quantity'=>'decimal:2','unit_price_cents'=>'integer','subtotal_cents'=>'integer','tax_cents'=>'integer','total_cents'=>'integer'];
    public function debitNote(){return $this->belongsTo(SaleDebitNote::class,'debit_note_id');}
}
