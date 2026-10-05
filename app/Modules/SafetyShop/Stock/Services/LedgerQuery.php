<?php
namespace App\Modules\SafetyShop\Stock\Services;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Stock\Models\Movement;
use Illuminate\Http\Request;
class LedgerQuery
{
    public function build(Request $r)
    {
        $r->validate(['type'=>'nullable|in:receipt,issue,return,adjustment,transfer','from'=>'nullable|date_format:Y-m-d','to'=>array_filter(['nullable','date_format:Y-m-d',$r->filled('from')?'after_or_equal:from':null]),'product_id'=>'nullable|integer','location_id'=>'nullable|integer']);
        $q=Movement::with(['product','location','destination','supplier','creator']);
        if ($r->filled('type')) $q->where('type',$r->type);
        if ($r->filled('from')) $q->whereDate('movement_date','>=',$r->from);
        if ($r->filled('to')) $q->whereDate('movement_date','<=',$r->to);
        if ($r->filled('product_id')) $q->where('product_id',$r->product_id);
        if ($r->filled('location_id')) $q->where(function ($q) use ($r) { $q->where('location_id',$r->location_id)->orWhere('destination_id',$r->location_id); });
        return $q;
    }
}
