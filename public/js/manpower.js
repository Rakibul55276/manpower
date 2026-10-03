document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-menu-toggle]');
    if (toggle) toggle.addEventListener('click', () => { const side = document.querySelector('.sidebar'); side.classList.toggle('open'); toggle.setAttribute('aria-expanded', side.classList.contains('open') ? 'true' : 'false'); });
    document.querySelectorAll('form[data-confirm]').forEach(form => form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); }));
    document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
    const container = document.querySelector('[data-experiences]');
    const add = document.querySelector('[data-add-experience]');
    if (container && add) {
        const refresh = () => { add.disabled = container.children.length >= 5; document.querySelector('[data-experience-count]').textContent = container.children.length + ' / 5'; container.querySelectorAll('textarea').forEach((area, index) => area.setAttribute('aria-label', 'Previous experience ' + (index + 1))); };
        add.addEventListener('click', () => {
            if (container.children.length >= 5) return;
            const row = document.createElement('div'); row.className = 'experience-row';
            const area = document.createElement('textarea'); area.name = 'previous_experience[]'; area.maxLength = 2000; area.placeholder = 'Company, role, duration, and responsibilities…';
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn danger small'; remove.dataset.removeExperience = ''; remove.textContent = '− Remove';
            row.append(area, remove); container.append(row); refresh(); area.focus();
        });
        container.addEventListener('click', event => { if (event.target.closest('[data-remove-experience]')) { event.target.closest('.experience-row').remove(); refresh(); } }); refresh();
    }
    const photo = document.querySelector('[data-photo-input]');
    if (photo) { let previewUrl; photo.addEventListener('change', () => { const preview = document.querySelector('[data-photo-preview]'); if (previewUrl) URL.revokeObjectURL(previewUrl); if (photo.files[0]) { previewUrl = URL.createObjectURL(photo.files[0]); preview.src = previewUrl; preview.hidden = false; } }); }
});
