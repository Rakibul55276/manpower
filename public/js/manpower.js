document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-menu-toggle]');
    const side = document.querySelector('.sidebar');
    if (toggle && side) {
        const desktop = window.matchMedia('(min-width: 761px)');
        const applyMenuState = () => {
            if (desktop.matches) {
                const collapsed = localStorage.getItem('manpower-navigation-collapsed') === 'true';
                document.body.classList.toggle('sidebar-collapsed', collapsed);
                side.classList.remove('open');
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                toggle.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
                toggle.title = collapsed ? 'Expand navigation' : 'Collapse navigation';
            } else {
                document.body.classList.remove('sidebar-collapsed');
                toggle.setAttribute('aria-expanded', side.classList.contains('open') ? 'true' : 'false');
                toggle.setAttribute('aria-label', 'Toggle navigation');
                toggle.title = 'Toggle navigation';
            }
        };
        toggle.addEventListener('click', () => {
            if (desktop.matches) {
                const collapsed = !document.body.classList.contains('sidebar-collapsed');
                document.body.classList.toggle('sidebar-collapsed', collapsed);
                localStorage.setItem('manpower-navigation-collapsed', collapsed ? 'true' : 'false');
            } else side.classList.toggle('open');
            applyMenuState();
        });
        desktop.addEventListener('change', applyMenuState);
        applyMenuState();
    }
    document.querySelectorAll('[data-nav-section-toggle]').forEach(sectionToggle => {
        const key = sectionToggle.dataset.navSectionToggle;
        const section = document.querySelector(`[data-nav-section="${key}"]`);
        if (!section) return;
        const storageKey = `manpower-navigation-section-${key}`;
        const activeSection = Boolean(section.querySelector('.nav-link.active'));
        let expanded = activeSection || localStorage.getItem(storageKey) !== 'false';
        const applySectionState = () => {
            section.hidden = !expanded;
            sectionToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        };
        sectionToggle.addEventListener('click', () => {
            expanded = !expanded;
            localStorage.setItem(storageKey, expanded ? 'true' : 'false');
            applySectionState();
        });
        applySectionState();
    });
    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); }));
    document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
    const employeeSearch = document.querySelector('[data-employee-search]');
    if (employeeSearch) {
        const employeeRows = [...document.querySelectorAll('[data-employee-row]')];
        const searchCount = document.querySelector('[data-employee-search-count]');
        const searchEmployees = () => {
            const query = employeeSearch.value.trim().toLocaleLowerCase();
            let visible = 0;
            employeeRows.forEach(row => { const show = !query || row.dataset.searchText.includes(query); row.hidden = !show; if (show) visible++; });
            if (searchCount) searchCount.textContent = `${visible} ${visible === 1 ? 'employee' : 'employees'} shown`;
        };
        employeeSearch.addEventListener('input', searchEmployees);
        const clearEmployeeSearch = document.querySelector('[data-clear-employee-search]');
        if (clearEmployeeSearch) clearEmployeeSearch.addEventListener('click', () => { employeeSearch.value = ''; searchEmployees(); employeeSearch.focus(); });
    }
    const pendingEntries = [...document.querySelectorAll('[data-pending-entry]')];
    const selectAllPending = document.querySelector('[data-select-all-pending]');
    const bulkApprove = document.querySelector('[data-bulk-approve]');
    const bulkCount = document.querySelector('[data-bulk-count]');
    if (pendingEntries.length && bulkApprove && bulkCount) {
        const refreshBulkApproval = () => {
            const selected = pendingEntries.filter(input => input.checked).length;
            bulkApprove.disabled = selected === 0;
            bulkCount.textContent = selected ? `${selected} ${selected === 1 ? 'entry' : 'entries'} selected.` : 'Select pending entries below.';
            if (selectAllPending) {
                selectAllPending.checked = selected === pendingEntries.length;
                selectAllPending.indeterminate = selected > 0 && selected < pendingEntries.length;
            }
        };
        pendingEntries.forEach(input => input.addEventListener('change', refreshBulkApproval));
        if (selectAllPending) selectAllPending.addEventListener('change', () => { pendingEntries.forEach(input => { input.checked = selectAllPending.checked; }); refreshBulkApproval(); });
        refreshBulkApproval();
    }
    const container = document.querySelector('[data-experiences]');
    const add = document.querySelector('[data-add-experience]');
    if (container && add) {
        const refresh = () => {
            add.disabled = container.children.length >= 5;
            document.querySelector('[data-experience-count]').textContent = container.children.length + ' / 5';
            [...container.children].forEach((card, index) => {
                card.querySelector('[data-position]').name = `previous_experience[${index}][position]`;
                card.querySelector('[data-company-name]').name = `previous_experience[${index}][company_name]`;
                card.querySelector('[data-location]').name = `previous_experience[${index}][location]`;
                card.querySelector('[data-start-date]').name = `previous_experience[${index}][start_date]`;
                card.querySelector('[data-end-date]').name = `previous_experience[${index}][end_date]`;
                card.querySelector('[data-responsibilities]').name = `previous_experience[${index}][responsibilities]`;
            });
        };
        add.addEventListener('click', () => {
            if (container.children.length >= 5) return;
            const row = document.createElement('div'); row.className = 'experience-row experience-card';
            row.innerHTML = '<div class="form-grid" style="flex:1"><div class="field"><label>Company Name</label><input data-company-name maxlength="150"></div><div class="field"><label>Location</label><input data-location maxlength="150" placeholder="City, Country"></div><div class="field"><label>Position</label><input data-position maxlength="150"></div><div class="field"><label>Start Date</label><input data-start-date type="date"></div><div class="field"><label>End Date</label><input data-end-date type="date"><p class="help">Leave blank if currently employed.</p></div><div class="field full"><label>Roles & Responsibilities</label><textarea data-responsibilities maxlength="2000"></textarea></div></div>';
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn danger small'; remove.dataset.removeExperience = ''; remove.textContent = '− Remove';
            row.append(remove); container.append(row); refresh(); row.querySelector('[data-position]').focus();
        });
        container.addEventListener('click', event => { if (event.target.closest('[data-remove-experience]')) { event.target.closest('.experience-row').remove(); refresh(); } }); refresh();
    }
    const photo = document.querySelector('[data-photo-input]');
    if (photo) { let previewUrl; photo.addEventListener('change', () => { const preview = document.querySelector('[data-photo-preview]'); if (previewUrl) URL.revokeObjectURL(previewUrl); if (photo.files[0]) { previewUrl = URL.createObjectURL(photo.files[0]); preview.src = previewUrl; preview.hidden = false; } }); }
    const workDate = document.querySelector('#work_date');
    if (workDate) {
        const day = document.createElement('p'); day.className = 'help'; workDate.after(day);
        const updateDay = () => { day.textContent = workDate.value ? 'Day: ' + new Date(workDate.value + 'T00:00:00Z').toLocaleDateString('en-US', { weekday: 'short', timeZone: 'UTC' }) : 'Select a date to see the day.'; };
        workDate.addEventListener('change', updateDay); updateDay();
    }
    const employeeHours = document.querySelector('[data-employee-hours]');
    const regularHours = document.querySelector('#regular_hours');
    if (employeeHours && regularHours && !employeeHours.dataset.editing) {
        const applyRegularHours = () => {
            const selected = employeeHours.options[employeeHours.selectedIndex];
            if (selected && selected.dataset.regularHours) regularHours.value = selected.dataset.regularHours;
        };
        employeeHours.addEventListener('change', applyRegularHours);
        if (employeeHours.value) applyRegularHours();
    }
});
