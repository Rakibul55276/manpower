<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CommercialDocument;
use App\Models\QuotationItemMaster;
use App\Modules\SafetyShop\Products\Models\Product;
use App\Modules\SafetyShop\Sales\Models\Customer;
use App\Services\Documents;
use App\Services\Pay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommercialDocumentController extends Controller
{
    private function type(Request $request): string
    {
        $type = $request->route('document_type');
        abort_unless(in_array($type, ['quotation', 'delivery_note'], true), 404);
        $setting = $type === 'quotation' ? 'quotation_enabled' : 'delivery_note_enabled';
        abort_unless(config('commercial_documents.'.$setting), 404);
        return $type;
    }

    private function names(string $type): array
    {
        return $type === 'quotation' ? ['Quotation', 'quotations', 'QTN'] : ['Delivery note', 'delivery-notes', 'DN'];
    }

    private function scoped(Request $request, $id = null)
    {
        $query = CommercialDocument::where('company_id', $request->user()->company_id);
        if ($id) $query->whereKey($id);
        return $query;
    }

    private function randomNumber(string $type, int $companyId): string
    {
        $prefix = $this->names($type)[2].'-';
        do {
            $number = $prefix.Str::upper(Str::random(8));
        } while (CommercialDocument::where('company_id', $companyId)->where('type', $type)->where('number', $number)->exists());

        return $number;
    }

    private function assertQuotationAdmin(Request $request): void
    {
        abort_unless($request->user()->isCompanyAdmin()&&!$request->user()->isSuperAdmin(),403);
    }

    private function assertCompanyApprover(Request $request): void
    {
        abort_unless(!$request->user()->isSuperAdmin()&&$request->user()->company_id&&$request->user()->canApprove(),403);
    }

    private function mergeRepeatedLines(array $lines,string $type): array
    {
        $merged=[];
        foreach($lines as $index=>$line){
            $key=!empty($line['product_id'])?'product:'.$line['product_id']:(!empty($line['quotation_item_master_id'])?'quotation:'.$line['quotation_item_master_id']:'manual:'.$index);
            if(!isset($merged[$key])){$merged[$key]=$line;continue;}
            $samePrice=$type!=='quotation'||Pay::units($merged[$key]['unit_price']??0)===Pay::units($line['unit_price']??0);
            if(!$samePrice||$merged[$key]['unit']!==$line['unit'])throw \Illuminate\Validation\ValidationException::withMessages(['lines'=>'Repeated items must use the same unit and unit price.']);
            $merged[$key]['quantity']=(float)$merged[$key]['quantity']+(float)$line['quantity'];
            if($merged[$key]['quantity']>999999)throw \Illuminate\Validation\ValidationException::withMessages(['lines'=>'Combined item quantity cannot exceed 999,999.']);
        }
        return array_values($merged);
    }

    private function quotationDiscount(array $data, int $subtotal): int
    {
        $value=(float)($data['discount_value']??0);
        $discount=($data['discount_type']??'fixed')==='percent'
            ? (int)round($subtotal*$value/100)
            : Pay::units($value);
        if($discount>$subtotal)throw \Illuminate\Validation\ValidationException::withMessages(['discount_value'=>'Discount cannot exceed the quotation subtotal.']);
        return $discount;
    }

    public function index(Request $request)
    {
        $type = $this->type($request); $n = $this->names($type);
        $documents = $this->scoped($request)->where('type', $type)->with('customer')->latest('id')->paginate(25);
        return view('commercial-documents.index', compact('documents', 'type', 'n'));
    }

    public function create(Request $request)
    {
        $type = $this->type($request); $n = $this->names($type); $company = $request->user()->company_id;
        $customers = Customer::where('company_id', $company)->where('customer_type', 'company')->where('approval_status', 'approved')->where('is_active', 1)->orderBy('name')->get();
        $products = Product::where('company_id', $company)->where('is_active', 1)->orderBy('name')->get();
        $quotationItems = QuotationItemMaster::where('company_id', $company)->where('is_active', 1)->orderBy('name')->get();
        $quotations = CommercialDocument::where('company_id', $company)->where('type', 'quotation')->where('status', 'approved')->whereNull('superseded_by_id')->with('customer')->latest()->get();
        $initialLines=[];
        return view('commercial-documents.form', compact('type', 'n', 'customers', 'products', 'quotationItems', 'quotations','initialLines'));
    }

    public function edit(Request $request,$document)
    {
        $this->assertQuotationAdmin($request);$type=$this->type($request);abort_unless($type==='quotation',404);$n=$this->names($type);$company=$request->user()->company_id;
        $document=$this->scoped($request,$document)->where('type','quotation')->where('status','draft')->with('lines')->firstOrFail();
        $customers=Customer::where('company_id',$company)->where('customer_type','company')->where('approval_status','approved')->where('is_active',1)->orderBy('name')->get();
        $products=Product::where('company_id',$company)->where('is_active',1)->orderBy('name')->get();
        $quotationItems=QuotationItemMaster::where('company_id',$company)->where('is_active',1)->orderBy('name')->get();$quotations=collect();
        $initialLines=$document->lines->map(fn($line)=>['product_id'=>$line->product_id,'quotation_item_master_id'=>$line->quotation_item_master_id,'description'=>$line->description,'sku'=>$line->sku,'unit'=>$line->unit,'quantity'=>$line->quantity,'unit_price'=>Pay::decimal($line->unit_price_cents)])->values()->all();
        return view('commercial-documents.form',compact('type','n','customers','products','quotationItems','quotations','document','initialLines'));
    }

    public function update(Request $request,$document)
    {
        $this->assertQuotationAdmin($request);$type=$this->type($request);abort_unless($type==='quotation',404);$company=(int)$request->user()->company_id;
        $document=$this->scoped($request,$document)->where('type','quotation')->where('status','draft')->firstOrFail();
        $request->merge(['lines'=>collect($request->input('lines',[]))->filter(fn($line)=>filled($line['description']??null))->values()->all()]);
        $data=$request->validate([
            'customer_id'=>['required',Rule::exists('safety_shop_customers','id')->where(fn($q)=>$q->where('company_id',$company)->where('customer_type','company')->where('approval_status','approved'))],
            'document_date'=>'required|date','valid_until'=>'required|date|after_or_equal:document_date','reference'=>'nullable|string|max:100','notes'=>'nullable|string|max:2000','discount_type'=>'nullable|in:fixed,percent','discount_value'=>'nullable|numeric|min:0|max:99999999','lines'=>'required|array|min:1|max:100',
            'lines.*.product_id'=>['nullable',Rule::exists('safety_shop_products','id')->where('company_id',$company)],'lines.*.quotation_item_master_id'=>['nullable',Rule::exists('quotation_item_masters','id')->where('company_id',$company)->where('is_active',1)],
            'lines.*.description'=>'required|string|max:255','lines.*.sku'=>'nullable|string|max:50','lines.*.unit'=>'required|string|max:20','lines.*.quantity'=>'required|numeric|min:0.01|max:999999','lines.*.unit_price'=>'required|numeric|min:0|max:99999999',
        ]);
        $data['lines']=$this->mergeRepeatedLines($data['lines'],$type);
        DB::transaction(function()use($document,$data,$company){$subtotal=0;$lines=[];foreach($data['lines'] as $line){$price=Pay::units($line['unit_price']);if(empty($line['product_id'])){$master=!empty($line['quotation_item_master_id'])?QuotationItemMaster::where('company_id',$company)->find($line['quotation_item_master_id']):null;if(!$master){$lookup=QuotationItemMaster::where('company_id',$company);filled($line['sku']??null)?$lookup->where('sku',$line['sku']):$lookup->where('name',$line['description'])->where('unit',$line['unit']);$master=$lookup->first();}$values=['name'=>$line['description'],'sku'=>$line['sku']??null,'unit'=>$line['unit'],'unit_price_cents'=>$price,'tax_rate_units'=>1500,'is_active'=>true];if($master)$master->update($values);else $master=QuotationItemMaster::create(['company_id'=>$company]+$values);$line['quotation_item_master_id']=$master->id;}$lineSubtotal=(int)round($line['quantity']*$price);$subtotal+=$lineSubtotal;$lines[]=$line+['unit_price_cents'=>$price,'subtotal_cents'=>$lineSubtotal,'tax_rate_units'=>1500,'tax_cents'=>(int)round($lineSubtotal*.15)];}$discount=$this->quotationDiscount($data,$subtotal);$tax=(int)round(($subtotal-$discount)*.15);$document->update(['customer_id'=>$data['customer_id'],'document_date'=>$data['document_date'],'valid_until'=>$data['valid_until'],'reference'=>$data['reference']??null,'notes'=>$data['notes']??null,'discount_type'=>$data['discount_type']??'fixed','discount_value'=>$data['discount_value']??0,'subtotal_cents'=>$subtotal,'discount_cents'=>$discount,'tax_cents'=>$tax,'total_cents'=>$subtotal-$discount+$tax]);$document->lines()->delete();foreach($lines as $line){unset($line['unit_price']);$document->lines()->create($line);}});
        ActivityLog::record('Updated quotation draft','#'.$document->id);return redirect()->route('quotations.show',$document)->with('success','Quotation draft updated.');
    }

    public function revise(Request $request,$document)
    {
        $this->assertQuotationAdmin($request);$this->type($request);
        $revision=DB::transaction(function()use($request,$document){$source=$this->scoped($request,$document)->where('type','quotation')->where('status','approved')->whereNull('superseded_by_id')->with('lines')->lockForUpdate()->firstOrFail();abort_if($source->revisions()->where('status','draft')->exists(),422,'An editable revision already exists.');$rootId=$source->revision_of_id?:$source->id;$next=(int)CommercialDocument::where(function($q)use($rootId){$q->whereKey($rootId)->orWhere('revision_of_id',$rootId);})->max('revision_number')+1;$copy=$source->replicate(['number','status','approved_by','approved_at','superseded_by_id','cancelled_by','cancelled_at','cancellation_reason']);$copy->number=null;$copy->status='draft';$copy->revision_of_id=$rootId;$copy->revision_number=$next;$copy->created_by=$request->user()->id;$copy->save();foreach($source->lines as $line){$copy->lines()->create($line->only(['product_id','quotation_item_master_id','description','sku','unit','quantity','unit_price_cents','subtotal_cents','tax_rate_units','tax_cents']));}return $copy;});
        ActivityLog::record('Created quotation revision','#'.$revision->id);return redirect()->route('quotations.edit',$revision)->with('success','Revision draft created. Review and approve it when ready.');
    }

    public function cancel(Request $request,$document)
    {
        $this->assertQuotationAdmin($request);$this->type($request);$data=$request->validate(['cancellation_reason'=>'required|string|min:5|max:1000']);
        $item=$this->scoped($request,$document)->where('type','quotation')->whereIn('status',['draft','approved'])->firstOrFail();abort_if($item->superseded_by_id,422,'A superseded quotation cannot be cancelled.');$item->update(['status'=>'cancelled','cancelled_by'=>$request->user()->id,'cancelled_at'=>now(),'cancellation_reason'=>$data['cancellation_reason']]);ActivityLog::record('Cancelled quotation',($item->number?:'#'.$item->id).' · '.$data['cancellation_reason']);return back()->with('success','Quotation cancelled with audit history.');
    }

    public function store(Request $request)
    {
        $type = $this->type($request); $company = (int) $request->user()->company_id;
        $request->merge(['lines' => collect($request->input('lines', []))->filter(fn ($line) => filled($line['description'] ?? null))->values()->all()]);
        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('safety_shop_customers', 'id')->where(fn ($q) => $q->where('company_id', $company)->where('customer_type', 'company')->where('approval_status', 'approved'))],
            'quotation_id' => ['nullable', Rule::exists('commercial_documents', 'id')->where(fn ($q) => $q->where('company_id', $company)->where('type', 'quotation')->where('status', 'approved')->whereNull('superseded_by_id'))],
            'document_date' => 'required|date', 'valid_until' => $type === 'quotation' ? 'required|date|after_or_equal:document_date' : 'nullable|date',
            'reference' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:2000', 'discount_type' => 'nullable|in:fixed,percent', 'discount_value' => 'nullable|numeric|min:0|max:99999999', 'lines' => 'required|array|min:1|max:100',
            'lines.*.product_id' => ['nullable', Rule::exists('safety_shop_products', 'id')->where('company_id', $company)],
            'lines.*.quotation_item_master_id' => ['nullable', Rule::exists('quotation_item_masters', 'id')->where('company_id', $company)->where('is_active', 1)],
            'lines.*.description' => 'required|string|max:255', 'lines.*.sku' => 'nullable|string|max:50',
            'lines.*.unit' => 'required|string|max:20', 'lines.*.quantity' => 'required|numeric|min:0.01|max:999999',
            'lines.*.unit_price' => 'nullable|numeric|min:0|max:99999999',
        ]);
        $data['lines']=$this->mergeRepeatedLines($data['lines'],$type);
        $document = DB::transaction(function () use ($request, $data, $type, $company) {
            $subtotal = 0; $lines = [];
            foreach ($data['lines'] as $line) {
                $price = $type === 'quotation' ? Pay::units($line['unit_price'] ?? 0) : 0;
                if ($type === 'quotation' && empty($line['product_id'])) {
                    $master = !empty($line['quotation_item_master_id']) ? QuotationItemMaster::where('company_id',$company)->find($line['quotation_item_master_id']) : null;
                    if (!$master) {
                        $lookup=QuotationItemMaster::where('company_id',$company);
                        filled($line['sku'] ?? null) ? $lookup->where('sku',$line['sku']) : $lookup->where('name',$line['description'])->where('unit',$line['unit']);
                        $master=$lookup->first();
                    }
                    $values=['name'=>$line['description'],'sku'=>$line['sku']??null,'unit'=>$line['unit'],'unit_price_cents'=>$price,'tax_rate_units'=>1500,'is_active'=>true];
                    if($master)$master->update($values);else $master=QuotationItemMaster::create(['company_id'=>$company]+$values);
                    $line['quotation_item_master_id']=$master->id;
                }
                $lineSubtotal = (int) round($line['quantity'] * $price); $tax = $type === 'quotation' ? (int) round($lineSubtotal * .15) : 0;
                $subtotal += $lineSubtotal;
                $lines[] = $line + ['unit_price_cents' => $price, 'subtotal_cents' => $lineSubtotal, 'tax_rate_units' => $type === 'quotation' ? 1500 : 0, 'tax_cents' => $tax];
            }
            $discount=$type==='quotation'?$this->quotationDiscount($data,$subtotal):0;
            $tax = $type==='quotation'?(int)round(($subtotal-$discount)*.15):0;
            $document = CommercialDocument::create(['company_id' => $company, 'type' => $type, 'customer_id' => $data['customer_id'], 'quotation_id' => $type === 'delivery_note' ? ($data['quotation_id'] ?? null) : null, 'document_date' => $data['document_date'], 'valid_until' => $type === 'quotation' ? $data['valid_until'] : null, 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null, 'discount_type'=>$type==='quotation'?($data['discount_type']??'fixed'):'fixed','discount_value'=>$type==='quotation'?($data['discount_value']??0):0,'subtotal_cents' => $subtotal, 'discount_cents'=>$discount,'tax_cents' => $tax, 'total_cents' => $subtotal-$discount+$tax, 'created_by' => $request->user()->id]);
            foreach ($lines as $line) { unset($line['unit_price']); $document->lines()->create($line); }
            ActivityLog::record('Created '.$type.' draft', '#'.$document->id);
            return $document;
        });
        return redirect()->route($this->names($type)[1].'.show', $document);
    }

    public function show(Request $request, $document)
    {
        $type = $this->type($request); $n = $this->names($type);
        $document = $this->scoped($request, $document)->where('type', $type)->with(['customer', 'lines', 'quotation', 'creator', 'approver','revisionOf','revisions','supersededBy','canceller','sales'])->firstOrFail();
        return view('commercial-documents.show', compact('document', 'type', 'n'));
    }

    public function approve(Request $request, $document)
    {
        $this->assertCompanyApprover($request);
        $type = $this->type($request);
        $document = DB::transaction(function () use ($request, $document, $type) {
            $item = $this->scoped($request, $document)->where('type', $type)->lockForUpdate()->firstOrFail();
            abort_unless($item->status === 'draft', 422);
            $previous=null;
            if($type==='quotation'&&$item->revision_of_id){$root=CommercialDocument::whereKey($item->revision_of_id)->firstOrFail();$base=preg_replace('/-R\d+$/','',$root->number);$number=$base.'-R'.$item->revision_number;$previous=CommercialDocument::where('company_id',$item->company_id)->where('type','quotation')->where('status','approved')->whereNull('superseded_by_id')->where(function($q)use($root){$q->whereKey($root->id)->orWhere('revision_of_id',$root->id);})->lockForUpdate()->first();}
            else $number = $type === 'quotation'
                ? $this->randomNumber($type, (int) $request->user()->company_id)
                : $this->names($type)[2].'-'.str_pad($item->id, 6, '0', STR_PAD_LEFT);
            $item->update(['number' => $number, 'status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
            if($previous&&$previous->id!==$item->id)$previous->update(['superseded_by_id'=>$item->id]);
            return $item;
        });
        ActivityLog::record('Approved '.$type, $document->number);
        return back()->with('success', $this->names($type)[0].' approved.');
    }

    public function pdf(Request $request, $document)
    {
        $type = $this->type($request);
        $doc = $this->scoped($request, $document)->where('type', $type)->with(['customer', 'lines', 'quotation', 'creator', 'approver'])->firstOrFail();
        abort_unless($doc->status === 'approved', 403);
        $receiptSettings = Documents::branding();
        $qr = Documents::qrDataUri($doc->number.'|'.$doc->customer->name.'|'.$doc->document_date->format('Y-m-d').'|'.Pay::decimal($doc->total_cents));
        return Documents::download(Documents::render('pdf.commercial-document', compact('doc', 'type', 'receiptSettings', 'qr'), 'A4', 'portrait', false, false, true), $doc->number.'.pdf');
    }
}
