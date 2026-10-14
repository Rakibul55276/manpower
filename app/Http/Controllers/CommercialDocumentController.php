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
        $quotations = CommercialDocument::where('company_id', $company)->where('type', 'quotation')->where('status', 'approved')->with('customer')->latest()->get();
        return view('commercial-documents.form', compact('type', 'n', 'customers', 'products', 'quotationItems', 'quotations'));
    }

    public function store(Request $request)
    {
        $type = $this->type($request); $company = (int) $request->user()->company_id;
        $request->merge(['lines' => collect($request->input('lines', []))->filter(fn ($line) => filled($line['description'] ?? null))->values()->all()]);
        $data = $request->validate([
            'customer_id' => ['required', Rule::exists('safety_shop_customers', 'id')->where(fn ($q) => $q->where('company_id', $company)->where('customer_type', 'company')->where('approval_status', 'approved'))],
            'quotation_id' => ['nullable', Rule::exists('commercial_documents', 'id')->where(fn ($q) => $q->where('company_id', $company)->where('type', 'quotation')->where('status', 'approved'))],
            'document_date' => 'required|date', 'valid_until' => $type === 'quotation' ? 'required|date|after_or_equal:document_date' : 'nullable|date',
            'reference' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:2000', 'lines' => 'required|array|min:1|max:100',
            'lines.*.product_id' => ['nullable', Rule::exists('safety_shop_products', 'id')->where('company_id', $company)],
            'lines.*.quotation_item_master_id' => ['nullable', Rule::exists('quotation_item_masters', 'id')->where('company_id', $company)->where('is_active', 1)],
            'lines.*.description' => 'required|string|max:255', 'lines.*.sku' => 'nullable|string|max:50',
            'lines.*.unit' => 'required|string|max:20', 'lines.*.quantity' => 'required|numeric|min:0.01|max:999999',
            'lines.*.unit_price' => 'nullable|numeric|min:0|max:99999999',
        ]);
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
            $tax = array_sum(array_column($lines, 'tax_cents'));
            $document = CommercialDocument::create(['company_id' => $company, 'type' => $type, 'customer_id' => $data['customer_id'], 'quotation_id' => $type === 'delivery_note' ? ($data['quotation_id'] ?? null) : null, 'document_date' => $data['document_date'], 'valid_until' => $type === 'quotation' ? $data['valid_until'] : null, 'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null, 'subtotal_cents' => $subtotal, 'tax_cents' => $tax, 'total_cents' => $subtotal + $tax, 'created_by' => $request->user()->id]);
            foreach ($lines as $line) { unset($line['unit_price']); $document->lines()->create($line); }
            ActivityLog::record('Created '.$type.' draft', '#'.$document->id);
            return $document;
        });
        return redirect()->route($this->names($type)[1].'.show', $document);
    }

    public function show(Request $request, $document)
    {
        $type = $this->type($request); $n = $this->names($type);
        $document = $this->scoped($request, $document)->where('type', $type)->with(['customer', 'lines', 'quotation', 'creator', 'approver'])->firstOrFail();
        return view('commercial-documents.show', compact('document', 'type', 'n'));
    }

    public function approve(Request $request, $document)
    {
        $type = $this->type($request);
        $document = DB::transaction(function () use ($request, $document, $type) {
            $item = $this->scoped($request, $document)->where('type', $type)->lockForUpdate()->firstOrFail();
            abort_unless($item->status === 'draft', 422);
            $number = $type === 'quotation'
                ? $this->randomNumber($type, (int) $request->user()->company_id)
                : $this->names($type)[2].'-'.str_pad($item->id, 6, '0', STR_PAD_LEFT);
            $item->update(['number' => $number, 'status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
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
