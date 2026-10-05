(() => {
    'use strict';
    const print = document.getElementById('shop-print-receipt');
    if (print) print.addEventListener('click', () => window.print());
    const form = document.getElementById('shop-checkout');
    if (!form) return;
    const location = document.getElementById('checkout-location');
    const barcode = document.getElementById('checkout-barcode');
    const body = document.getElementById('checkout-lines');
    const paid = document.getElementById('checkout-paid');
    const method = document.getElementById('checkout-method');
    const submit = document.getElementById('checkout-submit');
    const message = document.getElementById('checkout-message');
    const add = document.getElementById('checkout-add');
    const cart = new Map();
    let busy = false;
    let posting = false;
    const money = cents => (cents / 100).toFixed(2);
    const total = () => Array.from(cart.values()).reduce((sum, item) => sum + item.quantity * item.price_cents, 0);
    function payment() {
        const value = Math.round(Number(paid.value || 0) * 100);
        const amount = total();
        document.getElementById('checkout-change').textContent = money(method.value === 'cash' ? Math.max(0, value - amount) : 0);
        submit.disabled = posting || busy || !cart.size || !paid.value || value < amount || (method.value !== 'cash' && value !== amount);
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
        document.getElementById('checkout-total').textContent = money(total()); payment();
    }
    async function scan() {
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
            const previous = cart.get(item.id); const quantity = previous ? previous.quantity + 1 : 1;
            if (quantity > item.available || quantity > 1000000) throw new Error('Insufficient stock at this location.');
            if (!previous && cart.size >= 100) throw new Error('A checkout supports at most 100 products.');
            cart.set(item.id, {...item, quantity}); render(); barcode.value = '';
            message.textContent = `${item.name} added. ${quantity} ${item.unit} in cart.`;
        } catch (error) { message.textContent = error.message; }
        finally { busy = false; location.disabled = false; add.disabled = false; barcode.disabled = false; payment(); barcode.focus(); barcode.select(); }
    }
    barcode.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); scan(); } });
    add.addEventListener('click', scan);
    location.addEventListener('change', () => { cart.clear(); render(); message.textContent = 'Location changed. Scan products for this location.'; barcode.focus(); });
    paid.addEventListener('input', payment); method.addEventListener('change', payment);
    form.addEventListener('keydown', event => { if (event.key === 'Enter' && event.target !== submit) event.preventDefault(); });
    form.addEventListener('submit', event => {
        if (posting || busy || !cart.size || submit.disabled) { event.preventDefault(); return; }
        posting = true; submit.disabled = true; submit.textContent = 'Posting sale…';
    });
    render();
})();
