@extends('layouts.panel')

@section('page_title','Add Client')
@section('page_desc', 'Register a new client profile and contact information.')

@section('content')
<form method="POST" action="{{ route('clients.store') }}" class="space-y-4 max-w-xl">
@csrf

<div>
    <label class="block text-sm font-medium">Full Name</label>
    <input type="text" name="full_name" value="{{ old('full_name') }}" class="w-full border p-2 rounded" pattern="[A-Za-z ]+" title="Letters and spaces only" required>
    @error('full_name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Contact Number</label>
    <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="w-full border p-2 rounded" pattern="[0-9+\-\s()]+" title="Digits, spaces, +, -, and parentheses only">
    @error('contact_number') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Address</label>
    <input type="text" name="address" value="{{ old('address') }}" class="w-full border p-2 rounded">
</div>

<div class="flex gap-2">
    <button class="bg-black text-white px-4 py-2 rounded">Save</button>
    <a href="{{ route('clients.index') }}" class="border px-4 py-2 rounded">Cancel</a>
</div>
</form>
@endsection

