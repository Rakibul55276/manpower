(() => {
    'use strict';
    const lookup = document.getElementById('stock_product_lookup');
    const product = document.getElementById('product_id');
    const message = document.getElementById('stock-barcode-message');
    const preview = document.getElementById('stock-product-preview');
    if (!lookup || !product || !message || !preview) return;

    const movementType = document.getElementById('type');
    const locationLabel = document.getElementById('location-label');
    const destinationField = document.getElementById('destination-field');
    const destination = document.getElementById('destination_id');
    const location = document.getElementById('location_id');
    const supportingDocumentLabel = document.getElementById('supporting-document-label');
    const syncMovementFields = () => {
        const transfer = movementType?.value === 'transfer';
        locationLabel.textContent = transfer ? 'Transfer source *' : 'Stock location *';
        destinationField.hidden = !transfer;
        destination.required = transfer;
        supportingDocumentLabel.firstChild.textContent = transfer ? 'Transfer document ' : 'Supporting receipt ';
        if (!transfer) destination.value = '';
        updateStockBalance();
    };
    movementType?.addEventListener('change', syncMovementFields);

    const normalize = value => String(value || '').trim().toLocaleLowerCase();
    const options = Array.from(product.options).filter(option => option.value).map(option => ({
        option,
        barcode: normalize(option.dataset.barcode),
        sku: normalize(option.dataset.sku),
        name: normalize(option.dataset.name),
        label: normalize(option.textContent),
        stocks: JSON.parse(option.dataset.stocks || '{}')
    }));
    const selectedMatch = () => options.find(item => item.option.value === product.value);
    const stockAt = (match, locationId) => Number(match?.stocks?.[locationId] || 0);
    function updateStockBalance() {
        const match = selectedMatch();
        if (!match || preview.hidden) return;
        let stock = preview.querySelector('.stock-location-balance');
        if (!stock) { stock = document.createElement('span'); stock.className = 'stock-location-balance'; preview.appendChild(stock); }
        if (!location?.value) { stock.textContent = 'Choose a location to view its current stock.'; return; }
        const sourceName = location.options[location.selectedIndex]?.textContent.trim() || 'selected location';
        const sourceQuantity = stockAt(match, location.value);
        if (movementType?.value === 'transfer' && destination?.value) {
            const destinationName = destination.options[destination.selectedIndex]?.textContent.trim() || 'destination';
            stock.textContent = `Available at ${sourceName}: ${sourceQuantity} ${match.option.dataset.unit} · Current at ${destinationName}: ${stockAt(match, destination.value)} ${match.option.dataset.unit}`;
            return;
        }
        stock.textContent = `Current stock at ${sourceName}: ${sourceQuantity} ${match.option.dataset.unit}`;
    }
    syncMovementFields();
    const showProduct = match => {
        product.value = match.option.value;
        lookup.value = match.option.dataset.barcode || match.option.dataset.sku;
        preview.innerHTML = `<strong>${escapeHtml(match.option.dataset.name)}</strong><span>SKU ${escapeHtml(match.option.dataset.sku)} · Barcode ${escapeHtml(match.option.dataset.barcode || '—')} · Unit ${escapeHtml(match.option.dataset.unit)}</span><span class="stock-location-balance"></span>`;
        preview.hidden = false;
        updateStockBalance();
        message.textContent = 'Existing product selected. Enter only the movement quantity and transaction details.';
        message.setAttribute('role', 'status');
    };
    const clearProduct = text => {
        product.value = '';
        preview.hidden = true;
        preview.innerHTML = '';
        message.textContent = text;
        message.setAttribute('role', 'alert');
    };
    const resolveProduct = focusQuantity => {
        const value = normalize(lookup.value);
        if (!value) { clearProduct('Scan a barcode, type a barcode/SKU, or choose an existing product.'); return false; }
        const exact = options.find(item => item.barcode === value || item.sku === value || item.label === value);
        const matches = exact ? [exact] : options.filter(item => item.name.includes(value) || item.label.includes(value));
        if (matches.length !== 1) {
            clearProduct(matches.length ? 'More than one product matches. Choose the exact product from the list.' : 'No active product matches this barcode or search.');
            return false;
        }
        showProduct(matches[0]);
        if (focusQuantity) document.getElementById('quantity')?.focus();
        return true;
    };
    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]));
    }

    lookup.addEventListener('input', () => {
        const value = normalize(lookup.value);
        const exact = options.find(item => item.barcode === value || item.sku === value);
        if (exact) showProduct(exact); else { product.value = ''; preview.hidden = true; }
    });
    lookup.addEventListener('change', () => resolveProduct(false));
    location?.addEventListener('change', updateStockBalance);
    destination?.addEventListener('change', updateStockBalance);
    lookup.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        resolveProduct(true);
    });
    lookup.form?.addEventListener('submit', event => {
        if (product.value || resolveProduct(false)) return;
        event.preventDefault();
        lookup.focus();
    });
})();
