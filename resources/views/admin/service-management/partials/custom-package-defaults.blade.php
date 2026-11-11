@php
    $customDefaults = \App\Models\CustomPackageDefault::find(1);
@endphp
<details class="custom-package-defaults" @if($errors->hasAny(['embalming_days','home_viewing_days','retrieval_kilometers'])) open @endif>
    <summary>Basic Funeral Inclusions <i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
    <form method="POST" action="{{ route('admin.custom-package-defaults.update') }}">
        @csrf @method('PUT')
        <p>Shared starting values for regular and custom packages. Enter an extra rate for each configured service. Use 0 for no extra charge.</p>
        <div class="custom-package-defaults__fields">
            @foreach(\App\Models\CustomPackageDefault::SERVICE_FIELDS as $type => [$field, $rate, $unit])
                <div class="custom-package-defaults__row">
                    <strong>{{ \App\Models\Package::serviceTypeOptions()[$type] }}</strong>
                    <label>Included {{ $unit }}
                        <input type="number" name="{{ $field }}" min="{{ $unit === 'days' ? 1 : 0 }}" max="100000" step="{{ $unit === 'days' ? 1 : '0.01' }}" @required($type !== \App\Models\Package::SERVICE_HEARSE) placeholder="Not configured" value="{{ old($field, $customDefaults?->{$field}) }}">
                        @error($field)<span class="text-red-700" role="alert">{{ $message }}</span>@enderror
                    </label>
                    <label>₱ per extra {{ $unit === 'days' ? 'day' : 'km' }} {{ $type === \App\Models\Package::SERVICE_HEARSE ? '(required with included km)' : '*' }}
                        <input type="number" name="{{ $rate }}" min="0" max="999999.99" step="0.01" @required($type !== \App\Models\Package::SERVICE_HEARSE || filled(old($field, $customDefaults?->{$field}))) placeholder="0.00" value="{{ old($rate, $customDefaults?->{$rate}) }}">
                        @error($rate)<span class="text-red-700" role="alert">{{ $message }}</span>@enderror
                    </label>
                </div>
            @endforeach
        </div>
        <button type="submit" class="reference-page-action btn btn-primary-custom">Save defaults</button>
    </form>
</details>
<style>
.custom-package-defaults{padding:16px 20px;margin:16px 0;background:#F7FAF8;border-radius:12px;color:inherit}
.custom-package-defaults summary{display:flex;align-items:center;justify-content:space-between;cursor:pointer;font-weight:600;list-style:none}
.custom-package-defaults summary::-webkit-details-marker{display:none}
.custom-package-defaults summary i{transition:transform 180ms ease}.custom-package-defaults[open] summary i{transform:rotate(180deg)}
.custom-package-defaults form{display:grid;gap:16px;padding-top:16px;border-top:1px solid #AEC7B7;margin-top:16px}
.custom-package-defaults p{font-size:13px;line-height:1.5}.custom-package-defaults__fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.custom-package-defaults label{display:grid;gap:8px;font-size:13px}.custom-package-defaults input{min-width:0;width:100%;border:1px solid transparent;border-radius:8px;background:#EAF2ED;padding:10px 12px;color:inherit}
.custom-package-defaults input:focus{outline:1px solid #7FA98E}.custom-package-defaults button{justify-self:end}
html[data-theme='dark'] .custom-package-defaults{background:#15251C}html[data-theme='dark'] .custom-package-defaults input{background:#22392C}
@media(max-width:640px){.custom-package-defaults__fields{grid-template-columns:1fr}}
@media(prefers-reduced-motion:reduce){.custom-package-defaults summary i{transition:none}}
</style>
<style>
.custom-package-defaults .custom-package-defaults__fields{display:block}
.custom-package-defaults__row{display:grid;grid-template-columns:1fr 1fr 1fr;align-items:center;gap:16px;padding:10px 0;border-bottom:1px solid var(--border,#aec7b7)}
@media(max-width:640px){.custom-package-defaults__row{grid-template-columns:1fr;gap:8px}}
</style>
