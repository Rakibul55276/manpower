<?php
namespace App\Modules\Invoicing\Models;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model {
    protected $guarded = ['id']; protected $casts = ['issue_date'=>'date','supply_date'=>'date','due_date'=>'date','approved_at'=>'datetime','paid_at'=>'datetime'];
    public function customer(){ return $this->belongsTo(InvoiceCustomer::class,'customer_id'); }
    public function lines(){ return $this->hasMany(InvoiceLine::class); }
    public function events(){ return $this->hasMany(InvoiceEvent::class)->latest(); }
    public function creator(){ return $this->belongsTo(\App\Models\User::class,'created_by'); }
    public function approver(){ return $this->belongsTo(\App\Models\User::class,'approved_by'); }
    public function reference(){ return $this->belongsTo(self::class,'reference_invoice_id'); }
}
