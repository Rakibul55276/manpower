<?php
namespace App\Modules\SafetyShop\Stock\Services;
use App\Models\ActivityLog;
use App\Modules\SafetyShop\Shared\Models\Master;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Stock\Models\Stock;
use App\Modules\SafetyShop\Stock\Models\Movement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class InventoryService
{
    public function post(array $data, $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            // Serializing on the product also protects creation of new stock rows.
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            if ((int)$product->company_id !== (int)$data['company_id']) $this->fail('product_id','Select a product belonging to this company.');
            if (!$product->is_active) $this->fail('product_id', 'This product is inactive.');
            if (Movement::where('request_key', $data['request_key'])->exists()) $this->fail('request_key', 'This movement was already posted. Refresh before entering another.');
            $this->master($data['location_id'], 'location', 'location_id',$data['company_id']);
            if (!empty($data['supplier_id'])) $this->master($data['supplier_id'], 'supplier', 'supplier_id',$data['company_id']);
            if ($data['type'] === 'transfer') {
                if (empty($data['destination_id']) || $data['destination_id'] == $data['location_id']) $this->fail('destination_id', 'Choose a different destination.');
                $this->master($data['destination_id'], 'location', 'destination_id',$data['company_id']);
            } else { $data['destination_id'] = null; }
            $stock = Stock::firstOrCreate(['product_id'=>$product->id, 'location_id'=>$data['location_id']], ['quantity'=>0]);
            $stock = Stock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
            $quantity = (int)$data['quantity'];
            if ($data['type'] !== 'adjustment' && $quantity <= 0) $this->fail('quantity', 'Enter a positive whole quantity.');
            if ($data['type'] === 'adjustment' && $quantity === 0) $this->fail('quantity', 'An adjustment must change stock.');
            $delta = in_array($data['type'], ['issue', 'transfer'], true) ? -$quantity : $quantity;
            $balance = $stock->quantity + $delta;
            if ($balance < 0) $this->fail('quantity', 'Insufficient stock. Available: '.$stock->quantity.'.');
            if ($balance > 1000000000) $this->fail('quantity', 'Stock quantity exceeds the supported limit.');
            $stock->update(['quantity'=>$balance]);
            $destinationBalance = null;
            if ($data['type'] === 'transfer') {
                $destination = Stock::firstOrCreate(['product_id'=>$product->id, 'location_id'=>$data['destination_id']], ['quantity'=>0]);
                $destination = Stock::whereKey($destination->id)->lockForUpdate()->firstOrFail();
                $destinationBalance = $destination->quantity + $quantity;
                if ($destinationBalance > 1000000000) $this->fail('quantity', 'Destination quantity exceeds the supported limit.');
                $destination->update(['quantity'=>$destinationBalance]);
            }
            $movement = Movement::create(array_merge($data, ['quantity'=>$delta, 'balance_after'=>$balance, 'destination_balance_after'=>$destinationBalance, 'created_by'=>$userId]));
            ActivityLog::record('Posted safety shop '.$data['type'], $product->sku.' · movement #'.$movement->id);
            return $movement;
        }, 3);
    }
    private function master($id, $type, $field, $companyId)
    {
        if (!Master::whereKey($id)->where('company_id',$companyId)->where('type', $type)->where('is_active', true)->lockForUpdate()->first()) $this->fail($field, 'Select an active '.$type.'.');
    }
    private function fail($field, $message) { throw ValidationException::withMessages([$field=>$message]); }
}
