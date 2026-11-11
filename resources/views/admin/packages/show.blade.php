@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('title', 'View Package')
@section('hide_layout_topbar', '1')
@section('suppress_layout_page_header', '1')
@section('page_title', 'View Package')
@section('page_desc', 'Review package pricing, inclusions, freebies, add-ons, and availability.')

@section('content')
@php
    $returnTo = request('return_to', route('admin.service-management.index', ['tab' => 'packages']));
    $editUrl = route('admin.packages.edit', ['package' => $package, 'return_to' => $returnTo]);
    $money = fn ($amount) => 'PHP ' . number_format((float) $amount, 2);
    $serviceLabels = \App\Models\Package::serviceTypeOptions();
    $visiblePackageInclusions = $package->packageInclusions
        ->reject(fn ($item) => \App\Models\Package::isLegacyPackageDescription($item->inclusion_name));
    [$basicInclusions, $otherInclusions] = $visiblePackageInclusions->partition(fn ($item) => \App\Models\Package::isBasicServiceType($item->service_type));
    $casket = $package->packageInclusions->firstWhere('service_type', \App\Models\Package::SERVICE_CASKET)?->casketCatalog?->display_name
        ?: ($package->coffin_type ?: 'No casket selected');
    $statusActions = [[
        'label' => $package->is_active ? 'Archive package' : 'Restore package',
        'title' => $package->is_active ? 'Archive this package?' : 'Restore package status?',
        'icon' => $package->is_active ? 'archive' : 'arrow-counterclockwise',
        'message' => ! $package->is_active && $restoreBlockers !== []
            ? 'Restoring the administrative status does not resolve required catalog dependencies. This package remains Needs Attention until the items below are resolved.'
            : ($package->is_active ? 'This package will no longer be available for new intake. Existing cases keep their saved details.' : 'This package will be available for new intake again.'),
        'affected' => collect(),
        'details' => ! $package->is_active ? $restoreBlockers : [],
        'blocked' => false,
        'url' => route('admin.packages.toggleActive', $package),
    ]];
@endphp

@include('admin.service-management.partials.detail-styles')
@include('admin.service-management.partials.catalog-item-styles')

<section class="svc-detail">
    @include('admin.service-management.partials.status-feedback')
    <article class="svc-detail-card pkg-summary-view">
        @include('admin.service-management.partials.detail-header', ['detailTitle' => 'Package Details', 'detailRecord' => 'Package ID: ' . $package->getKey(), 'detailName' => $package->name, 'detailActive' => $package->is_active, 'detailAvailable' => null, 'detailPromo' => $package->promo_status, 'detailSubtitle' => $package->short_description ?: ''])

        @include('admin.service-management.partials.photo', ['photoRecord' => $package, 'photoSize' => 'detail'])
        <div class="svc-detail-overview">
            <div class="svc-detail-overview-price"><span>Base Price</span><strong>{{ $money($package->price) }}</strong></div>
        </div>



        <section class="svc-detail-section">
            <h3>Package Contents</h3>
            <div class="svc-detail-list-grid">
                @foreach(['Basic Funeral Inclusions' => $basicInclusions, 'Other Inclusions' => $otherInclusions] as $heading => $inclusions)
                <div class="svc-detail-list">
                    <h3>{{ $heading }}</h3>
                    @if($inclusions->isNotEmpty())
                        <ul>
                            @foreach($inclusions as $item)
                                @php
                                    $label = $item->inclusion_name ?: ($serviceLabels[$item->service_type] ?? 'Included service');
                                    $limit = null;
                                    $rate = null;
                                    $catalogItem = match ($item->service_type) {
                                        \App\Models\Package::SERVICE_CASKET => $item->casketCatalog,
                                        \App\Models\Package::SERVICE_HEARSE => $item->hearseCatalog,
                                        default => null,
                                    };
                                    if (in_array($item->service_type, [\App\Models\Package::SERVICE_BODY_RETRIEVAL, \App\Models\Package::SERVICE_HEARSE], true)) {
                                        $limit = $item->included_kilometers !== null ? $item->included_kilometers . ' km' : 'Not configured';
                                        if ((float) $item->price_per_excess_kilometer > 0) $rate = $money($item->price_per_excess_kilometer) . ' / extra km';
                                    } elseif (in_array($item->service_type, [\App\Models\Package::SERVICE_EMBALMING, \App\Models\Package::SERVICE_HOME_VIEWING], true)) {
                                        $limit = $item->included_days !== null ? $item->included_days . 'D/' . max(0, (int) $item->included_days - 1) . 'N' : 'Not configured';
                                        if ((float) $item->price_per_extended_day > 0) $rate = $money($item->price_per_extended_day) . ' / extra day';
                                    } elseif ($item->service_type === \App\Models\Package::SERVICE_CASKET) {
                                        $limit = $item->casketCatalog?->display_name ?: ($item->casket_type ?: 'Not configured');
                                    }
                                @endphp
                                <li class="pkg-inclusion-line">
                                    <div>{{ $label }}
                                        @if($catalogItem)
                                            - @include('admin.service-management.partials.catalog-item', ['itemCatalog' => $catalogItem, 'itemText' => $catalogItem->display_name ?? $catalogItem->name, 'itemAdvice' => false, 'itemKind' => $item->service_type])
                                            @if($item->service_type === \App\Models\Package::SERVICE_HEARSE && $limit)
                                                - {{ $limit }}
                                            @endif
                                        @elseif($item->service_type === \App\Models\Package::SERVICE_HEARSE)
                                            <span>- Not selected</span>
                                            @if($item->included_kilometers !== null)
                                                - {{ $item->included_kilometers }} km
                                            @endif
                                        @elseif($limit)
                                            - {{ $limit }}
                                        @elseif($item->included_quantity)
                                            - {{ $item->included_quantity }} {{ $item->addOnCatalog?->unit ?: 'item' }} included
                                        @endif
                                    </div>
                                    @if($catalogItem)
                                        @include('admin.service-management.partials.catalog-advice', ['adviceCatalog' => $catalogItem, 'adviceKind' => $item->service_type])
                                    @elseif($item->service_type === \App\Models\Package::SERVICE_HEARSE)
                                        <div class="package-catalog-advice is-unavailable">No hearse selected. Choose one.</div>
                                    @endif
                                    @if($rate)<small>{{ $rate }}</small>@endif
                                </li>

                            @endforeach
                        </ul>
                    @else
                        <p class="svc-detail-empty">No inclusions listed.</p>
                    @endif
                </div>
                @endforeach

                <div class="svc-detail-list">
                    <h3>Freebies</h3>
                    @if($package->packageFreebies->isNotEmpty())
                        <ul class="pkg-freebie-list" tabindex="0" aria-label="Package freebies">
                            @foreach($package->packageFreebies as $item)
                                <li class="pkg-inclusion-line">
                                    <div>
                                        @include('admin.service-management.partials.catalog-item', ['itemCatalog' => $item->catalog, 'itemText' => $item->catalog?->name ?: $item->freebie_name, 'itemAdvice' => false])
                                        @if($item->quantity)({{ $item->quantity }} {{ $item->unit ?: 'item' }})@endif
                                        @unless($item->catalog)<small class="package-legacy-freebie-label">Legacy package freebie</small>@endunless
                                    </div>
                                    @if($item->catalog)
                                        @include('admin.service-management.partials.catalog-advice', ['adviceCatalog' => $item->catalog, 'adviceKind' => 'freebie'])
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="svc-detail-empty">No Freebies Included</p>
                    @endif
                    <p class="svc-detail-note">All freebies are complimentary.</p>
                </div>
            </div>
        </section>



        <section class="svc-detail-section">
            <h3>Promo</h3>
            @include('admin.packages._promotion_summary')
        </section>

        @unless(request()->boolean('modal'))
<footer class="svc-detail-actions">
            @if($canManage)
                <a class="svc-detail-secondary" href="{{ $editUrl }}" data-service-modal-url="{{ route('admin.packages.edit', ['package' => $package, 'modal' => 1, 'return_to' => $returnTo]) }}" data-service-modal-title="Edit Package">
                    <i class="bi bi-pencil" aria-hidden="true"></i> Edit Package
                </a>
                @include('admin.service-management.partials.status-actions', ['canManage' => $canManage])
            @endif
            @unless(request()->boolean('modal'))<a class="svc-detail-secondary" href="{{ $returnTo }}" data-parent-modal-close>
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to Service Management
            </a>@endunless
        </footer>
@endunless
    </article>
</section>
@endsection

<style>
html[data-theme] body.panel-shell-body .svc-detail { max-width:960px; width:100%; margin:0 auto; padding:0 20px 28px; }
html[data-theme] body.panel-shell-body .svc-detail-card { box-shadow:none; }
html[data-theme] body.panel-shell-body .svc-detail-head { padding:18px 20px; }
html[data-theme] body.panel-shell-body .svc-detail-identity { padding:18px 20px; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; }
html[data-theme] body.panel-shell-body .svc-detail-identity h1 { font-size:24px; }
html[data-theme] body.panel-shell-body .svc-detail-section { padding:16px 20px; }
html[data-theme] body.panel-shell-body .svc-detail-actions { padding:16px 20px; }
@media(max-width:640px) { html[data-theme] body.panel-shell-body .svc-detail { padding:0 12px 24px; } }

html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-head { padding:18px 60px 14px 20px; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-identity { padding:16px 20px 0; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-overview { margin:12px 20px 0; padding:0 0 12px; border:0; border-bottom:1px solid var(--border); border-radius:0; background:transparent; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-overview-price { display:flex; align-items:center; justify-content:space-between; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-overview-price strong { font-size:20px; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-section { padding:12px 20px 0; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-list-grid { display:grid; grid-template-columns:1.4fr 1fr; gap:20px; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-list { padding:0; border:0; border-radius:0; background:transparent; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-list li { padding:5px 0; font-size:13px; line-height:1.5; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-list small { display:block; font-size:12px; color:var(--ink-muted); }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-pricing-note summary { font-size:12px; color:var(--ink-muted); cursor:pointer; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-pricing-note p { margin-top:8px; font-size:12px; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-actions { margin-top:16px; padding:12px 20px; }
@media(max-width:480px) { html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-list-grid { grid-template-columns:1fr; gap:14px; } }

html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view { color:var(--ink); }
html[data-theme='light'] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view :is(p,small,li,.svc-detail-record,.svc-detail-overview-price span,.pkg-pricing-note summary) { color:#17251C !important; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-inclusion-line { display:block; position:relative; padding:6px 0 6px 14px; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-inclusion-line::before { position:absolute; left:0; top:13px; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-inclusion-line small { padding-top:2px; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-list-grid { grid-template-columns:minmax(0,2fr) minmax(0,1fr); }
@media(max-width:480px) { html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .svc-detail-list-grid { grid-template-columns:1fr; } }

html[data-theme] .pkg-summary-view .pkg-inclusion-line::before { margin-top:0 !important; top:calc(6px + .8em) !important; transform:translateY(-50%); }

html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-inclusion-line { display:grid !important; grid-template-columns:7px minmax(0,1fr); column-gap:9px; padding:6px 0 !important; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-inclusion-line::before { position:static !important; margin-top:.55em !important; transform:none !important; grid-column:1; grid-row:1; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-inclusion-line > :is(div,small) { grid-column:2; min-width:0; overflow-wrap:anywhere; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-freebie-list { max-height:min(36vh,300px); min-height:0; overflow-y:auto; overscroll-behavior:contain; scrollbar-width:thin; scrollbar-gutter:stable; gap:4px; padding-right:8px; }
html[data-theme] body.service-catalog-modal-frame:is(.branch-admin-ui,.system-admin-ui) .svc-detail-card.pkg-summary-view .pkg-freebie-list > .pkg-inclusion-line { padding:4px 0 !important; }
</style>

<style>
.pkg-pricing-info { margin-left:0; flex:0 0 32px; width:32px; height:32px; border:0; border-radius:8px; background:transparent; color:var(--ink); cursor:pointer; font-size:18px; }
.pkg-pricing-info:hover { background:var(--card); }
.pkg-pricing-popover { position:fixed; inset:88px 24px auto auto; margin:0; width:min(360px,calc(100vw - 48px)); padding:18px; border:1px solid var(--border); border-radius:12px; background:var(--card); color:var(--ink); box-shadow:0 8px 24px rgb(0 0 0 / .18); font-size:13px; line-height:1.6; }
.pkg-pricing-popover p { margin:8px 0 0; }
</style>

<style>
.pkg-summary-view .package-catalog-advice { font-size:12px; line-height:1.5; margin-top:3px; }
.pkg-summary-view .package-catalog-advice.is-unavailable { color:#986000; }
.pkg-summary-view .package-catalog-advice.is-archived { color:#786247; }
html[data-theme='dark'] .pkg-summary-view .package-catalog-advice.is-unavailable { color:#FBBF24; }
html[data-theme='dark'] .pkg-summary-view .package-catalog-advice.is-archived { color:#D4B98B; }
</style>
