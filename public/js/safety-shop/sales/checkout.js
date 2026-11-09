(() => {
    'use strict';
    const print = document.getElementById('shop-print-receipt');
    if (print) print.addEventListener('click', () => window.print());
    const form = document.getElementById('shop-checkout');
    if (!form) return;
    const location = document.getElementById('checkout-location');
    const savedCustomer = document.getElementById('checkout-saved-customer');
    const newCustomerToggle = document.getElementById('checkout-new-customer-toggle');
    const customerDialog = document.getElementById('checkout-customer-dialog');
    const customerQuickForm = document.getElementById('checkout-customer-form');
    const productDialog = document.getElementById('checkout-product-dialog');
    const productQuickForm = document.getElementById('checkout-product-form');
    const newProduct = document.getElementById('checkout-new-product');
    const customerType = document.getElementById('checkout-customer-type');
    const customerName = document.getElementById('checkout-customer');
    const customerContact = document.getElementById('checkout-customer-contact');
    const customerPhone = document.getElementById('checkout-customer-phone');
    const customerEmail = document.getElementById('checkout-customer-email');
    const customerAddress = document.getElementById('checkout-customer-address');
    const customerVat = document.getElementById('checkout-customer-vat');
    const customerCr = document.getElementById('checkout-customer-cr');
    const customerCountry = document.getElementById('checkout-customer-country');
    const barcode = document.getElementById('checkout-barcode');
    const body = document.getElementById('checkout-lines');
    const discount = document.getElementById('checkout-discount');
    const cash = document.getElementById('checkout-cash');
    const card = document.getElementById('checkout-card');
    const bank = document.getElementById('checkout-bank');
    const paymentRows = document.getElementById('checkout-payment-rows');
    const addPayment = document.getElementById('checkout-add-payment');
    const submit = document.getElementById('checkout-submit');
    const saveDraft = document.getElementById('checkout-save-draft');
    const message = document.getElementById('checkout-message');
    const add = document.getElementById('checkout-add');
    const fromQuotation = document.getElementById('checkout-from-quotation');
    const quotationPanel = document.getElementById('checkout-quotation-panel');
    const quotationSelect = document.getElementById('checkout-quotation');
    const customerPo = document.getElementById('checkout-customer-po');
    const invoiceNotes = document.getElementById('checkout-invoice-notes');
    const catalogGrid = document.getElementById('catalog-grid');
    const catalogCount = document.getElementById('catalog-count');
    const catalogProducts = Array.from(document.querySelectorAll('.catalog-product'));
    const catalogTabs = Array.from(document.querySelectorAll('.catalog-tab'));
    const catalogTabsBar = document.querySelector('.catalog-tabs');
    const cart = new Map();
    let busy = false;
    let posting = false;
    let scanTimer = null;
    let barcodeInputStartedAt = 0;
    let previousBarcodeLength = 0;
    let recoveryItem = null;
    const paymentEntries = [];
    const paymentLabels = {cash: 'Cash', card: 'Card', bank: 'Bank transfer'};
    const productPlaceholderNode=document.getElementById('checkout-product-placeholder');
    if(productPlaceholderNode){try{const source=JSON.parse(productPlaceholderNode.textContent);catalogProducts.forEach(button=>{const placeholder=button.querySelector('.catalog-product-icon');if(!placeholder)return;const image=document.createElement('img');image.className='catalog-product-image';image.src=source;image.alt='';placeholder.replaceWith(image);});}catch(error){/* Text fallback remains available. */}}
    const productImagesNode=document.getElementById('checkout-product-images');
    if(productImagesNode){try{const images=JSON.parse(productImagesNode.textContent);catalogProducts.forEach(button=>{const source=images[button.dataset.id];if(!source)return;const image=button.querySelector('.catalog-product-image');if(image){image.src=source;image.loading='lazy';image.dataset.imagePreview='';image.alt=button.dataset.name||'Product image';}});}catch(error){/* Shared placeholder remains available. */}}
    const productSuggestionsNode=document.getElementById('checkout-product-suggestions');
    if(productSuggestionsNode&&productQuickForm){
        try{const suggestions=JSON.parse(productSuggestionsNode.textContent);['brand','size','variant','safety_standard'].forEach(name=>{const field=productQuickForm.elements[name];if(!field)return;const list=document.createElement('datalist');list.id=`checkout-${name.replace('_','-')}-options`;(suggestions[name]||[]).forEach(value=>list.append(new Option(value)));field.setAttribute('list',list.id);field.setAttribute('autocomplete','off');productQuickForm.append(list);});}catch(error){/* Inputs remain usable when suggestions cannot load. */}
    }
    let catalogFilter = 'all';
    let catalogFilterEmpty = null;
    if (catalogTabsBar) {
        const outOfStockTab=document.createElement('button');outOfStockTab.type='button';outOfStockTab.className='catalog-tab';outOfStockTab.dataset.catalogFilter='out';outOfStockTab.textContent='Out of stock';
        const firstCategory=catalogTabsBar.querySelector('[data-catalog-filter^="category-"]');
        catalogTabsBar.insertBefore(outOfStockTab,firstCategory);catalogTabs.splice(2,0,outOfStockTab);
    }
    if (catalogTabsBar) {
        const nav=document.createElement('div');nav.className='catalog-nav';
        const previous=document.createElement('button');previous.type='button';previous.className='catalog-nav-button';previous.textContent='‹';previous.setAttribute('aria-label','Previous product categories');
        const next=document.createElement('button');next.type='button';next.className='catalog-nav-button';next.textContent='›';next.setAttribute('aria-label','Next product categories');
        catalogTabsBar.before(nav);nav.append(previous,catalogTabsBar,next);
        previous.addEventListener('click',()=>catalogTabsBar.scrollBy({left:-240,behavior:'smooth'}));
        next.addEventListener('click',()=>catalogTabsBar.scrollBy({left:240,behavior:'smooth'}));
    }
    const catalogItem = button => ({id:Number(button.dataset.id),sku:button.dataset.sku,name:button.dataset.name,barcode:button.dataset.barcode,unit:button.dataset.unit,price_cents:Number(button.dataset.priceCents),price_includes_vat:button.dataset.priceIncludesVat==='1',tax_category:button.dataset.taxCategory,tax_rate_units:Number(button.dataset.taxRateUnits),available:Number(button.dataset.available)});
    function filterCatalog() {
        const term=barcode.value.trim().toLowerCase();let visible=0;
        catalogProducts.forEach(button=>{const item=catalogItem(button);const matchesText=!term||(button.dataset.search||'').includes(term);const matchesTab=catalogFilter==='all'||(catalogFilter==='stock'&&item.available>0)||(catalogFilter==='out'&&item.available<1)||button.dataset.category===catalogFilter;button.hidden=!(matchesText&&matchesTab);if(!button.hidden)visible++;});
        if(catalogCount)catalogCount.textContent=`${visible} item${visible===1?'':'s'}`;
        if(!catalogFilterEmpty&&catalogGrid){catalogFilterEmpty=document.createElement('div');catalogFilterEmpty.className='catalog-filter-empty';catalogGrid.append(catalogFilterEmpty);}
        if(catalogFilterEmpty)catalogFilterEmpty.textContent=catalogFilter==='out'?'No out-of-stock products at this shop.':'No products in this category at the shop location.';
        if(catalogFilterEmpty)catalogFilterEmpty.hidden=visible>0;
    }
    catalogTabs.forEach(tab=>tab.addEventListener('click',()=>{catalogTabs.forEach(node=>node.classList.remove('active'));tab.classList.add('active');catalogFilter=tab.dataset.catalogFilter;barcode.value='';filterCatalog();}));
    catalogProducts.forEach(button => {
        button.disabled = false;
        button.addEventListener('click', () => {
            const item = catalogItem(button);
            if (item.available < 1) {
                recoveryItem = item; barcode.value = item.barcode;
                newProduct.textContent = '+ Add stock for this product'; newProduct.hidden = false; newProduct.click(); return;
            }
            const previous = cart.get(item.id); const quantity = previous ? previous.quantity + 1 : 1;
            if (quantity > item.available) { message.textContent = `Only ${item.available} ${item.unit} available.`; return; }
            cart.set(item.id, {...item, quantity}); render();
            message.textContent = `${item.name} added. ${quantity} ${item.unit} in cart.`; barcode.value = ''; filterCatalog();
        });
    });
    const syncCustomerType = () => {
        const company = customerType.value === 'company';
        document.querySelectorAll('.checkout-company-field').forEach(field => field.hidden = !company);
        document.querySelectorAll('[data-checkout-company-label]').forEach(label => label.hidden = !company);
        document.querySelectorAll('[data-checkout-retail-label]').forEach(label => label.hidden = company);
        customerName.required = false; customerContact.required = false; customerVat.required = false;
        if (customerCountry) customerCountry.required = false;
        if (!company) { customerContact.value = ''; customerVat.value = ''; customerCr.value = ''; }
    };
    customerType.addEventListener('change', syncCustomerType);
    newCustomerToggle.addEventListener('click', () => { customerQuickForm.reset(); customerQuickForm.elements.country_code.value = 'SA'; syncQuickCustomerType(); customerDialog.showModal(); customerQuickForm.elements.name.focus(); });
    if (savedCustomer) savedCustomer.addEventListener('change', () => {
        const option = savedCustomer.selectedOptions[0];
        if (!option || !option.value) { customerType.value = 'retail'; customerName.value = ''; customerContact.value = ''; customerPhone.value = ''; customerEmail.value = ''; customerVat.value = ''; customerCr.value = ''; customerAddress.value = ''; syncCustomerType(); return; }
        customerType.value = option.dataset.type || 'retail'; customerName.value = option.dataset.name || ''; customerContact.value = option.dataset.contact || ''; customerPhone.value = option.dataset.phone || ''; customerEmail.value = option.dataset.email || ''; customerVat.value = option.dataset.vat || ''; customerCr.value = option.dataset.cr || ''; customerAddress.value = option.dataset.address || ''; syncCustomerType();
    });
    const csrf = form.querySelector('input[name="_token"]').value;
    const errorText = payload => payload?.errors ? Object.values(payload.errors).flat().join('\n') : (payload?.message || 'Unable to save. Please check the details.');
    const postQuickForm = async (url, quickForm) => {
        const response = await fetch(url,{method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf},credentials:'same-origin',body:new FormData(quickForm)});
        const payload = await response.json().catch(()=>({}));
        if(!response.ok) throw new Error(errorText(payload));
        return payload;
    };
    const syncQuickCustomerType = () => {
        const company=customerQuickForm.elements.customer_type.value==='company';
        customerQuickForm.querySelectorAll('.quick-company').forEach(field=>field.hidden=!company);
        ['name_ar','contact_person','vat_number'].forEach(name=>customerQuickForm.elements[name].required=company);
    };
    customerQuickForm.elements.customer_type.addEventListener('change',syncQuickCustomerType);
    document.querySelectorAll('[data-close-dialog]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog').close()));
    customerQuickForm.addEventListener('submit',async event=>{
        event.preventDefault();if(!customerQuickForm.reportValidity())return;
        const error=customerQuickForm.querySelector('.quick-error'),button=event.submitter;error.textContent='';button.disabled=true;
        try{const payload=await postQuickForm(form.dataset.customerUrl,customerQuickForm);const c=payload.customer;const option=new Option(`${c.customer_type==='company'?'Company':'Retail'} · ${c.name}${c.phone?' · '+c.phone:''}`,c.id,true,true);Object.assign(option.dataset,{type:c.customer_type,name:c.name,contact:c.contact_person||'',phone:c.phone||'',email:c.email||'',vat:c.vat_number||'',cr:c.commercial_registration||'',address:c.address||''});savedCustomer.add(option);savedCustomer.dispatchEvent(new Event('change'));customerDialog.close();message.textContent=payload.message;}
        catch(e){error.textContent=e.message;}finally{button.disabled=false;}
    });
    productQuickForm.addEventListener('submit',async event=>{
        event.preventDefault();if(!productQuickForm.reportValidity())return;
        const error=productQuickForm.querySelector('.quick-error'),button=event.submitter;error.textContent='';button.disabled=true;
        const locationField=document.createElement('input');locationField.type='hidden';locationField.name='location_id';locationField.value=location.value;productQuickForm.append(locationField);
        try{const payload=await postQuickForm(form.dataset.productUrl,productQuickForm);const item=payload.product;cart.set(item.id,{...item,quantity:1});render();productDialog.close();barcode.value='';newProduct.hidden=true;message.textContent=payload.message;}
        catch(e){error.textContent=e.message;}finally{locationField.remove();button.disabled=false;}
    });
    newProduct.addEventListener('click',async()=>{
        productQuickForm.reset();let item=recoveryItem;
        if(item){try{const url=new URL(form.dataset.barcodeUrl,window.location.href);url.searchParams.set('barcode',item.barcode);url.searchParams.set('location_id',location.value);const response=await fetch(url,{headers:{'Accept':'application/json'},credentials:'same-origin'});if(response.ok)item=await response.json();}catch(error){/* Use the catalogue snapshot when detail lookup is unavailable. */}}
        const existing=!!item,canEdit=form.dataset.canEditProducts==='1';
        productQuickForm.elements.barcode.value=item?.barcode||barcode.value.trim();productQuickForm.elements.barcode.readOnly=existing;
        productQuickForm.elements.name.value=item?.name||'';productQuickForm.elements.category_id.value=item?.category_id||'';productQuickForm.elements.unit.value=item?.unit||'piece';
        productQuickForm.elements.quantity.value='1';productQuickForm.elements.cost.value=item?money(item.cost_cents||0):'0.00';productQuickForm.elements.price.value=item?money(item.price_cents):'';productQuickForm.elements.price_includes_vat.value=item?.price_includes_vat?'1':'0';
        productQuickForm.elements.brand.value=item?.brand||'';productQuickForm.elements.size.value=item?.size||'';productQuickForm.elements.variant.value=item?.variant||'';productQuickForm.elements.safety_standard.value=item?.safety_standard||'';productQuickForm.elements.reorder_level.value=item?.reorder_level||0;productQuickForm.elements.notes.value=item?.notes||'';
        productQuickForm.querySelectorAll('input,select,textarea').forEach(field=>{field.disabled=existing&&!canEdit&&!['barcode','quantity'].includes(field.name);});
        productDialog.querySelector('h2').textContent=existing?(canEdit?'Edit product & add stock':'Add stock'):'Add product & opening stock';
        productDialog.showModal();(existing?productQuickForm.elements.quantity:productQuickForm.elements.name).focus();
    });
    const money = cents => (cents / 100).toFixed(2);
    const lineNet = item => {
        const entered = item.quantity * item.price_cents;
        const rate = Number(item.tax_rate_units || 0);
        return item.price_includes_vat && rate > 0 ? Math.round(entered * 10000 / (10000 + rate)) : entered;
    };
    const subtotal = () => Array.from(cart.values()).reduce((sum, item) => sum + lineNet(item), 0);
    const enteredCents = field => Math.max(0, Math.round(Number(field.value || 0) * 100));
    const totals = () => {
        const beforeDiscount = subtotal();
        const discountCents = enteredCents(discount);
        const taxable = Math.max(0, beforeDiscount - discountCents);
        const items = Array.from(cart.values()); let remainingDiscount = Math.min(discountCents, beforeDiscount); let vat = 0;
        items.forEach((item,index) => { const net=lineNet(item); const share=index===items.length-1?remainingDiscount:(beforeDiscount?Math.min(remainingDiscount,Math.round(discountCents*net/beforeDiscount)):0); remainingDiscount-=share; vat+=Math.round((net-share)*Number(item.tax_rate_units||0)/10000); });
        return {beforeDiscount, discountCents, taxable, vat, final: taxable + vat};
    };
    function syncPaymentFields() {
        const values = {cash: 0, card: 0, bank: 0};
        paymentEntries.forEach(entry => { values[entry.method.value] += enteredCents(entry.amount); });
        cash.value = money(values.cash); card.value = money(values.card); bank.value = money(values.bank);
        return values;
    }
    function addPaymentRow(methodName = '') {
        if (paymentEntries.length >= 3) return;
        const used = paymentEntries.map(entry => entry.method.value);
        const available = Object.keys(paymentLabels).filter(name => !used.includes(name));
        const selected = available.includes(methodName) ? methodName : available[0];
        if (!selected) return;
        const row = document.createElement('div'); row.className = 'payment-entry';
        const methodField = document.createElement('div'); methodField.className = 'field';
        const methodLabel = document.createElement('label'); methodLabel.textContent = `Payment ${paymentEntries.length + 1} method`;
        const method = document.createElement('select'); method.setAttribute('aria-label', methodLabel.textContent);
        available.forEach(name => { const option = document.createElement('option'); option.value = name; option.textContent = paymentLabels[name]; option.selected = name === selected; method.append(option); });
        methodField.append(methodLabel, method);
        const amountField = document.createElement('div'); amountField.className = 'field';
        const amountLabel = document.createElement('label'); amountLabel.textContent = 'Amount (SAR)';
        const amount = document.createElement('input'); amount.type = 'number'; amount.step = '0.01'; amount.min = '0'; amount.max = '99999999.99'; amount.value = '0.00'; amount.setAttribute('aria-label', amountLabel.textContent);
        amountField.append(amountLabel, amount); row.append(methodField, amountField);
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn secondary payment-remove'; remove.textContent = '−'; remove.title = 'Remove payment method'; remove.setAttribute('aria-label', 'Remove payment method');
        if (paymentEntries.length > 0) row.append(remove);
        paymentRows.append(row);
        const entry = {row, method, amount, remove: paymentEntries.length > 0 ? remove : null}; paymentEntries.push(entry);
        method.addEventListener('change', () => { rebuildMethodOptions(); payment(); });
        amount.addEventListener('input', payment);
        if (entry.remove) entry.remove.addEventListener('click', () => {
            const index = paymentEntries.indexOf(entry);
            if (index < 1) return;
            paymentEntries.splice(index, 1); row.remove(); rebuildMethodOptions(); reindexPaymentRows(); payment();
        });
        rebuildMethodOptions();
        reindexPaymentRows();
    }
    function reindexPaymentRows() {
        paymentEntries.forEach((entry, index) => {
            const label = entry.method.previousElementSibling;
            if (label) label.textContent = `Payment ${index + 1} method`;
            entry.method.setAttribute('aria-label', `Payment ${index + 1} method`);
        });
        if (addPayment) addPayment.disabled = paymentEntries.length >= Object.keys(paymentLabels).length;
    }
    function rebuildMethodOptions() {
        paymentEntries.forEach(current => {
            const selected = current.method.value;
            const usedElsewhere = paymentEntries.filter(entry => entry !== current).map(entry => entry.method.value);
            current.method.replaceChildren();
            Object.keys(paymentLabels).filter(name => name === selected || !usedElsewhere.includes(name)).forEach(name => {
                const option = document.createElement('option'); option.value = name; option.textContent = paymentLabels[name]; option.selected = name === selected; current.method.append(option);
            });
        });
    }
    function removeUnusedTrailingRows() {
        while (paymentEntries.length > 1) {
            const last = paymentEntries[paymentEntries.length - 1];
            const earlierPaid = paymentEntries.slice(0, -1).reduce((sum, entry) => sum + enteredCents(entry.amount), 0);
            if (earlierPaid < totals().final || enteredCents(last.amount) > 0) break;
            last.row.remove(); paymentEntries.pop();
        }
        rebuildMethodOptions(); reindexPaymentRows();
    }
    function payment() {
        const amount = totals();
        let values = syncPaymentFields();
        let received = values.cash + values.card + values.bank;
        const cashCents = values.cash;
        const change = Math.max(0, received - amount.final);
        document.getElementById('checkout-subtotal').textContent = money(amount.beforeDiscount);
        document.getElementById('checkout-tax').textContent = money(amount.vat);
        document.getElementById('checkout-total').textContent = money(amount.final);
        document.getElementById('checkout-paid-total').textContent = money(received);
        document.getElementById('checkout-balance').textContent = money(Math.max(0, amount.final - received));
        document.getElementById('checkout-change').textContent = money(change);
        const status = received <= 0 ? 'Unpaid / آجل' : received < amount.final ? 'Partially paid / مدفوع جزئياً' : 'Paid / مدفوع';
        const statusNode = document.getElementById('checkout-payment-status');
        if (statusNode) statusNode.textContent = status;
        const invalidDiscount = amount.discountCents > amount.beforeDiscount;
        const invalidChange = change > cashCents;
        submit.disabled = posting || busy || !cart.size || invalidDiscount || invalidChange;
        if (saveDraft) saveDraft.disabled = posting || busy || !cart.size || invalidDiscount;
    }
    function input(name, value) {
        const field = document.createElement('input');
        field.type = 'hidden'; field.name = name; field.value = value;
        return field;
    }
    function cell(row, text) {
        const td = document.createElement('td'); td.textContent = text; row.append(td); return td;
    }
    body.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-product]');
        if (!button) return;
        event.preventDefault(); event.stopPropagation();
        const id = String(button.dataset.removeProduct);
        const key = Array.from(cart.keys()).find(cartKey => String(cartKey) === id);
        if (key === undefined) return;
        const removed = cart.get(key); cart.delete(key); render();
        message.textContent = `${removed.name} removed from cart.`;
    });
    function render() {
        body.replaceChildren();
        let index = 0;
        for (const item of cart.values()) {
            const row = document.createElement('tr');
            const product = cell(row, `${item.name} · ${item.barcode}`);
            product.append(input(`lines[${index}][product_id]`, item.id), input(`lines[${index}][price_cents]`, item.price_cents));
            cell(row, String(item.available)); cell(row, `${money(item.price_cents)} ${item.price_includes_vat?'incl. VAT':'excl. VAT'} · ${item.tax_category||'S'} ${Number(item.tax_rate_units||0)/100}%`);
            const quantityCell = cell(row, '');
            const quantity = document.createElement('input');
            quantity.type = 'number'; quantity.min = '1'; quantity.max = String(Math.min(item.available, 1000000)); quantity.step = '1';
            quantity.name = `lines[${index}][quantity]`; quantity.value = item.quantity; quantity.required = true; quantity.setAttribute('aria-label', `${item.name} quantity`);
            quantity.addEventListener('change', () => {
                const value = Number(quantity.value);
                if (!Number.isInteger(value) || value < 1 || value > item.available || value > 1000000) {
                    message.textContent = 'Quantity must be within available stock.'; quantity.value = item.quantity; return;
                }
                item.quantity = value; render();
            });
            quantityCell.append(quantity); const entered=item.quantity*item.price_cents,rate=Number(item.tax_rate_units||0),net=lineNet(item),tax=Math.round(net*rate/10000);cell(row,money(item.price_includes_vat?entered:net+tax));
            const removeCell = cell(row, ''); const remove = document.createElement('button');
            remove.type = 'button'; remove.className = 'cart-remove'; remove.textContent = '×'; remove.dataset.removeProduct = item.id; remove.title = 'Remove from cart'; remove.setAttribute('aria-label', `Remove ${item.name} from cart`); removeCell.append(remove);
            body.append(row); index++;
        }
        if (!cart.size) { const row = document.createElement('tr'); const td = cell(row, ''); td.colSpan = 6; td.className='checkout-empty-cell'; const empty=document.createElement('div');empty.className='checkout-empty';empty.innerHTML='<div class="checkout-empty-icon">🛒</div><strong>Your cart is empty</strong><span>Scan a barcode or type it in the search box above</span>';td.append(empty);body.append(row); }
        payment();
    }
    async function scan() {
        if (scanTimer) { window.clearTimeout(scanTimer); scanTimer = null; }
        if (busy || posting) return;
        if (!location.value) { message.textContent = 'Choose a shop location first.'; location.focus(); return; }
        const code = barcode.value.trim();
        if (!code) { barcode.focus(); return; }
        busy = true; recoveryItem=null; newProduct.hidden=true; location.disabled = true; add.disabled = true; barcode.disabled = true; payment();
        try {
            const url = new URL(form.dataset.barcodeUrl, window.location.href);
            url.searchParams.set('barcode', code); url.searchParams.set('location_id', location.value);
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) throw new Error(response.status === 404 ? 'Barcode not found or product inactive.' : 'Unable to load this barcode. Check your session and location.');
            const item = await response.json();
            recoveryItem=item;
            const preview = document.getElementById('checkout-product-preview');
            document.getElementById('checkout-preview-name').textContent = item.name;
            document.getElementById('checkout-preview-meta').textContent = `SKU ${item.sku} · Barcode ${item.barcode} · ${money(item.price_cents)} SAR ${item.price_includes_vat?'including':'excluding'} VAT · ${item.tax_category} ${Number(item.tax_rate_units||0)/100}% / ${item.unit}`;
            document.getElementById('checkout-preview-stock').textContent = `${item.available} ${item.unit} available at this checkout location.`;
            preview.hidden = false;
            const previous = cart.get(item.id); const quantity = previous ? previous.quantity + 1 : 1;
            if (quantity > item.available || quantity > 1000000) throw new Error(`${item.name} was found, but there is no available stock at this checkout location. Transfer or receive stock here before selling.`);
            if (!previous && cart.size >= 100) throw new Error('A checkout supports at most 100 products.');
            cart.set(item.id, {...item, quantity}); render(); barcode.value = '';
            message.textContent = `${item.name} added. ${quantity} ${item.unit} in cart.`;
        } catch (error) { message.textContent = error.message; if(error.message.includes('not found')||error.message.includes('no available stock')){newProduct.textContent=error.message.includes('no available stock')?'+ Add stock for this product':'+ Add this product and stock';newProduct.hidden=false;} }
        finally { busy = false; location.disabled = false; add.disabled = false; barcode.disabled = false; payment(); barcode.focus(); barcode.select(); }
    }
    function queueAutomaticScan(event) {
        if (scanTimer) window.clearTimeout(scanTimer);
        const code = barcode.value.trim();
        if (!code || busy || posting) { barcodeInputStartedAt = 0; previousBarcodeLength = 0; return; }
        const now = Date.now();
        if (!barcodeInputStartedAt || code.length <= previousBarcodeLength) barcodeInputStartedAt = now;
        const insertedTogether = code.length - previousBarcodeLength > 1 || event.inputType === 'insertFromPaste';
        previousBarcodeLength = code.length;
        const averageCharacterDelay = code.length > 1 ? (now - barcodeInputStartedAt) / (code.length - 1) : Infinity;
        const scannerSpeed = code.length >= 4 && averageCharacterDelay <= 60;
        if (!insertedTogether && !scannerSpeed) return;
        scanTimer = window.setTimeout(() => { scanTimer = null; scan(); }, 120);
    }
    barcode.addEventListener('input', queueAutomaticScan);
    barcode.addEventListener('input', filterCatalog);
    barcode.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); const visible=catalogProducts.filter(button=>!button.hidden); if(visible.length===1)visible[0].click();else scan(); } });
    add.addEventListener('click', event => { event.preventDefault(); scan(); });
    if (fromQuotation && quotationSelect) {
        fromQuotation.addEventListener('change', () => {
            quotationPanel.hidden = !fromQuotation.checked;
            if (!fromQuotation.checked) quotationSelect.value = '';
            else quotationSelect.focus();
        });
        quotationSelect.addEventListener('change', async () => {
            if (!quotationSelect.value) return;
            busy = true; payment(); message.textContent = 'Loading quotation...';
            try {
                const url = new URL(quotationSelect.dataset.urlTemplate.replace('__ID__', quotationSelect.value), window.location.href);
                url.searchParams.set('location_id', location.value);
                const response = await fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'Unable to load this quotation.');
                cart.clear();
                payload.lines.forEach(item => cart.set(item.id, item));
                savedCustomer.value = String(payload.customer_id || '');
                savedCustomer.dispatchEvent(new Event('change'));
                discount.value = Number(payload.discount || 0).toFixed(2);
                if (customerPo) customerPo.value = payload.customer_po_number || '';
                if (invoiceNotes && payload.notes) invoiceNotes.value = payload.notes.slice(0, 250);
                render();
                const insufficient = payload.lines.filter(item => item.quantity > item.available);
                message.textContent = insufficient.length
                    ? `${payload.number} loaded. ${insufficient.length} item(s) exceed available stock; adjust stock or quantity before finalizing.`
                    : `${payload.number} loaded. Review the invoice before saving or finalizing.`;
            } catch (error) {
                cart.clear(); render(); message.textContent = error.message;
            } finally { busy = false; payment(); }
        });
    }
    location.addEventListener('change', () => { cart.clear(); render(); message.textContent = 'Location changed. Scan products for this location.'; barcode.focus(); });
    discount.addEventListener('input', payment);
    form.addEventListener('keydown', event => { if (event.key === 'Enter' && event.target !== submit) event.preventDefault(); });
    form.addEventListener('submit', event => {
        const draftAction = event.submitter?.value === 'draft';
        if (posting || busy || !cart.size || (draftAction ? saveDraft?.disabled : submit.disabled)) { event.preventDefault(); return; }
        if (!draftAction && !window.confirm('Finalize and lock this invoice? Stock will be deducted and the invoice cannot be edited directly afterward.')) { event.preventDefault(); return; }
        // Disabled submit buttons are omitted from browser form data. Preserve
        // the selected workflow before locking both buttons against double-clicks.
        const action = input('submit_action', draftAction ? 'draft' : 'final');
        form.append(action);
        if (event.submitter) event.submitter.removeAttribute('name');
        posting = true; submit.disabled = true; if(saveDraft)saveDraft.disabled=true;
        if (event.submitter) event.submitter.textContent = draftAction ? 'Saving draft…' : 'Posting sale…';
    });
    const initialNode = document.getElementById('checkout-initial-data');
    if (initialNode) {
        try {
            const initial = JSON.parse(initialNode.textContent);
            (initial.lines || []).forEach(item => cart.set(item.id, {...item, quantity: Math.min(item.quantity, item.available)}));
            message.textContent = 'Replacement items loaded. Review quantities and scan any additional products before posting.';
        } catch (error) { message.textContent = 'The replacement invoice data could not be loaded. Rescan the required products.'; }
    }
    const saved = {cash: Number(paymentRows.dataset.cash || 0), card: Number(paymentRows.dataset.card || 0), bank: Number(paymentRows.dataset.bank || 0)};
    const savedMethods = Object.keys(saved).filter(name => saved[name] > 0);
    (savedMethods.length ? savedMethods : ['cash']).forEach(name => { addPaymentRow(name); paymentEntries[paymentEntries.length - 1].amount.value = saved[name] ? saved[name].toFixed(2) : '0.00'; });
    if (addPayment) addPayment.addEventListener('click', () => { addPaymentRow(); payment(); });
    if (savedCustomer?.value) savedCustomer.dispatchEvent(new Event('change')); else syncCustomerType();
    render();
})();
