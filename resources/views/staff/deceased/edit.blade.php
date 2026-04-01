@extends('layouts.panel')

@section('page_title','Edit Deceased')

@section('content')
<form id="deceasedEditForm" method="POST" action="{{ route('deceased.update', $deceased) }}" enctype="multipart/form-data" class="max-w-3xl w-full mx-auto space-y-6">
@csrf
@method('PUT')

<div class="p-5 md:p-6 space-y-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Edit Deceased</h2>
            <p class="text-sm text-slate-500">Update details for {{ $deceased->full_name }}.</p>
        </div>
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wide">ID: {{ $deceased->deceased_code ?? ('DC-' . str_pad($deceased->id,3,'0',STR_PAD_LEFT)) }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="label-section">Client</label>
            <select name="client_id" class="form-select" required>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ old('client_id', $deceased->client_id)==$client->id ? 'selected' : '' }}>
                        {{ $client->full_name }}
                    </option>
                @endforeach
            </select>
            @error('client_id') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Name</label>
            <input type="text" name="full_name" value="{{ old('full_name', $deceased->full_name) }}" class="form-input" pattern="[A-Za-z ]+" title="Letters and spaces only" required>
            @error('full_name') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div class="md:col-span-2">
            <label class="label-section">Address</label>
            <input type="text" name="address" value="{{ old('address', $deceased->address) }}" class="form-input">
            @error('address') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Birthdate</label>
            <input type="date" name="born" value="{{ old('born', $deceased->born?->format('Y-m-d')) }}" class="form-input">
            @error('born') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Age</label>
            <input type="number" name="age" value="{{ old('age', $deceased->age) }}" class="form-input" min="0" max="150">
            @error('age') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Died</label>
            <input type="date" name="died" value="{{ old('died', ($deceased->died ?? $deceased->date_of_death)?->format('Y-m-d')) }}" class="form-input">
            @error('died') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Interment Date/Time</label>
            <input type="datetime-local" name="interment_at" id="interment_at" value="{{ old('interment_at', $deceased->interment_at?->format('Y-m-d\\TH:i') ?? ($deceased->interment?->format('Y-m-d') ? $deceased->interment?->format('Y-m-d') . 'T09:00' : null)) }}" class="form-input">
            @error('interment_at') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Wake Days</label>
            <input type="number" name="wake_days" id="wake_days" value="{{ old('wake_days', $deceased->wake_days) }}" class="form-input" min="1" max="30" placeholder="Auto-computed if empty">
            @error('wake_days') <div class="form-error">{{ $message }}</div> @enderror
        </div>

        <div>
            <label class="label-section">Place of Cemetery</label>
            <input type="text" name="place_of_cemetery" value="{{ old('place_of_cemetery', $deceased->place_of_cemetery) }}" class="form-input">
            @error('place_of_cemetery') <div class="form-error">{{ $message }}</div> @enderror
        </div>


        <div>
            <label class="block text-sm font-medium">Photo (Optional)</label>
            @if($deceased->photo_path)
                <img src="{{ asset('storage/' . $deceased->photo_path) }}" alt="Deceased photo" class="w-28 h-28 object-cover border rounded mb-2">
            @endif
            <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full border p-2 rounded">
            <div class="text-xs text-gray-500 mt-1">Accepted: JPG, PNG, WEBP. Max size: 3MB.</div>
            @error('photo') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror

            @if($deceased->photo_path)
            <label class="inline-flex items-center gap-2 mt-2 text-sm">
                <input type="hidden" name="remove_photo" value="0">
                <input type="checkbox" name="remove_photo" value="1">
                Remove existing photo
            </label>
            @endif
        </div>
    </div>

    <div class="flex gap-2 pt-3 justify-end">
        <a href="{{ route('deceased.index') }}" class="btn btn-outline">
            Cancel
        </a>
        <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
            <i class="bi bi-save2"></i>
            Save Changes
        </button>
    </div>
</form>

<script>
    (function () {
        const bornInput = document.querySelector('input[name="born"]');
        const diedInput = document.querySelector('input[name="died"]');
        const ageInput = document.querySelector('input[name="age"]');
        const wakeDaysInput = document.getElementById('wake_days');
        const intermentAtInput = document.getElementById('interment_at');

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
            syncWakeDays();
        }

        function syncWakeDays() {
            if (!wakeDaysInput || !diedInput || !intermentAtInput) return;
            if (String(wakeDaysInput.dataset.manuallyEdited || '') === '1') return;
            if (!diedInput.value || !intermentAtInput.value) {
                wakeDaysInput.value = '';
                return;
            }
            const diedDate = new Date(diedInput.value + 'T00:00:00');
            const intermentDate = new Date(intermentAtInput.value);
            if (Number.isNaN(diedDate.getTime()) || Number.isNaN(intermentDate.getTime())) {
                wakeDaysInput.value = '';
                return;
            }
            const intermentDateOnly = new Date(intermentDate.getFullYear(), intermentDate.getMonth(), intermentDate.getDate());
            const diff = intermentDateOnly.getTime() - diedDate.getTime();
            if (diff <= 0) {
                wakeDaysInput.value = '';
                return;
            }
            wakeDaysInput.value = String(Math.max(1, Math.floor(diff / (1000 * 60 * 60 * 24))));
        }


        if (bornInput) {
            bornInput.addEventListener('change', syncAge);
            bornInput.addEventListener('input', syncAge);
        }
        if (diedInput) {
            diedInput.addEventListener('change', syncAge);
            diedInput.addEventListener('input', syncAge);
        }
        if (intermentAtInput) {
            intermentAtInput.addEventListener('change', syncWakeDays);
            intermentAtInput.addEventListener('input', syncWakeDays);
        }
        if (wakeDaysInput) {
            wakeDaysInput.addEventListener('input', function () {
                wakeDaysInput.dataset.manuallyEdited = wakeDaysInput.value ? '1' : '';
            });
        }
        syncAge();
    })();
</script>
@endsection

