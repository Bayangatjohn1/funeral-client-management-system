@extends('layouts.panel')

@section('page_title','Create User')
@section('page_desc', 'Create a new system user and assign role access.')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.users.index'))))
<form id="userCreateForm" method="POST" action="{{ route('admin.users.store') }}" class="max-w-4xl w-full mx-auto font-ui-body">
@csrf
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="modal-shell-card rounded-2xl border border-slate-200 bg-white overflow-hidden">
    <div class="px-6 py-5 border-b border-slate-200">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Create User</h2>
                <p class="text-base text-slate-500">Create a new system user and assign role access.</p>
            </div>
            <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-semibold tracking-wide text-slate-700">
                New Account
            </span>
        </div>
    </div>

    <div class="p-6 space-y-5">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label-section">Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="form-input" required>
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="label-section">Email <span class="text-rose-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-input" required>
                @error('email') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="label-section">Password <span class="text-rose-500">*</span></label>
                <input type="password" name="password" class="form-input" required>
                @error('password') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="label-section">Role <span class="text-rose-500">*</span></label>
                <select name="role" id="role" class="form-select" required>
                    <option value="staff" {{ old('role')=='staff' ? 'selected' : '' }}>Staff</option>
                    <option value="admin" {{ old('role')=='admin' ? 'selected' : '' }}>Branch Admin</option>
                </select>
                @error('role') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-hint">New admin accounts are created as Branch Admins automatically.</div>
            </div>

            <div class="md:col-span-2">
                <label class="label-section">Branch</label>
                <select name="branch_id" id="branch_id" class="form-select">
                    <option value="">- None -</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id')==$branch->id ? 'selected' : '' }}>
                            {{ $branch->branch_name }}
                        </option>
                    @endforeach
                </select>
                @error('branch_id') <div class="form-error">{{ $message }}</div> @enderror
                <div class="form-hint">Branch is required for staff and branch admin accounts.</div>
            </div>
        </div>

        <div id="cross_branch_wrap" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-4">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="hidden" name="can_encode_any_branch" value="0">
                <input type="checkbox" id="can_encode_any_branch" name="can_encode_any_branch" value="1" {{ old('can_encode_any_branch') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]">
                Allow cross-branch encoding (Main Branch staff only)
            </label>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label-section">Contact Number</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="form-input" placeholder="+63 9XX XXX XXXX">
                @error('contact_number') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="label-section">Position</label>
                <input type="text" name="position" value="{{ old('position') }}" class="form-input" placeholder="Staff/Admin Position">
                @error('position') <div class="form-error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <label class="label-section">Address</label>
            <input type="text" name="address" value="{{ old('address') }}" class="form-input">
            @error('address') <div class="form-error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="px-6 py-4 border-t border-slate-200 flex flex-wrap items-center justify-end gap-2">
        <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
            <i class="bi bi-save2"></i>
            Save User
        </button>
    </div>
</div>
</form>

<script>
    (function () {
        const roleSelect = document.getElementById('role');
        const branchSelect = document.getElementById('branch_id');
        const crossWrap = document.getElementById('cross_branch_wrap');
        const crossCheckbox = document.getElementById('can_encode_any_branch');

        function sync() {
            const needsBranch = roleSelect && ['staff', 'admin'].includes(roleSelect.value);
            const isStaff = roleSelect && roleSelect.value === 'staff';
            if (crossWrap) {
                crossWrap.classList.toggle('hidden', !isStaff);
            }
            if (branchSelect) {
                branchSelect.required = !!needsBranch;
            }
            if (!isStaff && crossCheckbox) {
                crossCheckbox.checked = false;
            }
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', sync);
        }

        sync();
    })();
</script>
@endsection
