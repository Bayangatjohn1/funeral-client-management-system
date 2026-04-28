@extends('layouts.panel')

@section('page_title','Add Client')
@section('page_desc', 'Register a new client profile and contact information.')

@section('content')
<form method="POST" action="{{ route('clients.store') }}" class="space-y-4 max-w-xl">
@csrf

<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="block text-sm font-medium">First Name <span class="text-red-500">*</span></label>
        <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full border p-2 rounded" pattern="[A-Za-zÀ-öø-ÿĀ-žḀ-ỿ .'\-]+" title="Letters (including accented like Ñ, É), spaces, apostrophes, dots, and hyphens only" required>
        @error('first_name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium">Last Name <span class="text-red-500">*</span></label>
        <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full border p-2 rounded" pattern="[A-Za-zÀ-öø-ÿĀ-žḀ-ỿ .'\-]+" title="Letters (including accented like Ñ, É), spaces, apostrophes, dots, and hyphens only" required>
        @error('last_name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium">Middle Name</label>
        <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full border p-2 rounded" pattern="[A-Za-zÀ-öø-ÿĀ-žḀ-ỿ .'\-]+" title="Letters (including accented like Ñ, É), spaces, apostrophes, dots, and hyphens only">
        @error('middle_name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium">Suffix</label>
        <input type="text" name="suffix" value="{{ old('suffix') }}" class="w-full border p-2 rounded" placeholder="Jr., Sr., III…" maxlength="20">
        @error('suffix') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>
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
