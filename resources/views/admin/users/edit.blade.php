@extends('layouts.panel')

@section('page_title','Edit User')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.users.index'))))
<form id="userEditForm" method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-3xl w-full mx-auto space-y-6">
@csrf
@method('PUT')
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div class="p-5 md:p-6 space-y-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Edit User</h2>
            <p class="text-sm text-slate-500">Update account details and access for {{ $user->name }}.</p>
        </div>
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wide">User ID: {{ $user->id }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="label-section">Name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input" required>
            @error('name') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required>
            @error('email') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Role</label>
            <select name="role" id="role" class="form-select" required>
                <option value="staff" {{ old('role', $user->role)=='staff' ? 'selected' : '' }}>Staff</option>
                <option value="admin" {{ old('role', $user->role)=='admin' ? 'selected' : '' }}>Admin</option>
                <option value="owner" {{ old('role', $user->role)=='owner' ? 'selected' : '' }}>Owner</option>
            </select>
        </div>

        <div>
            <label class="label-section">Branch</label>
            <select name="branch_id" id="branch_id" class="form-select">
                <option value="">- None -</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ old('branch_id', $user->branch_id)==$branch->id ? 'selected' : '' }}>
                        {{ $branch->branch_name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="md:col-span-2" id="cross_branch_wrap" class="hidden">
            <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="hidden" name="can_encode_any_branch" value="0">
                <input type="checkbox" name="can_encode_any_branch" value="1" {{ old('can_encode_any_branch', $user->can_encode_any_branch) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-[var(--brand-mid)] focus:ring-[var(--brand-mid)]">
                <span>Allow cross-branch encoding (Main Branch staff only)</span>
            </label>
        </div>

        <div>
            <label class="label-section">Contact Number</label>
            <input type="text" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" class="form-input">
            @error('contact_number') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Position</label>
            <input type="text" name="position" value="{{ old('position', $user->position) }}" class="form-input" placeholder="Staff/Admin Position">
            @error('position') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="label-section">Address</label>
            <input type="text" name="address" value="{{ old('address', $user->address) }}" class="form-input">
            @error('address') <div class="form-error">{{ $message }}</div> @enderror
        </div>
    </div>
</div>

<div class="flex flex-wrap gap-2 pt-3 justify-end">
    <a href="{{ $returnTo }}" class="btn btn-outline">Cancel</a>
    <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
        <i class="bi bi-save2"></i>
        Save Changes
    </button>
</div>
</form>

<div class="mt-8 max-w-3xl border rounded bg-white p-4">
    <div class="font-semibold mb-2">Reset Password (Optional)</div>
    <form method="POST" action="{{ route('admin.users.resetPassword', $user) }}" class="space-y-3">
        @csrf
        @method('PATCH')

        <div>
            <label class="block text-sm font-medium">New Password</label>
            <input type="password" name="password" class="w-full border p-2 rounded">
        </div>

        <div>
            <label class="block text-sm font-medium">Confirm Password</label>
            <input type="password" name="password_confirmation" class="w-full border p-2 rounded">
        </div>

        <button class="bg-gray-900 text-white px-4 py-2 rounded">Reset</button>
    </form>
</div>

<script>
    (function () {
        const roleSelect = document.getElementById('role');
        const branchSelect = document.getElementById('branch_id');
        const crossWrap = document.getElementById('cross_branch_wrap');

        function sync() {
            const isStaff = roleSelect && roleSelect.value === 'staff';
            if (crossWrap) {
                crossWrap.classList.toggle('hidden', !isStaff);
            }
            if (branchSelect) {
                branchSelect.required = isStaff;
            }
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', sync);
        }

        sync();
    })();
</script>
@endsection