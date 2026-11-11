@props(['context', 'proof' => null, 'formId' => null, 'restoreOld' => true, 'target' => null, 'label' => 'Payment Proof', 'accept' => 'image/jpeg,image/png,application/pdf', 'limit' => 5, 'dropZone' => false, 'proofIdName' => 'payment_proof_id', 'formIdName' => 'payment_proof_form_id'])
@php($errors = $errors ?? new \Illuminate\Support\ViewErrorBag())
<section class="payment-proof-widget" data-payment-proof data-context="{{ $context }}" data-proof-target="{{ $target }}" data-proof-limit="{{ $limit }}" data-base="{{ url('/payment-proof/sessions') }}">
    <input type="hidden" data-proof-form-id @unless($target) name="{{ $formIdName }}" @endunless value="{{ ($restoreOld ? old($formIdName) : null) ?? $formId ?? (string) \Illuminate\Support\Str::uuid() }}">
    <input type="hidden" data-proof-session-id @unless($target) name="{{ $proofIdName }}" @endunless value="{{ $restoreOld ? old($proofIdName, $proof) : $proof }}">
    <div class="proof-label">{{ $label }} @if($target)<small>(Optional)</small>@else<span aria-label="required">*</span>@endif</div>
    @if($dropZone)
        <div class="catalog-photo-dropzone" data-proof-drop>
            <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
            <span>Drag and drop a photo here</span>
        </div>
    @endif
    <p class="proof-instruction" data-proof-instruction>
        @switch($context)
            @case('senior')
                JPG, PNG, WebP or PDF · max {{ $limit }} MB
                @break
            @case('deceased')
                JPG, PNG or WebP · max {{ $limit }} MB
                @break
            @case('catalog')
            @case('tarpaulin')
                JPG or PNG · max {{ $limit }} MB
                @break
            @default
                Attach the issued receipt or transaction confirmation. JPG, PNG or PDF · up to {{ $limit }} MB.
        @endswitch
    </p>
    <div class="proof-actions">
        <button type="button" data-proof-choose><i class="bi bi-paperclip" aria-hidden="true"></i> <span data-proof-file-label>Choose File</span></button>
        <button type="button" data-proof-phone><i class="bi bi-qr-code" aria-hidden="true"></i> Upload using Phone</button>
        <input type="file" accept="{{ $accept }}" data-proof-file hidden>
    </div>
    <div data-proof-qr hidden>
        <img alt="Scan to upload {{ $context === 'catalog' ? 'a catalog photo' : 'an attachment' }}" width="224" height="224" data-proof-qr-image>
        <p>Scan with your phone camera. No phone sign-in needed.</p>
        <p data-proof-countdown role="timer"></p>
        <button type="button" data-proof-cancel>Cancel QR</button>
    </div>
    <div data-proof-received hidden>
        <img data-proof-image alt="Uploaded {{ $context === 'catalog' ? 'catalog photo' : 'attachment' }}" hidden>
        <p data-proof-name></p>
        <a data-proof-preview target="_blank" rel="noopener">View Proof <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
        <button type="button" data-proof-remove>Remove</button>
    </div>
    <p data-proof-state class="proof-state" role="status" aria-live="polite"></p>
    <progress data-proof-progress max="100" hidden aria-label="Uploading payment proof"></progress>
    <p data-proof-error class="proof-error" role="alert" hidden>@if($restoreOld) @error('payment_proof_id'){{ $message }}@enderror @endif</p>
    @if($restoreOld) @error('payment_proof_form_id')<p class="proof-error" role="alert">{{ $message }}</p>@enderror @endif
</section>
