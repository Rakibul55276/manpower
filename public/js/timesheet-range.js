document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-bulk-hours]');
    if (!form) return;
    const rows = form.querySelector('[data-hours-rows]');
    const target = form.querySelector('#total_hours');
    const start = form.querySelector('#range_start');
    const end = form.querySelector('#range_end');
    const message = form.querySelector('[data-hours-message]');
    const units = value => Math.round(Number(value || 0) * 100);
    const weekday = value => value ? new Date(value + 'T00:00:00Z').toLocaleDateString('en-US', { weekday: 'short', timeZone: 'UTC' }) : '—';
    const enhanceRow = row => {
        const dateInput = row.querySelector('input[type="date"]');
        let dayCell = row.querySelector('[data-weekday]');
        if (!dayCell) { dayCell = document.createElement('td'); dayCell.dataset.weekday = ''; dateInput.closest('td').after(dayCell); }
        dayCell.textContent = weekday(dateInput.value);
    };
    const header = rows.closest('table').querySelector('thead tr');
    if (header && !header.querySelector('[data-weekday-heading]')) { const th = document.createElement('th'); th.dataset.weekdayHeading = ''; th.textContent = 'Day'; header.children[0].after(th); }
    const notify = text => { message.textContent = text; message.hidden = !text; };
    const refresh = () => {
        let total = 0;
        Array.from(rows.children).forEach((row, index) => {
            enhanceRow(row);
            const fields = row.querySelectorAll('input');
            ['work_date', 'regular_hours', 'overtime_hours'].forEach((key, i) => { fields[i].name = `entries[${index}][${key}]`; });
            total += units(fields[1].value) + units(fields[2].value);
        });
        form.querySelector('[data-hours-summary]').textContent = `${rows.children.length} dates · ${(total / 100).toFixed(2)} total hours` + (target.value ? ` · Target: ${target.value} hours` : '');
        form.querySelector('[data-add-hours-date]').disabled = rows.children.length >= 120;
    };
    const add = (date = '', hours = 10) => {
        const row = document.createElement('tr');
        [['date', date, 'Work date'], ['number', hours, 'Regular hours'], ['number', 0, 'Overtime hours']].forEach(([type, value, label]) => {
            const cell = document.createElement('td'); const input = document.createElement('input');
            input.type = type; input.value = value; input.required = true; input.setAttribute('aria-label', label);
            if (type === 'date') input.max = end.max;
            else { input.min = '0'; input.max = '24'; input.step = '0.01'; }
            cell.append(input); row.append(cell);
        });
        const cell = document.createElement('td'); const remove = document.createElement('button');
        remove.type = 'button'; remove.className = 'btn danger small'; remove.textContent = 'Remove'; remove.dataset.removeHoursDate = '';
        cell.append(remove); row.append(cell); rows.append(row);
        enhanceRow(row);
    };
    form.querySelector('[data-generate-hours]').addEventListener('click', () => {
        if (!start.value || !end.value || start.value > end.value || end.value > end.max) { notify('Select a valid date range ending no later than today.'); return; }
        if (target.value && !target.checkValidity()) { notify('Enter a valid total-hours target with up to two decimal places.'); return; }
        const dates = []; let day = new Date(start.value + 'T00:00:00Z'); const last = new Date(end.value + 'T00:00:00Z');
        if ((last - day) / 86400000 >= 120) { notify('Choose a range of at most 120 calendar days.'); return; }
        while (day <= last) { if (day.getUTCDay() !== 5) dates.push(day.toISOString().slice(0, 10)); day.setUTCDate(day.getUTCDate() + 1); }
        let remaining = target.value ? units(target.value) : dates.length * 1000;
        if (!dates.length || remaining > dates.length * 1000) { notify('This range has capacity for ' + dates.length * 10 + ' hours excluding Fridays. Extend the range or reduce the target.'); return; }
        if (rows.children.length && !window.confirm('Replace the current date rows with the generated dates?')) return;
        rows.replaceChildren();
        for (const date of dates) { if (remaining <= 0) break; const amount = Math.min(1000, remaining); add(date, (amount / 100).toFixed(2)); remaining -= amount; }
        notify('Dates generated. Review hours before submitting.'); refresh();
    });
    form.querySelector('[data-add-hours-date]').addEventListener('click', () => { if (rows.children.length < 120) { add(); refresh(); rows.lastElementChild.querySelector('input').focus(); } });
    rows.addEventListener('click', event => { if (event.target.closest('[data-remove-hours-date]')) { event.target.closest('tr').remove(); refresh(); } });
    rows.addEventListener('input', refresh); rows.addEventListener('change', refresh); target.addEventListener('input', refresh);
    form.addEventListener('submit', event => {
        const dates = new Set(); let total = 0; let error = rows.children.length ? '' : 'Generate dates or add at least one date.';
        Array.from(rows.children).forEach(row => {
            const fields = row.querySelectorAll('input'); const hours = units(fields[1].value) + units(fields[2].value);
            if (dates.has(fields[0].value)) error = 'Each date may appear only once.';
            dates.add(fields[0].value); total += hours;
            if (hours <= 0 || hours > 2400) error = 'Each date must have more than zero and no more than 24 total hours.';
        });
        if (target.value && units(target.value) !== total) error = 'Rows must match the total-hours target. Adjust the rows or clear the target.';
        if (error) { event.preventDefault(); notify(error); }
    });
    refresh();
});
