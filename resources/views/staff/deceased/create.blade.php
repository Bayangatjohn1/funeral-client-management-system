@extends('layouts.panel')

@section('page_title','Add Deceased')
@section('page_desc', 'Register deceased information for a case record.')

@section('content')
<form method="POST" action="{{ route('deceased.store') }}" enctype="multipart/form-data" class="space-y-4 max-w-xl">
@csrf

<div>
    <label class="block text-sm font-medium">Client</label>
    <select name="client_id" class="w-full border p-2 rounded" required>
        <option value="">&mdash; Select Client &mdash;</option>
        @foreach($clients as $client)
            <option value="{{ $client->id }}" {{ old('client_id')==$client->id ? 'selected' : '' }}>
                {{ $client->full_name }}
            </option>
        @endforeach
    </select>
    @error('client_id') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

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
    <label class="block text-sm font-medium">Address</label>
    <input type="text" name="address" value="{{ old('address') }}" class="w-full border p-2 rounded">
    @error('address') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Birthdate</label>
    <input type="date" name="born" value="{{ old('born') }}" class="w-full border p-2 rounded">
    @error('born') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Age</label>
    <input type="number" name="age" value="{{ old('age') }}" class="w-full border p-2 rounded" min="0" max="150">
    @error('age') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Died</label>
    <input type="date" name="died" value="{{ old('died') }}" class="w-full border p-2 rounded">
    @error('died') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Interment</label>
    <input type="date" name="interment" value="{{ old('interment') }}" class="w-full border p-2 rounded">
    @error('interment') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Place of Cemetery</label>
    <input type="text" name="place_of_cemetery" value="{{ old('place_of_cemetery') }}" class="w-full border p-2 rounded">
    @error('place_of_cemetery') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div>
    <label class="block text-sm font-medium">Photo (Optional)</label>
    <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full border p-2 rounded">
    <div class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, WEBP. Max size: 3MB.</div>
    @error('photo') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
</div>

<div class="flex gap-2">
    <button class="bg-black text-white px-4 py-2 rounded">Save</button>
    <a href="{{ route('deceased.index') }}" class="border px-4 py-2 rounded">Cancel</a>
</div>
</form>

<script>
    (function () {
        const bornInput = document.querySelector('input[name="born"]');
        const diedInput = document.querySelector('input[name="died"]');
        const ageInput = document.querySelector('input[name="age"]');

        function parseDate(value) {
            if (!value) return null;
            const parts = value.split('-').map(Number);
            if (parts.length !== 3 || parts.some((n) => !Number.isFinite(n))) return null;
            return new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
        }

        function calcAge(bornValue, diedValue) {
            const bornDate = parseDate(bornValue);
            const diedDate = parseDate(diedValue);
            if (!bornDate || !diedDate || diedDate < bornDate) return null;
            let age = diedDate.getUTCFullYear() - bornDate.getUTCFullYear();
            const monthDiff = diedDate.getUTCMonth() - bornDate.getUTCMonth();
            if (monthDiff < 0 || (monthDiff === 0 && diedDate.getUTCDate() < bornDate.getUTCDate())) {
                age -= 1;
            }
            return age;
        }

        function syncAge() {
            if (!ageInput) return;
            const age = calcAge(bornInput?.value, diedInput?.value);
            ageInput.value = Number.isFinite(age) ? age : '';
        }

        if (bornInput) {
            bornInput.addEventListener('change', syncAge);
            bornInput.addEventListener('input', syncAge);
        }
        if (diedInput) {
            diedInput.addEventListener('change', syncAge);
            diedInput.addEventListener('input', syncAge);
        }
        syncAge();
    })();
</script>
@endsection


