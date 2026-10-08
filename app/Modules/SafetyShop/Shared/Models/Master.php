<?php
namespace App\Modules\SafetyShop\Shared\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\SafetyShop\Stock\Models\Stock;
class Master extends Model
{
    protected $table = 'safety_shop_masters';
    protected $fillable = ['company_id','type','name','sku_prefix','contact_person','phone','email','vat_number','commercial_registration','website','address','is_active'];
    protected $casts = ['is_active'=>'boolean'];
}
