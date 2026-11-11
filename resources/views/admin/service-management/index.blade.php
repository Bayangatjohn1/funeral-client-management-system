@extends('layouts.panel')

@section('page_title', $canManage ? 'Service Management' : 'Services')
@section('page_desc', $canManage ? 'Manage packages, caskets, add-ons, freebies, and hearses in one workspace.' : 'View active and archived service catalog records in read-only mode.')
@section('hide_layout_topbar', '1')
@section('suppress_layout_page_header', '1')

@php
    $serviceHeaderTab = in_array($activeTab ?? 'packages', ['packages', 'caskets', 'addons', 'freebies', 'hearses', 'promotions'], true)
        ? $activeTab
        : 'packages';
    $serviceHeaderActions = [
        'packages' => ['label' => 'Add Package', 'url' => route('admin.packages.create', ['return_to' => route('admin.service-management.index', ['tab' => 'packages'])])],
        'caskets' => ['label' => 'Add Casket', 'url' => route('admin.casket-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'caskets'])])],
        'addons' => ['label' => 'Add Add-on', 'url' => route('admin.add-on-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'addons'])])],
        'freebies' => ['label' => 'Add Freebie', 'url' => route('admin.freebie-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'freebies'])])],
        'hearses' => ['label' => 'Add Hearse', 'url' => route('admin.hearse-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'hearses'])])],
        'promotions' => ['label' => 'Add Promotion', 'url' => route('admin.promotions.create', ['modal' => 1, '_service_modal' => 1])],
    ];
    $serviceHeaderAction = $serviceHeaderActions[$serviceHeaderTab];
@endphp

@section('topbar_actions')
    @if($canManage)
        <button type="button" class="btn btn-primary-custom btn-sm" data-service-modal-url="{{ $serviceHeaderAction['url'] }}" data-service-modal-title="{{ $serviceHeaderAction['label'] }}">
            <i class="bi bi-plus-circle" aria-hidden="true"></i>
            <span>{{ $serviceHeaderAction['label'] }}</span>
        </button>
    @endif
@endsection

@section('content')
@include('partials.services_visual_styles')
<style>
    .service-management-hub .service-catalog-record-id {
        display:block;
        margin-top:3px;
        color:var(--ink-muted, var(--ink));
        font-size:.75rem;
        font-weight:500;
        font-variant-numeric:tabular-nums;
    }
</style>
@include('admin.service-management.partials.catalog-item-styles')
@include('partials.case_records_layout_styles')
@php
    $activeTab = in_array($activeTab ?? 'packages', ['packages', 'caskets', 'addons', 'freebies', 'hearses', 'promotions'], true) ? $activeTab : 'packages';
    $isAvailabilityAwareCatalog = in_array($activeTab, ['caskets', 'hearses', 'freebies', 'addons'], true);
    $defaultServiceStatus = $isAvailabilityAwareCatalog ? 'all' : 'active';
    $serviceStatusOptions = $isAvailabilityAwareCatalog
        ? ['all' => 'All', 'available' => 'Available', 'unavailable' => 'Unavailable', 'archived' => 'Archived']
        : ['active' => 'Active', 'archived' => 'Archived', 'all' => 'All statuses'];
    $hasServiceFilters = $activeTab === 'packages'
        ? (($packageStatus ?? 'all') !== 'all')
        : ($activeTab === 'promotions' ? (($promotionStatus ?? 'all') !== 'all') : (($status ?? $defaultServiceStatus) !== $defaultServiceStatus));
    $money = fn ($value) => '&#8369;' . number_format((float) $value, 2);
    $packageDisplay = function ($package) {
        $structured = $package->packageInclusions ?? collect();
        $displayStructured = $structured->reject(fn ($item) => \App\Models\Package::isLegacyPackageDescription($item->inclusion_name));
        $casketRow = $displayStructured->firstWhere('service_type', \App\Models\Package::SERVICE_CASKET);
        $includedCasket = $casketRow?->casketCatalog?->name ?: ($casketRow?->casket_type ?: $package->coffin_type);
        $inclusions = array_values(array_filter($package->inclusionNames(), fn ($name) => ! \App\Models\Package::isLegacyPackageDescription($name)));
        $freebies = $package->freebieNames();

        $serviceOrder = array_flip([
            \App\Models\Package::SERVICE_BODY_RETRIEVAL,
            \App\Models\Package::SERVICE_EMBALMING,
            \App\Models\Package::SERVICE_CASKET,
            \App\Models\Package::SERVICE_HOME_VIEWING,
            \App\Models\Package::SERVICE_HEARSE,
            \App\Models\Package::SERVICE_CUSTOM,
        ]);
        $services = $displayStructured->isNotEmpty()
            ? $displayStructured->sortBy(fn ($item) => $serviceOrder[$item->service_type] ?? 99)->values()->map(function ($item) use ($includedCasket) {
                return match ($item->service_type) {
                    \App\Models\Package::SERVICE_CASKET => $includedCasket ?: ($item->inclusion_name ?: 'Casket'),
                    \App\Models\Package::SERVICE_HOME_VIEWING => $item->included_days !== null
                        ? 'Viewing (' . $item->included_days . 'd)'
                        : ($item->inclusion_name ?: 'Home Viewing'),
                    default => $item->inclusion_name ?: (\App\Models\Package::serviceTypeOptions()[$item->service_type] ?? 'Included service'),
                };
            })->all()
            : $inclusions;

        $serviceRows = $displayStructured->sortBy(fn ($item) => $serviceOrder[$item->service_type] ?? 99)->values();
        $serviceItems = collect($services)->map(function ($text, $index) use ($serviceRows) {
            $row = $serviceRows->get($index);
            $catalog = match ($row?->service_type) {
                \App\Models\Package::SERVICE_CASKET => $row->casketCatalog,
                \App\Models\Package::SERVICE_HEARSE => $row->hearseCatalog,
                default => null,
            };
            $tableText = match ($row?->service_type) {
                \App\Models\Package::SERVICE_CASKET => 'Casket',
                \App\Models\Package::SERVICE_HEARSE => 'Hearse',
                default => $text,
            };
            return ['text' => $catalog?->name ?: $text, 'table_text' => $tableText, 'catalog' => $catalog];
        });
        $cardInclusions = $serviceItems->filter(fn ($service, $index) => $serviceRows->get($index)?->service_type !== \App\Models\Package::SERVICE_CASKET)
            ->map(function ($service, $index) use ($serviceRows) {
                $row = $serviceRows->get($index);
                if ($row?->service_type === \App\Models\Package::SERVICE_HEARSE && $service['catalog']) {
                    return 'Hearse: ' . $service['catalog']->name;
                }
                return $service['text'];
            })->values();
        if ($displayStructured->isEmpty()) {
            $cardInclusions = collect($inclusions)->reject(fn ($name) => in_array(mb_strtolower(trim($name)), ['casket', 'coffin'], true))->values();
        }
        return [
            'service_items' => $serviceItems,
            'card_inclusions' => $cardInclusions->all(),
            'services' => $services,
            'casket' => $includedCasket ?: 'No casket selected',
            'inclusions' => $inclusions,
            'freebies' => $freebies,
            'inclusions_count' => count($inclusions),
            'freebies_count' => count($freebies),
        ];
    };
    $sectionDescriptions = [
        'packages' => 'Predefined funeral service packages and bundled pricing.',
        'caskets' => 'Casket models, materials, and reference values for package inclusions and upgrades.',
        'hearses' => 'Ceremonial funeral coaches and reference values for package inclusions and upgrades.',
        'promotions' => 'Manage reusable promotions, assignments, schedules, and effective status.',
        'freebies' => 'Complimentary items and gestures bundled into package offerings.',
        'addons' => 'Optional ceremonial supplies and extra services.',
    ];
    $tabs = [
        'packages' => ['label' => 'Packages', 'icon' => 'bi-collection', 'count' => $stats['packages']['total'] ?? 0, 'create' => route('admin.packages.create', ['return_to' => route('admin.service-management.index', ['tab' => 'packages'])]), 'action' => 'Add Package'],
        'caskets' => ['label' => 'Caskets', 'icon' => 'bi-box2-heart', 'count' => $stats['caskets']['total'] ?? 0, 'create' => route('admin.casket-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'caskets'])]), 'action' => 'Add Casket'],
        'addons' => ['label' => 'Add-ons', 'icon' => 'bi-plus-circle', 'count' => $stats['add_ons']['total'] ?? 0, 'create' => route('admin.add-on-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'addons'])]), 'action' => 'Add Add-on'],
        'freebies' => ['label' => 'Freebies', 'icon' => 'bi-gift', 'count' => $stats['freebies']['total'] ?? 0, 'create' => route('admin.freebie-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'freebies'])]), 'action' => 'Add Freebie'],
        'hearses' => ['label' => 'Hearses', 'icon' => 'bi-truck', 'count' => $stats['hearses']['total'] ?? 0, 'create' => route('admin.hearse-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'hearses'])]), 'action' => 'Add Hearse'],
        'promotions' => ['label' => 'Promotions', 'icon' => 'bi-ticket-perforated', 'count' => $stats['promotions']['total'] ?? 0, 'create' => route('admin.promotions.create', ['modal' => 1, '_service_modal' => 1]), 'action' => 'Add Promotion'],
    ];
@endphp

<div class="admin-table-page admin-catalog-page service-management-hub service-reference px-4 sm:px-6 lg:px-8 py-6" data-service-hub data-service-server-navigation data-active-tab="{{ $activeTab }}">
    <div class="mx-auto max-w-[1440px] space-y-5">
        <header class="service-reference-header panel-title-surface">
            <div class="service-reference-heading">
                <h1>{{ $canManage ? 'Service Management' : 'Services' }}</h1>
                <p>{{ $canManage ? 'Manage funeral packages, caskets, hearses, freebies, and add-ons in one place.' : 'View active and archived service catalog records in read-only mode.' }}</p>
            </div>
            <div class="service-hub-nav">
                <div class="service-hub-tabs" role="tablist" aria-label="Service management sections">
                    @foreach(['packages', 'caskets', 'hearses', 'freebies', 'addons', 'promotions'] as $key)
                        @php
                            $tab = $tabs[$key];
                        @endphp
                        <a href="{{ route('admin.service-management.index', ['tab' => $key]) }}" class="service-hub-tab {{ $key === $activeTab ? 'is-active' : '' }}" role="tab" aria-selected="{{ $key === $activeTab ? 'true' : 'false' }}" data-service-tab="{{ $key }}">
                            <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $tab['label'] }}</span>
                            <b>{{ $tab['count'] }}</b>
                        </a>
                    @endforeach
                </div>
            </div>
        </header>
        @if(session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="flash-error" role="alert">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="flash-error">{{ $errors->first() }}</div>
        @endif

        @if($canManage && $activeTab === 'packages')
            @include('admin.service-management.partials.custom-package-defaults')
        @endif
        <section class="table-system-card admin-table-card service-hub-card">
            <div class="service-section-intro">
                <div><h2>{{ $tabs[$activeTab]['label'] }}</h2><p>{{ $sectionDescriptions[$activeTab] }}</p></div>
                @if($canManage)
                    <button type="button" class="reference-page-action btn btn-primary-custom" data-service-modal-url="{{ $serviceHeaderAction['url'] }}" data-service-modal-title="{{ $serviceHeaderAction['label'] }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> {{ $serviceHeaderAction['label'] }}</button>
                @else
                    <span class="service-readonly"><i class="bi bi-lock" aria-hidden="true"></i> Read-only</span>
                    <span class="sr-only">Read-only branch access. System Administrators manage catalog items and casket availability.</span>
                @endif
            </div>

            <div class="table-system-toolbar service-hub-toolbar">
                <form method="GET" action="{{ route('admin.service-management.index') }}" class="table-toolbar service-hub-filter uniform-record-filters shared-record-filter-toolbar" data-table-toolbar data-service-filter-form data-catalog-filter-toolbar>
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <div class="table-toolbar-field service-search-field">
                        <label class="table-toolbar-label">Search</label>
                        <div class="table-toolbar-input-wrap shared-record-search service-record-search">
                            <i class="bi bi-search table-toolbar-leading-icon" aria-hidden="true"></i>
                            <input type="search" name="q" value="{{ $search }}" class="table-toolbar-search shared-record-search-input has-clear-action" aria-label="Search {{ strtolower($tabs[$activeTab]['label']) }}" placeholder="{{ ['packages' => 'Package name, description, service or freebie', 'caskets' => 'Casket name, material or description', 'hearses' => 'Hearse name or description', 'freebies' => 'Freebie name or unit', 'addons' => 'Add-on name, category, unit or description', 'promotions' => 'Promotion name, package or discount'][$activeTab] ?? 'Catalog name or description' }}" data-service-search data-shared-search-ready="1" maxlength="100" aria-autocomplete="list" aria-haspopup="listbox" aria-controls="serviceSearchMatches" aria-expanded="false" autocomplete="off">
                            <button type="button" class="live-search-clear" data-service-clear-search aria-label="Clear search" title="Clear search" hidden>
                                <i class="bi bi-x" aria-hidden="true"></i>
                            </button>
                            <button type="submit" class="service-search-submit" data-service-search-submit>Search</button>
                            <div id="serviceSearchMatches" class="service-search-matches" role="listbox" aria-label="Matching catalog records" data-service-search-matches hidden></div>
                        </div>
                    </div>
                    <div class="service-filter-actions">
                        @if($activeTab === 'packages')
                            @foreach([
                                ['name' => 'package_status', 'category' => 'Status', 'label' => 'Package status', 'value' => $packageStatus ?? 'all', 'options' => ['all' => 'All', 'active' => 'Active', 'active_unavailable' => 'Active with Unavailable Items', 'archived' => 'Archived'], 'icon' => ['all' => 'collection', 'active' => 'check-circle', 'active_unavailable' => 'exclamation-circle', 'archived' => 'archive']],
                            ] as $filter)
                                <div class="table-toolbar-field catalog-native-control">
                                    <div class="table-toolbar-select-wrap">
                                        <i class="bi bi-funnel table-toolbar-leading-icon" aria-hidden="true"></i>
                                        <select name="{{ $filter['name'] }}" class="table-toolbar-select" data-filter-category="{{ $filter['category'] }}" data-default-value="all" aria-label="{{ $filter['label'] }}" @if($filter['multiple'] ?? false) multiple @endif>
                                            @foreach($filter['options'] as $value => $label)
                                                <option data-icon="{{ $filter['icon'][$value] }}" value="{{ $value }}" @selected(($filter['multiple'] ?? false) ? in_array($value, $filter['value'], true) : $filter['value'] === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                                    </div>
                                </div>
                            @endforeach
                        @elseif($activeTab === 'promotions')
                            <div class="table-toolbar-field">
                                <div class="table-toolbar-select-wrap">
                                    <i class="bi bi-funnel table-toolbar-leading-icon" aria-hidden="true"></i>
                                    <select name="promotion_status" class="table-toolbar-select" data-promotion-status data-default-value="all" aria-label="Promotion status">
                                        @foreach(array_filter(['all' => 'All', 'incomplete_setup' => ($hasIncompleteSetupPromotions ?? false) ? 'Incomplete Setup' : null, 'scheduled' => 'Scheduled', 'active' => 'Active', 'expired' => 'Expired', 'turned_off' => 'Turned Off'], fn ($label) => $label !== null) as $value => $label)
                                            <option value="{{ $value }}" @selected(($promotionStatus ?? 'all') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                                </div>
                            </div>
                        @else
                            <div class="table-toolbar-field">
                                <div class="table-toolbar-select-wrap">
                                    <i class="bi bi-funnel table-toolbar-leading-icon" aria-hidden="true"></i>
                                    <select name="status" class="table-toolbar-select" data-service-status data-default-value="{{ $defaultServiceStatus }}" aria-label="Status">
                                        @foreach($serviceStatusOptions as $value => $label)
                                            <option data-icon="{{ ['active'=>'check-circle', 'available'=>'check-circle', 'unavailable'=>'pause-circle', 'archived'=>'archive', 'all'=>'collection'][$value] }}" value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                                </div>
                            </div>
                        @endif
                        <div class="table-toolbar-field">
                            <div class="table-toolbar-select-wrap">
                                <i class="bi bi-sort-alpha-down table-toolbar-leading-icon" aria-hidden="true"></i>
                                <select name="sort" class="table-toolbar-select" data-service-sort data-default-value="name_asc" aria-label="Sort {{ strtolower($tabs[$activeTab]['label']) }}">
                                    @php
                                        $sortOptions = $activeTab === 'promotions'
                                            ? ['name_asc' => 'Name: A–Z', 'name_desc' => 'Name: Z–A', 'latest' => 'Newest First', 'oldest' => 'Oldest First', 'discount_asc' => 'Discount: Low to High', 'discount_desc' => 'Discount: High to Low', 'start_date' => 'Start Date', 'end_date' => 'End Date']
                                            : ['name_asc' => 'Name: A–Z', 'name_desc' => 'Name: Z–A', 'latest' => 'Newest First', 'oldest' => 'Oldest First'];
                                    @endphp
                                    @foreach($sortOptions as $value => $label)
                                    <option data-icon="{{ ['name_asc'=>'sort-alpha-down', 'name_desc'=>'sort-alpha-up', 'latest'=>'sort-down', 'oldest'=>'sort-up', 'discount_asc'=>'sort-down', 'discount_desc'=>'sort-up', 'start_date'=>'calendar-event', 'end_date'=>'calendar-event'][$value] }}" value="{{ $value }}" @selected(request('sort', 'name_asc') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                            </div>
                        </div>
                        @if($hasServiceFilters)
                        <div class="table-toolbar-reset-wrap">
                            <span class="table-toolbar-label opacity-0 select-none" aria-hidden="true">Actions</span>
                            <button type="button" class="btn btn-secondary btn-sm filter-clear-action" data-service-reset>
                                <span>Clear filters</span>
                            </button>
                        </div>
                        @endif
                    </div>
                </form>
                <div class="service-viewbar">
                    @if($canManage)
                        <a class="service-print-action" href="{{ route('admin.service-management.print', array_merge(request()->only(['q','status','availability','package_status','promotion_status','sort']), ['tab'=>$activeTab,'scope'=>$hasServiceFilters ? 'filtered' : 'all'])) }}" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> Print Preview</a>
                    @endif
                    <div class="service-view-toggle catalog-view-switch is-visible" data-service-view-toggle role="group" aria-label="{{ $activeTab === 'promotions' ? 'Promotion view' : 'Catalog view' }}">
                        <button type="button" class="service-view-option is-active" data-catalog-view="cards" aria-pressed="true" aria-label="Grid view" title="Grid view">
                            <i class="bi bi-grid" aria-hidden="true"></i><span>Grid</span>
                        </button>
                        <button type="button" class="service-view-option" data-catalog-view="table" aria-pressed="false" aria-label="Table view" title="Table view">
                            <i class="bi bi-table" aria-hidden="true"></i><span>Table</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="service-hub-panels">
                <div class="service-hub-panel {{ $activeTab === 'packages' ? 'is-active' : '' }}" data-service-panel="packages" role="tabpanel">
                    <div class="service-package-cards" data-catalog-view-panel="cards">
                        @forelse($packages as $package)
                            @php
                                $display = $packageDisplay($package);
                                $visibleInclusions = $display['card_inclusions'];
                                $visibleFreebies = $display['freebies'];
                                $intakeBlockers = app(\App\Support\ServiceCatalogPolicy::class)->packageBlockers($package);
                            @endphp
                            <article class="service-package-card" data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $package]) data-status="{{ $package->is_active ? 'active' : 'archived' }}" data-search="{{ strtolower($package->name . ' ' . $display['casket'] . ' ' . implode(' ', $display['inclusions']) . ' ' . implode(' ', $display['freebies']) . ' ' . ($package->short_description ?? '')) }}">
                                @include('admin.service-management.partials.card-media', ['mediaRecord' => $package, 'allowPhotoUpload' => $canManage])
                                <div class="service-package-card__head">
                                    <div class="service-package-card__identity">
                                        <span class="service-package-card__icon" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
                                        <div>
                                            <div class="package-title-with-warning"><h3>{{ $package->name }}</h3>@if($package->is_active && $intakeBlockers !== []) @include('admin.service-management.partials.package-warning', ['warningView' => 'card']) @endif</div> <small class="service-catalog-record-id">ID #{{ $package->getKey() }}</small>
                                            <div class="service-package-code">Package ID: {{ $package->getKey() }}</div>
                                        </div>
                                    </div>
                                    <span class="service-status {{ $package->is_active ? 'is-active' : 'is-archived' }}">{{ $package->is_active ? 'Active' : 'Archived' }}</span>
                                </div>


                                <div class="service-package-card__price">
                                    <span>Base Package Price</span>
                                    <strong>{!! $money($package->price) !!}</strong>
                                    @include('admin.packages._promotion_summary')
                                </div>

                                @if($package->short_description)
                                    <p class="service-package-card__description">{{ $package->short_description }}</p>
                                @endif

                                <div class="service-package-card__lists">
                                    <div>
                                        <h4>Included Services ({{ count($visibleInclusions) }})</h4>
                                        @if($visibleInclusions)
                                            <p>{{ implode(' · ', $visibleInclusions) }}</p>
                                        @else
                                            <em>None listed</em>
                                        @endif
                                    </div>
                                    <div>
                                        <h4><i class="bi bi-gift" aria-hidden="true"></i> Freebies</h4>
                                        @if($visibleFreebies)
                                            <p>{{ implode(' · ', $visibleFreebies) }}</p>
                                        @else
                                            <em>No Freebies</em>
                                        @endif
                                    </div>
                                </div>

                                <div class="service-package-summary"><i class="bi bi-layers" aria-hidden="true"></i><span>Included Casket: <strong>{{ $display['casket'] }}</strong></span></div>
                                <div class="service-package-card__foot">
                                    @include('admin.service-management._catalog-actions', ['record' => $package, 'catalogRoute' => 'packages', 'catalogParameter' => 'package', 'catalogLabel' => 'Package', 'cardAction' => true])
                                </div>
                            </article>
                        @empty
                            <div class="service-hub-empty is-inline" data-service-empty>
                                <i class="bi bi-box-seam"></i>
                                <strong>No packages found</strong>
                                <span>Add a service package to get started.</span>
                            </div>
                        @endforelse
                    </div>

                    <div class="table-system-wrap service-package-table service-package-list-table" data-catalog-view-panel="table" hidden>
                        <table class="table-base table-system-table w-full">
                            <colgroup><col class="package-name-col"><col class="package-price-col"><col class="package-services-col"><col class="package-freebies-col"><col class="package-promo-col"><col class="package-status-col"><col class="package-actions-col"></colgroup>
                            <thead><tr>
                                <th scope="col">Package</th>
                                <th scope="col" class="table-col-number">Base Price</th>
                                <th scope="col">Included Services</th>
                                <th scope="col" class="package-centered">Freebies</th>
                                <th scope="col" class="package-centered">Promo</th>
                                <th scope="col" class="package-centered">Status</th>
                                <th scope="col" class="table-col-actions">Actions</th>
                            </tr></thead>
                            <tbody>
                                @forelse($packages as $package)
                                    @php
                                        $display = $packageDisplay($package);
                                        $intakeBlockers = app(\App\Support\ServiceCatalogPolicy::class)->packageBlockers($package);
                                        $tableServiceItems = $display['service_items']->sortBy(function ($service) {
                                            $catalog = $service['catalog'];
                                            if (! $catalog) return 2;
                                            return (! $catalog->is_active || ! $catalog->is_available) ? 0 : 1;
                                        })->values();
                                        $affectedFreebies = $package->packageFreebies->filter(fn ($freebie) => $freebie->catalog && (! $freebie->catalog->is_active || ! $freebie->catalog->is_available))->values();
                                        $normalFreebies = $package->packageFreebies->reject(fn ($freebie) => $freebie->catalog && (! $freebie->catalog->is_active || ! $freebie->catalog->is_available))->values();
                                        $freebiePreview = $affectedFreebies->concat($normalFreebies->take(2))->map(fn ($freebie) => ['catalog' => $freebie->catalog, 'name' => $freebie->catalog?->name ?: $freebie->freebie_name])->values();
                                        $freebieMore = $normalFreebies->slice(2)->map(fn ($freebie) => $freebie->catalog?->name ?: $freebie->freebie_name)->values();
                                        if ($package->packageFreebies->isEmpty() && $display['freebies'] !== []) {
                                            $legacyFreebies = collect($display['freebies']);
                                            $freebiePreview = $legacyFreebies->take(2)->map(fn ($name) => ['catalog' => null, 'name' => $name])->values();
                                            $freebieMore = $legacyFreebies->slice(2)->values();
                                        }
                                        $freebiePopoverId = 'package-freebies-more-' . $package->getKey();
                                    @endphp
                                    <tr data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $package]) data-status="{{ $package->is_active ? 'active' : 'archived' }}" data-search="{{ strtolower($package->name . ' ' . implode(' ', $display['services'])) }}">
                                        <td>
                                            <div class="catalog-primary-cell">
                                                @include('admin.service-management.partials.photo', ['photoRecord' => $package])
                                                <div>
                                                    <div class="package-title-with-warning"><div class="font-semibold">{{ $package->name }}</div>@if($package->is_active && $intakeBlockers !== []) @include('admin.service-management.partials.package-warning', ['warningView' => 'table']) @endif</div>
                                                    <small class="service-catalog-record-id">ID #{{ $package->getKey() }}</small>
                                                    <div class="service-package-code">Package ID: {{ $package->getKey() }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="table-col-number package-base-price">{!! $money($package->price) !!}</td>
                                        <td>
                                            <ul class="service-inclusion-chips" aria-label="Included services">
                                                @forelse($tableServiceItems as $service)
                                                    @php
                                                        $serviceCatalog = $service['catalog'];
                                                        $serviceCatalogAffected = $serviceCatalog && (! $serviceCatalog->is_active || ! $serviceCatalog->is_available);
                                                    @endphp
                                                    <li class="{{ $serviceCatalogAffected ? 'is-catalog-affected' : ($serviceCatalog ? 'is-catalog-item' : 'is-service-item') }}">@include('admin.service-management.partials.catalog-item', ['itemCatalog' => $serviceCatalog, 'itemText' => $service['table_text'] ?? $service['text'], 'itemWarningIcon' => true])</li>
                                                @empty
                                                    <li class="is-empty">None listed</li>
                                                @endforelse
                                            </ul>
                                        </td>
                                        <td class="package-freebies-cell">
                                            @if($freebiePreview->isNotEmpty())
                                                <ul class="package-freebie-preview" aria-label="Included freebies">
                                                    @foreach($freebiePreview as $freebie)
                                                        <li>@include('admin.service-management.partials.catalog-item', ['itemCatalog' => $freebie['catalog'], 'itemText' => $freebie['name'], 'itemWarningIcon' => true])</li>
                                                    @endforeach
                                                </ul>
                                            @elseif($freebieMore->isEmpty())
                                                <span class="package-no-freebies">No Freebies</span>
                                            @endif
                                            @if($freebieMore->isNotEmpty())
                                                <button type="button" class="package-freebie-more-trigger" popovertarget="{{ $freebiePopoverId }}" aria-expanded="false" aria-controls="{{ $freebiePopoverId }}" data-package-freebie-trigger>+ {{ $freebieMore->count() }} more</button>
                                                <div id="{{ $freebiePopoverId }}" popover="auto" class="package-freebie-more-popover" role="dialog" aria-label="More freebies in {{ $package->name }}">
                                                    <strong>More freebies</strong>
                                                    <ul>@foreach($freebieMore as $freebie)<li>{{ $freebie }}</li>@endforeach</ul>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="package-centered">
                                            @include('admin.packages._promotion_summary')
                                        </td>
                                        <td class="package-centered"><span class="service-status {{ $package->is_active ? 'is-active' : 'is-archived' }}">{{ $package->is_active ? 'Active' : 'Archived' }}</span></td>
                                        <td class="table-col-actions">
                                            @include('admin.service-management._catalog-actions', ['record' => $package, 'catalogRoute' => 'packages', 'catalogParameter' => 'package', 'catalogLabel' => 'Package', 'cardAction' => false])
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-service-empty><td colspan="7" class="table-system-empty">No packages found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($packages->hasPages())
                        <div class="service-pagination">{{ $packages->onEachSide(1)->links('components.pagination.table') }}</div>
                    @endif
                </div>

                <div class="service-hub-panel {{ $activeTab === 'caskets' ? 'is-active' : '' }}" data-service-panel="caskets" role="tabpanel">
                    <div class="service-package-cards" data-catalog-view-panel="cards">
                        @forelse($caskets as $catalog)
                            <article class="service-package-card" data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $catalog]) data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->type_or_material . ' ' . $catalog->description) }}">
                                @include('admin.service-management.partials.card-media', ['mediaRecord' => $catalog, 'allowPhotoUpload' => $canManage])
                                <div class="service-package-card__head">
                                    <div class="service-package-card__identity">
                                        <span class="service-package-card__icon" aria-hidden="true"><i class="bi bi-box2-heart"></i></span>
                                        <div>
                                            <h3>{{ $catalog->name }}</h3> <small class="service-catalog-record-id">ID #{{ $catalog->getKey() }}</small>
                                            <p>{{ $catalog->type_or_material ?: 'Material not set' }}</p>
                                        </div>
                                    </div>
                                    <span class="service-status {{ ! $catalog->is_active ? 'is-archived' : ($catalog->is_available ? 'is-active' : 'is-warning') }}">{{ ! $catalog->is_active ? 'Archived' : ($catalog->is_available ? 'Available' : 'Unavailable') }}</span>
                                </div>
                                <div class="service-package-card__price">
                                    <span>Reference Value</span>
                                    @if((float) $catalog->standard_price > 0)
                                        <strong>{!! $money($catalog->standard_price) !!}</strong>
                                        <small>Configured for package setup</small>
                                    @else
                                        <strong>Price needed</strong>
                                        <small>Add a reference value before using broadly</small>
                                    @endif
                                </div>
                                <div class="service-package-card__lists is-single">
                                    <div>
                                        <h4><i class="bi bi-card-text" aria-hidden="true"></i> Notes</h4>
                                        @if($catalog->description)
                                            <p>{{ $catalog->description }}</p>
                                        @else
                                            <em>No description added</em>
                                        @endif
                                    </div>
                                </div>
                                <div class="service-package-card__foot">
                                    @include('admin.service-management._catalog-actions', ['record' => $catalog, 'catalogRoute' => 'casket-catalogs', 'catalogParameter' => 'casket_catalog', 'catalogLabel' => 'Casket', 'cardAction' => true])
                                </div>
                            </article>
                        @empty
                            <div class="service-hub-empty is-inline" data-service-empty>
                                <i class="bi bi-box2-heart"></i>
                                <strong>No caskets found</strong>
                                <span>Add reusable casket options for packages.</span>
                            </div>
                        @endforelse
                    </div>

                    <div class="table-system-wrap service-package-table" data-catalog-view-panel="table" hidden>
                        <table class="table-base table-system-table w-full">
                            <thead>
                                <tr>
                                    <th>Casket Name</th>
                                    <th>Material / Type</th>
                                    <th class="table-col-number">Reference Value</th>
                                    <th>Status</th>
                                    <th class="table-col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($caskets as $catalog)
                                    <tr data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $catalog]) data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->type_or_material) }}">
                                        <td>@include('admin.service-management.partials.photo-identity')@if($catalog->description)<div class="service-row-note">{{ $catalog->description }}</div>@endif</td>
                                        <td>{{ $catalog->type_or_material ?: '-' }}</td>
                                        <td class="table-col-number font-semibold">
                                            @if((float) $catalog->standard_price > 0)
                                                {!! $money($catalog->standard_price) !!}
                                            @else
                                                <span class="service-status is-warning">Price needed</span>
                                            @endif
                                        </td>
                                        <td><span class="service-status {{ ! $catalog->is_active ? 'is-archived' : ($catalog->is_available ? 'is-active' : 'is-warning') }}">{{ ! $catalog->is_active ? 'Archived' : ($catalog->is_available ? 'Available' : 'Unavailable') }}</span></td>
                                        <td class="table-col-actions">
                                                <div class="row-hover-actions">
                                                    @include('admin.service-management._catalog-actions', ['record' => $catalog, 'catalogRoute' => 'casket-catalogs', 'catalogParameter' => 'casket_catalog', 'catalogLabel' => 'Casket', 'cardAction' => false])
                                                </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-service-empty><td colspan="5" class="table-system-empty">No caskets found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($caskets->hasPages())
                        <div class="service-pagination">{{ $caskets->onEachSide(1)->links('components.pagination.table') }}</div>
                    @endif
                </div>

                <div class="service-hub-panel {{ $activeTab === 'addons' ? 'is-active' : '' }}" data-service-panel="addons" role="tabpanel">
                    <div class="service-package-cards" data-catalog-view-panel="cards">
                        @forelse($addOns as $catalog)
                            <article class="service-package-card" data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $catalog]) data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->category . ' ' . $catalog->unit . ' ' . $catalog->description) }}">
                                @include('admin.service-management.partials.card-media', ['mediaRecord' => $catalog, 'allowPhotoUpload' => $canManage])
                                <div class="service-package-card__head">
                                    <div class="service-package-card__identity">
                                        <span class="service-package-card__icon" aria-hidden="true"><i class="bi bi-plus-circle"></i></span>
                                        <div>
                                            <h3>{{ $catalog->name }}</h3> <small class="service-catalog-record-id">ID #{{ $catalog->getKey() }}</small>
                                            <p>{{ $catalog->category ?: 'General' }}</p>
                                            @include('admin.service-management.partials.chapel-context')
                                        </div>
                                    </div>
                                    <span class="service-status {{ ! $catalog->is_active ? 'is-archived' : ($catalog->is_available ? 'is-active' : 'is-warning') }}">{{ ! $catalog->is_active ? 'Archived' : ($catalog->is_available ? 'Available' : 'Unavailable') }}</span>
                                </div>
                                <div class="service-package-card__price">
                                    <span>Standard Price</span>
                                    <strong>{!! $money($catalog->price) !!}</strong>
                                    <small>Unit: {{ $catalog->unit ?: 'item' }}</small>
                                </div>
                                <div class="service-package-card__lists is-single">
                                    <div>
                                        <h4><i class="bi bi-card-text" aria-hidden="true"></i> Description</h4>
                                        @if($catalog->description)
                                            <p>{{ $catalog->description }}</p>
                                        @else
                                            <em>No description added</em>
                                        @endif
                                    </div>
                                </div>
                                <div class="service-package-card__foot">
                                    @include('admin.service-management._catalog-actions', ['record' => $catalog, 'catalogRoute' => 'add-on-catalogs', 'catalogParameter' => 'add_on_catalog', 'catalogLabel' => 'Add-on', 'cardAction' => true])
                                </div>
                            </article>
                        @empty
                            <div class="service-hub-empty is-inline" data-service-empty>
                                <i class="bi bi-plus-circle"></i>
                                <strong>No add-ons found</strong>
                                <span>Add optional services for package setup.</span>
                            </div>
                        @endforelse
                    </div>

                    <div class="table-system-wrap service-package-table" data-catalog-view-panel="table" hidden>
                        <table class="table-base table-system-table w-full">
                            <thead>
                                <tr>
                                    <th>Add-on Name</th>
                                    <th>Category</th>
                                    <th class="table-col-number">Standard Price</th>
                                    <th>Charged Per</th>
                                    <th>Status</th>
                                    <th class="table-col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($addOns as $catalog)
                                    <tr data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $catalog]) data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->category . ' ' . $catalog->unit . ' ' . $catalog->description) }}">
                                        <td>
                                            @include('admin.service-management.partials.photo-identity')
                                            @if($catalog->description)<div class="service-row-note">{{ $catalog->description }}</div>@endif
                                        </td>
                                        <td>{{ $catalog->category ?: 'General' }}@include('admin.service-management.partials.chapel-context')</td>
                                        <td class="table-col-number font-semibold">{!! $money($catalog->price) !!}</td>
                                        <td>{{ $catalog->unit }}</td>
                                        <td><span class="service-status {{ ! $catalog->is_active ? 'is-archived' : ($catalog->is_available ? 'is-active' : 'is-warning') }}">{{ ! $catalog->is_active ? 'Archived' : ($catalog->is_available ? 'Available' : 'Unavailable') }}</span></td>
                                        <td class="table-col-actions">
                                                <div class="row-hover-actions">
                                                    @include('admin.service-management._catalog-actions', ['record' => $catalog, 'catalogRoute' => 'add-on-catalogs', 'catalogParameter' => 'add_on_catalog', 'catalogLabel' => 'Add-on', 'cardAction' => false])
                                                </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-service-empty><td colspan="6" class="table-system-empty">No add-ons found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($addOns->hasPages())
                        <div class="service-pagination">{{ $addOns->onEachSide(1)->links('components.pagination.table') }}</div>
                    @endif
                </div>

                <div class="service-hub-panel {{ $activeTab === 'freebies' ? 'is-active' : '' }}" data-service-panel="freebies" role="tabpanel">
                    <div class="service-package-cards" data-catalog-view-panel="cards">
                        @forelse($freebies as $catalog)
                            <article class="service-package-card" data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $catalog]) data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->default_unit) }}">
                                @include('admin.service-management.partials.card-media', ['mediaRecord' => $catalog, 'allowPhotoUpload' => $canManage])
                                <div class="service-package-card__head">
                                    <div class="service-package-card__identity">
                                        <span class="service-package-card__icon" aria-hidden="true"><i class="bi bi-gift"></i></span>
                                        <div>
                                            <h3>{{ $catalog->name }}</h3> <small class="service-catalog-record-id">ID #{{ $catalog->getKey() }}</small>
                                            <p>{{ $catalog->default_unit ?: 'item' }}</p>
                                        </div>
                                    </div>
                                    <span class="service-status {{ ! $catalog->is_active ? 'is-archived' : ($catalog->is_available ? 'is-active' : 'is-warning') }}">{{ ! $catalog->is_active ? 'Archived' : ($catalog->is_available ? 'Available' : 'Unavailable') }}</span>
                                </div>
                                <div class="service-package-card__price">
                                    <span>Used In Packages</span>
                                    <strong>{{ (int) ($catalog->package_freebies_count ?? 0) }}</strong>
                                    <small>package{{ (int) ($catalog->package_freebies_count ?? 0) === 1 ? '' : 's' }}</small>
                                </div>
                                <div class="service-package-card__lists is-single">
                                    <div>
                                        <h4><i class="bi bi-info-circle" aria-hidden="true"></i> Default Unit</h4>
                                        <p>{{ $catalog->default_unit ?: 'item' }}</p>
                                    </div>
                                </div>
                                <div class="service-package-card__foot">
                                    @include('admin.service-management._catalog-actions', ['record' => $catalog, 'catalogRoute' => 'freebie-catalogs', 'catalogParameter' => 'freebie_catalog', 'catalogLabel' => 'Freebie', 'cardAction' => true])
                                </div>
                            </article>
                        @empty
                            <div class="service-hub-empty is-inline" data-service-empty>
                                <i class="bi bi-gift"></i>
                                <strong>No freebies found</strong>
                                <span>Add complimentary package items.</span>
                            </div>
                        @endforelse
                    </div>

                    <div class="table-system-wrap service-package-table" data-catalog-view-panel="table" hidden>
                        <table class="table-base table-system-table w-full">
                            <thead>
                                <tr>
                                    <th>Freebie Item Name</th>
                                    <th>Default Unit</th>
                                    <th>Used In Packages</th>
                                    <th>Status</th>
                                    <th class="table-col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($freebies as $catalog)
                                    <tr data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $catalog]) data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->default_unit) }}">
                                        <td>@include('admin.service-management.partials.photo-identity')</td>
                                        <td>{{ $catalog->default_unit ?: 'item' }}</td>
                                        <td>{{ (int) ($catalog->package_freebies_count ?? 0) }} package{{ (int) ($catalog->package_freebies_count ?? 0) === 1 ? '' : 's' }}</td>
                                        <td><span class="service-status {{ ! $catalog->is_active ? 'is-archived' : ($catalog->is_available ? 'is-active' : 'is-warning') }}">{{ ! $catalog->is_active ? 'Archived' : ($catalog->is_available ? 'Available' : 'Unavailable') }}</span></td>
                                        <td class="table-col-actions">
                                                <div class="row-hover-actions">
                                                    @include('admin.service-management._catalog-actions', ['record' => $catalog, 'catalogRoute' => 'freebie-catalogs', 'catalogParameter' => 'freebie_catalog', 'catalogLabel' => 'Freebie', 'cardAction' => false])
                                                </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-service-empty><td colspan="5" class="table-system-empty">No freebies found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($freebies->hasPages())
                        <div class="service-pagination">{{ $freebies->onEachSide(1)->links('components.pagination.table') }}</div>
                    @endif
                </div>

                <div class="service-hub-panel {{ $activeTab === 'hearses' ? 'is-active' : '' }}" data-service-panel="hearses" role="tabpanel">
                    <div class="service-package-cards" data-catalog-view-panel="cards" hidden>
                        @forelse($hearses as $catalog)
                            <article class="service-package-card" data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $catalog]) data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name.' '.$catalog->description) }}">
                                @include('admin.service-management.partials.card-media', ['mediaRecord' => $catalog, 'allowPhotoUpload' => $canManage])
                                <div class="service-package-card__head"><div class="service-package-card__identity"><span class="service-package-card__icon" aria-hidden="true"><i class="bi bi-truck"></i></span><div><h3>{{ $catalog->name }}</h3> <small class="service-catalog-record-id">ID #{{ $catalog->getKey() }}</small><p>Included in the package base price</p></div></div><span class="service-status {{ ! $catalog->is_active ? 'is-archived' : ($catalog->is_available ? 'is-active' : 'is-warning') }}">{{ ! $catalog->is_active ? 'Archived' : ($catalog->is_available ? 'Available' : 'Unavailable') }}</span></div>
                                <p class="service-package-card__description">{{ $catalog->description ?: '—' }}</p>
                                <p class="service-package-summary">Reference Value: {{ $catalog->reference_value !== null ? 'PHP ' . number_format((float) $catalog->reference_value, 2) : 'Not configured' }}</p>
                                <div class="service-package-card__foot">@include('admin.service-management._catalog-actions', ['record' => $catalog, 'catalogRoute' => 'hearse-catalogs', 'catalogParameter' => 'hearse_catalog', 'catalogLabel' => 'Hearse', 'cardAction' => true])</div>
                            </article>
                        @empty
                            <div class="service-hub-empty is-inline" data-service-empty>No hearses found.</div>
                        @endforelse
                    </div>
                    <div class="table-system-wrap service-package-table" data-catalog-view-panel="table">
                        <table class="table-base table-system-table w-full"><thead><tr><th>Hearse Coach Identity</th><th>Hearse Details</th><th class="table-col-number">Reference Value</th><th>Status</th><th>Last Updated</th><th class="table-col-actions">Actions</th></tr></thead><tbody>
                        @forelse($hearses as $catalog)
                            <tr data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $catalog]) data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name.' '.$catalog->description) }}">
                                <td class="font-semibold"><div class="service-catalog-identity">@include('admin.service-management.partials.photo', ['photoRecord' => $catalog, 'photoSize' => 'thumbnail'])<i class="bi bi-truck" aria-hidden="true"></i><span>{{ $catalog->name }} <small class="service-catalog-record-id">ID #{{ $catalog->getKey() }}</small></span></div></td>
                                <td>{{ $catalog->description ?: '—' }}</td><td class="table-col-number">{{ $catalog->reference_value !== null ? 'PHP ' . number_format((float) $catalog->reference_value, 2) : 'Not configured' }}</td>
                                <td><span class="service-status {{ ! $catalog->is_active ? 'is-archived' : ($catalog->is_available ? 'is-active' : 'is-warning') }}">{{ ! $catalog->is_active ? 'Archived' : ($catalog->is_available ? 'Available' : 'Unavailable') }}</span></td>
                                <td>{{ $catalog->updated_at?->timezone(config('app.timezone'))->format('M d, Y') ?: '—' }}</td>
                                <td class="table-col-actions">@include('admin.service-management._catalog-actions', ['record' => $catalog, 'catalogRoute' => 'hearse-catalogs', 'catalogParameter' => 'hearse_catalog', 'catalogLabel' => 'Hearse', 'cardAction' => false])</td>
                            </tr>
                        @empty<tr data-service-empty><td colspan="6" class="table-system-empty">No hearses found.</td></tr>@endforelse
                        </tbody></table>
                    </div>
                    @if($hearses->hasPages())<div class="service-pagination">{{ $hearses->onEachSide(1)->links('components.pagination.table') }}</div>@endif
                </div>

                <div class="service-hub-panel {{ $activeTab === 'promotions' ? 'is-active' : '' }}" data-service-panel="promotions" role="tabpanel">
                    <div class="service-promotion-cards" data-catalog-view-panel="cards" hidden>
                        @forelse($promotions as $promotion)
                            @php($promotionEffectiveStatus = $promotion->statusAt(now('Asia/Manila'), $promotion->package))
                            <article class="service-promotion-card" data-skeleton-item data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $promotion]) data-search="{{ strtolower(($promotion->name ?? '') . ' ' . ($promotion->package?->name ?? '') . ' ' . $promotionEffectiveStatus) }}">
                                <header class="service-promotion-card__head">
                                    <div class="service-promotion-card__identity">
                                        <span class="service-promotion-card__icon" aria-hidden="true"><i class="bi bi-ticket-perforated"></i></span>
                                        <div>
                                        <h3>{{ $promotion->name ?: 'Unnamed promotion' }}</h3> <small class="service-catalog-record-id">ID #{{ $promotion->getKey() }}</small>
                                        @if($promotionEffectiveStatus === 'Incomplete Setup')<p class="service-promotion-card__review">{{ implode(' ', array_values($promotion->reviewFields($promotion->package))) }}</p>@endif
                                        </div>
                                    </div>
                                    <span class="service-status is-{{ \Illuminate\Support\Str::slug($promotionEffectiveStatus) }}">{{ $promotionEffectiveStatus }}</span>
                                </header>
                                <dl class="service-promotion-card__details">
                                    <div><dt>Assigned Package</dt><dd>{{ $promotion->package?->name ?? 'Unassigned' }}</dd></div>
                                    <div><dt>Discount</dt><dd>{{ strtoupper((string) $promotion->discount_type) === 'PERCENT' ? number_format((float) $promotion->discount_value, 2).'%' : ($promotion->discount_value !== null ? '₱'.number_format((float) $promotion->discount_value, 2) : '—') }}</dd></div>
                                    <div><dt>Start Date</dt><dd>{{ $promotion->starts_at?->format('M d, Y') ?? 'No start date' }}</dd></div>
                                    <div><dt>End Date</dt><dd>{{ $promotion->ends_at?->format('M d, Y') ?? 'No end date' }}</dd></div>
                                </dl>
                                <footer class="service-promotion-card__actions">
                                    @include('admin.service-management._promotion-actions', ['promotion' => $promotion, 'promotionEffectiveStatus' => $promotionEffectiveStatus, 'canManage' => $canManage, 'cardAction' => true])
                                </footer>
                            </article>
                        @empty
                            <div class="service-hub-empty is-inline" data-skeleton-empty data-service-empty><i class="bi bi-ticket-perforated" aria-hidden="true"></i><strong>{{ ($stats['promotions']['total'] ?? 0) === 0 ? 'No promotions yet' : 'No matching promotions' }}</strong><span>{{ ($stats['promotions']['total'] ?? 0) === 0 ? 'Add a promotion to configure reusable offers.' : 'Try another search or status filter.' }}</span></div>
                        @endforelse
                    </div>
                    <div class="table-system-wrap service-package-table" data-catalog-view-panel="table">
                        <table class="table-base table-system-table w-full">
                            <thead><tr><th>Promotion Name</th><th>Assigned Package</th><th class="table-col-number">Discount</th><th>Start Date</th><th>End Date</th><th>Effective Status</th><th class="table-col-actions">Actions</th></tr></thead>
                            <tbody>
                            @forelse($promotions as $promotion)
                                @php($promotionEffectiveStatus = $promotion->statusAt(now('Asia/Manila'), $promotion->package))
                                <tr data-service-row @include('admin.service-management.partials.search-metadata', ['searchRecord' => $promotion]) data-search="{{ strtolower(($promotion->name ?? '') . ' ' . ($promotion->package?->name ?? '') . ' ' . $promotionEffectiveStatus) }}">
                                    <td><strong>{{ $promotion->name ?: 'Unnamed promotion' }}</strong> <small class="service-catalog-record-id">ID #{{ $promotion->getKey() }}</small>
                                        @if($promotionEffectiveStatus === 'Incomplete Setup')<small class="block text-muted">{{ implode(' ', array_values($promotion->reviewFields($promotion->package))) }}</small>@endif
                                    </td>
                                    <td>{{ $promotion->package?->name ?? 'Unassigned' }}</td>
                                    <td class="table-col-number">{{ strtoupper((string) $promotion->discount_type) === 'PERCENT' ? number_format((float) $promotion->discount_value, 2).'%' : ($promotion->discount_value !== null ? '₱'.number_format((float) $promotion->discount_value, 2) : '—') }}</td>
                                    <td>{{ $promotion->starts_at?->format('M d, Y') ?? 'No start date' }}</td>
                                    <td>{{ $promotion->ends_at?->format('M d, Y') ?? 'No end date' }}</td>
                                    <td><span class="service-status is-{{ \Illuminate\Support\Str::slug($promotionEffectiveStatus) }}">{{ $promotionEffectiveStatus }}</span></td>
                                    <td class="table-col-actions">
                                        @include('admin.service-management._promotion-actions', ['promotion' => $promotion, 'promotionEffectiveStatus' => $promotionEffectiveStatus, 'canManage' => $canManage])
                                    </td>
                                </tr>
                            @empty
                                <tr data-service-empty><td colspan="7" class="table-system-empty">{{ ($stats['promotions']['total'] ?? 0) === 0 ? 'No promotions have been created yet.' : (($search ?? '') !== '' ? 'No promotions found matching your search.' : 'No promotions match the selected filters. Clear your filters to see all promotions.') }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($promotions->hasPages())<div class="service-pagination">{{ $promotions->onEachSide(1)->links('components.pagination.table') }}</div>@endif
                </div>

                    <div class="service-hub-empty" data-service-filter-empty hidden>
                    <i class="bi bi-search"></i>
                    <strong>No matching records</strong>
                    <span>Try another search or status filter.</span>
                </div>
            </div>
        </section>
    </div>
</div>

<dialog class="promotion-confirm-dialog service-feedback-dialog" data-promotion-confirm-dialog aria-labelledby="promotion-confirm-title" aria-describedby="promotion-confirm-copy">
    <div class="promotion-confirm-dialog__body">
        <span class="promotion-confirm-dialog__icon" aria-hidden="true"><i class="bi bi-exclamation-circle"></i></span>
        <div>
            <h2 id="promotion-confirm-title" data-promotion-confirm-title>Turn Off Promotion?</h2>
            <p id="promotion-confirm-copy" data-promotion-confirm-copy>This promotion will immediately stop applying to new cases. Its configuration and schedule will be retained. Existing case prices will remain unchanged.</p>
        </div>
    </div>
    <footer><button type="button" class="btn btn-secondary" data-promotion-confirm-cancel>Cancel</button><button type="button" class="btn btn-primary-custom" data-promotion-confirm-submit>Turn Off Promotion</button></footer>
</dialog>

<style>
.promotion-confirm-dialog { width:min(400px,calc(100vw - 32px)); padding:16px; border:1px solid var(--border); border-radius:14px; background:var(--card); color:var(--ink); box-shadow:0 20px 60px rgb(15 23 42 / .28); }
.promotion-confirm-dialog::backdrop { background:rgb(15 23 42 / .48); }
.promotion-confirm-dialog__body { display:grid; justify-items:center; gap:7px; text-align:center; }
.promotion-confirm-dialog__icon { display:grid; width:40px; height:40px; flex:none; place-items:center; border-radius:11px; background:var(--surface-muted); color:var(--brand); font-size:17px; }
.promotion-confirm-dialog[data-feedback-kind='warning'] .promotion-confirm-dialog__icon { background:var(--feedback-warning-bg); color:var(--feedback-warning-fg); }
.promotion-confirm-dialog[data-feedback-kind='success'] .promotion-confirm-dialog__icon { background:var(--feedback-success-bg); color:var(--feedback-success-fg); }
.promotion-confirm-dialog h2 { margin:0; font-size:1rem; font-weight:700; }
.promotion-confirm-dialog p { margin:4px 0 0; color:var(--ink-muted); font-size:13px; line-height:1.45; }
.promotion-confirm-dialog footer { display:flex; justify-content:center; gap:8px; margin-top:12px; }
.promotion-confirm-dialog :is(button,a):focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
.promotion-confirm-dialog [data-promotion-confirm-submit].is-warning { background:var(--danger,#b5473c); border-color:var(--danger,#b5473c); color:#fff; }
.promotion-confirm-dialog [data-promotion-confirm-submit]:not(.is-warning) { background:var(--brand); border-color:var(--brand); color:#fff; }
html[data-theme='dark'] .promotion-confirm-dialog { background:var(--card); color:var(--ink); }
@media(max-width:480px) { .promotion-confirm-dialog footer { flex-direction:column-reverse; } .promotion-confirm-dialog footer button { width:100%; } }
</style>

<script>
(() => {
    if (window.promotionConfirmDialogReady) return;
    window.promotionConfirmDialogReady = true;

    const dialog = document.querySelector('[data-promotion-confirm-dialog]');
    if (!dialog) return;

    const title = dialog.querySelector('[data-promotion-confirm-title]');
    const copy = dialog.querySelector('[data-promotion-confirm-copy]');
    const confirm = dialog.querySelector('[data-promotion-confirm-submit]');
    const cancel = dialog.querySelector('[data-promotion-confirm-cancel]');
    let pendingForm = null;
    let pendingSubmitter = null;
    let trigger = null;
    const confirmedForms = new WeakSet();

    document.addEventListener('submit', event => {
        const form = event.target.closest?.('form[data-promotion-confirm]');
        if (!form) return;
        if (confirmedForms.has(form)) {
            confirmedForms.delete(form);
            return;
        }

        event.preventDefault();
        pendingForm = form;
        pendingSubmitter = event.submitter;
        trigger = pendingSubmitter || form.querySelector('button[type="submit"]');
        const turningOn = form.dataset.promotionConfirm === 'on';
        dialog.dataset.feedbackKind = turningOn ? 'success' : 'warning';
        dialog.dataset.confirmTone = turningOn ? 'success' : 'warning';
        dialog.querySelector('.promotion-confirm-dialog__icon i').className = `bi bi-${turningOn ? 'check-circle' : 'exclamation-triangle'}`;
        const promotionName = form.dataset.promotionName || 'this promotion';
        title.textContent = turningOn ? 'Turn On Promotion?' : 'Turn Off Promotion?';
        copy.textContent = turningOn
            ? `Turn on “${promotionName}” using its saved schedule? It will be Scheduled or Active according to its dates. Existing case prices will remain unchanged.`
            : `Turn off “${promotionName}”? It will immediately stop applying to new cases. Its configuration and schedule will be retained. Existing case prices will remain unchanged.`;
        confirm.textContent = turningOn ? 'Turn On Promotion' : 'Turn Off Promotion';
        confirm.classList.toggle('is-warning', !turningOn);
        cancel.focus();
        dialog.showModal();
    });

    const clearPending = () => {
        const priorTrigger = trigger;
        pendingForm = pendingSubmitter = trigger = null;
        if (priorTrigger?.isConnected) priorTrigger.focus({ preventScroll:true });
    };
    cancel.addEventListener('click', () => dialog.close('cancel'));
    dialog.addEventListener('cancel', event => { event.preventDefault(); dialog.close('cancel'); });
    dialog.addEventListener('close', () => { if (dialog.returnValue !== 'confirm') clearPending(); });
    confirm.addEventListener('click', () => {
        const form = pendingForm;
        const submitter = pendingSubmitter;
        if (!form?.isConnected) { dialog.close('cancel'); return; }
        confirm.disabled = true;
        confirm.textContent = form.dataset.promotionConfirm === 'on' ? 'Turning on...' : 'Turning off...';
        form.setAttribute('aria-busy', 'true');
        confirmedForms.add(form);
        dialog.close('confirm');
        form.requestSubmit(submitter || undefined);
    });
})();
</script>

<dialog class="service-modal" data-service-modal hidden aria-labelledby="service-modal-title">
    <section class="service-modal__panel">
        <header class="service-modal__head">
            <span class="service-modal__icon" aria-hidden="true"><i class="bi bi-eye" data-service-modal-icon></i></span>
            <div>
                <h2 id="service-modal-title" data-service-modal-title>Add Catalog Item</h2>
                <p data-service-modal-copy>Manage catalog details without leaving Service Management.</p>
            </div>
        </header>
        <button type="button" class="service-modal__floating-close" data-service-modal-close aria-label="Close modal">
            <i class="bi bi-x" aria-hidden="true"></i>
        </button>
        <iframe class="service-modal__frame" data-service-modal-frame title="Service catalog form"></iframe>
        <div class="service-modal__loading" data-service-modal-loading hidden role="status" aria-live="polite">
            <span class="service-modal__spinner" aria-hidden="true"></span>
            <span>Loading catalog details…</span>
        </div>
    </section>
</dialog>

<style>
.service-modal[hidden] {
    display:none !important;
}
html.service-modal-open,
body.service-modal-open {
    overflow:hidden;
}
body.service-modal-open .page-content {
    overflow:hidden !important;
}
.service-modal {
    position:fixed;
    inset:0;
    z-index:10000;
    display:flex;
    align-items:center;
    justify-content:center;
    box-sizing:border-box;
    width:100vw;
    height:100dvh;
    max-width:none;
    max-height:none;
    margin:0;
    padding:28px;
    border:0;
    overflow:hidden;
    overscroll-behavior:contain;
    touch-action:none;
    opacity:1;
    visibility:visible;
}
.service-modal__backdrop {
    position:absolute;
    inset:0;
    background:var(--modal-scrim);
    backdrop-filter:blur(3px);
}
.service-modal__panel {
    position:relative;
    z-index:1;
    display:flex;
    flex-direction:column;
    width:min(1120px, calc(100vw - 40px));
    height:min(820px, calc(100vh - 56px));
    overflow:hidden;
    border:1px solid #B9CBB1;
    border-radius:14px;
    background:#F7F8F1;
    box-shadow:0 24px 70px rgba(31,45,32,.3);
    touch-action:auto;
    opacity:1;
    visibility:visible;
}
.service-modal.is-detail {
    align-items:stretch;
    justify-content:flex-end;
    padding:0;
}
.service-modal.is-detail .service-modal__panel {
    width:min(620px, 100vw);
    height:100dvh;
    max-height:none;
    border-width:0 0 0 1px;
    border-radius:20px 0 0 20px;
    animation:serviceDetailDrawerIn .2s ease-out;
}
.service-modal.is-detail .service-modal__head {
    display:none;
}
@keyframes serviceDetailDrawerIn {
    from { transform:translateX(28px); opacity:.76; }
    to { transform:translateX(0); opacity:1; }
}
@media (prefers-reduced-motion: reduce) {
    .service-modal.is-detail .service-modal__panel { animation:none; }
}
.service-modal__head {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    min-height:78px;
    padding:18px 22px;
    border-bottom:1px solid #C9D7C0;
    background:#DCE6D6;
}
.service-modal__head h2 {
    margin:0;
    color:#1F2D20;
    font-size:1.22rem;
    font-weight:950;
}
.service-modal__head p {
    margin:4px 0 0;
    color:#5F685F;
    font-size:.84rem;
    font-weight:700;
}
.service-modal__close {
    display:inline-grid;
    place-items:center;
    width:40px;
    height:40px;
    border:1px solid #B9CBB1;
    border-radius:8px;
    background:#F7F8F1;
    color:#1F2D20;
}
.service-modal__floating-close {
    position:absolute;
    top:16px;
    right:16px;
    z-index:4;
    display:none;
    place-items:center;
    width:40px;
    height:40px;
    border:1px solid #B9CBB1;
    border-radius:999px;
    background:#F7F8F1;
    color:#1F2D20;
    box-shadow:0 8px 22px rgba(31,45,32,.16);
    transition:background-color .16s ease, transform .16s ease;
}
.service-modal__floating-close:hover,
.service-modal__floating-close:focus-visible {
    background:#EEF4E8;
    transform:translateY(-1px);
    outline:none;
}
.service-modal__frame {
    flex:1;
    width:100%;
    border:0;
    background:#DCE6D6;
}
.service-modal__loading {
    position:absolute;
    inset:78px 0 0;
    z-index:3;
    display:grid;
    place-content:center;
    justify-items:center;
    gap:12px;
    background:#E8F1EC;
    color:#1B4332;
    font-size:.84rem;
    font-weight:700;
}
.service-modal.is-detail .service-modal__loading { inset:0; }
.service-modal__loading[hidden] { display:none; }
.service-modal__spinner {
    width:30px;
    height:30px;
    border:3px solid #AEC7B7;
    border-top-color:#2D6A4F;
    border-radius:999px;
    animation:serviceModalSpin .7s linear infinite;
}
@keyframes serviceModalSpin { to { transform:rotate(360deg); } }
.service-save-toast {
    position:fixed;
    right:24px;
    top:24px;
    z-index:10020;
    max-width:min(420px, calc(100vw - 32px));
    padding:12px 16px;
    border:1px solid #7FA98D;
    border-radius:12px;
    background:#F3FAF5;
    color:#173D2B;
    box-shadow:0 14px 34px rgba(8,29,19,.2);
    font-size:.875rem;
    display:flex;
    align-items:flex-start;
    gap:8px;
    font-weight:600;
}
.service-save-toast i { flex:none; color:#167044; font-size:16px; line-height:1.35; }
html[data-theme='dark'] .service-save-toast i { color:#52E6B0; }
html[data-theme='dark'] .service-modal__panel,
html[data-theme='dark'] .service-modal__head,
html[data-theme='dark'] .service-modal__frame { background:#0F241C; border-color:#2D6A4F; }
html[data-theme='dark'] .service-modal__head h2 { color:#F8FAFC; }
html[data-theme='dark'] .service-modal__head p { color:#B5D6C4; }
html[data-theme='dark'] .service-modal__floating-close { background:#132D22; border-color:#41634F; color:#F8FAFC; }
html[data-theme='dark'] .service-save-toast { background:#132D22; border-color:#41634F; color:#EAF7EF; }
html[data-theme='dark'] .service-modal__loading { background:#0F241C; color:#EAF7EF; }
html[data-theme='dark'] .service-modal__spinner { border-color:#41634F; border-top-color:#86D2A8; }
@media (min-width: 1px) {
    .service-modal__floating-close {
        display:grid;
    }
}
@media (max-width: 640px) {
    .service-modal {
        padding:10px;
        align-items:stretch;
    }
    .service-modal__panel {
        width:100%;
        height:calc(100vh - 20px);
        border-radius:12px;
    }
    .service-modal.is-detail .service-modal__panel {
        width:100%;
        height:100dvh;
        border-left:0;
        border-radius:0;
    }
    .service-modal__head {
        padding:15px 58px 15px 16px;
    }
}
</style>

<script>
if (!window.packageFreebiePopoverEventsBound) {
    const syncPackageFreebiePopover = (popover) => {
        const trigger = [...document.querySelectorAll('[data-package-freebie-trigger]')]
            .find((button) => button.getAttribute('aria-controls') === popover.id);
        if (!trigger) return;

        const isOpen = popover.matches(':popover-open');
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        if (!isOpen) return;

        requestAnimationFrame(() => {
            if (!popover.matches(':popover-open')) return;
            const triggerRect = trigger.getBoundingClientRect();
            const popoverRect = popover.getBoundingClientRect();
            const margin = 12;
            const left = Math.max(margin, Math.min(triggerRect.left, window.innerWidth - popoverRect.width - margin));
            let top = triggerRect.bottom + 8;
            if (top + popoverRect.height > window.innerHeight - margin) {
                top = Math.max(margin, triggerRect.top - popoverRect.height - 8);
            }
            popover.style.left = `${left}px`;
            popover.style.top = `${top}px`;
        });
    };

    document.addEventListener('toggle', (event) => {
        const popover = event.target;
        if (popover instanceof HTMLElement && popover.matches('.package-freebie-more-popover[popover]')) {
            syncPackageFreebiePopover(popover);
        }
    }, true);
    window.addEventListener('resize', () => {
        document.querySelectorAll('.package-freebie-more-popover:popover-open').forEach((popover) => popover.hidePopover());
    });
    window.packageFreebiePopoverEventsBound = true;
}

window.initServicesHub = () => {
    const hub = document.querySelector('[data-service-hub]');
    if (!hub) return;
    const hearseDefault = hub.querySelector('.custom-package-defaults [name="hearse_kilometers"]');
    hearseDefault?.addEventListener('input', () => {
        hearseDefault.form.elements.namedItem('hearse_extra_rate').required = hearseDefault.value !== '';
    });

    const filterForm = hub.querySelector('[data-service-filter-form]');
    const search = hub.querySelector('[data-service-search]');
    const status = hub.querySelector('[data-service-status]');
    const sort = hub.querySelector('[data-service-sort]');
    const clearSearch = hub.querySelector('[data-service-clear-search]');
    const reset = hub.querySelector('[data-service-reset]');
    const empty = hub.querySelector('[data-service-filter-empty]');
    const viewButtons = [...hub.querySelectorAll('[data-catalog-view]')];
    const viewPanels = [...hub.querySelectorAll('[data-catalog-view-panel]')];
    const modal = document.querySelector('[data-service-modal]');
    const modalFrame = modal?.querySelector('[data-service-modal-frame]');
    const modalTitle = modal?.querySelector('[data-service-modal-title]');
    const modalCopy = modal?.querySelector('[data-service-modal-copy]');
    const modalLoading = modal?.querySelector('[data-service-modal-loading]');
    const pageContent = document.querySelector('.page-content');
    const activeCatalog = hub.querySelector('.service-hub-panel.is-active')?.dataset.servicePanel;
    const tabStrip = hub.querySelector('.service-hub-tabs');
    const selectedTab = tabStrip?.querySelector('.is-active');
    if (tabStrip && selectedTab && tabStrip.scrollWidth > tabStrip.clientWidth) {
        tabStrip.scrollLeft += selectedTab.getBoundingClientRect().left - tabStrip.getBoundingClientRect().left
            - (tabStrip.clientWidth - selectedTab.offsetWidth) / 2;
    }
    let catalogView = localStorage.getItem(`service-catalog-view-${activeCatalog}`) || 'cards';
    let modalScrollY = 0;
    let modalTrigger;
    let modalPrevious = null;
    let modalResizeObserver = null;
    const disconnectModalResize = () => {
        modalResizeObserver?.disconnect();
        modalResizeObserver = null;
    };
    const sizeModalFrame = () => {
        if (!modal?.open || !modalFrame?.contentDocument) return;
        if (modalFrame.contentDocument.querySelector('.svc-status-dialog[open], .service-discard-dialog[open]')) return;
        const content = modalFrame.contentDocument.querySelector('.page-content');
        const panel = modal.querySelector('.service-modal__panel');
        if (!content || !panel) return;
        const header = modal.classList.contains('is-detail') ? 0 : modal.querySelector('.service-modal__head')?.getBoundingClientRect().height || 0;
        panel.style.setProperty('--service-header-height', `${Math.ceil(header)}px`);
        const contentHeight = Math.ceil(content.getBoundingClientRect().height);
        if (contentHeight > 0) panel.style.setProperty('--service-frame-height', `${contentHeight}px`);
    };
    const observeModalSize = () => {
        disconnectModalResize();
        const content = modalFrame?.contentDocument?.querySelector('.page-content');
        if (!modal?.open || !content) return;
        sizeModalFrame();
        modalResizeObserver = new ResizeObserver(sizeModalFrame);
        modalResizeObserver.observe(content);
        const header = modal.querySelector('.service-modal__head');
        if (header) modalResizeObserver.observe(header);
        modalFrame.contentDocument.fonts?.ready.then(() => { if (modal.open) sizeModalFrame(); });
    };
    if (modal && modal.parentElement !== document.body) document.body.appendChild(modal);
    let requestController, requestVersion = 0;
    const loadSection = async url => {
        hub.dispatchEvent(new Event('catalog-search:cancel'));
        requestController?.abort();
        const request = new AbortController();
        requestController = request;
        const version = ++requestVersion;
        hub.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, { signal:request.signal, headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin' });
            if (!response.ok) throw new Error('Unable to load services.');
            const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (request.signal.aborted || version !== requestVersion || !hub.isConnected) return;
            const nextHub = nextDocument.querySelector('[data-service-hub]');
            if (!nextHub) throw new Error('Services content is missing.');
            const searchFocus = document.activeElement === search
                ? { start: search.selectionStart, end: search.selectionEnd }
                : null;
            const headerAction = document.querySelector('[data-service-modal-url]');
            const nextHeaderAction = nextDocument.querySelector('[data-service-modal-url]');
            if (headerAction && !hub.contains(headerAction) && nextHeaderAction) headerAction.replaceWith(nextHeaderAction);
            hub.replaceWith(nextHub);
            window.history.replaceState({}, '', url);
            window.initServicesHub();
            window.installCatalogFilterSort?.();
            document.dispatchEvent(new Event('panel-ui:reset'));
            if (searchFocus) {
                const nextSearch = nextHub.querySelector('[data-service-search]');
                nextSearch?.focus({ preventScroll: true });
                nextSearch?.setSelectionRange(searchFocus.start, searchFocus.end);
            }
        } catch (error) {
            if (error.name !== 'AbortError' && version === requestVersion && hub.isConnected) {
                let message = hub.querySelector('[data-service-load-error]');
                if (!message) { message = document.createElement('p'); message.dataset.serviceLoadError = ''; message.setAttribute('role','alert'); hub.prepend(message); }
                message.textContent = hub.dataset.activeTab === 'promotions'
                    ? 'Unable to load promotions. Please try again.'
                    : 'Unable to load services. Please try again.';
            }
        } finally { if (version === requestVersion) hub.removeAttribute('aria-busy'); }
    };
    window.refreshServiceCatalogHub = () => loadSection(window.location.href);
    hub.addEventListener('service-catalog:filter-navigate', event => loadSection(event.detail.url));
    hub.querySelectorAll('[data-service-tab]').forEach(tab => tab.addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        const url = new URL(tab.href);
        const current = new URLSearchParams(new FormData(filterForm));
        const nextTab = tab.dataset.serviceTab;
        for (const key of ['q', 'sort']) {
            if (current.has(key)) url.searchParams.set(key, current.get(key));
        }
        if (nextTab === 'packages') {
            url.searchParams.delete('status');
            const values = current.getAll('package_status').filter(value => value && value !== 'all');
            url.searchParams.delete('package_status');
            values.forEach(value => url.searchParams.append('package_status', value));
            url.searchParams.delete('promotion_status');
        } else if (nextTab === 'promotions') {
            for (const key of ['status', 'package_status', 'catalog_items', 'catalog_items[]']) url.searchParams.delete(key);
            const promotionStatus = current.get('promotion_status');
            if (promotionStatus && promotionStatus !== 'all') url.searchParams.set('promotion_status', promotionStatus);
        } else {
            for (const key of ['package_status', 'promotion_status', 'catalog_items', 'catalog_items[]']) url.searchParams.delete(key);
            const currentStatus = current.get('status');
            const catalogStatuses = ['all', 'available', 'unavailable', 'archived'];
            url.searchParams.set('status', catalogStatuses.includes(currentStatus) ? currentStatus : 'all');
        }
        loadSection(url.toString());
    }));
    hub.addEventListener('click', event => {
        const link = event.target.closest('.service-pagination a[href]');
        if (!link) return;
        event.preventDefault(); loadSection(link.href);
    });
    filterForm?.addEventListener('submit', event => {
        event.preventDefault();
        if (search) search.value = search.value.trim();
        const url = new URL(filterForm.action); url.search = new URLSearchParams(new FormData(filterForm)).toString();
        if (!search?.value) url.searchParams.delete('q');
        loadSection(url.toString());
    });
    const setCatalogView = (view) => {
        catalogView = view === 'table' ? 'table' : 'cards';
        localStorage.setItem(`service-catalog-view-${activeCatalog}`, catalogView);

        viewButtons.forEach((button) => {
            const selected = button.dataset.catalogView === catalogView;
            button.classList.toggle('is-active', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });

        viewPanels.forEach((panel) => {
            panel.hidden = panel.dataset.catalogViewPanel !== catalogView;
        });

        applyFilters();
    };

    const applyFilters = () => {
        const panel = hub.querySelector('.service-hub-panel.is-active');
        if (!panel) return;

        const query = (search?.value || '').trim().toLowerCase();
        const currentStatus = status?.value || '';
        let shown = 0;

        panel.querySelectorAll('[data-service-row]').forEach((row) => {
            const matchesText = true; // Search is applied by the server before pagination.
            const matchesStatus = true; // Status and availability are applied by the server.
            const visible = matchesText && matchesStatus;
            row.hidden = !visible;
            if (visible && !row.closest('[hidden]')) shown += 1;
        });

        if (clearSearch) clearSearch.hidden = (search?.value ?? '') === '';
        if (empty) empty.hidden = shown > 0 || panel.querySelector('[data-service-empty]');
    };

    viewButtons.forEach((button) => button.addEventListener('click', () => setCatalogView(button.dataset.catalogView)));
    // Catalog search owns suggestions and explicit Search/Enter/X actions.
    hub.querySelectorAll('[data-service-status-option]').forEach(button => button.addEventListener('click', () => {
        status.value = button.dataset.serviceStatusOption;
        filterForm?.requestSubmit();
    }));
    status?.addEventListener('change', () => filterForm?.requestSubmit());
    hub.querySelector('[data-promotion-status]')?.addEventListener('change', () => filterForm?.requestSubmit());
    sort?.addEventListener('change', () => filterForm?.requestSubmit());
    reset?.addEventListener('click', () => {
        const url = new URL(filterForm.action); url.searchParams.set('tab', hub.dataset.activeTab); if (search?.value) url.searchParams.set('q', search.value); if (sort?.value) url.searchParams.set('sort', sort.value); loadSection(url.toString());
    });

    const embeddedServiceForm = () => {
        try {
            return modalFrame?.contentDocument?.querySelector('form.cc-form, form.aoc-form, form.fbc-form, form#packageForm, form#promotionForm, form[data-quick-catalog-photo]') || null;
        } catch (_) {
            return null;
        }
    };
    const requestEmbeddedFormClose = async () => {
        const frameWindow = modalFrame?.contentWindow;
        if (typeof frameWindow?.requestServiceFormClose === 'function') return frameWindow.requestServiceFormClose();
        const form = embeddedServiceForm();
        if (form && typeof frameWindow?.canDiscardForm === 'function') return frameWindow.canDiscardForm(form);
        return true;
    };

    let pendingPhotoDrop = null;
    const closeModal = async (force = false) => {
        if (!modal) return;
        if (force !== true && (modal.classList.contains('is-form') || embeddedServiceForm())
            && !await requestEmbeddedFormClose()) return;
        pendingPhotoDrop = null;
        disconnectModalResize();
        if (modal.classList.contains('is-form') && modalFrame) delete modalFrame.dataset.loadedUrl;
        modalPrevious = null;
        if (window.recordDialogMotion) {
            if (!await window.recordDialogMotion.close(modal)) return;
        } else {
            modal.close();
        }
        modal.hidden = true;
        if (modal.classList.contains('is-form') && modalFrame) modalFrame.src = 'about:blank';
        document.documentElement.classList.remove('service-modal-open');
        document.body.classList.remove('service-modal-open');
        if (pageContent) pageContent.scrollTop = modalScrollY;
        modalTrigger?.focus();
    };

    const openModal = (action, event, droppedFiles = []) => {
        if (!modal || !modalFrame || !action) return;
        event?.preventDefault();
        disconnectModalResize();
        const title = action.dataset.serviceModalTitle || 'Catalog Item';
        const isPhotoForm = action.hasAttribute('data-service-photo-upload');
        const isForm = isPhotoForm || /^(add|edit)\b/i.test(title);
        if (!isPhotoForm && isForm && /package/i.test(title)) {
            const pageUrl = new URL(action.dataset.serviceModalUrl || action.href, window.location.origin);
            pageUrl.searchParams.delete('modal');
            pageUrl.searchParams.delete('service_form');
            window.location.assign(pageUrl.toString());
            return;
        }
        const formWidth = /package/i.test(title) ? (isForm ? 760 : 672) : /promotion/i.test(title) ? (isForm ? 672 : 640) : /casket/i.test(title) ? (isForm ? 672 : 576) : /hearse/i.test(title) ? (isForm ? 512 : 576) : /freebie/i.test(title) ? (isForm ? 448 : 576) : 576;
        modal.style.setProperty('--service-modal-width', `${isPhotoForm ? 480 : formWidth}px`);
        modal.classList.toggle('is-form', isForm);
        modal.classList.toggle('is-package-form', isForm && /package/i.test(title));
        modal.classList.toggle('is-detail', !isForm);
        if (action.ownerDocument !== document && modal.open && !modalPrevious) {
            modalPrevious = { src: modalFrame.src, title: modalTitle?.textContent, copy: modalCopy?.textContent };
        } else if (action.ownerDocument === document) {
            modalPrevious = null;
        }
        if (modalTitle) modalTitle.textContent = title;
        if (modalCopy) {
            const catalogCopy = /casket/i.test(title) ? 'Manage casket catalog entry and baseline replacement reference value.' : /hearse/i.test(title) ? 'Register ceremonial funeral coach identity for package and case assignments.' : /freebie/i.test(title) ? 'Manage complimentary funeral service inclusions.' : /add-on/i.test(title) ? 'Configure optional separately priced item for case intake customization.' : null;
            modalCopy.textContent = isPhotoForm ? 'Attach an image or upload one from your phone.' : catalogCopy || (title.toLowerCase().startsWith('add')
                ? 'Enter the catalog details and save when ready.'
                : title.toLowerCase().startsWith('edit')
                    ? 'Update the catalog details and save your changes.'
                    : 'Review the catalog details.');
        }
        const modalIcon = modal.querySelector('[data-service-modal-icon]');
        if (modalIcon) modalIcon.className = `bi bi-${isPhotoForm ? 'image' : /promotion/i.test(title) ? 'ticket-perforated' : /casket/i.test(title) ? 'box2-heart' : /hearse/i.test(title) ? 'truck' : /freebie/i.test(title) ? 'gift' : /add-on/i.test(title) ? 'plus-circle' : 'box-seam'}`;
        const modalUrl = new URL(action.dataset.serviceModalUrl || action.href, window.location.origin);
        modalUrl.searchParams.set('modal', '1');
        if (isForm) modalUrl.searchParams.set('service_form', '1');
        const nextModalUrl = modalUrl.toString();
        pendingPhotoDrop = droppedFiles.length ? { url:nextModalUrl, files:droppedFiles } : null;
        // Detail views must read current catalog availability on every open.
        const reuseLoadedFrame = isForm && modalFrame.dataset.loadedUrl === nextModalUrl;
        if (!reuseLoadedFrame) {
            modal.querySelector('.service-modal__panel')?.style.setProperty('--service-frame-height', '240px');
            if (modalLoading) modalLoading.hidden = false;
            modalFrame.setAttribute('aria-busy', 'true');
            modalFrame.src = nextModalUrl;
            modalFrame.dataset.loadedUrl = nextModalUrl;
        }
        modalScrollY = pageContent?.scrollTop || window.scrollY || document.documentElement.scrollTop || 0;
        modal.hidden = false;
        if (action.ownerDocument === document) {
            modalTrigger = isPhotoForm ? action.closest('[data-row-menu]')?.querySelector('[data-row-menu-trigger]') || action : action;
        }
        if (window.recordDialogMotion) window.recordDialogMotion.open(modal);
        else if (!modal.open) modal.showModal();
        if (reuseLoadedFrame) {
            if (modalLoading) modalLoading.hidden = true;
            requestAnimationFrame(observeModalSize);
        }
        modal.querySelector('[data-service-modal-close]')?.focus();
        document.documentElement.classList.add('service-modal-open');
        document.body.classList.add('service-modal-open');
    };

    let photoDropTarget = null;
    const clearPhotoDropTarget = () => {
        photoDropTarget?.classList.remove('is-photo-drop-target');
        photoDropTarget = null;
    };
    hub.addEventListener('dragover', event => {
        if (!event.dataTransfer.types.includes('Files')) return;
        const row = event.target.closest('[data-service-row]');
        if (!row?.querySelector('[data-service-photo-upload]')) {
            clearPhotoDropTarget();
            return;
        }
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        if (photoDropTarget !== row) {
            clearPhotoDropTarget();
            photoDropTarget = row;
            row.classList.add('is-photo-drop-target');
        }
    });
    hub.addEventListener('dragleave', event => {
        if (photoDropTarget && !photoDropTarget.contains(event.relatedTarget)) clearPhotoDropTarget();
    });
    hub.addEventListener('drop', event => {
        clearPhotoDropTarget();
        const action = event.target.closest('[data-service-row]')?.querySelector('[data-service-photo-upload]');
        const files = Array.from(event.dataTransfer.files);
        if (action && files.length) openModal(action, event, files);
    });

    window.openServiceCatalogModal = openModal;
    window.closeServiceCatalogModal = closeModal;
    window.cancelServiceCatalogModal = async () => {
        // Add forms opened directly from the catalog hub have no previous detail
        // view. They still need the same unsaved-changes confirmation as edits.
        if ((modal?.classList.contains('is-form') || embeddedServiceForm()) && !await requestEmbeddedFormClose()) return;
        if (!modalPrevious) { closeModal(true); return; }
        disconnectModalResize();
        modal.querySelector('.service-modal__panel')?.style.setProperty('--service-frame-height', '240px');
        if (modalTitle) modalTitle.textContent = modalPrevious.title || 'Catalog Item';
        if (modalCopy) modalCopy.textContent = modalPrevious.copy || 'Review the catalog details.';
        const modalIcon = modal?.querySelector('[data-service-modal-icon]');
        if (modalIcon) modalIcon.className = 'bi bi-eye';
        modal?.classList.remove('is-package-form');
        modal?.classList.remove('is-form');
        modal?.classList.add('is-detail');
        modalFrame.src = modalPrevious.src;
        modalFrame.dataset.loadedUrl = modalPrevious.src;
        if (modalLoading) modalLoading.hidden = false;
        modalFrame.setAttribute('aria-busy', 'true');
        modalPrevious = null;
        modal.classList.remove('is-discard-confirming');
        modal?.querySelector('[data-service-modal-close]')?.focus();
    };
    if (modal) modal.oncancel = event => { event.preventDefault(); closeModal(); };
    if (modal) modal.onclick = event => { if (event.target === modal) closeModal(); };

    if (!window.servicesModalDelegated) document.addEventListener('click', (event) => {
        const trigger = event.target.closest?.('[data-service-modal-url]');
        if (!trigger) return;
        window.openServiceCatalogModal(trigger, event);
    });
    window.servicesModalDelegated = true;

    window.handleServiceCatalogSaved = async event => {
        if (event.origin !== window.location.origin || event.data?.type !== 'service-catalog:saved') return;
        if (modalFrame) {
            modalFrame.dataset.loadedUrl = '';
            modalFrame.src = 'about:blank';
        }
        await closeModal(true);
        window.refreshServiceCatalogHub().then(() => {
            const liveRegion = document.querySelector('[data-service-save-status]') || document.createElement('div');
            liveRegion.dataset.serviceSaveStatus = '';
            liveRegion.className = 'service-save-toast';
            liveRegion.setAttribute('role', 'status');
            liveRegion.setAttribute('aria-live', 'polite');
            const icon = document.createElement('i');
            icon.className = 'bi bi-check-circle';
            icon.setAttribute('aria-hidden', 'true');
            const copy = document.createElement('span');
            copy.textContent = event.data.message || 'Catalog changes saved successfully.';
            liveRegion.replaceChildren(icon, copy);
            document.body.appendChild(liveRegion);
            window.setTimeout(() => liveRegion.remove(), 3200);
        });
    };
    if (!window.servicesModalMessageBound) window.addEventListener('message', event => window.handleServiceCatalogSaved?.(event));
    window.servicesModalMessageBound = true;

    if (modalFrame) modalFrame.onload = () => {
        if (modalLoading) modalLoading.hidden = true;
        modalFrame.removeAttribute('aria-busy');
        try {
            disconnectModalResize();
            const frameUrl = new URL(modalFrame.contentWindow.location.href);
            if (frameUrl.origin === window.location.origin && frameUrl.pathname === window.location.pathname) {
                const message = modalFrame.contentDocument?.querySelector('.flash-success')?.textContent.trim() || 'Catalog changes saved successfully.';
                window.handleServiceCatalogSaved({ origin: window.location.origin, data: { type: 'service-catalog:saved', message } });
                return;
            }
            if (modal?.open && modal.classList.contains('is-form')) {
                const frameDocument = modalFrame.contentDocument;
                const photoDropZone = frameDocument?.querySelector('[data-proof-drop]');
                if (photoDropZone && pendingPhotoDrop?.url === frameUrl.href) {
                    const transfer = new DataTransfer();
                    pendingPhotoDrop.files.forEach(file => transfer.items.add(file));
                    pendingPhotoDrop = null;
                    photoDropZone.dispatchEvent(new DragEvent('drop', { dataTransfer:transfer, cancelable:true }));
                }
                const errorField = frameDocument?.querySelector('.form-error:not([hidden])')?.parentElement?.querySelector('input:not([type="hidden"]), select, textarea');
                const firstField = errorField || frameDocument?.querySelector('[data-quick-catalog-photo] [data-proof-choose]') || frameDocument?.querySelector('[data-wizard-step="1"] input:not([type="hidden"]):not(:disabled), .cc-body input:not([type="hidden"]):not(:disabled), .aoc-body input:not([type="hidden"]):not(:disabled), .fbc-body input:not([type="hidden"]):not(:disabled)');
                firstField?.focus({ preventScroll: true });
            }
            if (modal?.open) observeModalSize();
        } catch (_) {
            // Ignore inaccessible or initial iframe states.
        }
    };

    modal?.querySelectorAll('[data-service-modal-close]').forEach((button) => {
        button.onclick = closeModal;
    });
    modal?.addEventListener('wheel', (event) => {
        if (event.target === modal || event.target?.dataset?.serviceModalClose !== undefined) {
            event.preventDefault();
        }
    }, { passive:false });
    modal?.addEventListener('touchmove', (event) => {
        if (event.target === modal || event.target?.dataset?.serviceModalClose !== undefined) {
            event.preventDefault();
        }
    }, { passive:false });

    setCatalogView(catalogView);
};
window.initServicesHub();
</script>
@include('admin.service-management.partials.reference-ui')
@endsection
