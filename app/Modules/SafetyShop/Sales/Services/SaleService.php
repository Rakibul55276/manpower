<?php
namespace App\Modules\SafetyShop\Sales\Services;
use App\Models\ActivityLog;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\Customer;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Stock\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;
class SaleService
{
    public function post(array $data, $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            $items = collect($data['lines'])->keyBy('product_id');
            $location=Master::whereKey($data['location_id'])->where('type','location')->where('is_active',true)->lockForUpdate()->firstOrFail();
            $companyId=(int)$location->company_id;
            $products = Product::where('company_id',$companyId)->whereIn('id',$items->keys())->orderBy('id')->lockForUpdate()->get();
            if (Sale::where('request_key',$data['request_key'])->exists()) throw ValidationException::withMessages(['request_key'=>'This sale was already posted. Start a new checkout.']);
            if ($products->count() !== $items->count()) throw ValidationException::withMessages(['lines'=>'A product no longer exists.']);
            $subtotal = 0; $lines = [];
            foreach ($products as $product) {
                if (!$product->is_active) throw ValidationException::withMessages(['lines'=>'Product '.$product->sku.' is inactive.']);
                if ((int)$items[$product->id]['price_cents'] !== $product->price_cents) throw ValidationException::withMessages(['lines'=>'A product price changed. Rescan the cart before posting.']);
                $quantity=(int)$items[$product->id]['quantity']; $lineTotal=$quantity*$product->price_cents; $subtotal+=$lineTotal;
                $lines[]=['product_id'=>$product->id,'sku'=>$product->sku,'barcode'=>$product->barcode,'name'=>$product->name,'unit'=>$product->unit,'quantity'=>$quantity,'cost_cents'=>$product->cost_cents,'price_cents'=>$product->price_cents,'total_cents'=>$lineTotal];
            }
            if ($subtotal > 9999999999) throw ValidationException::withMessages(['lines'=>'Sale total exceeds SAR 99,999,999.99.']);
            $discount=$this->cents($data['discount']??'0');
            if ($discount > $subtotal) throw ValidationException::withMessages(['discount'=>'Discount cannot exceed the subtotal.']);
            $taxable=$subtotal-$discount;
            $taxRateUnits=(int)round(((float)($data['tax_rate']??0))*100);
            $tax=(int)round($taxable*$taxRateUnits/10000);
            $total=$taxable+$tax;
            $usesSplit=array_key_exists('cash_paid',$data)||array_key_exists('card_paid',$data)||array_key_exists('bank_paid',$data);
            if ($usesSplit) {
                $cash=$this->cents($data['cash_paid']??'0'); $card=$this->cents($data['card_paid']??'0'); $bank=$this->cents($data['bank_paid']??'0');
                $paid=$cash+$card+$bank;
                if ($paid < $total) throw ValidationException::withMessages(['cash_paid'=>'Combined payments must cover the final total.']);
                if (($paid-$total) > $cash) throw ValidationException::withMessages(['cash_paid'=>'Only the cash portion may include change.']);
                $methods=collect(['cash'=>$cash,'card'=>$card,'bank'=>$bank])->filter()->keys();
                if ($methods->isEmpty()) throw ValidationException::withMessages(['cash_paid'=>'Enter at least one payment amount.']);
                $method=$methods->count()>1?'split':$methods->first();
            } else {
                $paid=$this->cents($data['paid']??'0'); $method=$data['payment_method']??'cash';
                $cash=$method==='cash'?$paid:0; $card=$method==='card'?$paid:0; $bank=$method==='bank'?$paid:0;
                if ($paid < $total) throw ValidationException::withMessages(['paid'=>'Payment must cover the sale total.']);
                if ($method !== 'cash' && $paid !== $total) throw ValidationException::withMessages(['paid'=>'Card and bank payments must equal the sale total.']);
            }
            $customerName=trim($data['customer']??'')?:'Walk-in customer'; $phoneDisplay=!empty($data['customer_phone'])?trim($data['customer_phone']):null; $phone=$phoneDisplay?preg_replace('/[^0-9+]/','',$phoneDisplay):null; $customerRecord=null;
            if ($customerName!=='Walk-in customer' || $phone || !empty($data['customer_email']) || !empty($data['customer_address'])) {
                $customerRecord=!empty($data['customer_id'])?Customer::whereKey($data['customer_id'])->lockForUpdate()->firstOrFail():null;
                if($phone&&Customer::where('phone',$phone)->when($customerRecord,fn($query)=>$query->where('id','<>',$customerRecord->id))->exists())$this->fail('customer_phone','This mobile number belongs to another saved customer. Select that customer instead.');
                if(!$customerRecord&&$phone)$customerRecord=Customer::where('phone',$phone)->lockForUpdate()->first();
                if(!$customerRecord){$approver=User::find($userId);$approved=$approver&&$approver->canApprove();$customerRecord=Customer::create(['name'=>$customerName,'phone'=>$phone,'customer_type'=>$data['customer_type']??'retail','country_code'=>'SA','approval_status'=>$approved?'approved':'pending','approved_by'=>$approved?$userId:null,'approved_at'=>$approved?now():null]);}
                $customerRecord->update(['customer_type'=>$data['customer_type']??'retail','name'=>$customerName,'contact_person'=>$data['customer_contact_person']??null,'phone'=>$phone,'email'=>$data['customer_email']??null,'vat_number'=>$data['customer_vat_number']??null,'commercial_registration'=>$data['customer_commercial_registration']??null,'address'=>$data['customer_address']??null,'building_number'=>$data['customer_building_number']??null,'street'=>$data['customer_street']??null,'district'=>$data['customer_district']??null,'city'=>$data['customer_city']??null,'postal_code'=>$data['customer_postal_code']??null,'country_code'=>$data['customer_country_code']??'SA','is_active'=>true]);
            }
            $sale=Sale::create(['request_key'=>$data['request_key'],'location_id'=>$data['location_id'],'customer_id'=>optional($customerRecord)->id,'customer_type'=>$data['customer_type']??'retail','customer'=>$customerName,'customer_contact_person'=>$data['customer_contact_person']??null,'customer_phone'=>$phoneDisplay,'customer_email'=>$data['customer_email']??null,'customer_vat_number'=>$data['customer_vat_number']??null,'customer_commercial_registration'=>$data['customer_commercial_registration']??null,'customer_address'=>$data['customer_address']??null,'subtotal_cents'=>$subtotal,'discount_cents'=>$discount,'taxable_cents'=>$taxable,'tax_rate_units'=>$taxRateUnits,'tax_cents'=>$tax,'total_cents'=>$total,'paid_cents'=>$paid,'cash_cents'=>$cash,'card_cents'=>$card,'bank_cents'=>$bank,'payment_method'=>$method,'created_by'=>$userId]);
            if($customerRecord)$customerRecord->update(['purchase_count'=>$customerRecord->purchase_count+1,'lifetime_value_cents'=>$customerRecord->lifetime_value_cents+$total,'last_purchase_at'=>now()]);
            foreach ($lines as $line) {
                $sale->lines()->create($line);
                (new InventoryService)->post(['company_id'=>$companyId,'request_key'=>(string)Str::uuid(),'type'=>'issue','product_id'=>$line['product_id'],'location_id'=>$data['location_id'],'quantity'=>$line['quantity'],'movement_date'=>now()->format('Y-m-d'),'reference'=>'SALE-'.$sale->id,'recipient'=>$sale->customer,'notes'=>'Barcode checkout sale #'.$sale->id],$userId);
            }
            ActivityLog::record('Posted safety shop sale','SALE-'.$sale->id);
            return $sale;
        },3);
    }

    private function cents($amount)
    {
        $parts=explode('.',(string)$amount);
        return (int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0');
    }
    private function fail($field,$message){throw ValidationException::withMessages([$field=>$message]);}
}
