@php
    $promotion = $package->promotion;
    $legacyPromotionData = filled($package->promo_label)
        || filled($package->promo_value_type)
        || $package->promo_value !== null
        || $package->promo_starts_at !== null
        || $package->promo_ends_at !== null
        || $package->promo_manually_stopped_at !== null;
    $legacyReviewFields = $package->legacyPromotionReviewFields();
    $legacyPromotionValid = $legacyReviewFields === [];
    $promoStatus = $promotion?->statusAt(now('Asia/Manila'), $package)
        ?? ($legacyPromotionData
            ? ($legacyPromotionValid ? $package->promotionStatusAt(now('Asia/Manila')) : 'Incomplete Setup')
            : 'No Promotion');
    $promoName = $promotion?->name ?? $package->promo_label;
    $promoType = $promotion?->discount_type ?? $package->promo_value_type;
    $promoValue = $promotion?->discount_value ?? $package->promo_value;
    $promoStartsAt = $promotion?->starts_at ?? $package->promo_starts_at;
    $promoEndsAt = $promotion?->ends_at ?? $package->promo_ends_at;
    $promoStatusClass = \Illuminate\Support\Str::slug($promoStatus);
@endphp
<div class="package-promo-summary" data-promo-status="{{ $promoStatus }}">
    <span class="package-promo-status is-{{ $promoStatusClass }}"><i class="bi bi-tag" aria-hidden="true"></i> {{ $promoStatus }}</span>
    @if($promotion || $legacyPromotionData)
        <span class="package-promo-label">{{ $promoName ?: 'Unnamed promotion' }}</span>
        @if($promoValue !== null)
            <small>{{ strtoupper((string) $promoType) === 'PERCENT' ? 'Percentage' : 'Amount' }}:
                {{ strtoupper((string) $promoType) === 'PERCENT' ? number_format((float) $promoValue, 2).'%' : 'PHP '.number_format((float) $promoValue, 2) }}</small>
        @endif
        @if($promoStartsAt)<small>Start Date: {{ $promoStartsAt->format('M d, Y') }}</small>@endif
        @if($promoEndsAt)<small>End Date: {{ $promoEndsAt->format('M d, Y') }}</small>@endif
        @if($promoStatus === 'Incomplete Setup')
            <small>Missing or invalid: {{ implode(', ', array_unique($legacyReviewFields)) }}</small>
        @elseif($promoStatus === 'Turned Off')
            <small>This promotion was manually turned off and will not apply to new cases.</small>
        @endif
    @endif
</div>
@once
<style>
.package-promo-summary { display:flex; flex-direction:column; align-items:flex-start; gap:4px; min-width:0; overflow-wrap:anywhere; }
.package-promo-summary small { display:block; color:inherit; }
.package-promo-status { display:inline-flex; align-items:center; gap:5px; font-weight:600; }
.package-promo-status.is-active { color:#18794E; }
.package-promo-status.is-expired { color:#986000; }
.package-promo-status.is-scheduled { color:#32658B; }
.package-promo-status.is-no-promotion { color:var(--ink-muted); }
.package-promo-status.is-turned-off { color:#9A4E32; }
.package-promo-status.is-incomplete-setup { color:#9A4E32; }
html[data-theme='dark'] .package-promo-status.is-active { color:#52B788; }
html[data-theme='dark'] .package-promo-status.is-expired { color:#FBBF24; }
html[data-theme='dark'] .package-promo-status.is-scheduled { color:#93C5FD; }
html[data-theme='dark'] .package-promo-status.is-turned-off { color:#FDBA9A; }
html[data-theme='dark'] .package-promo-status.is-incomplete-setup { color:#FDBA9A; }
</style>
@endonce
