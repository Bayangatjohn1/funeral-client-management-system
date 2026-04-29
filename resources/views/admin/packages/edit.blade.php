@extends('layouts.panel')

@section('page_title','Edit Package')
@section('page_desc', 'Update package details, pricing, and included services.')

@section('content')
@php
    $returnTo = old('return_to', request('return_to', route('admin.packages.index')));
    $oldInclusions = old('inclusions');
    $oldFreebies = old('freebies');
    $initialInclusions = is_array($oldInclusions)
        ? $oldInclusions
        : ($oldInclusions !== null ? \App\Models\Package::parseLegacyItems($oldInclusions) : $package->inclusionNames());
    $initialFreebies = is_array($oldFreebies)
        ? $oldFreebies
        : ($oldFreebies !== null ? \App\Models\Package::parseLegacyItems($oldFreebies) : $package->freebieNames());
@endphp
<form id="packageEditForm" method="POST" action="{{ route('admin.packages.update', $package) }}" enctype="multipart/form-data" class="max-w-4xl w-full mx-auto font-ui-body">
@csrf
@method('PUT')
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="modal-shell-card rounded-2xl border border-slate-200 bg-white overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Edit Package</h2>
                <p class="text-base text-slate-500">Update service inclusions, pricing, and promo details.</p>
            </div>
            <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-semibold tracking-wide text-slate-700">PKG-{{ $package->id }}</span>
        </div>
    </div>

    <div class="p-6 grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="label-section">Name <span class="text-rose-500">*</span></label>
            <input type="text" name="name" value="{{ old('name', $package->name) }}" class="form-input" placeholder="First Class Package" required>
            @error('name') <div class="form-error">{{ $message }}</div> @enderror
            <div class="form-error hidden" data-field-error="name"></div>
        </div>

        <div>
            <label class="label-section">Coffin Type <span class="text-rose-500">*</span></label>
            <input type="text" name="coffin_type" value="{{ old('coffin_type', $package->coffin_type) }}" class="form-input" placeholder="Premium Coffin" required>
            @error('coffin_type') <div class="form-error">{{ $message }}</div> @enderror
            <div class="form-error hidden" data-field-error="coffin_type"></div>
        </div>

        <div>
            <label class="label-section">Price <span class="text-rose-500">*</span></label>
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $package->price) }}" class="form-input" placeholder="0.00" required>
            @error('price') <div class="form-error">{{ $message }}</div> @enderror
            <div class="form-error hidden" data-field-error="price"></div>
        </div>

        @include('admin.packages._list-inputs', compact('initialInclusions', 'initialFreebies'))

        <div>
            <label class="label-section">Promo Value Type</label>
            <select name="promo_value_type" class="form-select">
                <option value="">None</option>
                <option value="PERCENT" {{ old('promo_value_type', $package->promo_value_type) === 'PERCENT' ? 'selected' : '' }}>Percent</option>
                <option value="AMOUNT" {{ old('promo_value_type', $package->promo_value_type) === 'AMOUNT' ? 'selected' : '' }}>Fixed Amount</option>
            </select>
        </div>

        <div>
            <label class="label-section">Promo Value</label>
            <input type="number" step="0.01" name="promo_value" value="{{ old('promo_value', $package->promo_value) }}" class="form-input">
        </div>

        <div>
            <label class="label-section">Promo Label</label>
            <input type="text" name="promo_label" value="{{ old('promo_label', $package->promo_label) }}" class="form-input">
        </div>

        <div>
            <label class="label-section">Promo Starts At</label>
            <input type="datetime-local" name="promo_starts_at" value="{{ old('promo_starts_at', $package->promo_starts_at?->format('Y-m-d\TH:i')) }}" class="form-input">
        </div>

        <div>
            <label class="label-section">Promo Ends At</label>
            <input type="datetime-local" name="promo_ends_at" value="{{ old('promo_ends_at', $package->promo_ends_at?->format('Y-m-d\TH:i')) }}" class="form-input">
        </div>

        <div class="md:col-span-2 flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $package->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]">
                <span>Active</span>
            </label>
        </div>
    </div>
    </div>

    <div class="px-6 py-4 border-t border-slate-200 flex flex-wrap gap-2 justify-end">
        <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
            <i class="bi bi-save2"></i>
            Save Changes
        </button>
    </div>
</div>
</form>
<script>
    (function () {
        const form = document.getElementById('packageEditForm');
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
            [
                ['name', 'Package name is required.', 'Package name must include letters.'],
                ['coffin_type', 'Coffin type is required.', 'Coffin type must include valid description text.'],
                ['inclusions', 'At least one inclusion is required.', 'Inclusion must include valid description text.'],
            ].forEach(([field, requiredMessage, invalidMessage]) => {
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
