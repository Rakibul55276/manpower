(() => {
    'use strict';
    const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
    const months = ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'];

    function value(cell) {
        if (cell.dataset.sortValue !== undefined) return cell.dataset.sortValue;
        const input = cell.querySelector('input:not([type="hidden"]),select');
        if (input) return input.tagName === 'SELECT' ? input.selectedOptions[0]?.textContent || '' : input.value;
        const copy = cell.cloneNode(true);
        copy.querySelectorAll('.sub,small,form,button').forEach(node => node.remove());
        return copy.textContent.trim().replace(/\s+/g, ' ');
    }

    function key(text) {
        if (!text || /^[\s\u2014\u2013-]+$/.test(text)) return { type: 'empty', value: '' };
        let date = text.match(/^(\d{1,2}) ([A-Za-z]{3}) (\d{4})(?: (\d{2}):(\d{2}))?$/);
        if (date && months.includes(date[2].toLowerCase())) return { type: 'date', value: Date.UTC(+date[3], months.indexOf(date[2].toLowerCase()), +date[1], +(date[4] || 0), +(date[5] || 0)) };
        date = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (date) return { type: 'date', value: Date.UTC(+date[1], +date[2] - 1, +date[3]) };
        date = text.match(/^(\d{2})[-/](\d{2})[-/](\d{4})$/);
        if (date) return { type: 'date', value: Date.UTC(+date[3], +date[2] - 1, +date[1]) };
        const number = text.replace(/^SAR\s*/i, '').replace(/,/g, '').match(/^([+-]?\d+(?:\.\d+)?)(?:\s*(?:%|h|hours?|units?|pieces?|pairs?|boxes|packs?|sets?|rolls?|cartons?))?$/i);
        return number ? { type: 'number', value: +number[1] } : { type: 'text', value: text };
    }

    function initialise(table) {
        if (table.dataset.sortReady || table.closest('.safety-shop-receipt,[data-sortable="false"]')) return;
        const head = table.tHead;
        const body = table.tBodies[0];
        if (!head || head.rows.length !== 1 || !body || table.tBodies.length !== 1) return;
        const headers = Array.from(head.rows[0].cells);
        if (headers.some(cell => cell.colSpan > 1 || cell.rowSpan > 1)) return;
        const rows = () => Array.from(body.rows).filter(row => row.cells.length === headers.length && Array.from(row.cells).every(cell => cell.colSpan === 1 && cell.rowSpan === 1));
        if (Array.from(body.rows).some(row => Array.from(row.cells).some(cell => cell.rowSpan > 1))) return;
        table.dataset.sortReady = 'true';
        headers.forEach((header, column) => {
            const label = header.textContent.trim();
            if (!label || /^(actions?|select)$/i.test(label) || header.querySelector('input,button,a,select')) return;
            header.setAttribute('aria-sort', 'none');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'table-sort-button';
            button.title = 'Sort ' + label + ' (rows on this page)';
            while (header.firstChild) button.appendChild(header.firstChild);
            const icon = document.createElement('span');
            icon.className = 'table-sort-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.textContent = ' ↕';
            button.appendChild(icon);
            header.appendChild(button);
            button.addEventListener('click', () => {
                const direction = header.getAttribute('aria-sort') === 'ascending' ? -1 : 1;
                const entries = rows().map((row, index) => ({ row, index, key: key(value(row.cells[column])) }));
                entries.sort((a, b) => {
                    if (a.key.type === 'empty' || b.key.type === 'empty') return a.key.type === b.key.type ? a.index - b.index : a.key.type === 'empty' ? 1 : -1;
                    const comparison = a.key.type === b.key.type && a.key.type !== 'text' ? a.key.value - b.key.value : collator.compare(String(a.key.value), String(b.key.value));
                    return comparison * direction || a.index - b.index;
                });
                headers.forEach(cell => {
                    if (cell.hasAttribute('aria-sort')) cell.setAttribute('aria-sort', 'none');
                    const marker = cell.querySelector('.table-sort-icon');
                    if (marker) marker.textContent = ' ↕';
                });
                header.setAttribute('aria-sort', direction === 1 ? 'ascending' : 'descending');
                icon.textContent = direction === 1 ? ' ↑' : ' ↓';
                entries.forEach(entry => body.appendChild(entry.row));
            });
        });
    }
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('main table').forEach(initialise);
        new MutationObserver(records => {
            records.forEach(record => record.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                if (node.matches('table')) initialise(node);
                node.querySelectorAll('table').forEach(initialise);
            }));
        }).observe(document.querySelector('main') || document.body, { childList: true, subtree: true });
    });
})();
