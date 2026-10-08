(() => {
    'use strict';
    const barcode = document.getElementById('stock_barcode');
    const product = document.getElementById('product_id');
    const message = document.getElementById('stock-barcode-message');
    if (!barcode || !product || !message) return;

    const movementType = document.getElementById('type');
    const locationLabel = document.getElementById('location-label');
    const destinationField = document.getElementById('destination-field');
    const destination = document.getElementById('destination_id');
    const supportingDocumentLabel = document.getElementById('supporting-document-label');
    const syncMovementFields = () => {
        const transfer = movementType?.value === 'transfer';
        locationLabel.textContent = transfer ? 'Transfer source *' : 'Stock location *';
        destinationField.hidden = !transfer;
        destination.required = transfer;
        supportingDocumentLabel.firstChild.textContent = transfer ? 'Transfer document ' : 'Supporting receipt ';
        if (!transfer) destination.value = '';
    };
    movementType?.addEventListener('change', syncMovementFields);
    syncMovementFields();

    const options = Array.from(product.options).filter(option => option.dataset.barcode);
    const selectByBarcode = () => {
        const code = barcode.value.trim().toLocaleLowerCase();
        if (!code) return;
        const match = options.find(option => option.dataset.barcode.trim().toLocaleLowerCase() === code);
        if (!match) {
            product.value = '';
            message.textContent = 'No active product was found for this barcode.';
            message.setAttribute('role', 'alert');
            return;
        }
        product.value = match.value;
        product.dispatchEvent(new Event('change', { bubbles: true }));
        message.textContent = `Selected: ${match.textContent.trim()}`;
        message.setAttribute('role', 'status');
        document.getElementById('quantity')?.focus();
    };

    barcode.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            selectByBarcode();
        }
    });
    barcode.addEventListener('change', selectByBarcode);
})();
