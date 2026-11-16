<?php
namespace App\Modules\SafetyShop\Shared\Models;
use Illuminate\Database\Eloquent\Model;
class ReceiptSetting extends Model
{
    public const DEFAULT_QUOTATION_TERMS = "Prices are valid for 30 days.\nDelivery is subject to stock availability.\nPayment is due within 30 days.";

    protected $table='safety_shop_receipt_settings';
    protected $guarded=['id'];
    public function scopeForCompany($query, $companyId) { return $query->where('company_id', $companyId); }
    public static function defaultQuotationTerms(): string { return self::DEFAULT_QUOTATION_TERMS; }
    public function effectiveQuotationTerms(): string { return filled($this->quotation_terms) ? trim((string)$this->quotation_terms) : self::DEFAULT_QUOTATION_TERMS; }
}
