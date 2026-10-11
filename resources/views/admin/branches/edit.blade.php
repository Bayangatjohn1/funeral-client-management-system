@extends('layouts.panel')

@section('page_title','Edit Branch')
@section('page_desc', 'Update branch profile and operational details.')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.branches.index'))))
@include('admin.branches._edit_form_styles')
<form id="branchEditForm" method="POST" action="{{ route('admin.branches.update', $branch) }}" class="branch-edit-form max-w-3xl w-full mx-auto font-ui-body">
@csrf
@method('PUT')
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="branch-edit-panel rounded-2xl border border-slate-200 bg-white overflow-hidden">
    <div class="branch-edit-header px-6 py-5 border-b border-slate-200">
        <div class="branch-edit-heading">
            <span class="branch-edit-heading-icon"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
            <div>
                <h2 class="font-ui-heading">Edit Branch</h2>
                <p>Update the branch name and address. Change operating status from Branch Details.</p>
            </div>
        </div>
        <button type="button" class="branch-modal-close" aria-label="Close Edit Branch form"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>

    <div class="branch-edit-body p-6">
        <div class="branch-edit-fields">
            <div class="branch-edit-field">
                <label class="label-section" for="branch_edit_code">Branch Code</label>
                <input id="branch_edit_code" type="text" value="{{ $branch->branch_code }}" class="form-input branch-edit-code" readonly>
                <p class="branch-edit-help">Auto-assigned; cannot be changed.</p>
            </div>
            <div class="branch-edit-field">
                <label class="label-section" for="branch_name">Branch Name <span class="text-rose-500">*</span></label>
                <input id="branch_name" type="text" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}" class="form-input" placeholder="Caguioa Sabangan Funeral Home" autocomplete="off" inputmode="text" aria-describedby="branch-edit-name-error" required>
                @error('branch_name') <div class="form-error">{{ $message }}</div> @enderror
                <div id="branch-edit-name-error" class="form-error hidden" data-field-error="branch_name"></div>
            </div>
            <div class="branch-edit-field branch-edit-field--wide">
                <label class="label-section" for="branch_edit_address">Address <span class="text-rose-500">*</span></label>
                <input id="branch_edit_address" type="text" name="address" value="{{ old('address', $branch->address) }}" class="form-input" placeholder="Street, City, Province" aria-describedby="branch-edit-address-error" required>
                @error('address') <div class="form-error">{{ $message }}</div> @enderror
                <div id="branch-edit-address-error" class="form-error hidden" data-field-error="address"></div>
            </div>
        </div>
        <div class="branch-edit-status">
            <div>
                <span class="branch-edit-status-title">Operating status</span>
                <p>Use the separate status action in Branch Details to enable or disable this branch.</p>
            </div>
            <input type="hidden" name="is_active" value="{{ $branch->is_active ? 1 : 0 }}">
            <span class="status-badge {{ $branch->is_active ? 'status-badge-success' : 'status-badge-danger' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
    </div>

    <div class="branch-edit-footer px-6 py-4 border-t border-slate-200">
        <a href="{{ $returnTo }}" class="btn btn-outline branch-modal-cancel">Cancel</a>
        <button type="submit" class="btn btn-primary-custom"><i class="bi bi-save2" aria-hidden="true"></i>Save Changes</button>
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
