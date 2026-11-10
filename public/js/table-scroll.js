(() => {
    const MOBILE_WIDTH = 900;

    const enhance = (wrap) => {
        if (wrap.dataset.scrollAssistReady === '1') return;

        const table = wrap.querySelector(':scope > table');
        if (!table || table.classList.contains('receipt-pdf-table') || table.classList.contains('receipt-settlement')) return;

        wrap.dataset.scrollAssistReady = '1';

        const assist = document.createElement('div');
        assist.className = 'table-scroll-assist';
        assist.setAttribute('aria-label', 'Horizontal table navigation');
        assist.innerHTML = '<button type="button" class="table-scroll-left" aria-label="Scroll table left">‹</button><div class="table-scroll-proxy" aria-label="Table horizontal scrollbar" tabindex="0"><div class="table-scroll-proxy-content"></div></div><button type="button" class="table-scroll-right" aria-label="Scroll table right">›</button>';
        wrap.before(assist);

        const proxy = assist.querySelector('.table-scroll-proxy');
        const proxyContent = assist.querySelector('.table-scroll-proxy-content');
        const left = assist.querySelector('.table-scroll-left');
        const right = assist.querySelector('.table-scroll-right');
        let syncing = false;

        const updateButtons = () => {
            const max = Math.max(0, wrap.scrollWidth - wrap.clientWidth);
            left.disabled = wrap.scrollLeft <= 1;
            right.disabled = wrap.scrollLeft >= max - 1;
        };

        const update = () => {
            const overflowing = wrap.scrollWidth > wrap.clientWidth + 1;
            const visible = wrap.clientWidth <= MOBILE_WIDTH && overflowing && !wrap.hidden;
            assist.classList.toggle('is-visible', visible);
            proxyContent.style.width = `${wrap.scrollWidth}px`;
            proxy.scrollLeft = wrap.scrollLeft;
            updateButtons();
        };

        wrap.addEventListener('scroll', () => {
            if (syncing) return;
            syncing = true;
            proxy.scrollLeft = wrap.scrollLeft;
            updateButtons();
            syncing = false;
        }, { passive: true });

        proxy.addEventListener('scroll', () => {
            if (syncing) return;
            syncing = true;
            wrap.scrollLeft = proxy.scrollLeft;
            updateButtons();
            syncing = false;
        }, { passive: true });

        const move = (direction) => wrap.scrollBy({ left: direction * Math.max(180, wrap.clientWidth * .72), behavior: 'smooth' });
        left.addEventListener('click', () => move(-1));
        right.addEventListener('click', () => move(1));

        new ResizeObserver(update).observe(wrap);
        new ResizeObserver(update).observe(table);
        update();
    };

    const initialize = () => document.querySelectorAll('.table-wrap').forEach(enhance);
    document.addEventListener('DOMContentLoaded', initialize);
    window.addEventListener('resize', initialize, { passive: true });
})();
