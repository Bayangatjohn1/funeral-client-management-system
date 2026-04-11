@extends('layouts.panel')

@section('page_title','Create Branch')
@section('page_desc', 'Register a new branch and configure branch information.')

@section('content')
@php($returnTo = old('return_to', request('return_to', route('admin.branches.index'))))
<form method="POST" action="{{ route('admin.branches.store') }}" class="space-y-4 max-w-xl">
@csrf
<input type="hidden" name="return_to" value="{{ $returnTo }}">

<div>
    <label class="block text-sm font-medium">Branch Code</label>
    <input type="text" value="{{ $nextCode }}" class="w-full border p-2 rounded bg-gray-100" readonly>
    <div class="text-xs text-gray-500 mt-1">Auto-generated for new branches.</div>
</div>

<div>
    <label class="block text-sm font-medium">Branch Name</label>
    <input type="text" name="branch_name" value="{{ old('branch_name') }}" class="w-full border p-2 rounded" required>
    @error('branch_name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Address</label>
    <input type="text" name="address" value="{{ old('address') }}" class="w-full border p-2 rounded">
    @error('address') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div class="flex items-center gap-2">
    <input type="hidden" name="is_active" value="0">
    <input id="is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
    <label for="is_active" class="text-sm">Active</label>
</div>

<div class="flex items-center gap-2">
    <button class="bg-[#9C5A1A] text-white px-4 py-2 rounded hover:bg-[#7A440F]">Save</button>
    <a href="{{ $returnTo }}" class="border px-4 py-2 rounded">Cancel</a>
</div>
</form>
@endsection

