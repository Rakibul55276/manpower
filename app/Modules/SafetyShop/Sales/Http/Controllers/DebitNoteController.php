<?php
namespace App\Modules\SafetyShop\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SafetyShop\Sales\Models\Sale;
use App\Modules\SafetyShop\Sales\Models\SaleDebitNote;
use App\Modules\SafetyShop\Sales\Services\DebitNoteService;
use App\Services\Documents;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Modules\Zatca\Services\ZatcaIssuanceGate;
use App\Modules\Zatca\Services\ZatcaQrService;
use App\Modules\Zatca\Services\ZatcaLifecycleService;

class DebitNoteController extends Controller
{
    public function index(Request $request){$notes=SaleDebitNote::whereHas('location',fn($q)=>$q->where('company_id',$request->user()->company_id))->with(['sale','location','creator'])->latest('id')->paginate(25);return view('safety-shop.debit-notes.index',compact('notes'));}
    public function create(){return view('safety-shop.debit-notes.create',['requestKey'=>(string)Str::uuid()]);}
    public function lookup(Request $request){$data=$request->validate(['invoice'=>'required|string|max:50']);$sale=Sale::where('invoice_number',strtoupper(trim($data['invoice'])))->where('status','finalized')->whereHas('location',fn($q)=>$q->where('company_id',$request->user()->company_id))->with('location')->firstOrFail();return response()->json(['id'=>$sale->id,'invoice'=>$sale->invoice_number,'customer'=>$sale->customer,'location'=>$sale->location->name,'date'=>$sale->created_at->format('d M Y H:i'),'tax_rate'=>number_format($sale->tax_rate_units/100,2,'.','')]);}
    public function store(Request $request,DebitNoteService $service,ZatcaLifecycleService $zatca){$data=$request->validate(['request_key'=>'required|uuid','sale_id'=>'required|integer|exists:safety_shop_sales,id','issue_date'=>'required|date|before_or_equal:today','reason'=>'required|string|max:500','lines'=>'required|array|min:1|max:50','lines.*.description'=>'required|string|max:255','lines.*.quantity'=>'required|numeric|min:0.01|max:999999.99','lines.*.unit'=>'required|string|max:20','lines.*.unit_price'=>'required|numeric|min:0.01|max:99999999.99']);$note=$service->post($data,$request->user()->id);$zatcaProcessed=$zatca->processDebitNote($note);return redirect()->route('safety-shop.debit-notes.show',$note)->with('success','Tax debit note posted. No inventory movement was created.'.($zatcaProcessed?' ZATCA accepted the debit note.':''));}
    public function show(Request $request,SaleDebitNote $debitNote){$this->authorizeCompany($request,$debitNote);$debitNote->load(['sale.customerRecord','location','creator','lines','zatcaDocument']);return view('safety-shop.debit-notes.show',compact('debitNote'));}
    public function pdf(Request $request,SaleDebitNote $debitNote,ZatcaIssuanceGate $gate,ZatcaQrService $qrService){$this->authorizeCompany($request,$debitNote);$debitNote->load(['sale.customerRecord','location','creator','lines','zatcaDocument']);$gate->assertPrintable((int)$request->user()->company_id,$debitNote->sale->invoice_type,$debitNote->zatcaDocument);$receiptSettings=Documents::branding();abort_unless($receiptSettings,404);$qr=$qrService->dataUri($debitNote->zatcaDocument);return Documents::download(Documents::render('pdf.safety-shop-debit-note',compact('debitNote','receiptSettings','qr'),'A4','portrait',false,false,true),$debitNote->debit_note_number.'.pdf');}
    private function authorizeCompany(Request $request,SaleDebitNote $note): void{abort_unless((int)$note->location()->value('company_id')===(int)$request->user()->company_id,404);}
}
