<?php
namespace App\Modules\Invoicing\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceCustomer extends Model { protected $guarded = ['id']; protected $casts = ['is_active'=>'boolean']; public function invoices(){ return $this->hasMany(Invoice::class,'customer_id'); } }
