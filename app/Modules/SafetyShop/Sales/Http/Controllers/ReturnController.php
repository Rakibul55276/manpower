<?php
namespace App\Modules\SafetyShop\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleReturn;
use App\Modules\SafetyShop\Sales\Models\SaleReturnLine;
use App\Modules\SafetyShop\Sales\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReturnController extends Controller
{
    public function index()
    {
        $returns=SaleReturn::with(['sale','location','creator'])->latest('id')->paginate(25);
        return view('safety-shop.returns.index', compact('returns'));
    }

    public function create()
    {
        return view('safety-shop.returns.create', ['requestKey'=>(string)Str::uuid()]);
    }

    public function lookup(Request $request)
    {
        $data=$request->validate(['receipt'=>'required|string|max:50']);
        $receipt=preg_replace('/^SALE-/i', '', trim($data['receipt']));
        abort_unless(ctype_digit($receipt), 404);
        $sale=Sale::with(['lines','location'])->findOrFail((int)$receipt);
        $returned=SaleReturnLine::whereIn('sale_line_id',$sale->lines->pluck('id'))->selectRaw('sale_line_id, SUM(quantity) quantity')->groupBy('sale_line_id')->pluck('quantity','sale_line_id');
        return response()->json([
            'id'=>$sale->id, 'receipt'=>'SALE-'.$sale->id, 'customer'=>$sale->customer,
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
        $return=$service->post($data,$request->user()->id);
        return redirect()->route('safety-shop.returns.show',$return)->with('success','Customer return posted and stock restored.');
    }

    public function show(SaleReturn $return)
    {
        $return->load(['sale','location','creator','lines.saleLine']);
        return view('safety-shop.returns.show',compact('return'));
    }
}
