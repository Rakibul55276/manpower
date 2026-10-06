@extends('layouts.app')
@section('title','Process customer return')
@section('eyebrow','Safety shop · Receipt-linked return')
@section('description','Scan or enter the original receipt, verify returned products, and restore stock with a complete audit trail.')
@section('content')
@include('safety-shop.shared.nav')
<div class="card"><div class="card-body">
<div class="form-grid">
<div class="field"><label for="return-receipt">Original receipt number *</label><input id="return-receipt" placeholder="Scan or enter SALE-123" autocomplete="off"><small>The receipt identifies the customer, location, and products sold.</small></div>
<div class="field"><label>&nbsp;</label><button class="btn" id="return-lookup" type="button">Find receipt</button></div>
</div>
<div id="return-message" class="notice error" role="alert" hidden></div>
</div></div>

<form method="POST" action="{{ route('safety-shop.returns.store') }}" id="return-form" hidden>@csrf
<input type="hidden" name="request_key" value="{{ $requestKey }}"><input type="hidden" name="sale_id" id="return-sale-id">
<div class="card"><div class="card-body">
<div class="form-grid"><div class="field"><label>Customer</label><strong id="return-customer"></strong><small id="return-phone"></small></div><div class="field"><label>Original sale</label><strong id="return-sale-meta"></strong><small id="return-location"></small></div></div>
<div class="field"><label for="return-barcode">Scan returned product barcode</label><input id="return-barcode" autocomplete="off" placeholder="Scan barcode to add one unit"><small>Barcode verifies the product against the original receipt. You may also enter quantities below.</small></div>
</div></div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>Return</th><th>Product</th><th>Barcode</th><th>Sold</th><th>Previously returned</th><th>Returnable</th><th>Quantity now</th></tr></thead><tbody id="return-lines"></tbody></table></div></div>
<div class="card"><div class="card-body"><div class="form-grid">
<div class="field"><label for="refund_method">Refund method *</label><select id="refund_method" name="refund_method" required><option value="cash">Cash</option><option value="card">Card reversal</option><option value="bank">Bank transfer</option><option value="store_credit">Store credit</option></select></div>
<div class="field"><label for="reason">Return reason *</label><textarea id="reason" name="reason" maxlength="500" required placeholder="Condition, reason, and authorization details">{{ old('reason') }}</textarea></div>
</div><div class="form-footer"><button class="btn" type="submit">Post return and restore stock</button><a class="btn secondary" href="{{ route('safety-shop.returns.index') }}">Cancel</a></div></div></div>
</form>
<script>
(() => {
 const receipt=document.getElementById('return-receipt'), lookup=document.getElementById('return-lookup'), form=document.getElementById('return-form'), message=document.getElementById('return-message'), body=document.getElementById('return-lines'), barcode=document.getElementById('return-barcode');
 let lines=[];
 const fail=text=>{message.textContent=text;message.hidden=false;form.hidden=true;};
 const load=async()=>{message.hidden=true; const value=receipt.value.trim(); if(!value)return fail('Enter or scan the original receipt number.'); lookup.disabled=true;
  try { const response=await fetch(@json(route('safety-shop.returns.lookup'))+'?receipt='+encodeURIComponent(value),{headers:{'Accept':'application/json'}}); if(!response.ok)throw new Error(response.status===404?'Receipt not found.':'Unable to load this receipt.'); const sale=await response.json();
   lines=sale.lines; document.getElementById('return-sale-id').value=sale.id; document.getElementById('return-customer').textContent=sale.customer; document.getElementById('return-phone').textContent=sale.customer_phone||'No mobile recorded'; document.getElementById('return-sale-meta').textContent=sale.receipt+' · '+sale.date; document.getElementById('return-location').textContent=sale.location;
   body.innerHTML=lines.map((line,i)=>`<tr data-index="${i}"><td><input type="checkbox" class="return-check" ${line.returnable?'':'disabled'} aria-label="Return ${escapeHtml(line.name)}"></td><td><strong>${escapeHtml(line.sku)} · ${escapeHtml(line.name)}</strong><span class="sub">${escapeHtml(line.unit)}</span><input type="hidden" name="lines[${i}][sale_line_id]" value="${line.id}"></td><td>${escapeHtml(line.barcode||'—')}<input type="hidden" name="lines[${i}][barcode]" value=""></td><td>${line.sold}</td><td>${line.returned}</td><td>${line.returnable}</td><td><input class="return-quantity" name="lines[${i}][quantity]" type="number" min="0" max="${line.returnable}" value="0" ${line.returnable?'':'disabled'} style="max-width:110px"></td></tr>`).join('');
   body.querySelectorAll('.return-check').forEach((check,i)=>check.addEventListener('change',()=>{const q=body.querySelectorAll('.return-quantity')[i];q.value=check.checked?Math.max(1,+q.value):0;})); form.hidden=false; barcode.value=''; barcode.focus();
  } catch(error){fail(error.message);} finally{lookup.disabled=false;}
 };
 const escapeHtml=value=>String(value).replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
 lookup.addEventListener('click',load); receipt.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();load();}});
 barcode.addEventListener('keydown',event=>{if(event.key!=='Enter')return;event.preventDefault();const value=barcode.value.trim();const index=lines.findIndex(line=>String(line.barcode||'')===value);if(index<0){message.textContent='This barcode is not on the selected receipt.';message.hidden=false;return;}const row=body.querySelector(`tr[data-index="${index}"]`),q=row.querySelector('.return-quantity'),check=row.querySelector('.return-check');if(!lines[index].returnable){message.textContent='This item has already been fully returned.';message.hidden=false;return;}q.value=Math.min(lines[index].returnable,(+q.value||0)+1);check.checked=true;row.querySelector(`input[name="lines[${index}][barcode]"]`).value=value;message.hidden=true;barcode.value='';});
 form.addEventListener('submit',event=>{const total=[...body.querySelectorAll('.return-quantity')].reduce((sum,input)=>sum+(+input.value||0),0);if(!total){event.preventDefault();fail('Select at least one item and enter its return quantity.');form.hidden=false;}});
 @if(old('sale_id')) receipt.value='SALE-{{ (int)old('sale_id') }}'; load(); @endif
})();
</script>
@endsection
