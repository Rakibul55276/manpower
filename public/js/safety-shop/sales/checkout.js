(() => {
    'use strict';
    const print = document.getElementById('shop-print-receipt');
    if (print) print.addEventListener('click', () => window.print());
    const form = document.getElementById('shop-checkout');
    if (!form) return;
    const location = document.getElementById('checkout-location');
    const savedCustomer = document.getElementById('checkout-saved-customer');
    const newCustomerToggle = document.getElementById('checkout-new-customer-toggle');
    const newCustomerPanel = document.getElementById('checkout-new-customer');
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
    const taxRate = document.getElementById('checkout-tax-rate');
    const cash = document.getElementById('checkout-cash');
    const card = document.getElementById('checkout-card');
    const bank = document.getElementById('checkout-bank');
    const paymentRows = document.getElementById('checkout-payment-rows');
    const submit = document.getElementById('checkout-submit');
    const saveDraft = document.getElementById('checkout-save-draft');
    const message = document.getElementById('checkout-message');
    const add = document.getElementById('checkout-add');
    const fromQuotation = document.getElementById('checkout-from-quotation');
    const quotationPanel = document.getElementById('checkout-quotation-panel');
    const quotationSelect = document.getElementById('checkout-quotation');
    const customerPo = document.getElementById('checkout-customer-po');
    const invoiceNotes = document.getElementById('checkout-invoice-notes');
    const cart = new Map();
    let busy = false;
    let posting = false;
    let scanTimer = null;
    let barcodeInputStartedAt = 0;
    let previousBarcodeLength = 0;
    const paymentEntries = [];
    const paymentLabels = {cash: 'Cash', card: 'Card', bank: 'Bank transfer'};
    const syncCustomerType = () => {
        const company = customerType.value === 'company';
        document.querySelectorAll('.checkout-company-field').forEach(field => field.hidden = !company);
        document.querySelectorAll('[data-checkout-company-label]').forEach(label => label.hidden = !company);
        document.querySelectorAll('[data-checkout-retail-label]').forEach(label => label.hidden = company);
        customerName.required = false; customerContact.required = false; customerVat.required = false;
        if (customerCountry) customerCountry.required = false;
        if (!company) { customerContact.value = ''; customerVat.value = ''; customerCr.value = ''; }
    };
    const showNewCustomer = show => {
        newCustomerPanel.hidden = !show; newCustomerToggle.setAttribute('aria-expanded', show ? 'true' : 'false');
        newCustomerToggle.textContent = show ? '− Remove retail customer details' : '+ Quick add retail customer';
        customerName.required = false;
        if (show) { savedCustomer.value = ''; customerType.value = 'retail'; customerContact.value = ''; customerEmail.value = ''; customerAddress.value = ''; customerVat.value = ''; customerCr.value = ''; customerName.focus(); }
        else if (!savedCustomer.value) { customerName.value = ''; customerPhone.value = ''; }
    };
    customerType.addEventListener('change', syncCustomerType);
    newCustomerToggle.addEventListener('click', () => showNewCustomer(newCustomerPanel.hidden));
    if (savedCustomer) savedCustomer.addEventListener('change', () => {
        const option = savedCustomer.selectedOptions[0];
        if (!option || !option.value) { customerType.value = 'retail'; customerName.value = ''; customerContact.value = ''; customerPhone.value = ''; customerEmail.value = ''; customerVat.value = ''; customerCr.value = ''; customerAddress.value = ''; syncCustomerType(); return; }
        showNewCustomer(false); customerType.value = option.dataset.type || 'retail'; customerName.value = option.dataset.name || ''; customerContact.value = option.dataset.contact || ''; customerPhone.value = option.dataset.phone || ''; customerEmail.value = option.dataset.email || ''; customerVat.value = option.dataset.vat || ''; customerCr.value = option.dataset.cr || ''; customerAddress.value = option.dataset.address || ''; syncCustomerType();
    });
    const money = cents => (cents / 100).toFixed(2);
    const subtotal = () => Array.from(cart.values()).reduce((sum, item) => sum + item.quantity * item.price_cents, 0);
    const enteredCents = field => Math.max(0, Math.round(Number(field.value || 0) * 100));
    const totals = () => {
        const beforeDiscount = subtotal();
        const discountCents = enteredCents(discount);
        const taxable = Math.max(0, beforeDiscount - discountCents);
        const vat = Math.round(taxable * Math.max(0, Number(taxRate.value || 0)) / 100);
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
        const row = document.createElement('div'); row.className = 'form-grid'; row.style.marginBottom = '10px';
        const methodField = document.createElement('div'); methodField.className = 'field';
        const methodLabel = document.createElement('label'); methodLabel.textContent = `Payment ${paymentEntries.length + 1} method`;
        const method = document.createElement('select'); method.setAttribute('aria-label', methodLabel.textContent);
        available.forEach(name => { const option = document.createElement('option'); option.value = name; option.textContent = paymentLabels[name]; option.selected = name === selected; method.append(option); });
        methodField.append(methodLabel, method);
        const amountField = document.createElement('div'); amountField.className = 'field';
        const amountLabel = document.createElement('label'); amountLabel.textContent = 'Amount (SAR)';
        const amount = document.createElement('input'); amount.type = 'number'; amount.step = '0.01'; amount.min = '0'; amount.max = '99999999.99'; amount.value = '0.00'; amount.setAttribute('aria-label', amountLabel.textContent);
        amountField.append(amountLabel, amount); row.append(methodField, amountField); paymentRows.append(row);
        const entry = {row, method, amount}; paymentEntries.push(entry);
        method.addEventListener('change', () => { rebuildMethodOptions(); payment(); });
        amount.addEventListener('input', payment);
        rebuildMethodOptions();
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
        rebuildMethodOptions();
    }
    function payment() {
        const amount = totals();
        removeUnusedTrailingRows();
        let values = syncPaymentFields();
        let received = values.cash + values.card + values.bank;
        const last = paymentEntries[paymentEntries.length - 1];
        if (cart.size && received < amount.final && last && enteredCents(last.amount) > 0 && paymentEntries.length < 3) {
            addPaymentRow(); values = syncPaymentFields(); received = values.cash + values.card + values.bank;
        }
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
    function render() {
        body.replaceChildren();
        let index = 0;
        for (const item of cart.values()) {
            const row = document.createElement('tr');
            const product = cell(row, `${item.name} · ${item.barcode}`);
            product.append(input(`lines[${index}][product_id]`, item.id), input(`lines[${index}][price_cents]`, item.price_cents));
            cell(row, String(item.available)); cell(row, money(item.price_cents));
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
            quantityCell.append(quantity); cell(row, money(item.quantity * item.price_cents));
            const removeCell = cell(row, ''); const remove = document.createElement('button');
            remove.type = 'button'; remove.className = 'btn link'; remove.textContent = 'Remove'; remove.setAttribute('aria-label', `Remove ${item.name}`);
            remove.addEventListener('click', () => { cart.delete(item.id); render(); }); removeCell.append(remove);
            body.append(row); index++;
        }
        if (!cart.size) { const row = document.createElement('tr'); const td = cell(row, 'Scan a product to start.'); td.colSpan = 6; body.append(row); }
        payment();
    }
    async function scan() {
        if (scanTimer) { window.clearTimeout(scanTimer); scanTimer = null; }
        if (busy || posting) return;
        if (!location.value) { message.textContent = 'Choose a shop location first.'; location.focus(); return; }
        const code = barcode.value.trim();
        if (!code) { barcode.focus(); return; }
        busy = true; location.disabled = true; add.disabled = true; barcode.disabled = true; payment();
        try {
            const url = new URL(form.dataset.barcodeUrl, window.location.href);
            url.searchParams.set('barcode', code); url.searchParams.set('location_id', location.value);
            const response = await fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!response.ok) throw new Error(response.status === 404 ? 'Barcode not found or product inactive.' : 'Unable to load this barcode. Check your session and location.');
            const item = await response.json();
            const preview = document.getElementById('checkout-product-preview');
            document.getElementById('checkout-preview-name').textContent = item.name;
            document.getElementById('checkout-preview-meta').textContent = `SKU ${item.sku} · Barcode ${item.barcode} · ${money(item.price_cents)} SAR / ${item.unit}`;
            document.getElementById('checkout-preview-stock').textContent = `${item.available} ${item.unit} available at this checkout location.`;
            preview.hidden = false;
            const previous = cart.get(item.id); const quantity = previous ? previous.quantity + 1 : 1;
            if (quantity > item.available || quantity > 1000000) throw new Error(`${item.name} was found, but there is no available stock at this checkout location. Transfer or receive stock here before selling.`);
            if (!previous && cart.size >= 100) throw new Error('A checkout supports at most 100 products.');
            cart.set(item.id, {...item, quantity}); render(); barcode.value = '';
            message.textContent = `${item.name} added. ${quantity} ${item.unit} in cart.`;
        } catch (error) { message.textContent = error.message; }
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
    barcode.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); scan(); } });
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
                taxRate.value = Number(payload.tax_rate || 15).toFixed(2);
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
    [discount, taxRate].forEach(field => field.addEventListener('input', payment));
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
    if (savedCustomer?.value) savedCustomer.dispatchEvent(new Event('change')); else syncCustomerType();
    if (customerName.value && !savedCustomer.value) showNewCustomer(true); render();
})();
