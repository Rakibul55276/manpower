<?php
namespace App\Modules\Invoicing\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceLine extends Model { protected $guarded = ['id']; public function invoice(){ return $this->belongsTo(Invoice::class); } public function item(){ return $this->belongsTo(InvoiceItem::class,'item_id'); } }
