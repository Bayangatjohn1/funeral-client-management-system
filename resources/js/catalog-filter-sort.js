const FILTER_NAMES = {
    status: 'Status',
    availability: 'Availability',
    category: 'Category',
    package_status: 'Status',
    promotion_status: 'Promotion Status',
};

const TOOLBAR_SELECTOR = 'form[data-catalog-filter-toolbar]';
const FILTER_SELECTOR = 'select[name="status"], select[name="availability"], select[name="category"], select[name="package_status"], select[name="promotion_status"]';

function filterKey(select) {
    return select.name.replace(/\[\]$/, '');
}

function defaultValue(select) {
    return select.dataset.defaultValue ?? select.options[0]?.value ?? '';
}

function clearedValue(select) {
    if (select.multiple) return '';
    if (['status', 'availability', 'package_status', 'promotion_status'].includes(select.name)) return 'all';
    return '';
}

function isApplied(select) {
    if (select.multiple) return select.selectedOptions.length > 0;
    return select.value !== clearedValue(select);
}

function clearSelect(select) {
    if (select.multiple) {
        [...select.options].forEach(option => { option.selected = false; });
        return;
    }
    select.value = clearedValue(select);
}

function navigateWithFormState(form) {
    const url = new URL(form.action || window.location.href, window.location.origin);
    const params = new URLSearchParams(window.location.search);
    const formData = new FormData(form);

    // Drop the retired package dependency filter from links when the unified
    // package status selector is submitted.
    if (form.querySelector('select[name="package_status"]')) {
        params.delete('catalog_items');
        params.delete('catalog_items[]');
    }

    const submittedEntries = [...formData.entries()].filter(([key]) => key !== 'page' && !key.endsWith('_page'));
    const submittedKeys = new Set([...form.elements].map(element => element.name).filter(Boolean));
    submittedKeys.forEach(key => {
        if (key !== 'page' && !key.endsWith('_page')) params.delete(key);
    });
    for (const [key, value] of submittedEntries) {
        if (value !== '') params.append(key, value.toString());
    }

    for (const key of [...params.keys()]) {
        if (key === 'page' || key.endsWith('_page')) params.delete(key);
    }

    url.search = params.toString();
    const hub = form.closest('[data-service-hub][data-service-server-navigation]');
    if (hub) {
        hub.dispatchEvent(new CustomEvent('service-catalog:filter-navigate', {
            detail: { url: url.toString() },
        }));
    } else {
        window.location.assign(url.toString());
    }
}

function makeMenu(kind, selects, form) {
    const details = document.createElement('details');
    details.className = `catalog-control catalog-control--${kind}`;
    details.dataset.catalogControl = kind;

    const summary = document.createElement('summary');
    summary.setAttribute('aria-label', `${kind === 'filter' ? 'Filter' : 'Sort'} options`);
    const icon = document.createElement('i');
    icon.className = `bi ${kind === 'filter' ? 'bi-funnel' : 'bi-sort-down'}`;
    icon.setAttribute('aria-hidden', 'true');
    const label = document.createElement('span');
    label.dataset.controlLabel = '';
    label.textContent = kind === 'filter' ? 'Filter' : 'Sort';
    summary.append(icon, label);

    if (kind === 'filter') {
        const count = document.createElement('span');
        count.className = 'catalog-filter-count';
        count.dataset.filterCount = '';
        count.hidden = true;
        summary.append(count);
    }

    const chevron = document.createElement('i');
    chevron.className = 'bi bi-chevron-down catalog-control-chevron';
    chevron.setAttribute('aria-hidden', 'true');
    summary.append(chevron);

    const panel = document.createElement('div');
    panel.className = 'catalog-control-panel';
    panel.setAttribute('aria-label', kind === 'filter' ? 'Filter by' : 'Sort by');
    const heading = document.createElement('h3');
    heading.textContent = kind === 'filter' ? 'Filter by' : 'Sort by';
    panel.append(heading);

    selects.forEach((select) => {
        const field = document.createElement('fieldset');
        field.className = 'catalog-control-group';
        const legend = document.createElement('legend');
        legend.textContent = kind === 'filter'
            ? (select.dataset.filterCategory || FILTER_NAMES[filterKey(select)] || select.getAttribute('aria-label') || filterKey(select))
            : (select.getAttribute('aria-label') || 'Order');
        field.append(legend);

        const choices = document.createElement('div');
        choices.className = 'catalog-control-choices';
        [...select.options].forEach((option) => {
            const choice = document.createElement('button');
            choice.type = 'button';
            choice.className = 'catalog-control-option';
            choice.setAttribute('aria-pressed', option.selected ? 'true' : 'false');
            const optionIconName = option.dataset.icon;
            if (optionIconName && /^[a-z0-9-]+$/.test(optionIconName)) {
                const optionIcon = document.createElement('i');
                optionIcon.className = `bi bi-${optionIconName}`;
                optionIcon.setAttribute('aria-hidden', 'true');
                choice.append(optionIcon);
            }
            const optionLabel = document.createElement('span');
            optionLabel.textContent = option.dataset.displayLabel || option.textContent.trim();
            const check = document.createElement('i');
            check.className = 'bi bi-check2 catalog-control-option-check';
            check.setAttribute('aria-hidden', 'true');
            choice.append(optionLabel, check);
            choice.addEventListener('click', () => {
                if (select.multiple) {
                    option.selected = !option.selected;
                    choice.setAttribute('aria-pressed', option.selected ? 'true' : 'false');
                    // Use the catalog hub's existing async form submission path.
                    if (form.matches('[data-service-filter-form]')) form.requestSubmit();
                    else navigateWithFormState(form);
                    return;
                }
                select.value = option.value;
                navigateWithFormState(form);
            });
            choices.append(choice);
        });
        field.append(choices);
        panel.append(field);
    });

    details.append(summary, panel);
    return details;
}

function initToolbar(form) {
    if (form.dataset.catalogFilterReady === '1') return;

    const filterSelects = [...form.querySelectorAll(FILTER_SELECTOR)];
    const sortSelect = form.querySelector('select[name="sort"]');
    if (!filterSelects.length && !sortSelect) return;
    form.dataset.catalogFilterReady = '1';
    form.classList.add('catalog-filter-toolbar');

    const actions = document.createElement('div');
    actions.className = 'catalog-filter-sort-actions';
    if (filterSelects.length) actions.append(makeMenu('filter', filterSelects, form));
    if (sortSelect) actions.append(makeMenu('sort', [sortSelect], form));

    const filterCountLabel = actions.querySelector('[data-catalog-control="filter"] [data-control-label]');
    const setSortLabel = () => {
        if (!sortSelect) return;
        const selectedOption = sortSelect.options[sortSelect.selectedIndex];
        const label = actions.querySelector('[data-catalog-control="sort"] [data-control-label]');
        if (label) {
            label.textContent = sortSelect.value === defaultValue(sortSelect)
                ? 'Sort'
                : (selectedOption?.dataset.displayLabel || selectedOption?.textContent.trim() || 'Sort');
        }
    };

    filterSelects.forEach((select) => {
        select.dataset.defaultValue ??= defaultValue(select);
        select.closest('.table-toolbar-field, .table-toolbar-select-wrap, .service-extra-filter')?.classList.add('catalog-native-control');
    });
    if (sortSelect) {
        sortSelect.dataset.defaultValue ??= defaultValue(sortSelect);
        sortSelect.closest('.table-toolbar-field, .table-toolbar-select-wrap')?.classList.add('catalog-native-control');
        setSortLabel();
    }

    const oldReset = form.querySelector('[data-service-reset]');
    oldReset?.closest('.table-toolbar-reset-wrap')?.classList.add('catalog-legacy-reset');
    oldReset?.classList.add('catalog-legacy-reset');

    const serviceActions = form.querySelector('.service-filter-actions');
    form.querySelector('input[name="q"]')?.closest('.table-toolbar-field')?.classList.add('catalog-search-field');
    if (serviceActions) serviceActions.prepend(actions);
    else {
        const firstFilter = filterSelects[0]?.closest('.table-toolbar-field, .table-toolbar-select-wrap, .service-extra-filter');
        firstFilter?.before(actions);
        if (!firstFilter) form.append(actions);
    }

    const chips = document.createElement('div');
    chips.className = 'catalog-active-filter-chips';
    chips.dataset.activeFilterChips = '';
    chips.setAttribute('aria-label', 'Applied filters');
    chips.hidden = true;
    // The hub view switch belongs to the same toolbar; chips must follow both controls.
    const toolbar = form.closest('.service-hub-toolbar');
    (toolbar || form).after(chips);

    const renderChips = () => {
        chips.replaceChildren();
        let count = 0;

        filterSelects.forEach((select) => {
            const selectedOptions = select.multiple
                ? [...select.selectedOptions]
                : (isApplied(select) && select.options[select.selectedIndex] ? [select.options[select.selectedIndex]] : []);
            if (!selectedOptions.length) return;

            selectedOptions.forEach(selected => {
            const value = selected.value;
            const label = `${select.dataset.filterCategory || FILTER_NAMES[filterKey(select)] || select.getAttribute('aria-label') || filterKey(select)}: ${selected.textContent.trim()}`;
            const chip = document.createElement('span');
            chip.className = 'catalog-filter-chip';
            chip.dataset.tone = filterKey(select) === 'category' ? 'category'
                : ['active', 'available', 'no_issues'].includes(value) ? 'positive'
                    : ['inactive', 'unavailable', 'active_unavailable', 'archived', 'expired', 'turned_off'].includes(value) ? 'caution'
                        : value === 'scheduled' ? 'category' : 'neutral';
            const text = document.createElement('span');
            text.textContent = label;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('aria-label', `Remove ${label} filter`);
            const closeIcon = document.createElement('i');
            closeIcon.className = 'bi bi-x';
            closeIcon.setAttribute('aria-hidden', 'true');
            remove.append(closeIcon);
            remove.addEventListener('click', () => {
                if (select.multiple) {
                    const option = [...select.options].find(item => item.value === value);
                    if (option) option.selected = false;
                } else {
                    clearSelect(select);
                }
                navigateWithFormState(form);
            });
            chip.append(text, remove);
            chips.append(chip);
            count += 1;
            });
        });

        const countBadge = actions.querySelector('[data-filter-count]');
        if (countBadge) {
            countBadge.textContent = String(count);
            countBadge.hidden = count === 0;
        }
        if (filterCountLabel) filterCountLabel.textContent = 'Filter';

        if (count > 0) {
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.className = 'catalog-clear-all';
            clear.textContent = 'Clear filters';
            clear.setAttribute('aria-label', 'Clear all active filters');
            clear.addEventListener('click', () => {
                filterSelects.forEach(clearSelect);
                navigateWithFormState(form);
            });
            chips.append(clear);
            chips.hidden = false;
        }
    };

    renderChips();

    form.addEventListener('click', (event) => {
        const summary = event.target.closest('summary');
        if (!summary) return;
        const current = summary.closest('details');
        requestAnimationFrame(() => {
            actions.querySelectorAll('details[open]').forEach((item) => {
                if (item !== current) item.open = false;
            });
        });
    });

}

document.addEventListener('click', (event) => {
    document.querySelectorAll('.catalog-filter-sort-actions details[open]').forEach((item) => {
        if (!item.contains(event.target)) item.open = false;
    });
});

export function installCatalogFilterSort() {
    document.querySelectorAll(TOOLBAR_SELECTOR).forEach(initToolbar);
}

window.installCatalogFilterSort = installCatalogFilterSort;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installCatalogFilterSort, { once: true });
} else {
    installCatalogFilterSort();
}
