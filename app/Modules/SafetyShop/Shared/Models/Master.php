<?php
namespace App\Modules\SafetyShop\Shared\Models;
use Illuminate\Database\Eloquent\Model;
use App\Modules\SafetyShop\Stock\Models\Stock;
class Master extends Model
{
    protected $table = 'safety_shop_masters';
    protected $fillable = ['type','name','phone','email','address','is_active'];
    protected $casts = ['is_active'=>'boolean'];
}
