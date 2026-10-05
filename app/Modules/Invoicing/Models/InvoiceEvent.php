<?php
namespace App\Modules\Invoicing\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceEvent extends Model { protected $guarded = ['id']; public function user(){ return $this->belongsTo(\App\Models\User::class); } }
