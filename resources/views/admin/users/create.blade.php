@extends('layouts.panel')

@section('page_title','Create User')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.users.index'))))
<form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 max-w-xl">
@csrf
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div>
    <label class="block text-sm font-medium">Name</label>
    <input type="text" name="name" value="{{ old('name') }}" class="w-full border p-2 rounded" required>
    @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Email</label>
    <input type="email" name="email" value="{{ old('email') }}" class="w-full border p-2 rounded" required>
    @error('email') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Password</label>
    <input type="password" name="password" class="w-full border p-2 rounded" required>
    @error('password') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Role</label>
    <select name="role" id="role" class="w-full border p-2 rounded" required>
        <option value="staff" {{ old('role')=='staff' ? 'selected' : '' }}>Staff</option>
        <option value="admin" {{ old('role')=='admin' ? 'selected' : '' }}>Admin</option>
        <option value="owner" {{ old('role')=='owner' ? 'selected' : '' }}>Owner</option>
    </select>
</div>

<div>
    <label class="block text-sm font-medium">Branch</label>
    <select name="branch_id" id="branch_id" class="w-full border p-2 rounded">
        <option value="">- None -</option>
        @foreach($branches as $branch)
            <option value="{{ $branch->id }}" {{ old('branch_id')==$branch->id ? 'selected' : '' }}>
                {{ $branch->branch_name }}
            </option>
        @endforeach
    </select>
    @error('branch_id') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div id="cross_branch_wrap" class="hidden">
    <label class="inline-flex items-center gap-2 text-sm">
        <input type="hidden" name="can_encode_any_branch" value="0">
        <input type="checkbox" name="can_encode_any_branch" value="1" {{ old('can_encode_any_branch') ? 'checked' : '' }}>
        Allow cross-branch encoding (Main Branch staff only)
    </label>
</div>

<div>
    <label class="block text-sm font-medium">Contact Number</label>
    <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="w-full border p-2 rounded">
    @error('contact_number') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Position</label>
    <input type="text" name="position" value="{{ old('position') }}" class="w-full border p-2 rounded" placeholder="Staff/Admin Position">
    @error('position') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Address</label>
    <input type="text" name="address" value="{{ old('address') }}" class="w-full border p-2 rounded">
    @error('address') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div class="flex items-center gap-2">
    <button class="bg-[#9C5A1A] text-white px-4 py-2 rounded hover:bg-[#7A440F]">Save</button>
    <a href="{{ $returnTo }}" class="border px-4 py-2 rounded">Cancel</a>
</div>
</form>

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

