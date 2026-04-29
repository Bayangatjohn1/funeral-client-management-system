@extends('layouts.panel')

@section('page_title', 'Add Package')
@section('page_desc', 'Create a new service package with pricing and inclusions.')

@section('content')
@php
    $returnTo = old('return_to', request('return_to', route('admin.packages.index')));
    $oldInclusions = old('inclusions', ['']);
    $oldFreebies = old('freebies', ['']);
    $initialInclusions = is_array($oldInclusions) ? $oldInclusions : \App\Models\Package::parseLegacyItems($oldInclusions);
    $initialFreebies = is_array($oldFreebies) ? $oldFreebies : \App\Models\Package::parseLegacyItems($oldFreebies);
@endphp
<form id="packageCreateForm" method="POST" action="{{ route('admin.packages.store') }}" class="max-w-4xl w-full mx-auto font-ui-body">
@csrf
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="modal-shell-card rounded-2xl border border-slate-200 bg-white overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200">
        <h2 class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Add Package</h2>
        <p class="text-base text-slate-500">Create a new service package with pricing and inclusions.</p>
    </div>

    <div class="p-6 space-y-5">
        <div>
            <label class="label-section">Package Name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="First Class Package" required>
            @error('name') <div class="form-error">{{ $message }}</div> @enderror
            <div class="form-error hidden" data-field-error="name"></div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label-section">Coffin Type <span class="text-rose-500">*</span></label>
                <input type="text" name="coffin_type" value="{{ old('coffin_type') }}" class="form-input" placeholder="Premium Coffin" required>
                @error('coffin_type') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="coffin_type"></div>
            </div>
            <div>
                <label class="label-section">Price <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" name="price" value="{{ old('price') }}" class="form-input" placeholder="0.00" required>
                @error('price') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="price"></div>
            </div>
        </div>

        @include('admin.packages._list-inputs', compact('initialInclusions', 'initialFreebies'))

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-4">
            <div class="text-[1.1rem] leading-tight text-slate-900 font-ui-heading">Promo Settings (Optional)</div>
            <div>
                <label class="label-section">Promo Label</label>
                <input type="text" name="promo_label" value="{{ old('promo_label') }}" class="form-input" placeholder="e.g. Summer Promo">
                @error('promo_label') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="label-section">Promo Value Type</label>
                    <select name="promo_value_type" class="form-select">
                        <option value="">None</option>
                        <option value="AMOUNT" {{ old('promo_value_type') === 'AMOUNT' ? 'selected' : '' }}>Amount</option>
                        <option value="PERCENT" {{ old('promo_value_type') === 'PERCENT' ? 'selected' : '' }}>Percent</option>
                    </select>
                    @error('promo_value_type') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="label-section">Promo Value</label>
                    <input type="number" step="0.01" min="0" name="promo_value" value="{{ old('promo_value') }}" class="form-input" placeholder="0.00">
                    @error('promo_value') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="label-section">Promo Start</label>
                    <input type="datetime-local" name="promo_starts_at" value="{{ old('promo_starts_at') }}" class="form-input">
                    @error('promo_starts_at') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label class="label-section">Promo End</label>
                    <input type="datetime-local" name="promo_ends_at" value="{{ old('promo_ends_at') }}" class="form-input">
                    @error('promo_ends_at') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 font-medium">
                <input type="hidden" name="promo_is_active" value="0">
                <input type="checkbox" name="promo_is_active" value="1" {{ old('promo_is_active') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]">
                Enable promo for this package
            </label>
        </div>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700 font-medium">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]">
            Active package
        </label>
    </div>

    <div class="px-6 py-4 border-t border-slate-200 flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
            <i class="bi bi-save2"></i>
            Save Package
        </button>
    </div>
</div>
</form>
<script>
    (function () {
        const form = document.getElementById('packageCreateForm');
        if (!form) return;
        const invalidClass = ['border-rose-300', 'bg-rose-50', 'focus:border-rose-500', 'focus:ring-rose-500'];
        const normalizeText = (value) => String(value || '').replace(/\s+/g, ' ').trim();
        const hasLetter = (value) => /[\p{L}\p{M}]/u.test(value);
        const isNumbersOnly = (value) => /^\d+(?:\.\d+)?$/.test(value);
        const showFieldError = (field, message) => {
            const input = form.querySelector(`[name="${field}"], [name="${field}[]"]`);
            const error = form.querySelector(`[data-field-error="${field}"]`);
            if (input) input.classList.add(...invalidClass);
            if (error) {
                error.textContent = message;
                error.classList.remove('hidden');
            }
        };
        const clearFieldError = (field) => {
            const input = form.querySelector(`[name="${field}"], [name="${field}[]"]`);
            const error = form.querySelector(`[data-field-error="${field}"]`);
            if (input) input.classList.remove(...invalidClass);
            if (error) {
                error.textContent = '';
                error.classList.add('hidden');
            }
        };
        form.addEventListener('input', (event) => {
            if (event.target?.name) clearFieldError(event.target.name.replace('[]', ''));
        });
        form.querySelector('[name="price"]')?.addEventListener('input', (event) => {
            if (Number(event.target.value) < 0) event.target.value = '0';
        });
        form.addEventListener('submit', (event) => {
            let valid = true;
            ['name', 'coffin_type', 'price', 'inclusions', 'freebies'].forEach(clearFieldError);
            ['name', 'coffin_type'].forEach((field) => {
                const input = form.querySelector(`[name="${field}"]`);
                if (input) input.value = normalizeText(input.value);
            });
            form.querySelectorAll('[name="inclusions[]"], [name="freebies[]"]').forEach((input) => {
                input.value = normalizeText(input.value);
            });
            const textRules = [
                ['name', 'Package name is required.', 'Package name must include letters.'],
                ['coffin_type', 'Coffin type is required.', 'Coffin type must include valid description text.'],
                ['inclusions', 'At least one inclusion is required.', 'Inclusion must include valid description text.'],
            ];
            textRules.forEach(([field, requiredMessage, invalidMessage]) => {
                const values = field === 'inclusions'
                    ? [...form.querySelectorAll('[name="inclusions[]"]')].map((input) => input.value).filter(Boolean)
                    : [form.querySelector(`[name="${field}"]`)?.value || ''];
                const value = values[0] || '';
                if (!value) {
                    valid = false;
                    showFieldError(field, requiredMessage);
                } else if (values.some((item) => !hasLetter(item) || isNumbersOnly(item))) {
                    valid = false;
                    showFieldError(field, invalidMessage);
                }
            });
            const freebies = [...form.querySelectorAll('[name="freebies[]"]')].map((input) => input.value).filter(Boolean);
            if (freebies.some((item) => !hasLetter(item) || isNumbersOnly(item))) {
                valid = false;
                showFieldError('freebies', 'Freebie must include valid description text.');
            }
            const price = form.querySelector('[name="price"]');
            if (!price?.value) {
                valid = false;
                showFieldError('price', 'Price is required.');
            } else if (Number.isNaN(Number(price.value))) {
                valid = false;
                showFieldError('price', 'Price must be a valid amount.');
            } else if (Number(price.value) < 0) {
                valid = false;
                showFieldError('price', 'Price cannot be negative.');
            }
            if (!valid) event.preventDefault();
        });

        const makeRow = (list) => {
            const placeholder = list.dataset.placeholder || '';
            const inputName = `${list.dataset.list}[]`;
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.dataset.listRow = '';
            row.innerHTML = `
                <input type="text" name="${inputName}" class="form-input flex-1" placeholder="${placeholder}">
                <button type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-rose-100 bg-rose-50 text-rose-600 hover:bg-rose-100" data-list-remove aria-label="Remove item">
                    <i class="bi bi-trash"></i>
                </button>`;
            return row;
        };
        form.querySelectorAll('[data-list-add]').forEach((button) => {
            button.addEventListener('click', () => {
                const list = form.querySelector(`[data-list="${button.dataset.listAdd}"]`);
                if (!list) return;
                const row = makeRow(list);
                list.appendChild(row);
                row.querySelector('input')?.focus();
                clearFieldError(button.dataset.listAdd);
            });
        });
        form.addEventListener('click', (event) => {
            const button = event.target.closest('[data-list-remove]');
            if (!button) return;
            const list = button.closest('[data-list]');
            const rows = list ? [...list.querySelectorAll('[data-list-row]')] : [];
            if (!list || (list.dataset.required === '1' && rows.length <= 1)) {
                button.closest('[data-list-row]')?.querySelector('input')?.focus();
                return;
            }
            button.closest('[data-list-row]')?.remove();
            if (list.querySelectorAll('[data-list-row]').length === 0) {
                list.appendChild(makeRow(list));
            }
        });
    })();
</script>
@endsection
