@extends('layouts.panel')

@section('page_title','Edit Branch')
@section('page_desc', 'Update branch profile and operational details.')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.branches.index'))))
<style>
.branch-edit-form {
    color:var(--ink);
}
.branch-edit-form > .rounded-2xl {
    border:1px solid #AEBFA6 !important;
    border-radius:.75rem !important;
    background:#D3DEC9 !important;
    box-shadow:none !important;
}
.branch-edit-form > .rounded-2xl > .border-b {
    border-top-left-radius:.75rem;
    border-top-right-radius:.75rem;
}
.branch-edit-form > .rounded-2xl > .border-t {
    border-bottom-left-radius:.75rem;
    border-bottom-right-radius:.75rem;
}
.branch-edit-form .border-b,
.branch-edit-form .border-t,
.branch-edit-form .border-slate-200 {
    border-color:#AEBFA6 !important;
}
.branch-edit-form .px-6.py-5 {
    background:#C7D5BE !important;
    padding:1rem 1.15rem !important;
}
.branch-edit-form h2 {
    color:var(--ink) !important;
    font-size:1.25rem !important;
    font-weight:760 !important;
}
.branch-edit-form p,
.branch-edit-form .text-slate-500,
.branch-edit-form .text-slate-700 {
    color:#566653 !important;
    font-weight:600;
}
.branch-edit-form .p-6 {
    padding:1rem !important;
}
.branch-edit-form .rounded-xl {
    border-color:#AEBFA6 !important;
    background:#DDE8D6 !important;
    box-shadow:none !important;
}
.branch-edit-form .label-section {
    color:#566653 !important;
    font-size:.76rem !important;
    font-weight:680 !important;
}
.branch-edit-form .form-input {
    min-height:2.65rem;
    border:1px solid #AEBFA6 !important;
    border-radius:.65rem;
    background:#E9F0E4 !important;
    color:var(--ink) !important;
    font-weight:650;
    box-shadow:none !important;
}
.branch-edit-form .form-input:focus {
    border-color:#8EA083 !important;
    background:#F4F8EF !important;
    box-shadow:none !important;
}
.branch-edit-form .branch-modal-close,
.branch-edit-form .btn-outline {
    border:1px solid #AEBFA6 !important;
    border-radius:.55rem !important;
    background:#E9F0E4 !important;
    color:#3E4A3D !important;
    box-shadow:none !important;
}
.branch-edit-form .branch-modal-close:hover,
.branch-edit-form .btn-outline:hover {
    background:#DDE8D6 !important;
    color:var(--ink) !important;
}
.branch-edit-form .btn-primary-custom {
    min-height:2.55rem;
    border-radius:.55rem !important;
    background:#344333 !important;
    border-color:#344333 !important;
    color:#fff !important;
    box-shadow:none !important;
}
.branch-edit-form .btn-primary-custom:hover {
    background:#2F3A2E !important;
    border-color:#2F3A2E !important;
}
</style>
<form id="branchEditForm" method="POST" action="{{ route('admin.branches.update', $branch) }}" class="branch-edit-form max-w-3xl w-full mx-auto font-ui-body">
@csrf
@method('PUT')
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Branch details</h2>
                <p class="text-base text-slate-500">Update the branch name, address, and operating status.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-semibold tracking-wide text-slate-700">
                    {{ $branch->branch_code }}
                </span>
                <button type="button" class="branch-modal-close inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-300 bg-white text-slate-400 hover:text-slate-700 focus:outline-none">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="p-6 space-y-5">
        <div class="rounded-xl border px-4 py-4">
            <label class="label-section">Branch Code</label>
            <input type="text" value="{{ $branch->branch_code }}" class="form-input bg-slate-100 text-slate-700 font-semibold" readonly>
            <div class="text-sm text-slate-500 mt-2">Branch code is auto-assigned and cannot be changed.</div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label-section">Branch Name <span class="text-rose-500">*</span></label>
                <input id="branch_name" type="text" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}" class="form-input" placeholder="Caguioa Sabangan Funeral Home" autocomplete="off" inputmode="text" required>
                @error('branch_name') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="branch_name"></div>
            </div>

            <div>
                <label class="label-section">Address <span class="text-rose-500">*</span></label>
                <input type="text" name="address" value="{{ old('address', $branch->address) }}" class="form-input" placeholder="Street, City, Province" required>
                @error('address') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-error hidden" data-field-error="address"></div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 flex items-center justify-between gap-4">
            <div>
                <div class="text-[1.1rem] leading-tight font-semibold text-slate-900">Operating status</div>
                <p class="text-sm text-slate-500">Active branches can process new cases and payments</p>
            </div>
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', $branch->is_active) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-12 h-7 bg-slate-300 rounded-full peer peer-checked:bg-emerald-600 transition-colors"></div>
                    <div class="absolute left-[3px] top-[3px] h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"></div>
                </label>
            </div>
        </div>
    </div>

    <div class="px-6 py-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div class="text-sm text-slate-500">Review the branch details before saving.</div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $returnTo }}" class="btn btn-outline branch-modal-cancel">Cancel</a>
            <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
                <i class="bi bi-save2"></i>
                Save Changes
            </button>
        </div>
    </div>
</div>
</form>
<script>
    (() => {
        const input = document.getElementById('branch_name');
        const form = document.getElementById('branchEditForm');
        const address = form?.querySelector('[name="address"]');
        if (!input) return;
        const pattern = /^[\p{L}\p{M}][\p{L}\p{M}\s'.&-]*$/u;
        const invalidClass = ['border-rose-300', 'bg-rose-50', 'focus:border-rose-500', 'focus:ring-rose-500'];

        const normalize = (value) => String(value || '')
            .replace(/\s+/g, ' ')
            .trim();
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
            if (/\d/.test(finalValue) || !pattern.test(finalValue)) {
                input.setCustomValidity('Branch name must contain letters only.');
                return;
            }
            input.setCustomValidity('');
        };

        input.addEventListener('input', () => clearFieldError('branch_name'));
        address?.addEventListener('input', () => clearFieldError('address'));
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
        sync();

    })();
</script>
@endsection
