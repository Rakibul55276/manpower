<?php
namespace App\Modules\SafetyShop\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use App\Modules\SafetyShop\Sales\Models\SaleReturnLine;
use App\Modules\SafetyShop\Sales\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\Documents;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns=SaleReturn::whereHas('location',fn($q)=>$q->where('company_id',$request->user()->company_id))->with(['sale','location','creator'])->latest('id')->paginate(25);
        return view('safety-shop.returns.index', compact('returns'));
    }

    public function create()
    {
        return view('safety-shop.returns.create', ['requestKey'=>(string)Str::uuid()]);
    }

    public function lookup(Request $request)
    {
        $data=$request->validate(['receipt'=>'required|string|max:50']);
        $invoice=trim($data['receipt']);
        $sale=Sale::where('invoice_number',strtoupper($invoice))->where('status','finalized')->whereHas('location',fn($q)=>$q->where('company_id',$request->user()->company_id))->with(['lines','location'])->firstOrFail();
        $returned=SaleReturnLine::whereIn('sale_line_id',$sale->lines->pluck('id'))->selectRaw('sale_line_id, SUM(quantity) quantity')->groupBy('sale_line_id')->pluck('quantity','sale_line_id');
        return response()->json([
            'id'=>$sale->id, 'receipt'=>$sale->invoice_number, 'customer'=>$sale->customer,
            'customer_phone'=>$sale->customer_phone, 'date'=>$sale->created_at->format('d M Y H:i'),
            'location'=>$sale->location->name,
            'lines'=>$sale->lines->map(function ($line) use ($returned) {
                $returnedQuantity=(int)($returned[$line->id]??0);
                return ['id'=>$line->id,'product_id'=>$line->product_id,'sku'=>$line->sku,'barcode'=>$line->barcode,'name'=>$line->name,'unit'=>$line->unit,'sold'=>$line->quantity,'returned'=>$returnedQuantity,'returnable'=>max(0,$line->quantity-$returnedQuantity),'price_cents'=>$line->price_cents];
            })->values(),
        ]);
    }

    public function store(Request $request, ReturnService $service)
    {
        $data=$request->validate([
            'request_key'=>'required|uuid', 'sale_id'=>'required|integer|exists:safety_shop_sales,id',
            'refund_method'=>'required|in:cash,card,bank,store_credit', 'reason'=>'required|string|max:500',
            'lines'=>'required|array|min:1|max:100', 'lines.*.sale_line_id'=>'required|integer|distinct',
            'lines.*.quantity'=>'nullable|integer|min:0|max:1000000', 'lines.*.barcode'=>'nullable|string|max:100',
        ]);
        abort_unless(Sale::whereKey($data['sale_id'])->where('status','finalized')->whereHas('location',fn($q)=>$q->where('company_id',$request->user()->company_id))->exists(),404);
        $return=$service->post($data,$request->user()->id);
        return redirect()->route('safety-shop.returns.show',$return)->with('success','Customer return posted and stock restored.');
    }

    public function show(Request $request,SaleReturn $return)
    {
        abort_unless((int)$return->location()->value('company_id')===(int)$request->user()->company_id,404);
        $return->load(['sale','location','creator','lines.saleLine']);
        return view('safety-shop.returns.show',compact('return'));
    }

    public function pdf(Request $request, SaleReturn $return)
    {
        abort_unless((int)$return->location()->value('company_id')===(int)$request->user()->company_id,404);
        $return->load(['sale.customerRecord','sale.location','location','creator','lines.saleLine']);
        $receiptSettings=Documents::branding();
        abort_unless($receiptSettings,404);
        $qr=$this->creditNoteQr($return,$receiptSettings);
        $bytes=Documents::render('pdf.safety-shop-credit-note',compact('return','receiptSettings','qr'),'A4','portrait',false,false,true);
        return Documents::download($bytes,$return->credit_note_number.'.pdf');
    }

    private function creditNoteQr(SaleReturn $return, $settings): string
    {
        $fields=[
            1=>(string)($settings->company_name ?: 'Harbour Edge Company Limited'),
            2=>(string)($settings->vat_number ?: ''),
            3=>$return->created_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            4=>\App\Services\Pay::decimal($return->refund_cents),
            5=>\App\Services\Pay::decimal($return->tax_refund_cents),
        ];
        $tlv='';
        foreach($fields as $tag=>$value){$bytes=mb_strcut((string)$value,0,255,'UTF-8');$tlv.=chr($tag).chr(strlen($bytes)).$bytes;}
        return Documents::qrDataUri(base64_encode($tlv));
    }
}
