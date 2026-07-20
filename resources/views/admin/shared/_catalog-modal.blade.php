<div class="svc-modal" data-service-catalog-modal hidden>
    <div class="svc-modal__panel" role="dialog" aria-modal="true" aria-labelledby="serviceCatalogModalTitle">
        <button type="button" class="svc-modal__close" data-service-catalog-close aria-label="Close details"><i class="bi bi-x-lg"></i></button>
        <div class="svc-modal__content" data-service-catalog-content></div>
    </div>
</div>

<script>
(function () {
    const modal = document.querySelector('[data-service-catalog-modal]');
    const content = document.querySelector('[data-service-catalog-content]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const canManage = @json($canManage ?? false);
    let activeRow = null;
    let activeKind = null;
    let storeUrl = null;
    let mode = 'view';
    let saving = false;

    if (!modal || !content) return;

    const money = value => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));

    function openModal(row) {
        activeRow = row;
        activeKind = row?.dataset.catalogKind || null;
        storeUrl = null;
        mode = 'view';
        renderView();
        modal.hidden = false;
        document.body.classList.add('overflow-hidden');
        setTimeout(() => modal.querySelector('[data-service-catalog-close]')?.focus(), 20);
    }

    function openCreate(kind, url) {
        activeRow = null;
        activeKind = kind;
        storeUrl = url;
        mode = 'create';
        modal.hidden = false;
        document.body.classList.add('overflow-hidden');
        renderEdit();
        setTimeout(() => content.querySelector('input[name="name"]')?.focus(), 20);
    }

    function closeModal() {
        modal.hidden = true;
        content.innerHTML = '';
        activeRow = null;
        activeKind = null;
        storeUrl = null;
        mode = 'view';
        document.body.classList.remove('overflow-hidden');
    }

    function label() {
        return (activeRow?.dataset.catalogKind || activeKind) === 'casket' ? 'Casket / Coffin' : 'Add-on';
    }

    function renderView() {
        if (!activeRow) return;
        mode = 'view';
        const kind = activeRow.dataset.catalogKind;
        const isCasket = kind === 'casket';
        const priceLabel = isCasket ? 'Reference Value' : 'Standard Price';
        const price = Number(activeRow.dataset.price || 0);
        content.innerHTML = `
            <div class="svc-modal__head">
                <div>
                    <p class="svc-modal__eyebrow">${label()}</p>
                    <h2 id="serviceCatalogModalTitle">${esc(activeRow.dataset.name)}</h2>
                </div>
                <span class="svc-status ${activeRow.dataset.active === '1' ? 'is-active' : 'is-archived'}">${esc(activeRow.dataset.status)}</span>
            </div>
            <div class="svc-detail-grid">
                ${isCasket ? `
                    <div><span>Type / Material</span><strong>${esc(activeRow.dataset.material || 'Not specified')}</strong></div>
                ` : `
                    <div><span>Category</span><strong>${esc(activeRow.dataset.category || 'General')}</strong></div>
                    <div><span>Unit</span><strong>${esc(activeRow.dataset.unit || 'item')}</strong></div>
                `}
                <div><span>${priceLabel}</span><strong>${price > 0 || !isCasket ? '&#8369;' + money(price) : 'Reference value not configured'}</strong></div>
                <div class="svc-detail-wide"><span>Description</span><strong>${esc(activeRow.dataset.description || 'No description provided.')}</strong></div>
            </div>
            <div class="svc-modal__actions">
                ${canManage ? `<button type="button" class="btn btn-primary-custom" data-service-catalog-edit><i class="bi bi-pencil-square"></i> Edit</button>
                <button type="button" class="btn-outline" data-service-catalog-toggle><i class="bi bi-${activeRow.dataset.active === '1' ? 'archive' : 'arrow-counterclockwise'}"></i> ${activeRow.dataset.active === '1' ? 'Archive' : 'Restore'}</button>` : ''}
                <button type="button" class="btn-outline" data-service-catalog-close><i class="bi bi-x-circle"></i> Close</button>
            </div>
        `;
    }

    function formValue(field, fallback = '', values = {}) {
        if (Object.prototype.hasOwnProperty.call(values, field)) return values[field] ?? fallback;
        if (activeRow) return activeRow.dataset[field] ?? fallback;
        return fallback;
    }

    function renderEdit(errors = {}, message = '', values = {}) {
        const isCasket = (activeRow?.dataset.catalogKind || activeKind) === 'casket';
        const isCreate = mode === 'create';
        content.innerHTML = `
            <form class="svc-edit-form" data-service-catalog-form>
                <div class="svc-modal__head">
                    <div>
                        <p class="svc-modal__eyebrow">${isCreate ? 'Add' : 'Edit'} ${label()}</p>
                        <h2 id="serviceCatalogModalTitle">${isCreate ? (isCasket ? 'New Casket / Coffin' : 'New Add-on') : esc(activeRow.dataset.name)}</h2>
                    </div>
                </div>
                ${message ? `<div class="svc-error">${esc(message)}</div>` : ''}
                <div class="svc-form-grid">
                    <label><span>Name</span><input class="form-input" name="name" value="${esc(formValue('name', '', values))}" required maxlength="150" pattern=".*[A-Za-z].*" title="${isCasket ? 'Enter a casket name with letters.' : 'Enter an add-on name with letters.'}" placeholder="${isCasket ? 'e.g. Premium Hardwood Casket' : 'e.g. Extra flower arrangement'}"></label>
                    ${isCasket
                        ? `<label><span>Type / Material</span><input class="form-input" name="type_or_material" value="${esc(formValue('material', '', values))}" maxlength="100" placeholder="e.g. Hardwood, Metal, Glass"></label>
                           <label><span>Reference Value</span><input class="form-input" type="number" inputmode="decimal" min="0.01" step="0.01" name="standard_price" value="${esc(formValue('price', '', values))}" required placeholder="0.00"><small class="svc-form-help">For future upgrade charges only.</small></label>`
                        : `<label><span>Category</span><input class="form-input" name="category" value="${esc(formValue('category', 'General', values))}" maxlength="80" pattern=".*[A-Za-z].*" title="Enter a category with letters." placeholder="e.g. Flowers, Transport, Media"></label>
                           <label><span>Standard Price</span><input class="form-input" type="number" inputmode="decimal" min="0" step="0.01" name="price" value="${esc(formValue('price', '', values))}" required placeholder="0.00"></label>
                           <label><span>Unit</span><input class="form-input" name="unit" value="${esc(formValue('unit', 'item', values))}" required maxlength="40" pattern=".*[A-Za-z].*" title="Enter a unit with letters." placeholder="item, set, day, trip, service"></label>`
                    }
                    <label class="svc-detail-wide"><span>Description</span><textarea class="form-input" name="description" rows="3" maxlength="500" placeholder="Optional notes for staff.">${esc(formValue('description', '', values))}</textarea></label>
                </div>
                <div class="svc-field-errors">${Object.values(errors).flat().map(error => `<div>${esc(error)}</div>`).join('')}</div>
                <div class="svc-modal__actions">
                    <button type="submit" class="btn btn-primary-custom" data-service-catalog-save><i class="bi bi-check2-circle"></i> ${isCreate ? 'Save' : 'Save Changes'}</button>
                    <button type="button" class="btn-outline" data-service-catalog-cancel><i class="bi bi-x-circle"></i> Cancel</button>
                </div>
            </form>
        `;
    }

    function updateRow(catalog) {
        if (!activeRow || !catalog) return;
        activeRow.dataset.name = catalog.name || '';
        activeRow.dataset.description = catalog.description || '';
        activeRow.dataset.price = catalog.price || 0;
        activeRow.dataset.active = catalog.is_active ? '1' : '0';
        activeRow.dataset.status = catalog.is_active ? 'Active' : 'Archived';
        if (activeRow.dataset.catalogKind === 'casket') {
            activeRow.dataset.material = catalog.material || '';
        } else {
            activeRow.dataset.category = catalog.category || 'General';
            activeRow.dataset.unit = catalog.unit || 'item';
        }
        activeRow.querySelector('[data-catalog-cell="name"]')?.replaceChildren(document.createTextNode(catalog.name || ''));
        const statusCell = activeRow.querySelector('[data-catalog-cell="status"]');
        if (statusCell) statusCell.textContent = catalog.is_active ? 'Active' : 'Archived';
        const priceCell = activeRow.querySelector('[data-catalog-cell="price"]');
        if (priceCell) {
            if (activeRow.dataset.catalogKind === 'casket' && Number(catalog.price || 0) <= 0) {
                priceCell.innerHTML = '<span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">Price not configured</span>';
            } else {
                priceCell.textContent = 'PHP ' + money(catalog.price || 0);
            }
        }
        if (activeRow.dataset.catalogKind === 'casket') {
            const materialCell = activeRow.querySelector('[data-catalog-cell="material"]');
            if (materialCell) materialCell.textContent = catalog.material || '-';
        } else {
            const categoryCell = activeRow.querySelector('[data-catalog-cell="category"]');
            const unitCell = activeRow.querySelector('[data-catalog-cell="unit"]');
            const descriptionCell = activeRow.querySelector('[data-catalog-cell="description"]');
            if (categoryCell) categoryCell.textContent = catalog.category || 'General';
            if (unitCell) unitCell.textContent = catalog.unit || 'item';
            if (descriptionCell) {
                descriptionCell.textContent = catalog.description || '';
                descriptionCell.classList.toggle('hidden', !catalog.description);
            }
        }
    }

    async function submitForm(form) {
        if (saving || (!activeRow && !storeUrl)) return;
        saving = true;
        form.querySelector('[data-service-catalog-save]')?.setAttribute('disabled', 'disabled');
        try {
            const payload = new FormData(form);
            const isCreate = mode === 'create';
            const values = Object.fromEntries(payload.entries());
            if (values.type_or_material !== undefined) values.material = values.type_or_material;
            if (values.standard_price !== undefined) values.price = values.standard_price;
            if (!isCreate) payload.append('_method', 'PUT');
            const response = await fetch(isCreate ? storeUrl : activeRow.dataset.updateUrl, {
                method: 'POST',
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf},
                body: payload,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                renderEdit(data.errors || {}, data.message || 'Please review the details.', values);
                return;
            }
            if (isCreate) {
                window.location.reload();
                return;
            }
            updateRow(data.catalog);
            renderView();
        } catch (error) {
            const values = Object.fromEntries(new FormData(form).entries());
            if (values.type_or_material !== undefined) values.material = values.type_or_material;
            if (values.standard_price !== undefined) values.price = values.standard_price;
            renderEdit({}, 'Unable to save right now. Please try again.', values);
        } finally {
            saving = false;
        }
    }

    document.addEventListener('click', event => {
        const view = event.target.closest('[data-catalog-view]');
        if (view) {
            event.preventDefault();
            openModal(view.closest('[data-catalog-row]'));
            return;
        }
        const create = event.target.closest('[data-service-catalog-create]');
        if (create) {
            event.preventDefault();
            openCreate(create.dataset.catalogKind, create.dataset.storeUrl);
            return;
        }
        if (event.target.closest('[data-service-catalog-close]') || event.target === modal) closeModal();
        if (event.target.closest('[data-service-catalog-edit]')) {
            mode = 'edit';
            renderEdit();
        }
        if (event.target.closest('[data-service-catalog-cancel]')) {
            if (mode === 'create') closeModal();
            else renderView();
        }
        if (event.target.closest('[data-service-catalog-toggle]') && activeRow) {
            const action = activeRow.dataset.active === '1' ? 'Archive' : 'Restore';
            if (confirm(action + ' this item?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = activeRow.dataset.toggleUrl;
                form.innerHTML = `<input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="PATCH">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    });

    document.addEventListener('keydown', event => {
        if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('[data-catalog-row]')) {
            event.preventDefault();
            openModal(event.target);
        }
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });

    content.addEventListener('submit', event => {
        const form = event.target.closest('[data-service-catalog-form]');
        if (!form) return;
        event.preventDefault();
        submitForm(form);
    });
})();
</script>
