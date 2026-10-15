<?php
namespace App\Modules\SafetyShop\Sales\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleDebitNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DebitNoteService
{
    public function post(array $data,int $userId): SaleDebitNote
    {
        return DB::transaction(function()use($data,$userId){
            if(SaleDebitNote::where('request_key',$data['request_key'])->exists())$this->fail('request_key','This debit note was already posted.');
            $sale=Sale::with('location')->whereKey($data['sale_id'])->lockForUpdate()->firstOrFail();
            $actor=User::findOrFail($userId);$companyId=(int)$sale->location->company_id;
            if($sale->status!=='finalized'||!$actor->canOperateSafetyShop()||(int)$actor->company_id!==$companyId)$this->fail('sale_id','Select a finalized invoice belonging to your company.');
            $lines=collect($data['lines'])->map(function($line)use($sale){$quantity=(float)$line['quantity'];$unitPrice=$this->cents($line['unit_price']);$subtotal=(int)round($quantity*$unitPrice);$tax=(int)round($subtotal*$sale->tax_rate_units/10000);return ['description'=>trim($line['description']),'quantity'=>$quantity,'unit'=>$line['unit'],'unit_price_cents'=>$unitPrice,'subtotal_cents'=>$subtotal,'tax_cents'=>$tax,'total_cents'=>$subtotal+$tax];});
            $subtotal=(int)$lines->sum('subtotal_cents');$tax=(int)$lines->sum('tax_cents');if($subtotal<=0)$this->fail('lines','Debit-note value must be greater than zero.');
            $note=SaleDebitNote::create(['request_key'=>$data['request_key'],'sale_id'=>$sale->id,'location_id'=>$sale->location_id,'issue_date'=>$data['issue_date'],'reason'=>trim($data['reason']),'subtotal_cents'=>$subtotal,'tax_rate_units'=>$sale->tax_rate_units,'tax_cents'=>$tax,'total_cents'=>$subtotal+$tax,'created_by'=>$userId]);
            foreach($lines as $line)$note->lines()->create($line);
            ActivityLog::record('Posted safety shop tax debit note',$note->debit_note_number.' · '.$sale->invoice_number);
            return $note;
        },3);
    }
    private function cents(string $value): int{$normalized=number_format((float)$value,2,'.','');[$whole,$fraction]=explode('.',$normalized);return (int)$whole*100+(int)$fraction;}
    private function fail(string $field,string $message): void{throw ValidationException::withMessages([$field=>$message]);}
}
