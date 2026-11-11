@once
    <style>
        .ceremony-location-fields { min-width: 0; }
        .ceremony-location-fields legend { margin-bottom: .45rem; }
        .ceremony-location-inputs { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); gap: .5rem; align-items: start; }
        .ceremony-location-inputs .form-input { width: 100%; min-width: 0; }
        .ceremony-location-fields:has(option[value="wake"]:checked) .ceremony-location-custom { display: none; }
        .ceremony-location-wake-hint { display: none; }
        .ceremony-location-fields:has(option[value="wake"]:checked) .ceremony-location-wake-hint { display: block; }
        @media (max-width: 640px) { .ceremony-location-inputs { grid-template-columns: 1fr; } }
    </style>
@endonce

<fieldset class="ceremony-location-fields" @disabled($ceremonyLocationLocked ?? false)>
    <legend class="{{ $ceremonyLocationLabelClass ?? 'field-label' }}">Funeral Ceremony Location <small>(Optional)</small></legend>
    <div class="ceremony-location-inputs">
        <select class="form-input" name="funeral_ceremony_location_mode" aria-label="Ceremony location source">
            <option value="custom" @selected($ceremonyLocationMode !== 'wake')>Enter a location</option>
            <option value="wake" @selected($ceremonyLocationMode === 'wake')>Same as Wake Location</option>
        </select>
        <div class="ceremony-location-custom">
            <input type="text" name="funeral_ceremony_location" class="form-input" aria-label="Funeral Ceremony Location"
                value="{{ $ceremonyLocationValue }}" maxlength="255" placeholder="e.g., St. Joseph Parish Church" @if($errors->has('funeral_ceremony_location')) aria-invalid="true" aria-describedby="ceremony-location-error" @endif>
            @error('funeral_ceremony_location')<p id="ceremony-location-error" class="intake-field-error text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
        </div>
        <p class="ceremony-location-wake-hint text-sm">Uses the wake address or selected chapel.</p>
    </div>
    @error('funeral_ceremony_location_mode')<p class="intake-field-error text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
</fieldset>
