<?php
namespace App\Modules\SafetyShop\Sales\Services;
use App\Models\ActivityLog;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Stock\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class SaleService
{
    public function post(array $data, $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = collect($data['lines'])->keyBy('product_id');
            $products = Product::whereIn('id',$items->keys())->orderBy('id')->lockForUpdate()->get();
            if (Sale::where('request_key',$data['request_key'])->exists()) throw ValidationException::withMessages(['request_key'=>'This sale was already posted. Start a new checkout.']);
            if ($products->count() !== $items->count()) throw ValidationException::withMessages(['lines'=>'A product no longer exists.']);
            $total = 0; $lines = [];
            foreach ($products as $product) {
                if (!$product->is_active) throw ValidationException::withMessages(['lines'=>'Product '.$product->sku.' is inactive.']);
                if ((int)$items[$product->id]['price_cents'] !== $product->price_cents) throw ValidationException::withMessages(['lines'=>'A product price changed. Rescan the cart before posting.']);
                $quantity=(int)$items[$product->id]['quantity']; $lineTotal=$quantity*$product->price_cents; $total+=$lineTotal;
                $lines[]=['product_id'=>$product->id,'sku'=>$product->sku,'barcode'=>$product->barcode,'name'=>$product->name,'unit'=>$product->unit,'quantity'=>$quantity,'price_cents'=>$product->price_cents,'total_cents'=>$lineTotal];
            }
            if ($total > 9999999999) throw ValidationException::withMessages(['lines'=>'Sale total exceeds SAR 99,999,999.99.']);
            $parts=explode('.',$data['paid']); $paid=(int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
            if ($paid < $total) throw ValidationException::withMessages(['paid'=>'Payment must cover the sale total.']);
            if ($data['payment_method'] !== 'cash' && $paid !== $total) throw ValidationException::withMessages(['paid'=>'Card and bank payments must equal the sale total.']);
            $sale=Sale::create(['request_key'=>$data['request_key'],'location_id'=>$data['location_id'],'customer'=>$data['customer']?:'Walk-in customer','total_cents'=>$total,'paid_cents'=>$paid,'payment_method'=>$data['payment_method'],'created_by'=>$userId]);
            foreach ($lines as $line) {
                $sale->lines()->create($line);
                (new InventoryService)->post(['request_key'=>(string)Str::uuid(),'type'=>'issue','product_id'=>$line['product_id'],'location_id'=>$data['location_id'],'quantity'=>$line['quantity'],'movement_date'=>now()->format('Y-m-d'),'reference'=>'SALE-'.$sale->id,'recipient'=>$sale->customer,'notes'=>'Barcode checkout sale #'.$sale->id],$userId);
            }
            ActivityLog::record('Posted safety shop sale','SALE-'.$sale->id);
            return $sale;
        },3);
    }
}
