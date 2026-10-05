<?php
namespace App\Modules\Invoicing\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceItem extends Model { protected $guarded = ['id']; protected $casts = ['is_active'=>'boolean']; }
