(() => {
    'use strict';

    const selector = '[data-image-preview]';
    const isRealProductImage = image => image && image.src && !image.src.includes('/images/product-placeholder.svg');
    let dialog;
    let preview;
    let caption;

    function ensureDialog() {
        if (dialog) return;
        dialog = document.createElement('dialog');
        dialog.className = 'product-image-lightbox';
        dialog.setAttribute('aria-label', 'Product image preview');
        dialog.innerHTML = '<button class="product-image-lightbox-close" type="button" aria-label="Close image preview">×</button><div class="product-image-lightbox-body"><img alt=""><p></p></div>';
        document.body.append(dialog);
        preview = dialog.querySelector('img');
        caption = dialog.querySelector('p');
        dialog.querySelector('button').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
    }

    document.querySelectorAll('.ledger-list img, .ledger-card-image').forEach(image => {
        if (isRealProductImage(image)) image.dataset.imagePreview = '';
    });

    document.addEventListener('click', event => {
        const image = event.target.closest(selector);
        if (!isRealProductImage(image)) return;
        event.preventDefault();
        event.stopPropagation();
        ensureDialog();
        preview.src = image.currentSrc || image.src;
        preview.alt = image.alt || 'Product image';
        caption.textContent = image.alt || '';
        caption.hidden = !caption.textContent;
        dialog.showModal();
    });
})();
