@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', $promotion->exists ? 'Edit Promotion' : 'Add Promotion')
@section('page_desc', 'Configure a promotion for later package assignment.')
@section('hide_layout_topbar', '1')
@section('suppress_layout_page_header', '1')

@section('content')
@php
    $editing = $promotion->exists;
    // Required configuration stays required for every edit, including stopped
    // legacy rows; validation feedback is separate from lifecycle status.
    $requiresDiscount = true;
    $action = $editing ? route('admin.promotions.update', $promotion) : route('admin.promotions.store');
    $back = old('return_to', request('return_to', route('admin.service-management.index', ['tab' => 'promotions'])));
@endphp
<style>
    .promotion-form { max-width:760px; margin:0 auto; padding:8px; color:var(--ink); }
    .promotion-form__card { overflow:hidden; border:1px solid var(--border); border-radius:14px; background:var(--card); }
    .promotion-form__head,.promotion-form__foot { padding:12px 16px; background:var(--surface-muted); }
    .promotion-form__head { display:flex; align-items:center; gap:12px; border-bottom:1px solid var(--border); }
    .promotion-form__icon { display:grid; flex:0 0 36px; place-items:center; width:36px; height:36px; border:1px solid var(--border); border-radius:10px; background:var(--surface); color:var(--brand); font-size:17px; }
    .promotion-form__head > div { min-width:0; }
    .promotion-form__head h1 { margin:0; font-size:1.2rem; font-weight:700; }
    .promotion-form__head p { margin:5px 0 0; color:var(--ink-muted); font-size:.9rem; }
    .promotion-form__body { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px 14px; padding:12px 16px; }
    .promotion-form__field--wide { grid-column:1/-1; }
    .promotion-form__optional { color:var(--ink-muted); font-size:.78rem; font-weight:400; }
    .promotion-form label { display:block; margin-bottom:4px; font-size:.84rem; font-weight:650; }
    .promotion-form :is(input,select) { width:100%; min-height:40px; padding:7px 10px; border:1px solid var(--border); border-radius:8px; background:var(--surface); color:var(--ink); }
    .promotion-form__value-wrap { display:flex; align-items:center; min-height:40px; overflow:hidden; border:1px solid var(--border); border-radius:8px; background:var(--surface); }
    .promotion-form__value-wrap input { order:2; min-width:0; flex:1 1 auto; border:0 !important; background:transparent !important; box-shadow:none !important; }
    .promotion-form__value-suffix { order:1; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; min-width:38px; align-self:stretch; padding:0 12px; color:var(--ink-muted); font-weight:650; pointer-events:none; }
    .promotion-form :is(input,select)[aria-invalid="true"] { border-color:var(--danger,#b5473c); box-shadow:0 0 0 1px var(--danger,#b5473c); }
    .promotion-form__required { color:var(--danger,#b5473c); }
    .promotion-form__field-error { margin:4px 0 0; color:var(--danger-ink,#9b3d31); font-size:.8rem; line-height:1.35; }
    .promotion-form__errors { grid-column:1/-1; padding:12px 14px; border:1px solid color-mix(in srgb,var(--danger,#b5473c) 35%,transparent); border-radius:9px; background:color-mix(in srgb,var(--danger,#b5473c) 8%,var(--card)); color:var(--danger-ink,#9b3d31); font-size:.85rem; }
    .promotion-form__foot { display:flex; justify-content:flex-end; gap:9px; border-top:1px solid var(--border); }
    .promotion-form__foot a,.promotion-form__foot button { display:inline-flex; min-height:42px; align-items:center; padding:8px 14px; border:1px solid var(--border); border-radius:8px; color:var(--ink); text-decoration:none; }
    .promotion-form__foot button { background:var(--brand); color:#fff; border-color:var(--brand); font-weight:650; }
    body.service-catalog-form-frame .promotion-form { max-width:none; margin:0; padding:0; color:var(--ink); font:400 14px/1.5 var(--font-body); }
    body.service-catalog-form-frame .promotion-form__card { border:0; border-radius:0; background:transparent; box-shadow:none; }
    body.service-catalog-form-frame .promotion-form__head { display:none; }
    body.service-catalog-form-frame .promotion-form__body { gap:12px 14px; padding:18px 24px; }
    body.service-catalog-form-frame .promotion-form label { margin-bottom:5px; color:var(--ink); font-size:13px; font-weight:600; }
    body.service-catalog-form-frame .promotion-form__optional { color:var(--ink-muted); font-size:12px; font-weight:400; }
    body.service-catalog-form-frame .promotion-form :is(input,select) { min-height:44px; padding:10px 13px; border:1px solid var(--border); border-radius:8px; background:#fff; color:var(--ink); font-size:14px; font-weight:400; }
    body.service-catalog-form-frame .promotion-form :is(input,select):focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
    body.service-catalog-form-frame .promotion-form__value-wrap { min-height:44px; border-color:var(--border); background:#fff; }
    body.service-catalog-form-frame .promotion-form__value-wrap:focus-within { outline:2px solid var(--brand); outline-offset:2px; }
    body.service-catalog-form-frame .promotion-form__value-wrap input { padding:10px 13px 10px 4px; }
    body.service-catalog-form-frame .promotion-form__value-suffix { min-width:38px; padding:0 12px; color:var(--ink-muted); }
    body.service-catalog-form-frame .promotion-form :is(input,select)[aria-invalid="true"] { border-color:#B42318; box-shadow:none; }
    body.service-catalog-form-frame .promotion-form__value-wrap:has(input[aria-invalid="true"]) { border-color:#B42318; }
    body.service-catalog-form-frame .promotion-form__field-error { margin:4px 0 0; color:#B42318; font-size:12px; line-height:1.5; font-weight:500; }
    body.service-catalog-form-frame .promotion-form__foot { position:sticky; bottom:0; z-index:2; padding:14px 24px; background:rgb(255 255 255 / 40%); }
    body.service-catalog-form-frame .promotion-form__foot a,body.service-catalog-form-frame .promotion-form__foot button { min-height:44px; padding:9px 16px; border-radius:8px; font-size:13px; font-weight:600; }
    body.service-catalog-form-frame .promotion-form__foot a { background:rgb(255 255 255 / 75%); border-color:#AEC7B7; }
    body.service-catalog-form-frame .promotion-form__foot button { background:#2D6A4F; border-color:#2D6A4F; }
    html[data-theme='dark'] body.service-catalog-form-frame .promotion-form :is(input,select) { background:#07130E; border-color:#41634F; color:#F8FAFC; }
    html[data-theme='dark'] body.service-catalog-form-frame .promotion-form__value-wrap { background:#07130E; border-color:#41634F; }
    html[data-theme='dark'] body.service-catalog-form-frame .promotion-form__value-suffix { color:#D1D5DB; }
    html[data-theme='dark'] body.service-catalog-form-frame .promotion-form__value-wrap:has(input[aria-invalid="true"]) { border-color:#FDA29B; }
    html[data-theme='dark'] body.service-catalog-form-frame .promotion-form :is(input,select)[aria-invalid="true"] { border-color:#FDA29B; }
    html[data-theme='dark'] body.service-catalog-form-frame .promotion-form__field-error { color:#FDA29B; }
    html[data-theme='dark'] body.service-catalog-form-frame .promotion-form__foot { background:rgb(0 0 0 / 10%); border-color:#1B4332; }
    html[data-theme='dark'] body.service-catalog-form-frame .promotion-form__foot a { background:#142B20; border-color:#41634F; color:#F8FAFC; }
    html[data-theme='dark'] body.service-catalog-form-frame .promotion-form__foot button { background:#1B4332; border-color:#41634F; }
    @media(max-width:600px) { body.service-catalog-form-frame .promotion-form__body { grid-template-columns:1fr; padding:16px; } body.service-catalog-form-frame .promotion-form__foot { padding:12px 16px; } }
    @media(max-width:600px) { .promotion-form { padding:10px; } .promotion-form__body { grid-template-columns:1fr; padding:14px; } .promotion-form__field--wide,.promotion-form__errors { grid-column:auto; } }
</style>
<form method="POST" action="{{ $action }}" class="promotion-form" id="promotionForm" data-promotion-form data-assigned-package-price="{{ $promotion->package?->price ?? '' }}">
    @csrf
    @if($editing) @method('PUT') @endif
    <input type="hidden" name="return_to" value="{{ $back }}">
    @if(request()->boolean('_service_modal')) <input type="hidden" name="_service_modal" value="1"> @endif
    <section class="promotion-form__card">
        @unless(request()->boolean('modal'))
            <header class="promotion-form__head"><span class="promotion-form__icon" aria-hidden="true"><i class="bi bi-ticket-perforated"></i></span><div><h1>{{ $editing ? 'Edit Promotion' : 'Add Promotion' }}</h1><p>Promotions are configured here and assigned to a package separately.</p></div></header>
        @endunless
        <div class="promotion-form__body">
            <div class="promotion-form__field promotion-form__field--wide">
                <label for="promotion-name">Promotion Name <span class="promotion-form__required" aria-hidden="true">*</span></label>
                <input id="promotion-name" name="name" value="{{ old('name', $promotion->name) }}" maxlength="120" placeholder="e.g., Christmas Promo, Anniversary Offer..." autocomplete="off" required data-text-case="words" autocapitalize="words" autocorrect="off" spellcheck="false" @if($errors->has('name')) aria-invalid="true" aria-describedby="promotion-name-error" @endif>
                @error('name')<p class="promotion-form__field-error" id="promotion-name-error" role="alert">{{ $message }}</p>@enderror
                <p class="promotion-form__field-error" data-promotion-client-error="name" role="alert" hidden></p>
            </div>
            <div class="promotion-form__field">
                <label for="promotion-type">Discount Type @if($requiresDiscount)<span class="promotion-form__required" aria-hidden="true">*</span>@endif</label>
                <select id="promotion-type" name="discount_type" @required($requiresDiscount) @if($errors->has('discount_type')) aria-invalid="true" aria-describedby="promotion-type-error" @endif>
                    <option value="">Select discount type</option>
                    <option value="AMOUNT" @selected(old('discount_type', $promotion->discount_type) === 'AMOUNT')>Amount</option>
                    <option value="PERCENT" @selected(old('discount_type', $promotion->discount_type) === 'PERCENT')>Percentage</option>
                </select>
                @error('discount_type')<p class="promotion-form__field-error" id="promotion-type-error" role="alert">{{ $message }}</p>@enderror
                <p class="promotion-form__field-error" data-promotion-client-error="discount_type" role="alert" hidden></p>
            </div>
            <div class="promotion-form__field">
                <label for="promotion-value">Discount Value @if($requiresDiscount)<span class="promotion-form__required" aria-hidden="true">*</span>@endif</label>
                <div class="promotion-form__value-wrap">
                    <input id="promotion-value" name="discount_value" type="number" min="0.01" max="9999999999.99" step="0.01" value="{{ old('discount_value', $promotion->discount_value) }}" placeholder="{{ old('discount_type', $promotion->discount_type) === 'PERCENT' ? 'e.g., 20' : 'e.g., 5000.00' }}" @required($requiresDiscount) @if($errors->has('discount_value')) aria-invalid="true" aria-describedby="promotion-value-error" @endif>
                    <span class="promotion-form__value-suffix" data-promotion-suffix aria-hidden="true">{{ old('discount_type', $promotion->discount_type) === 'PERCENT' ? '%' : '₱' }}</span>
                </div>
                @error('discount_value')<p class="promotion-form__field-error" id="promotion-value-error" role="alert">{{ $message }}</p>@enderror
                <p class="promotion-form__field-error" data-promotion-client-error="discount_value" role="alert" hidden></p>
            </div>
            <div class="promotion-form__field">
                <label for="promotion-start">Start Date <span class="promotion-form__optional">(Optional)</span></label>
                <input id="promotion-start" name="starts_at" type="date" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d')) }}" @if($errors->has('starts_at')) aria-invalid="true" aria-describedby="promotion-start-error" @endif>
                @error('starts_at')<p class="promotion-form__field-error" id="promotion-start-error" role="alert">{{ $message }}</p>@enderror
                <p class="promotion-form__field-error" data-promotion-client-error="starts_at" role="alert" hidden></p>
            </div>
            <div class="promotion-form__field">
                <label for="promotion-end">End Date <span class="promotion-form__optional">(Optional)</span></label>
                <input id="promotion-end" name="ends_at" type="date" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d')) }}" @if($errors->has('ends_at')) aria-invalid="true" aria-describedby="promotion-end-error" @endif>
                @error('ends_at')<p class="promotion-form__field-error" id="promotion-end-error" role="alert">{{ $message }}</p>@enderror
                <p class="promotion-form__field-error" data-promotion-client-error="ends_at" role="alert" hidden></p>
            </div>
            @if($editing && $promotion->statusAt(now('Asia/Manila'), $promotion->package) === 'Incomplete Setup')
                <div class="promotion-form__errors promotion-form__field--wide"><strong>Incomplete Setup</strong><p>This saved legacy promotion cannot be assigned or applied until corrected.</p><ul>@foreach($promotion->reviewFields($promotion->package) as $issue)<li>{{ $issue }}</li>@endforeach</ul></div>
            @endif
        </div>
        <footer class="promotion-form__foot">
            <a href="{{ $back }}" data-service-form-cancel>Cancel</a>
            <button type="submit">{{ $editing ? 'Save Changes' : 'Save Promotion' }}</button>
        </footer>
    </section>
</form>
@endsection
