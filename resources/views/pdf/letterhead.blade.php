<style>
@page { margin-top: 102pt; margin-bottom: 74pt; }
.document-coded-header { position: fixed; top: -91pt; left: 0; right: 0; height: 76pt; border-bottom: 2.5pt solid #1d6763; color: #17373b; }
.document-coded-header table { width: 100%; border-collapse: collapse; }
.document-coded-header td { padding: 7pt 0; vertical-align: middle; }
.document-coded-logo { width: 58pt; height: 48pt; object-fit: contain; margin-right: 9pt; }
.document-coded-name { font-family: 'DejaVu Sans', sans-serif; font-size: 17pt; font-weight: bold; letter-spacing: .3pt; }
.document-coded-tagline { display: block; margin-top: 2pt; color: #65797b; font-size: 8pt; letter-spacing: .7pt; text-transform: uppercase; }
.document-coded-identity { text-align: right; color: #516b6d; font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; line-height: 1.55; }
.document-coded-footer { position: fixed; bottom: -62pt; left: 0; right: 0; height: 48pt; border-top: 2pt solid #d9a323; background: #f2f6f5; color: #50696b; font-family: 'DejaVu Sans', sans-serif; font-size: 8pt; padding: 8pt 11pt; }
.document-coded-footer table { width: 100%; border-collapse: collapse; }
.document-coded-footer td { vertical-align: middle; }
.document-coded-footer .right { text-align: right; }
.document-body-watermark { position: fixed; top: 29%; left: 20%; width: 60%; height: 42%; text-align: center; opacity: .055; overflow: hidden; z-index: -1; }
.document-body-watermark img { width: 100%; height: 100%; object-fit: contain; }
</style>
<div class="document-coded-header"><table><tr><td>@if($documentBrandLogo)<img class="document-coded-logo" src="{{ $documentBrandLogo }}">@endif<span class="document-coded-name">{{ $documentBrand->company_name }}</span><span class="document-coded-tagline">{{ $documentBrand->tagline ?: 'Professional business document' }}</span></td><td class="document-coded-identity">@if($documentBrand->commercial_registration)CR: {{ $documentBrand->commercial_registration }}<br>@endif @if($documentBrand->vat_number)VAT: {{ $documentBrand->vat_number }}<br>@endif {{ $documentBrand->phone }} @if($documentBrand->email)<br>{{ $documentBrand->email }}@endif</td></tr></table></div>
@if($documentBrandWatermark)<div class="document-body-watermark"><img src="{{ $documentBrandWatermark }}"></div>@endif
<div class="document-coded-footer"><table><tr><td><strong>{{ $documentBrand->company_name }}</strong><br>{{ $documentBrand->address }} @if($documentBrand->city) · {{ $documentBrand->city }}@endif @if($documentBrand->postal_code) {{ $documentBrand->postal_code }}@endif</td><td class="right">{{ $documentBrand->website }}<br>{{ $documentBrand->footer_text }}</td></tr></table></div>
