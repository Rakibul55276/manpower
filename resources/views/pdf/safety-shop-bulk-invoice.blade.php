<!doctype html><html><head><meta charset="utf-8"><title>{{ $sale->invoice_number }}</title><style>@page{size:A4 portrait;margin:22pt 24pt 30pt}
*{box-sizing:border-box}
body{font-family:'DejaVu Sans',sans-serif;color:#24343b;font-size:8.5px;line-height:1.35;margin:0}
.rtl{direction:rtl}
table{width:100%;border-collapse:collapse}
.invoice-brand{border-bottom:2px solid #147d9a;margin-bottom:8px}
.invoice-brand td{padding:4px 5px;vertical-align:middle}
.invoice-brand .logo{width:64px}.invoice-brand img{width:54px;height:54px;object-fit:contain}
.invoice-brand .company{font-size:15px;font-weight:bold;color:#173c54}
.invoice-brand .tagline{font-size:7px;color:#62727b;max-width:280px}
.invoice-brand .identity{text-align:right;font-size:7.5px;line-height:1.5;width:165px}
.title{text-align:center;color:#147d9a;font-size:14px;font-weight:bold;letter-spacing:1px;margin:7px 0}
.grid,.details,.meta,.items,.settlement,.bottom{table-layout:fixed}
.grid td,.details td,.meta td,.items th,.items td,.bottom td,.bottom th{border:.6px solid #acbbc2;padding:4px;vertical-align:top;white-space:normal;word-wrap:break-word}
.grid td{font-size:8px}.num{text-align:right}.center{text-align:center}
.bar{background:#e8f2f5;color:#175c72;border:.6px solid #acbbc2;font-weight:bold;padding:4px 6px;page-break-after:avoid}
.details{font-size:8px}.details .key{width:14%;background:#f4f7f8;color:#586a73;font-weight:bold}.details .value{width:36%}
.details tr,.meta tr,.items tr{page-break-inside:avoid}
.meta{margin:6px 0}.meta td{text-align:center;width:25%;padding:5px 3px}
.label{display:block;font-size:6.5px;color:#63737c;margin-bottom:2px}
.items{margin:7px 0 0;font-size:8px}.items thead{display:table-header-group}
.items th{background:#edf3f5;color:#254957;text-align:center;font-size:6.5px;padding:5px 3px;vertical-align:middle}
.items td{padding:5px 3px;vertical-align:middle}.items td:nth-child(2){text-align:left}
.muted{font-size:6.5px;color:#687b84;margin-top:2px}
.last-page-block{page-break-inside:avoid;margin-top:0}
.invoice-closing-gap{border-left:.6px solid #acbbc2;border-right:.6px solid #acbbc2}
.invoice-end-items{text-align:center;line-height:12pt;font-size:8pt;color:#657985;border-left:.6pt solid #b8c5cc;border-right:.6pt solid #b8c5cc}
.settlement>tbody>tr>td{border:.6px solid #acbbc2;padding:6px;vertical-align:top;white-space:normal;word-wrap:break-word;font-size:8px}
.settlement-notes{width:32%}.settlement-words{width:30%}.settlement-calculation{width:38%;padding:0!important}
.settlement-calculation table{table-layout:fixed}.settlement-calculation th,.settlement-calculation td{padding:4px 5px;border-bottom:.5px solid #d5dfe3;font-size:7.5px}
.settlement-calculation th{width:65%;text-align:left;font-weight:normal}.settlement-calculation td{width:35%;text-align:right;font-weight:bold;white-space:nowrap}
.settlement-total th,.settlement-total td{background:#e8f2f5;color:#175c72;font-weight:bold}
.pdf-footer-wrap{margin-top:7px}.bottom{font-size:7px;page-break-inside:avoid}
.bottom thead th{background:#edf3f5;line-height:1.4;text-align:center;font-size:6.8px}
.bottom tbody th,.bank-key{font-weight:bold;color:#596e77;text-align:left}
.signature{text-align:center;vertical-align:bottom!important}.signature small{font-size:6.5px;color:#687b84}
.bottom .qr,.bottom .payment-status{vertical-align:middle!important;text-align:center}
.qr img{width:58px;height:58px}.qr{font-size:6px}
.received-attestation{display:inline-block;border:1.2px solid #244e6b;border-radius:3px;padding:5px 3px;color:#244e6b;font-size:7px;font-weight:bold;line-height:1.45}
.bottom tfoot td{border:0;text-align:center;font-size:7px;color:#63737c;padding-top:7px}
.page-break{page-break-after:always;height:0}
.continuation-brand{border-bottom:1.5px solid #147d9a;margin-bottom:8px}.continuation-brand td{padding:4px;vertical-align:middle}
.continuation-brand img{width:38px;height:38px;object-fit:contain}.continuation-brand .name{font-size:12px;font-weight:bold;color:#173c54}.continuation-brand .ref{text-align:right;font-size:7px}
.continuation-title{font-size:10px;color:#147d9a;font-weight:bold;margin:6px 0;text-align:center}
.pdf-watermark{position:fixed;z-index:-1;top:35%;left:25%;width:50%;opacity:.025}.pdf-watermark img{width:100%}
.void-mark{position:fixed;top:40%;left:10%;right:10%;text-align:center;font-size:48px;color:#c77;opacity:.4;transform:rotate(-20deg)}
.draft-mark{color:#35699e}

@page{size:A4 portrait;margin:26pt 28pt 54pt}
body{font-size:9pt;line-height:1.3}
.invoice-brand .company{font-size:16pt}.invoice-brand .tagline{font-size:7.5pt}.invoice-brand .identity{font-size:8pt}
.title{font-size:15pt;letter-spacing:.5pt}
.bar{font-size:8pt;padding:3pt 5pt}
.details{font-size:8pt}.details td{padding:3pt 4pt;line-height:1.3}
.grid td{font-size:8.5pt}.meta td{padding:4pt 3pt}.label{font-size:7pt}
.items{font-size:9pt}.items th{font-size:7pt;padding:4pt 2pt}.items td{padding:4pt 3pt;line-height:1.3}
.muted{font-size:7pt}.continuation-title{font-size:13pt;text-align:center}
.continuation-brand .name{font-size:14pt}.continuation-brand .ref{font-size:8pt}
.grid td,.details td,.meta td,.items th,.items td,.bottom td,.bottom th{border-color:#b8c5cc}
.invoice-totals{table-layout:fixed;border-collapse:collapse;margin:0;font-size:8pt}
.invoice-totals th,.invoice-totals td{width:20%;border:.6pt solid #b8c5cc;padding:4pt;text-align:right;white-space:normal;word-wrap:break-word}
.invoice-totals th{font-weight:normal;background:#f4f7f8;font-size:7.5pt}.invoice-totals td{font-size:9pt;font-weight:bold}
.invoice-totals .grand-total{background:#e8f2f5;color:#175c72;font-weight:bold}
.settlement>tbody>tr>td{font-size:8pt;padding:5pt;line-height:1.35;border-color:#b8c5cc}
.settlement-notes{width:40%}.settlement-words{width:60%}
.bottom{font-size:8pt}.bottom th,.bottom td{padding:3pt;line-height:1.25;word-wrap:break-word;white-space:normal}
.bottom thead th{font-size:7.5pt}.qr img{width:52pt;height:52pt}.qr{font-size:6.5pt}
.received-attestation{font-size:8pt;padding:4pt;line-height:1.3}
.bottom tfoot{display:table-row-group}.bottom tfoot td{font-size:7pt}
.invoice-contact-footer{position:fixed;bottom:-29pt;left:0;right:0;border-top:.5pt solid #b8c5cc;padding-top:4pt;font-size:7pt;color:#657985;text-align:center;line-height:1.3;word-wrap:break-word}
.pdf-watermark{position:fixed;z-index:-1;top:260pt;left:130pt;width:280pt;height:280pt;opacity:.07}
.pdf-watermark img{width:280pt;height:280pt;object-fit:contain}
.void-mark{font-size:62pt;z-index:10;border:3pt solid #c77;padding:12pt;opacity:.35}
.draft-mark{border-color:#35699e;color:#35699e;opacity:.3}
.items td{padding:3pt 3pt;line-height:1.1}.items .muted{font-size:7pt;line-height:1.1;margin-top:1pt}.items td.num{white-space:nowrap}.details td{padding:2.5pt 4pt}.meta td{padding:3pt 2pt}.bottom td{padding:2pt 3pt}.invoice-totals th,.invoice-totals td{padding:3pt}.settlement>tbody>tr>td{padding:4pt}.invoice-brand .tagline{max-width:none}.invoice-brand .identity{width:140pt}.invoice-brand .logo{width:48pt}.invoice-brand img{width:42pt;height:42pt}.details{font-size:7.8pt}.details td{padding:1.5pt 4pt;line-height:1.2}.bar{padding:2pt 5pt}.meta td{padding:2pt}.title{margin:4px 0}.invoice-brand img{width:34pt;height:34pt}.items td{padding:2pt 3pt}.bottom td{padding:1pt 3pt}.bottom thead th{padding:2pt;font-size:7pt}.invoice-totals th,.invoice-totals td{padding:2pt}.settlement>tbody>tr>td{padding:3pt}.pdf-footer-wrap{margin-top:4pt}</style></head><body>
@php
$ar=fn($text)=>\App\Modules\Invoicing\Services\InvoiceService::arabicPdf($text);
$customer=$sale->customerRecord;
$printedInvoiceNotes=mb_strlen((string)$sale->invoice_notes)>250 ? mb_substr((string)$sale->invoice_notes,0,247).'...' : ($sale->invoice_notes ?: '—');
$contact=$sale->customer_contact_person ?: optional($customer)->contact_person;
$phone=$sale->customer_phone ?: optional($customer)->phone;
$email=$sale->customer_email ?: optional($customer)->email;
$vat=$sale->customer_vat_number ?: optional($customer)->vat_number;
$cr=$sale->customer_commercial_registration ?: optional($customer)->commercial_registration;
$address=$sale->customer_address ?: collect([optional($customer)->address,optional($customer)->building_number ? 'Building '.optional($customer)->building_number : null,optional($customer)->street,optional($customer)->district,optional($customer)->city,optional($customer)->postal_code,optional($customer)->country_code])->filter()->unique()->implode(', ');
if ($email && preg_match('/(?:\.test|\.example)$/i', $email)) $email=null; if (in_array(trim($address),['SA','Saudi Arabia'],true)) $address=null;
$showPaymentStamp=$sale->status==='finalized' && in_array($sale->payment_status,['partially_paid','paid'],true);
@endphp
<table class="invoice-brand"><tr>@if($documentBrandLogo)<td class="logo"><img src="{{ $documentBrandLogo }}"></td>@endif<td><div class="company">{{ strtoupper($receiptSettings->company_name) }}</div><div class="tagline">{{ $receiptSettings->tagline ?: 'Safety equipment and stock sales' }}</div></td><td class="identity">@if($receiptSettings->company_name_ar)<strong class="rtl">{{ $ar($receiptSettings->company_name_ar) }}</strong><br>@endif @if($receiptSettings->tagline_ar)<span class="rtl">{{ $ar($receiptSettings->tagline_ar) }}</span><br>@endif @if($receiptSettings->commercial_registration)CR: {{ $receiptSettings->commercial_registration }}<br>@endif @if($receiptSettings->vat_number)VAT: {{ $receiptSettings->vat_number }}@endif</td></tr></table>
@if($documentBrandLogo)<div class="pdf-watermark"><img src="{{ $documentBrandLogo }}"></div>@endif
@if($sale->status==='void')<div class="void-mark">VOID / {{ $ar('ملغاة') }}</div>@endif
@if($sale->status==='draft')<div class="void-mark draft-mark">DRAFT / {{ $ar('مسودة') }}</div>@endif
<div class="title">VAT INVOICE / {{ $ar('فاتورة ضريبية') }}</div>
<table class="grid"><tr><td><strong>Invoice No:</strong> {{ $sale->invoice_number }}</td><td class="num"><strong>{{ $ar('رقم الفاتورة') }}:</strong> {{ $sale->invoice_number }}</td></tr></table>
<div class="bar">Our Details / {{ $ar('تفاصيلنا') }}</div>
<table class="details"><tr><td class="key">Name</td><td class="value">{{ $receiptSettings->company_name }}</td><td class="key">{{ $ar('الاسم') }}</td><td class="value rtl">{{ $receiptSettings->company_name_ar ? $ar($receiptSettings->company_name_ar) : $receiptSettings->company_name }}</td></tr><tr><td class="key">Address</td><td class="value">{{ collect([$receiptSettings->address,$receiptSettings->city,$receiptSettings->postal_code])->filter()->implode(', ') }}</td><td class="key">Contact Number</td><td class="value">{{ $receiptSettings->phone ?: '—' }}</td></tr><tr><td class="key">VAT Number</td><td class="value">{{ $receiptSettings->vat_number ?: '—' }}</td><td class="key">CRN</td><td class="value">{{ $receiptSettings->commercial_registration ?: '—' }}</td></tr></table>
<div class="bar">Client Details / {{ $ar('تفاصيل العميل') }}</div>
<table class="details"><tr><td class="key">Name</td><td class="value">{{ $sale->customer }}</td><td class="key">{{ $ar('اسم العميل') }}</td><td class="value rtl">{{ $sale->customer_name_ar ? $ar($sale->customer_name_ar) : $sale->customer }}</td></tr><tr><td class="key">Contact</td><td class="value">{{ $contact ?: '—' }}</td><td class="key">Mobile</td><td class="value">{{ $phone ?: '—' }}</td></tr>@if($address || $email)<tr><td class="key">Address</td><td class="value">{{ $address ?: '—' }}</td><td class="key">Email</td><td class="value">{{ $email ?: '—' }}</td></tr>@endif<tr><td class="key">VAT Number</td><td class="value">{{ $vat ?: '—' }}</td><td class="key">CRN</td><td class="value">{{ $cr ?: '—' }}</td></tr></table>
<table class="meta"><tr><td><span class="label">Invoice Date / {{ $ar('تاريخ الفاتورة') }}</span><strong>{{ $sale->created_at->format('d-m-Y') }}</strong></td><td><span class="label">Supply Date / {{ $ar('تاريخ التوريد') }}</span><strong>{{ $sale->created_at->format('d-m-Y') }}</strong></td><td><span class="label">Branch / {{ $ar('الفرع') }}</span><strong>{{ $sale->location->name }}</strong></td><td><span class="label">Payment Status / {{ $ar('حالة الدفع') }}</span><strong>{{ $sale->payment_status==='paid' ? 'Paid / '.$ar('مدفوع') : ($sale->payment_status==='partially_paid' ? 'Partially paid / '.$ar('مدفوع جزئياً') : 'Unpaid / '.$ar('آجل')) }}</strong></td></tr></table>
@php $linePages=$invoiceLinePages ?? collect([$sale->lines->values()]); $lineNumber=0; @endphp
@foreach($linePages as $pageLines)
@if(!$loop->first)<table class="continuation-brand"><tr>@if($documentBrandLogo)<td style="width:52px"><img src="{{ $documentBrandLogo }}"></td>@endif<td class="name">{{ strtoupper($receiptSettings->company_name) }}</td><td class="ref">@if($receiptSettings->company_name_ar)<strong class="rtl">{{ $ar($receiptSettings->company_name_ar) }}</strong><br>@endif VAT INVOICE<br>{{ $sale->invoice_number }}<br>Page {{ $loop->iteration }} of {{ $linePages->count() }}</td></tr></table><div class="continuation-title">VAT INVOICE / {{ $ar('فاتورة ضريبية') }}</div>@endif
<table class="items"><colgroup><col style="width:4%"><col style="width:28%"><col style="width:7%"><col style="width:6%"><col style="width:10%"><col style="width:12%"><col style="width:7%"><col style="width:11%"><col style="width:15%"></colgroup><thead class="invoice-items-heading"><tr><th style="width:4%">#</th><th style="width:28%">Nature of Goods / Services<br>{{ $ar('طبيعة السلع / الخدمات') }}</th><th style="width:7%">Unit<br>{{ $ar('الوحدة') }}</th><th style="width:6%">Qty<br>{{ $ar('الكمية') }}</th><th style="width:10%">Unit Rate<br>{{ $ar('سعر الوحدة') }}</th><th style="width:12%">Taxable Value<br>{{ $ar('المبلغ الخاضع') }}</th><th style="width:7%">VAT %</th><th style="width:11%">VAT Amount</th><th style="width:15%">Total incl. VAT</th></tr></thead><tbody>@foreach($pageLines as $line)@php $lineTaxable=$line->total_cents; $lineTax=(int)round($lineTaxable*$sale->tax_rate_units/10000); $lineNumber++; @endphp<tr data-invoice-row="{{ $lineNumber-1 }}"><td class="center">{{ $lineNumber }}</td><td><strong>{{ $line->name }}</strong><div class="muted">SKU {{ $line->sku }} {{-- Barcode intentionally omitted from customer invoices. --}}</div></td><td class="center">{{ $line->unit }}</td><td class="num">{{ number_format($line->quantity) }}</td><td class="num">{{ \App\Services\Pay::money($line->price_cents) }}</td><td class="num">{{ \App\Services\Pay::money($lineTaxable) }}</td><td class="num">{{ number_format($sale->tax_rate_units/100,2) }}%</td><td class="num">{{ \App\Services\Pay::money($lineTax) }}</td><td class="num">{{ \App\Services\Pay::money($lineTaxable+$lineTax) }}</td></tr>@endforeach</tbody></table>
@if(!$loop->last)<div class="page-break"></div>@endif
@endforeach
<div class="last-page-block {{ $linePages->count()>1 ? 'is-multipage' : '' }}">
<div class="invoice-end-items">***** {{ $ar('لا شيء يتبع') }} *****</div>
@if(!empty($invoiceClosingSpacer))<div class="invoice-closing-gap" style="height:{{ $invoiceClosingSpacer }}pt"></div>@endif

<table class="invoice-totals"><tr><th>Subtotal</th><th>Discount</th><th>Total excl. VAT</th><th>VAT amount</th><th class="grand-total">Total SAR</th></tr><tr><td>{{ \App\Services\Pay::money($sale->subtotal_cents) }}</td><td>{{ \App\Services\Pay::money($sale->discount_cents) }}</td><td>{{ \App\Services\Pay::money($sale->taxable_cents) }}</td><td>{{ \App\Services\Pay::money($sale->tax_cents) }}</td><td class="grand-total">{{ \App\Services\Pay::money($sale->total_cents) }}</td></tr></table>
<table class="settlement">
@if(mb_strlen((string)$sale->invoice_notes)>250)
<tr><td colspan="2"><strong>Notes / {{ $ar('ملاحظات') }}</strong><br>{!! nl2br(e($printedInvoiceNotes)) !!}</td></tr><tr><td colspan="2"><strong>Amount in words / {{ $ar('المبلغ كتابة') }}</strong><br>{{ ucfirst(\App\Services\Pay::moneyWords($sale->total_cents)) }}<br><span class="muted">Paid: SAR {{ \App\Services\Pay::money($sale->paid_cents) }} · Balance due: SAR {{ \App\Services\Pay::money($sale->balance_due_cents) }}</span></td></tr>
@else
<tr><td class="settlement-notes"><strong>Notes / {{ $ar('ملاحظات') }}</strong><br>{!! nl2br(e($printedInvoiceNotes)) !!}</td><td class="settlement-words"><strong>Amount in words / {{ $ar('المبلغ كتابة') }}</strong><br>{{ ucfirst(\App\Services\Pay::moneyWords($sale->total_cents)) }}<br><span class="muted">Paid: SAR {{ \App\Services\Pay::money($sale->paid_cents) }} · Balance due: SAR {{ \App\Services\Pay::money($sale->balance_due_cents) }}</span></td></tr>
@endif
</table>
<div class="pdf-footer-wrap"><table class="bottom"><colgroup><col style="width:13%"><col style="width:17%"><col style="width:19%"><col style="width:19%"><col style="width:13%">@if($showPaymentStamp)<col style="width:19%">@endif</colgroup><thead><tr><th colspan="2">Bank Details<br>{{ $ar('تفاصيل البنك') }}</th><th>Prepared By<br>{{ $ar('أعدت بواسطة') }}</th><th>Received By<br>{{ $ar('استلمت بواسطة') }}</th><th>QR</th>@if($showPaymentStamp)<th>Payment Status<br>{{ $ar('حالة الدفع') }}</th>@endif</tr></thead><tbody><tr><td class="bank-key">Account name</td><td>{{ $receiptSettings->bank_account_name ?: '—' }}</td><td rowspan="5" class="signature">{{ $sale->creator->name }}<br><br>Signature with stamp</td><td rowspan="5" class="signature">Signature with stamp</td><td rowspan="5" class="qr"><img src="{{ $qr }}"><br>Scan to verify / {{ $ar('امسح للتحقق') }}</td>@if($showPaymentStamp)<td rowspan="5" class="signature payment-status"><span class="received-attestation">{{ $sale->payment_status==='paid' ? 'PAYMENT RECEIVED' : 'PARTIALLY PAID' }} /<br>{{ $ar($sale->payment_status==='paid' ? 'تم استلام المبلغ' : 'مدفوع جزئياً') }}</span></td>@endif</tr><tr><td class="bank-key">Bank</td><td>{{ $receiptSettings->bank_name ?: '—' }}</td></tr><tr><td class="bank-key">Account no.</td><td>{{ $receiptSettings->bank_account_number ?: '—' }}</td></tr><tr><td class="bank-key">IBAN</td><td>{{ $receiptSettings->bank_iban ?: '—' }}</td></tr><tr><td class="bank-key">Branch</td><td>{{ $receiptSettings->bank_branch ?: '—' }}</td></tr></tbody><tfoot><tr><td colspan="{{ $showPaymentStamp ? 6 : 5 }}">Think Safe. Work Safe. Go Home Safe</td></tr></tfoot></table></div>
</div>
<div class="invoice-contact-footer">{{ $receiptSettings->company_name }} @if($receiptSettings->phone) · {{ $receiptSettings->phone }} @endif @if($receiptSettings->email && !preg_match('/(?:\.test|\.example)$/i',$receiptSettings->email)) · {{ $receiptSettings->email }} @endif @if($receiptSettings->website && !preg_match('/(?:\.test|\.example)(?:\/|$)/i',$receiptSettings->website)) · {{ $receiptSettings->website }} @endif</div></body></html>
