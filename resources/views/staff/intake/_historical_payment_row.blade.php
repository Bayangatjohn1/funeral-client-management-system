@php
    $templateRow = (bool) ($isTemplate ?? false);
    $rowIndex = (string) $index;
    $base = "historical_payments[{$rowIndex}]";
    $field = fn (string $key) => $base.'['.$key.']';
    $value = fn (string $key, $default = '') => $templateRow ? $default : data_get($row, $key, $default);
    $inputId = fn (string $key) => 'historical_payment_'.$rowIndex.'_'.$key;
@endphp
<article class="historical-payment-row rounded-xl bg-white/80 p-4 md:p-5" data-historical-payment-index="{{ $rowIndex }}">
    @if(!$templateRow && filled($value('parent_payment_index')))<input type="hidden" name="{{ $field('parent_payment_index') }}" value="{{ $value('parent_payment_index') }}">@endif
    <div class="mb-4 flex items-center justify-between gap-3">
        <h5 class="font-bold text-slate-900">Past payment</h5>
        <button type="button" data-remove-historical-payment class="rounded-lg bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-100">Remove</button>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label class="field-label" for="{{ $inputId('amount') }}">Amount <span class="text-rose-600">*</span></label>
            <input class="form-input" id="{{ $inputId('amount') }}" name="{{ $field('amount') }}" type="number" min="0.01" step="0.01" value="{{ $value('amount') }}" placeholder="e.g. 15000.00">
            @if(!$templateRow && $errors->has("{$base}.amount"))<p class="intake-field-error" role="alert">{{ $errors->first("{$base}.amount") }}</p>@endif
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('paid_at') }}">Actual payment date &amp; time <span class="text-rose-600">*</span></label>
            <input class="form-input" id="{{ $inputId('paid_at') }}" name="{{ $field('paid_at') }}" type="datetime-local" max="{{ now()->format('Y-m-d\TH:i') }}" value="{{ $value('paid_at') }}">
            @if(!$templateRow && $errors->has("{$base}.paid_at"))<p class="intake-field-error" role="alert">{{ $errors->first("{$base}.paid_at") }}</p>@endif
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('payment_method') }}">Payment method <span class="text-rose-600">*</span></label>
            <select class="form-input" id="{{ $inputId('payment_method') }}" name="{{ $field('payment_method') }}" data-historical-method>
                <option value="">Select method</option>
                <option value="cash" @selected($value('payment_method') === 'cash')>Cash</option>
                <option value="cashless" @selected($value('payment_method') === 'cashless')>Cashless</option>
            </select>
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('receipt_or_no') }}">Receipt / OR No. <span class="payment-field-note">(If applicable)</span></label>
            <input class="form-input" id="{{ $inputId('receipt_or_no') }}" name="{{ $field('receipt_or_no') }}" value="{{ $value('receipt_or_no') }}" maxlength="50" placeholder="e.g. OR-001234">
        </div>
    </div>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-historical-cashless-fields @class(['hidden' => $value('payment_method') !== 'cashless'])>
        <div>
            <label class="field-label" for="{{ $inputId('cashless_type') }}">Cashless type</label>
            <select class="form-input" id="{{ $inputId('cashless_type') }}" name="{{ $field('cashless_type') }}">
                <option value="">Select type</option>
                @foreach(\App\Support\Payments\PaymentDetails::CASHLESS_TYPES as $type)
                    <option value="{{ $type }}" @selected($value('cashless_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('bank_name') }}">Bank <span class="payment-field-note">(For bank transfer)</span></label>
            <select class="form-input" id="{{ $inputId('bank_name') }}" name="{{ $field('bank_name') }}">
                <option value="">Select bank</option>
                @foreach(\App\Support\Payments\PaymentDetails::BANKS as $bank)
                    <option value="{{ $bank }}" @selected($value('bank_name') === $bank)>{{ $bank }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('other_bank_name') }}">Other bank name <span class="payment-field-note">(If Other Bank)</span></label>
            <input class="form-input" id="{{ $inputId('other_bank_name') }}" name="{{ $field('other_bank_name') }}" value="{{ $value('other_bank_name') }}" maxlength="80" placeholder="e.g. EastWest Bank">
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('account_name') }}">Account name <span class="payment-field-note">(Optional)</span></label>
            <input class="form-input" id="{{ $inputId('account_name') }}" name="{{ $field('account_name') }}" value="{{ $value('account_name') }}" maxlength="100" placeholder="e.g. Maria Dela Cruz">
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('mobile_number') }}">Mobile number <span class="payment-field-note">(Optional)</span></label>
            <input class="form-input" id="{{ $inputId('mobile_number') }}" name="{{ $field('mobile_number') }}" value="{{ $value('mobile_number') }}" maxlength="30" placeholder="e.g. 09171234567">
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('terminal_provider') }}">Terminal / provider <span class="payment-field-note">(Card)</span></label>
            <input class="form-input" id="{{ $inputId('terminal_provider') }}" name="{{ $field('terminal_provider') }}" value="{{ $value('terminal_provider') }}" maxlength="80" placeholder="e.g. BDO POS">
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('payment_channel') }}">Payment channel <span class="payment-field-note">(For other)</span></label>
            <input class="form-input" id="{{ $inputId('payment_channel') }}" name="{{ $field('payment_channel') }}" value="{{ $value('payment_channel') }}" maxlength="80" placeholder="e.g. PalawanPay">
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('reference_number') }}">Transaction reference no.</label>
            <input class="form-input" id="{{ $inputId('reference_number') }}" name="{{ $field('reference_number') }}" value="{{ $value('reference_number') }}" maxlength="60" placeholder="e.g. TXN-20240101-001">
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('approval_code') }}">Approval code <span class="payment-field-note">(Card)</span></label>
            <input class="form-input" id="{{ $inputId('approval_code') }}" name="{{ $field('approval_code') }}" value="{{ $value('approval_code') }}" maxlength="40" placeholder="e.g. A12345">
        </div>
        <div>
            <label class="field-label" for="{{ $inputId('payment_notes') }}">Notes <span class="payment-field-note">(Optional)</span></label>
            <input class="form-input" id="{{ $inputId('payment_notes') }}" name="{{ $field('payment_notes') }}" value="{{ $value('payment_notes') }}" maxlength="255" placeholder="Payment details">
        </div>
    </div>
    @if(!$templateRow)
        @foreach(['receipt_or_no', 'reference_number', 'approval_code', 'cashless_type', 'bank_name', 'other_bank_name', 'payment_channel'] as $errorField)
            @if($errors->has("{$base}.{$errorField}"))<p class="intake-field-error mt-2" role="alert">{{ $errors->first("{$base}.{$errorField}") }}</p>@endif
        @endforeach
    @endif
    <div class="mt-4 rounded-xl bg-emerald-50/70 p-3">
        @if(!$templateRow && is_array($value('existing_proof')) && !empty($value('existing_proof')['path']) && session('historical_parent_submission_id') !== null && $value('parent_payment_index') !== '')
            <p class="mb-3 text-sm text-emerald-900">Previously attached proof retained: <a class="font-semibold underline" target="_blank" rel="noopener" href="{{ route('historical-cases.payment-proof', [session('historical_parent_submission_id'), $value('parent_payment_index')]) }}">{{ $value('existing_proof')['name'] ?? 'View proof' }}</a></p>
        @endif
        <x-payment-proof-upload context="intake" label="Payment proof" accept="image/jpeg,image/png,application/pdf" :limit="5" :restore-old="!$templateRow" :form-id="$templateRow ? '__FORM_ID__' : ($value('payment_proof_form_id') ?: (string) \Illuminate\Support\Str::uuid())" :proof="$value('payment_proof_id')" :proof-id-name="$field('payment_proof_id')" :form-id-name="$field('payment_proof_form_id')" />
        <label class="field-label mt-3" for="{{ $inputId('proof_missing_reason') }}">Why is the old proof unavailable? <span class="text-rose-600">*</span> if no proof is attached</label>
        <textarea class="form-input min-h-20" id="{{ $inputId('proof_missing_reason') }}" name="{{ $field('proof_missing_reason') }}" maxlength="500" placeholder="Explain why the original receipt or confirmation is unavailable.">{{ $value('proof_missing_reason') }}</textarea>
        @if(!$templateRow && $errors->has("{$base}.proof_missing_reason"))<p class="intake-field-error" role="alert">{{ $errors->first("{$base}.proof_missing_reason") }}</p>@endif
    </div>
</article>
