@extends('layouts.panel')

@section('page_title','Create Branch')
@section('page_desc', 'Register a new branch and configure branch information.')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.branches.index'))))
<form id="branchCreateStandaloneForm" method="POST" action="{{ route('admin.branches.store') }}" class="max-w-3xl w-full mx-auto font-ui-body">
@csrf
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Create Branch</h2>
                <p class="text-base text-slate-500">Register a new branch and configure branch information</p>
            </div>
            <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-semibold tracking-wide text-slate-700">
                {{ $nextCode }}
            </span>
        </div>
    </div>

    <div class="p-6 space-y-5">
        <div>
            <label class="label-section">Branch Code</label>
            <input type="text" value="{{ $nextCode }}" class="form-input bg-slate-100 text-slate-700 font-semibold" readonly>
            <div class="text-sm text-slate-500 mt-2">Branch code is auto-assigned and cannot be changed.</div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label-section">Branch Name <span class="text-rose-500">*</span></label>
                <input id="branch_name" type="text" name="branch_name" value="{{ old('branch_name') }}" class="form-input" placeholder="Caguioa Sabangan Funeral Home" autocomplete="off" inputmode="text" required>
                @error('branch_name') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="branch_name"></div>
            </div>

            <div>
                <label class="label-section">Address <span class="text-rose-500">*</span></label>
                <input type="text" name="address" value="{{ old('address') }}" class="form-input" placeholder="Street, City, Province" required>
                @error('address') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="address"></div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 flex items-center justify-between gap-4">
            <div>
                <div class="text-[1.1rem] leading-tight font-semibold text-slate-900">Branch Status</div>
                <p class="text-sm text-slate-500">Active branches can process new cases and payments</p>
            </div>
            <div class="flex items-center gap-3">
                <span id="branch-status-pill" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ old('is_active', 1) ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                    {{ old('is_active', 1) ? 'Active' : 'Inactive' }}
                </span>
                <input type="hidden" name="is_active" value="0">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-12 h-7 bg-slate-300 rounded-full peer peer-checked:bg-emerald-600 transition-colors"></div>
                    <div class="absolute left-[3px] top-[3px] h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"></div>
                </label>
            </div>
        </div>
    </div>

    <div class="px-6 py-4 border-t border-slate-200 flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
            <i class="bi bi-save2"></i>
            Save Branch
        </button>
    </div>
</div>
</form>
<script>
    (() => {
        const form = document.getElementById('branchCreateStandaloneForm');
        const input = document.getElementById('branch_name');
        const address = form?.querySelector('[name="address"]');
        const statusToggle = document.getElementById('is_active');
        const statusPill = document.getElementById('branch-status-pill');
        if (!input) return;
        const invalidClass = ['border-rose-300', 'bg-rose-50', 'focus:border-rose-500', 'focus:ring-rose-500'];
        const pattern = /^[\p{L}\p{M}][\p{L}\p{M}\s'.&-]*$/u;

        const normalize = (value) => String(value || '').replace(/\s+/g, ' ').trim();
        const showFieldError = (field, message) => {
            const target = form?.querySelector(`[name="${field}"]`);
            const error = form?.querySelector(`[data-field-error="${field}"]`);
            if (target) target.classList.add(...invalidClass);
            if (error) {
                error.textContent = message;
                error.classList.remove('hidden');
            }
        };
        const clearFieldError = (field) => {
            const target = form?.querySelector(`[name="${field}"]`);
            const error = form?.querySelector(`[data-field-error="${field}"]`);
            if (target) target.classList.remove(...invalidClass);
            if (error) {
                error.textContent = '';
                error.classList.add('hidden');
            }
        };

        const sync = () => {
            input.value = normalize(input.value);
            const finalValue = input.value.trim();
            if (!finalValue) {
                input.setCustomValidity('Branch name is required.');
                return;
            }
            if (!pattern.test(finalValue)) {
                input.setCustomValidity('Branch name must contain letters only (no numbers).');
                return;
            }
            input.setCustomValidity('');
        };

        form?.addEventListener('submit', (event) => {
            let valid = true;
            clearFieldError('branch_name');
            clearFieldError('address');
            input.value = normalize(input.value);
            if (address) address.value = normalize(address.value);
            if (!input.value) {
                valid = false;
                showFieldError('branch_name', 'Branch name is required.');
            } else if (/\d/.test(input.value) || !pattern.test(input.value)) {
                valid = false;
                showFieldError('branch_name', 'Branch name must contain letters only.');
            }
            if (!address?.value || !/[\p{L}\p{M}]/u.test(address.value) || /^\d+$/.test(address.value)) {
                valid = false;
                showFieldError('address', 'Address must include a valid place name.');
            }
            if (!valid) event.preventDefault();
        });
        input.addEventListener('input', () => clearFieldError('branch_name'));
        address?.addEventListener('input', () => clearFieldError('address'));
        sync();

        if (statusToggle && statusPill) {
            const syncStatus = () => {
                const active = !!statusToggle.checked;
                statusPill.textContent = active ? 'Active' : 'Inactive';
                statusPill.className = `inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ${active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`;
            };
            statusToggle.addEventListener('change', syncStatus);
            syncStatus();
        }
    })();
</script>
@endsection
