<?php
namespace App\Modules\SafetyShop\Sales\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalePaymentService
{
    public function record(Sale $sale, array $data, int $userId): SalePayment
    {
        return DB::transaction(function () use ($sale,$data,$userId) {
            $sale=Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $this->authorizeOperator($sale,$userId);
            if($sale->status!=='finalized')$this->fail('amount','Payments can only be recorded against finalized invoices.');
            $amount=$this->cents($data['amount']);
            if($amount<=0)$this->fail('amount','Enter a payment amount greater than zero.');
            if($amount>$sale->balance_due_cents)$this->fail('amount','Payment cannot exceed the outstanding invoice amount.');
            $payment=SalePayment::create(['sale_id'=>$sale->id,'payment_date'=>$data['payment_date'],'method'=>$data['method'],'amount_cents'=>$amount,'reference'=>$data['reference']??null,'notes'=>$data['notes']??null,'source'=>'recorded','created_by'=>$userId]);
            $this->refreshTotals($sale);
            ActivityLog::record('Recorded safety shop invoice payment',$sale->invoice_number.' · SAR '.number_format($amount/100,2).' · '.$data['method']);
            return $payment;
        },3);
    }

    public function reverse(SalePayment $payment, string $reason, int $userId): void
    {
        DB::transaction(function () use ($payment,$reason,$userId) {
            $payment=SalePayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $sale=Sale::whereKey($payment->sale_id)->lockForUpdate()->firstOrFail();
            $actor=User::findOrFail($userId);
            if(!$actor->canApprove() || (int)$actor->company_id!==(int)$sale->location()->value('company_id'))abort(403);
            if($payment->reversed_at)$this->fail('reversal_reason','This payment has already been reversed.');
            $payment->update(['reversed_by'=>$userId,'reversed_at'=>now(),'reversal_reason'=>$reason]);
            $this->refreshTotals($sale);
            ActivityLog::record('Reversed safety shop invoice payment',$sale->invoice_number.' · payment #'.$payment->id.' · '.$reason);
        },3);
    }

    private function refreshTotals(Sale $sale): void
    {
        $active=$sale->payments()->whereNull('reversed_at');
        $cash=(int)(clone $active)->where('method','cash')->sum('amount_cents');
        $card=(int)(clone $active)->where('method','card')->sum('amount_cents');
        $bank=(int)(clone $active)->where('method','bank')->sum('amount_cents');
        $paid=$cash+$card+$bank;
        $methods=collect(['cash'=>$cash,'card'=>$card,'bank'=>$bank])->filter()->keys();
        $sale->update(['cash_cents'=>$cash,'card_cents'=>$card,'bank_cents'=>$bank,'paid_cents'=>$paid,'payment_method'=>$methods->count()>1?'split':($methods->first()?:'credit'),'payment_received'=>$paid>=$sale->total_cents]);
    }

    private function authorizeOperator(Sale $sale,int $userId): void
    {
        $actor=User::findOrFail($userId);
        if(!$actor->canOperateSafetyShop() || (int)$actor->company_id!==(int)$sale->location()->value('company_id'))abort(403);
    }
    private function cents($amount): int { $parts=explode('.',(string)$amount);return (int)$parts[0]*100+(int)str_pad($parts[1]??'',2,'0'); }
    private function fail($field,$message): void { throw ValidationException::withMessages([$field=>$message]); }
}
