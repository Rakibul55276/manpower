<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItemMaster extends Model
{
    protected $fillable = ['company_id', 'name', 'sku', 'unit', 'unit_price_cents', 'tax_rate_units', 'is_active'];
    protected $casts = ['unit_price_cents'=>'integer', 'tax_rate_units'=>'integer', 'is_active'=>'boolean'];
    public function company() { return $this->belongsTo(Company::class); }
}
