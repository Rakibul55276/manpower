<?php
namespace App\Modules\Invoicing\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceSetting extends Model { protected $guarded = ['id']; protected $casts = ['demo_mode'=>'boolean','show_bank_details'=>'boolean','show_signatures'=>'boolean','show_qr'=>'boolean','show_company_cr'=>'boolean','show_seller_details'=>'boolean','show_customer_details'=>'boolean','show_references'=>'boolean','show_amount_words'=>'boolean','show_notes'=>'boolean','show_footer_uuid'=>'boolean']; }
