<?php
namespace App\Modules\SafetyShop\Sales\Services;

use App\Models\ActivityLog;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleLine;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use App\Modules\SafetyShop\Sales\Models\SaleReturnLine;
use App\Modules\SafetyShop\Stock\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function post(array $data, $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            if (SaleReturn::where('request_key', $data['request_key'])->exists()) {
                $this->fail('request_key', 'This return was already posted. Start a new return.');
            }

            $sale = Sale::whereKey($data['sale_id'])->lockForUpdate()->firstOrFail();
            $companyId=(int)$sale->location()->value('company_id');
            $requested = collect($data['lines'])->filter(fn ($line) => (int)($line['quantity'] ?? 0) > 0)->keyBy('sale_line_id');
            if ($requested->isEmpty()) $this->fail('lines', 'Select at least one item and enter its return quantity.');

            $saleLines = SaleLine::where('sale_id', $sale->id)->whereIn('id', $requested->keys())->orderBy('id')->lockForUpdate()->get();
            if ($saleLines->count() !== $requested->count()) $this->fail('lines', 'One or more selected items do not belong to this receipt.');

            $alreadyReturned = SaleReturnLine::whereIn('sale_line_id', $saleLines->pluck('id'))->selectRaw('sale_line_id, SUM(quantity) quantity')->groupBy('sale_line_id')->pluck('quantity', 'sale_line_id');
            $lineData = []; $refundTotal = 0; $grossRefund = 0; $discountRefund = 0; $taxRefund = 0; $costReversal = 0;
            foreach ($saleLines as $line) {
                $quantity = (int)$requested[$line->id]['quantity'];
                $available = $line->quantity - (int)($alreadyReturned[$line->id] ?? 0);
                if ($quantity > $available) $this->fail('lines', $line->name.' has only '.$available.' returnable unit(s).');
                if (!empty($requested[$line->id]['barcode']) && trim($requested[$line->id]['barcode']) !== (string)$line->barcode) {
                    $this->fail('lines', 'The scanned barcode does not match '.$line->name.'.');
                }
                $gross = $quantity * $line->price_cents;
                $discount = $sale->subtotal_cents ? (int)round($gross * $sale->discount_cents / $sale->subtotal_cents) : 0;
                $tax = (int)round(($gross - $discount) * $sale->tax_rate_units / 10000);
                $refund = ($gross - $discount) + $tax;
                $cost = $quantity * $line->cost_cents;
                $lineData[] = ['line'=>$line, 'quantity'=>$quantity, 'cost_cents'=>$cost, 'refund_cents'=>$refund];
                $refundTotal += $refund;
                $grossRefund += $gross; $discountRefund += $discount; $taxRefund += $tax; $costReversal += $cost;
            }

            $previousRefunds = (int)SaleReturn::where('sale_id', $sale->id)->sum('refund_cents');
            $remainingRefund = max(0, $sale->total_cents - $previousRefunds);
            $refundTotal = min($refundTotal, $remainingRefund);
            if ($refundTotal <= 0) $this->fail('lines', 'This receipt has no refundable balance remaining.');
            $remainingAllocation = $refundTotal;
            foreach ($lineData as &$item) {
                $item['refund_cents'] = min($item['refund_cents'], $remainingAllocation);
                $remainingAllocation -= $item['refund_cents'];
            }
            unset($item);

            $return = SaleReturn::create([
                'request_key'=>$data['request_key'], 'sale_id'=>$sale->id, 'location_id'=>$sale->location_id,
                'refund_method'=>$data['refund_method'], 'refund_cents'=>$refundTotal,
                'gross_refund_cents'=>$grossRefund, 'discount_refund_cents'=>$discountRefund,
                'tax_refund_cents'=>$taxRefund, 'cost_reversal_cents'=>$costReversal,
                'reason'=>$data['reason'], 'created_by'=>$userId,
            ]);

            foreach ($lineData as $item) {
                $return->lines()->create(['sale_line_id'=>$item['line']->id, 'product_id'=>$item['line']->product_id, 'quantity'=>$item['quantity'], 'cost_cents'=>$item['cost_cents'], 'refund_cents'=>$item['refund_cents']]);
                (new InventoryService)->post([
                    'company_id'=>$companyId, 'request_key'=>(string)Str::uuid(), 'type'=>'return', 'product_id'=>$item['line']->product_id,
                    'location_id'=>$sale->location_id, 'quantity'=>$item['quantity'], 'movement_date'=>now()->format('Y-m-d'),
                    'reference'=>'RETURN-'.$return->id.' / SALE-'.$sale->id, 'recipient'=>$sale->customer,
                    'notes'=>'Customer return #'.$return->id.' · '.$data['reason'],
                ], $userId);
            }

            ActivityLog::record('Posted safety shop customer return', 'RETURN-'.$return->id.' · SALE-'.$sale->id);
            return $return;
        }, 3);
    }

    private function fail($field, $message) { throw ValidationException::withMessages([$field=>$message]); }
}
