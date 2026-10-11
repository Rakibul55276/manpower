<?php
namespace App\Modules\SafetyShop\Shared\Models;
use Illuminate\Database\Eloquent\Model;
class ReceiptSetting extends Model
{
    protected $table='safety_shop_receipt_settings';
    protected $guarded=['id'];
    public function scopeForCompany($query, $companyId) { return $query->where('company_id', $companyId); }
}
