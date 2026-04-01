@extends('layouts.panel')

@section('page_title','Edit Client')

@section('content')
<form id="clientEditForm" method="POST" action="{{ route('clients.update', $client) }}" class="max-w-3xl w-full mx-auto space-y-6">
@csrf
@method('PUT')

<div class="p-5 md:p-6 space-y-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Edit Client</h2>
            <p class="text-sm text-slate-500">Update contact details for {{ $client->full_name }}.</p>
        </div>
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wide">Client ID: {{ $client->client_code ?? ('CL-' . str_pad($client->id,3,'0',STR_PAD_LEFT)) }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="label-section">Full Name</label>
            <input type="text" name="full_name" value="{{ old('full_name', $client->full_name) }}" class="form-input" pattern="[A-Za-z ]+" title="Letters and spaces only" required>
        </div>

        <div>
            <label class="label-section">Contact Number</label>
            <input type="text" name="contact_number" value="{{ old('contact_number', $client->contact_number) }}" class="form-input" pattern="[0-9+\-\s()]+" title="Digits, spaces, +, -, and parentheses only">
            @error('contact_number') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Address</label>
            <input type="text" name="address" value="{{ old('address', $client->address) }}" class="form-input">
        </div>
    </div>

    <div class="flex flex-wrap gap-2 pt-3 justify-end">
        <a href="{{ route('clients.index') }}" class="btn btn-outline">Cancel</a>
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
            <i class="bi bi-save2"></i>
            Save Changes
        </button>
    </div>
</div>
</form>
@endsection
