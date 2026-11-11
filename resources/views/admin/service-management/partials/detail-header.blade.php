@php
    $detailIcon = match (true) {
        str_contains(strtolower($detailTitle), 'package') => 'box-seam',
        str_contains(strtolower($detailTitle), 'casket') => 'layers',
        str_contains(strtolower($detailTitle), 'hearse') => 'truck',
        str_contains(strtolower($detailTitle), 'freebie') => 'gift',
        str_contains(strtolower($detailTitle), 'promotion') => 'ticket-perforated',
        default => 'plus-circle',
    };
@endphp
<header class="svc-detail-head">
    <span class="svc-detail-heading-icon" aria-hidden="true"><i class="bi bi-{{ $detailIcon }}"></i></span>
    <div><div class="svc-detail-title-row"><h2 class="svc-detail-modal-title">{{ $detailTitle }}</h2>@if($detailTitle === 'Package Details')<button type="button" class="pkg-pricing-info" popovertarget="package-pricing-info" aria-label="How pricing works"><i class="bi bi-info-circle" aria-hidden="true"></i></button>@endif</div><p class="svc-detail-record">{{ $detailRecord }}</p></div>
    @if($detailTitle === 'Package Details')
    <div id="package-pricing-info" class="pkg-pricing-popover" popover><strong>How pricing works</strong><p>The base package price includes the configured package inclusions and included casket. The included casket reference value is not added separately. Applicable casket upgrades and excess charges follow the configured package pricing rules.</p></div>
    @endif
    @if(auth()->user()?->isMainAdmin() || auth()->user()?->isBranchAdmin())
        @php
            $printRecord = match($detailTitle) { 'Package Details' => $package ?? null, 'Promotion Details' => $promotion ?? null, default => $catalog ?? null };
            $printTab = $detailTitle === 'Package Details' ? 'packages' : ($detailTitle === 'Promotion Details' ? 'promotions' : (isset($catalog) ? array_search($catalog::class, \App\Support\CatalogPhotos::TYPES, true) : null));
        @endphp
        @if($printRecord && $printTab)
            <a class="service-print-action" href="{{ route('admin.service-management.print',['tab'=>$printTab,'scope'=>'selected','ids'=>[$printRecord->getKey()]]) }}" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> Print Preview</a>
        @endif
    @endif
</header>
<div class="svc-detail-identity">
    <div><h1>{{ $detailName }}</h1>@if($detailSubtitle)<p>{{ $detailSubtitle }}</p>@endif</div>
    <div class="svc-detail-badges">
        @if(isset($detailStatusLabel))
            <span class="svc-detail-badge {{ $detailStatusClass ?? 'is-muted' }}"><i class="bi bi-{{ $detailStatusIcon ?? 'info-circle' }}" aria-hidden="true"></i> {{ $detailStatusLabel }}</span>
        @else
            <span class="svc-detail-badge {{ $detailActive ? 'is-active' : 'is-muted' }}"><i class="bi bi-{{ $detailActive ? 'check-circle' : 'archive' }}" aria-hidden="true"></i> {{ $detailActive ? 'Active' : 'Archived' }}</span>
            @if($detailAvailable !== null && $detailActive)<span class="svc-detail-badge {{ $detailAvailable ? 'is-active' : 'is-muted' }}">{{ $detailAvailable ? 'Available for Intake' : 'Unavailable' }}</span>@endif
            @if($detailPromo !== null)<span class="svc-detail-badge {{ $detailPromo === 'Active' ? 'is-active' : 'is-muted' }}">Promo: {{ $detailPromo }}</span>@endif
        @endif
    </div>
</div>

@if(isset($catalog) && in_array($catalog::class, \App\Support\CatalogPhotos::TYPES, true))
    @include('admin.service-management.partials.photo', ['photoRecord' => $catalog, 'photoSize' => 'detail'])
@endif
