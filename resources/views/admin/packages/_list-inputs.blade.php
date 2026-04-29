@php
    $initialInclusions = array_values(array_filter($initialInclusions ?? [''], fn ($item) => trim((string) $item) !== ''));
    $initialFreebies = array_values(array_filter($initialFreebies ?? [''], fn ($item) => trim((string) $item) !== ''));
    $initialInclusions = $initialInclusions === [] ? [''] : $initialInclusions;
    $initialFreebies = $initialFreebies === [] ? [''] : $initialFreebies;
@endphp

<div class="md:col-span-2">
    <div class="flex items-center justify-between gap-3 mb-2">
        <label class="label-section mb-0">Inclusions <span class="text-rose-500">*</span></label>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-list-add="inclusions">
            <i class="bi bi-plus-circle"></i>
            Add Inclusion
        </button>
    </div>
    <div class="space-y-2" data-list="inclusions" data-required="1" data-placeholder="e.g., Embalming service">
        @foreach($initialInclusions as $item)
            <div class="flex items-center gap-2" data-list-row>
                <input type="text" name="inclusions[]" value="{{ $item }}" class="form-input flex-1" placeholder="e.g., Embalming service">
                <button type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-rose-100 bg-rose-50 text-rose-600 hover:bg-rose-100" data-list-remove aria-label="Remove inclusion">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        @endforeach
    </div>
    @error('inclusions') <div class="form-error">{{ $message }}</div> @enderror
    @foreach($errors->get('inclusions.*') as $messages)
        @foreach($messages as $message)
            <div class="form-error">{{ $message }}</div>
        @endforeach
    @endforeach
    <div class="form-error hidden" data-field-error="inclusions"></div>
</div>

<div class="md:col-span-2">
    <div class="flex items-center justify-between gap-3 mb-2">
        <label class="label-section mb-0">Freebies</label>
        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" data-list-add="freebies">
            <i class="bi bi-plus-circle"></i>
            Add Freebie
        </button>
    </div>
    <div class="space-y-2" data-list="freebies" data-placeholder="e.g., Flower arrangement">
        @foreach($initialFreebies as $item)
            <div class="flex items-center gap-2" data-list-row>
                <input type="text" name="freebies[]" value="{{ $item }}" class="form-input flex-1" placeholder="e.g., Flower arrangement">
                <button type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-rose-100 bg-rose-50 text-rose-600 hover:bg-rose-100" data-list-remove aria-label="Remove freebie">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        @endforeach
    </div>
    @error('freebies') <div class="form-error">{{ $message }}</div> @enderror
    @foreach($errors->get('freebies.*') as $messages)
        @foreach($messages as $message)
            <div class="form-error">{{ $message }}</div>
        @endforeach
    @endforeach
    <div class="form-error hidden" data-field-error="freebies"></div>
</div>
