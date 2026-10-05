<?php
namespace App\Modules\Invoicing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceCustomer;
use App\Modules\Invoicing\Models\InvoiceEvent;
use App\Modules\Invoicing\Models\InvoiceItem;
use App\Modules\Invoicing\Models\InvoiceSetting;
use App\Modules\Invoicing\Services\InvoiceService;
use App\Services\Documents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search'=>'nullable|string|max:100','status'=>'nullable|in:draft,approved,paid,cancelled','type'=>'nullable|in:standard,simplified']);
        $query=Invoice::with('customer')->latest('issue_date')->latest('id');
        if($request->filled('search')){$search=trim($request->search);$query->where(function($q)use($search){$q->where('invoice_number','like','%'.$search.'%')->orWhereHas('customer',function($c)use($search){$c->where('name','like','%'.$search.'%')->orWhere('vat_number','like','%'.$search.'%');});});}
        if($request->filled('status'))$query->where('status',$request->status); if($request->filled('type'))$query->where('invoice_type',$request->type);
        $invoices=$query->paginate(20)->withQueryString();
        $stats=['draft'=>Invoice::where('status','draft')->count(),'approved'=>Invoice::where('status','approved')->count(),'paid'=>Invoice::where('status','paid')->count(),'total'=>Invoice::whereIn('status',['approved','paid'])->sum('total_cents')];
        return view('invoicing.index',compact('invoices','stats'));
    }
    public function create(){return view('invoicing.create',['customers'=>InvoiceCustomer::where('is_active',1)->orderBy('name')->get(),'items'=>InvoiceItem::where('is_active',1)->orderBy('name')->get(),'references'=>Invoice::whereIn('status',['approved','paid'])->latest()->limit(100)->get()]);}
    public function store(Request $request)
    {
        $data=$request->validate(['document_type'=>'required|in:invoice,credit_note,debit_note','invoice_type'=>'required|in:standard,simplified','customer_id'=>'required|exists:invoice_customers,id','reference_invoice_id'=>'nullable|required_unless:document_type,invoice|exists:invoices,id','issue_date'=>'required|date|before_or_equal:today','supply_date'=>'nullable|date','due_date'=>'nullable|date|after_or_equal:issue_date','contract_po'=>'nullable|string|max:100','delivery_note'=>'nullable|string|max:100','invoice_period'=>'nullable|string|max:100','project_reference'=>'nullable|string|max:100','notes'=>'nullable|string|max:2000','lines'=>'required|array|min:1|max:20','lines.*.item_id'=>'nullable|exists:invoice_items,id','lines.*.description'=>'required|string|max:255','lines.*.description_ar'=>'nullable|string|max:255','lines.*.quantity'=>'required|numeric|min:0.01|max:999999.99','lines.*.unit_code'=>'required|string|max:10','lines.*.unit_price'=>'required|numeric|min:0|max:99999999.99','lines.*.discount'=>'nullable|numeric|min:0|max:99999999.99','lines.*.tax_category'=>'required|in:standard,zero,exempt','lines.*.tax_rate_units'=>'required|integer|in:0,1500']);
        $customer=InvoiceCustomer::findOrFail($data['customer_id']);
        if($data['invoice_type']==='standard' && $customer->customer_type!=='business')return back()->withErrors(['customer_id'=>'A standard B2B invoice requires a business customer.'])->withInput();
        $totals=InvoiceService::calculate($data['lines']);
        $invoice=DB::transaction(function()use($data,$totals){$invoice=Invoice::create(['uuid'=>(string)Str::uuid(),'document_type'=>$data['document_type'],'invoice_type'=>$data['invoice_type'],'customer_id'=>$data['customer_id'],'reference_invoice_id'=>$data['reference_invoice_id']??null,'issue_date'=>$data['issue_date'],'supply_date'=>$data['supply_date']??null,'due_date'=>$data['due_date']??null,'contract_po'=>$data['contract_po']??null,'delivery_note'=>$data['delivery_note']??null,'invoice_period'=>$data['invoice_period']??null,'project_reference'=>$data['project_reference']??null,'currency'=>'SAR','subtotal_cents'=>$totals['subtotal'],'discount_cents'=>$totals['discount'],'tax_cents'=>$totals['tax'],'total_cents'=>$totals['total'],'status'=>'draft','zatca_status'=>'not_connected','notes'=>$data['notes']??null,'created_by'=>auth()->id()]); foreach($totals['calculated'] as $line)$invoice->lines()->create($line); $this->event($invoice,'Draft created','Invoice draft created.'); ActivityLog::record('Created invoice draft',$invoice->uuid); return $invoice;});
        return redirect()->route('invoicing.show',$invoice)->with('success','Invoice draft created.');
    }
    public function show(Invoice $invoice){$invoice->load(['customer','lines','events.user','creator','approver','reference']);$settings=InvoiceSetting::firstOrFail();$qrPayload=InvoiceService::invoiceQrPayload($invoice,$settings);$qr=InvoiceService::qrDataUri($invoice,$settings);return view('invoicing.show',compact('invoice','settings','qrPayload','qr'));}
    public function approve(Invoice $invoice)
    {
        DB::transaction(function()use($invoice){$record=Invoice::lockForUpdate()->findOrFail($invoice->id);abort_unless($record->status==='draft',403,'Only drafts can be approved.');$settings=InvoiceSetting::lockForUpdate()->firstOrFail();$number=$settings->invoice_prefix.'-'.$record->issue_date->format('Y').'-'.str_pad($settings->next_number,6,'0',STR_PAD_LEFT);$settings->increment('next_number');$record->update(['invoice_number'=>$number,'status'=>'approved','zatca_status'=>'not_connected','zatca_message'=>null,'approved_by'=>auth()->id(),'approved_at'=>now()]);$this->event($record,'Approved','Assigned invoice number '.$number.'.');ActivityLog::record('Approved invoice',$number);}); return back()->with('success','Invoice approved and numbered.');
    }
    public function paid(Invoice $invoice){abort_unless($invoice->status==='approved',403);$invoice->update(['status'=>'paid','paid_by'=>auth()->id(),'paid_at'=>now()]);$this->event($invoice,'Marked paid','Payment status recorded.');ActivityLog::record('Marked invoice paid',$invoice->invoice_number);return back()->with('success','Payment recorded.');}
    public function destroy(Invoice $invoice){abort_unless($invoice->status==='draft',403,'Only drafts can be deleted.');$uuid=$invoice->uuid;$invoice->delete();ActivityLog::record('Deleted invoice draft',$uuid);return redirect()->route('invoicing.index')->with('success','Draft deleted.');}
    public function pdf(Invoice $invoice){abort_if($invoice->status==='draft',403,'Approve the invoice before printing.');$invoice->load(['customer','lines.item','approver','creator']);$settings=InvoiceSetting::firstOrFail();$qrPayload=InvoiceService::invoiceQrPayload($invoice,$settings);$qr=InvoiceService::qrDataUri($invoice,$settings);$logo=null;if($settings->logo_path&&Storage::disk('local')->exists($settings->logo_path)){$path=Storage::disk('local')->path($settings->logo_path);$logo='data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));}return Documents::download(Documents::render('invoicing.pdf',compact('invoice','settings','qrPayload','qr','logo'),'A4','portrait',false),'invoice-'.$invoice->invoice_number.'.pdf');}
    public function xml(Invoice $invoice){abort_if($invoice->status==='draft',403,'Approve the invoice before exporting XML.');$xml=InvoiceService::xml($invoice,InvoiceSetting::firstOrFail());return response($xml,200,['Content-Type'=>'application/xml; charset=UTF-8','Content-Disposition'=>'attachment; filename="'.$invoice->invoice_number.'.xml"','Cache-Control'=>'private, no-store']);}
    public function masters(){return redirect()->route('invoicing.customers.index');}
    public function customers(Request $request)
    {
        $request->validate(['search'=>'nullable|string|max:100','type'=>'nullable|in:business,consumer','status'=>'nullable|in:active,inactive']);
        $query=InvoiceCustomer::query()->withCount('invoices')->orderBy('name');
        if($request->filled('search')){$search=trim($request->search);$query->where(function($q)use($search){$q->where('name','like','%'.$search.'%')->orWhere('name_ar','like','%'.$search.'%')->orWhere('vat_number','like','%'.$search.'%')->orWhere('commercial_registration','like','%'.$search.'%')->orWhere('email','like','%'.$search.'%');});}
        if($request->filled('type'))$query->where('customer_type',$request->type);
        if($request->filled('status'))$query->where('is_active',$request->status==='active');
        return view('invoicing.customers',['customers'=>$query->paginate(20)->withQueryString()]);
    }
    public function items(Request $request)
    {
        $request->validate(['search'=>'nullable|string|max:100','tax'=>'nullable|in:standard,zero,exempt','status'=>'nullable|in:active,inactive']);
        $query=InvoiceItem::query()->orderBy('name');
        if($request->filled('search')){$search=trim($request->search);$query->where(function($q)use($search){$q->where('sku','like','%'.$search.'%')->orWhere('name','like','%'.$search.'%')->orWhere('name_ar','like','%'.$search.'%');});}
        if($request->filled('tax'))$query->where('tax_category',$request->tax);
        if($request->filled('status'))$query->where('is_active',$request->status==='active');
        return view('invoicing.items',['items'=>$query->paginate(20)->withQueryString()]);
    }
    public function settingsPage(){return view('invoicing.settings',['settings'=>InvoiceSetting::firstOrFail()]);}
    public function designPage(){return view('invoicing.design',['settings'=>InvoiceSetting::firstOrFail()]);}
    public function design(Request $request)
    {
        $data=$request->validate(['logo'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=3000,max_height=1500','remove_logo'=>'nullable|boolean','design_primary_color'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],'design_text_color'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],'design_header_bg'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],'design_border_color'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],'invoice_title'=>'required|string|max:80','invoice_title_ar'=>'required|string|max:80','design_density'=>'required|in:compact,comfortable','design_header_layout'=>'required|in:split,centered,logo_left,blank','design_title_alignment'=>'required|in:left,center,right','design_logo_width'=>'required|integer|min:30|max:120','design_font_size'=>'required|numeric|min:6|max:11','invoice_footer'=>'nullable|string|max:500','show_bank_details'=>'nullable|boolean','show_signatures'=>'nullable|boolean','show_qr'=>'nullable|boolean','show_company_cr'=>'nullable|boolean','show_seller_details'=>'nullable|boolean','show_customer_details'=>'nullable|boolean','show_references'=>'nullable|boolean','show_amount_words'=>'nullable|boolean','show_notes'=>'nullable|boolean','show_footer_uuid'=>'nullable|boolean']);
        $settings=InvoiceSetting::firstOrFail();$oldLogo=$settings->logo_path;$newLogo=$request->hasFile('logo')?$request->file('logo')->store('invoice-branding','local'):null;
        $settings->update(['logo_path'=>$newLogo?:($request->boolean('remove_logo')?null:$oldLogo),'design_primary_color'=>$data['design_primary_color'],'design_text_color'=>$data['design_text_color'],'design_header_bg'=>$data['design_header_bg'],'design_border_color'=>$data['design_border_color'],'invoice_title'=>$data['invoice_title'],'invoice_title_ar'=>$data['invoice_title_ar'],'design_density'=>$data['design_density'],'design_header_layout'=>$data['design_header_layout'],'design_title_alignment'=>$data['design_title_alignment'],'design_logo_width'=>$data['design_logo_width'],'design_font_size'=>$data['design_font_size'],'invoice_footer'=>$data['invoice_footer']??null,'show_bank_details'=>$request->boolean('show_bank_details'),'show_signatures'=>$request->boolean('show_signatures'),'show_qr'=>$request->boolean('show_qr'),'show_company_cr'=>$request->boolean('show_company_cr'),'show_seller_details'=>$request->boolean('show_seller_details'),'show_customer_details'=>$request->boolean('show_customer_details'),'show_references'=>$request->boolean('show_references'),'show_amount_words'=>$request->boolean('show_amount_words'),'show_notes'=>$request->boolean('show_notes'),'show_footer_uuid'=>$request->boolean('show_footer_uuid')]);
        if($oldLogo&&($newLogo||$request->boolean('remove_logo')))Storage::disk('local')->delete($oldLogo);
        ActivityLog::record('Updated invoice design',$settings->invoice_title);
        return back()->with('success','Invoice design updated. New PDFs will use this template.');
    }
    public function settings(Request $request){$data=$request->validate(['legal_name'=>'required|string|max:255','legal_name_ar'=>'nullable|string|max:255','vat_number'=>'required|digits:15','commercial_registration'=>'required|string|max:30','address'=>'required|string|max:255','city'=>'required|string|max:100','postal_code'=>'required|string|max:10','country_code'=>'required|string|size:2','invoice_prefix'=>'required|alpha_dash|max:20','bank_name'=>'nullable|string|max:255','bank_account_name'=>'nullable|string|max:255','bank_account_number'=>'nullable|string|max:40','iban'=>'nullable|string|max:34','bank_branch'=>'nullable|string|max:255']);$setting=InvoiceSetting::firstOrFail();$setting->update($data);ActivityLog::record('Updated invoice settings',$setting->legal_name);return back()->with('success','Invoice settings updated.');}
    public function customer(Request $request){$data=$request->validate(['name'=>'required|string|max:255','name_ar'=>'nullable|string|max:255','customer_type'=>'required|in:business,consumer','vat_number'=>'nullable|required_if:customer_type,business|digits:15','commercial_registration'=>'nullable|string|max:30','email'=>'nullable|email|max:255','phone'=>'nullable|string|max:30','address'=>'required|string|max:255','city'=>'required|string|max:100','postal_code'=>'nullable|string|max:10','country_code'=>'required|string|size:2']);$customer=InvoiceCustomer::create($data+['is_active'=>true]);ActivityLog::record('Created invoice customer',$customer->name);return back()->with('success','Customer added.');}
    public function editCustomer(InvoiceCustomer $customer){return view('invoicing.customer-edit',compact('customer'));}
    public function updateCustomer(Request $request, InvoiceCustomer $customer)
    {
        $data=$request->validate(['name'=>'required|string|max:255','name_ar'=>'nullable|string|max:255','customer_type'=>'required|in:business,consumer','vat_number'=>'nullable|required_if:customer_type,business|digits:15','commercial_registration'=>'nullable|string|max:30','email'=>'nullable|email|max:255','phone'=>'nullable|string|max:30','address'=>'required|string|max:255','city'=>'required|string|max:100','postal_code'=>'nullable|string|max:10','country_code'=>'required|string|size:2','is_active'=>'required|boolean']);
        $customer->update($data);
        ActivityLog::record('Updated invoice customer',$customer->name);
        return redirect()->route('invoicing.customers.index')->with('success','Customer details updated.');
    }
    public function item(Request $request){$data=$request->validate(['sku'=>'required|alpha_dash|max:50|unique:invoice_items,sku','name'=>'required|string|max:255','name_ar'=>'nullable|string|max:255','unit_code'=>'required|string|max:10','unit_price'=>'required|numeric|min:0|max:99999999.99','tax_category'=>'required|in:standard,zero,exempt']);$item=InvoiceItem::create(['sku'=>$data['sku'],'name'=>$data['name'],'name_ar'=>$data['name_ar']??null,'unit_code'=>$data['unit_code'],'unit_price_cents'=>\App\Services\Pay::units($data['unit_price']),'tax_category'=>$data['tax_category'],'tax_rate_units'=>$data['tax_category']==='standard'?1500:0,'is_active'=>true]);ActivityLog::record('Created invoice item',$item->sku);return back()->with('success','Product/service added.');}
    private function event(Invoice $invoice,$event,$details){InvoiceEvent::create(['invoice_id'=>$invoice->id,'user_id'=>auth()->id(),'event'=>$event,'details'=>$details]);}
}
