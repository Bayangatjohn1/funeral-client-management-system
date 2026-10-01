@extends('layouts.panel')

@section('page_title', $canManage ? 'Service Management' : 'Services')
@section('page_desc', $canManage ? 'Manage packages, caskets, add-ons, and freebies in one workspace.' : 'View active and archived service catalog records in read-only mode.')
@section('hide_layout_topbar', '1')

@php
    $serviceHeaderTab = in_array($activeTab ?? 'packages', ['packages', 'caskets', 'addons', 'freebies'], true)
        ? $activeTab
        : 'packages';
    $serviceHeaderActions = [
        'packages' => ['label' => 'Add Package', 'url' => route('admin.packages.create', ['return_to' => route('admin.service-management.index', ['tab' => 'packages'])])],
        'caskets' => ['label' => 'Add Casket', 'url' => route('admin.casket-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'caskets'])])],
        'addons' => ['label' => 'Add Add-on', 'url' => route('admin.add-on-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'addons'])])],
        'freebies' => ['label' => 'Add Freebie', 'url' => route('admin.freebie-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'freebies'])])],
    ];
    $serviceHeaderAction = $serviceHeaderActions[$serviceHeaderTab];
@endphp

@section('topbar_actions')
    @if($canManage)
        <button type="button" class="btn btn-primary-custom btn-sm" data-service-modal-url="{{ $serviceHeaderAction['url'] }}" data-service-modal-title="{{ $serviceHeaderAction['label'] }}">
            <i class="bi bi-plus-circle" aria-hidden="true"></i>
            <span>{{ $serviceHeaderAction['label'] }}</span>
        </button>
    @else
        <span class="service-hub-scope"><i class="bi bi-lock-fill" aria-hidden="true"></i> Read-only branch access</span>
    @endif
@endsection

@section('content')
@php
    $activeTab = in_array($activeTab ?? 'packages', ['packages', 'caskets', 'addons', 'freebies'], true) ? $activeTab : 'packages';
    $money = fn ($value) => '&#8369;' . number_format((float) $value, 2);
    $packageDisplay = function ($package) {
        $structured = $package->packageInclusions ?? collect();
        $casketRow = $structured->firstWhere('service_type', \App\Models\Package::SERVICE_CASKET);
        $includedCasket = $casketRow?->casket_type ?: $package->coffin_type;
        $inclusions = $package->inclusionNames();
        $freebies = $package->freebieNames();

        return [
            'casket' => $includedCasket ?: 'No casket selected',
            'inclusions' => $inclusions,
            'freebies' => $freebies,
            'inclusions_count' => count($inclusions),
            'freebies_count' => count($freebies),
        ];
    };
    $tabs = [
        'packages' => ['label' => 'Packages', 'icon' => 'bi-collection', 'count' => $stats['packages']['total'] ?? 0, 'create' => route('admin.packages.create', ['return_to' => route('admin.service-management.index', ['tab' => 'packages'])]), 'action' => 'Add Package'],
        'caskets' => ['label' => 'Caskets', 'icon' => 'bi-box2-heart', 'count' => $stats['caskets']['total'] ?? 0, 'create' => route('admin.casket-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'caskets'])]), 'action' => 'Add Casket'],
        'addons' => ['label' => 'Add-ons', 'icon' => 'bi-plus-circle', 'count' => $stats['add_ons']['total'] ?? 0, 'create' => route('admin.add-on-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'addons'])]), 'action' => 'Add Add-on'],
        'freebies' => ['label' => 'Freebies', 'icon' => 'bi-gift', 'count' => $stats['freebies']['total'] ?? 0, 'create' => route('admin.freebie-catalogs.create', ['return_to' => route('admin.service-management.index', ['tab' => 'freebies'])]), 'action' => 'Add Freebie'],
    ];
@endphp

<div class="admin-table-page admin-catalog-page service-management-hub px-4 sm:px-6 lg:px-8 py-6" data-service-hub data-active-tab="{{ $activeTab }}">
    <div class="mx-auto max-w-[1440px] space-y-5">
        @if(session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="table-system-card admin-table-card service-hub-card">
            <div class="service-hub-nav">
                <div class="service-hub-tabs" role="tablist" aria-label="Service management sections">
                    @foreach($tabs as $key => $tab)
                        <a href="{{ route('admin.service-management.index', ['tab' => $key]) }}" class="service-hub-tab {{ $key === $activeTab ? 'is-active' : '' }}" role="tab" aria-selected="{{ $key === $activeTab ? 'true' : 'false' }}" data-service-tab="{{ $key }}">
                            <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $tab['label'] }}</span>
                            <b>{{ $tab['count'] }}</b>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="table-system-toolbar service-hub-toolbar">
                <form method="GET" action="{{ route('admin.service-management.index') }}" class="table-toolbar service-hub-filter uniform-record-filters" data-table-toolbar data-service-filter-form>
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <div class="table-toolbar-field service-search-field">
                        <label class="table-toolbar-label">Search</label>
                        <div class="table-toolbar-input-wrap">
                            <i class="bi bi-search table-toolbar-leading-icon" aria-hidden="true"></i>
                            <input type="search" name="q" value="{{ $search }}" class="table-toolbar-search has-clear-action" placeholder="Search current section" data-service-search autocomplete="off">
                            <button type="button" class="live-search-clear" data-service-clear-search aria-label="Clear search" title="Clear search" hidden>
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="service-filter-actions">
                        <div class="table-toolbar-field">
                            <label class="table-toolbar-label">Status</label>
                            <div class="table-toolbar-select-wrap">
                                <i class="bi bi-activity table-toolbar-leading-icon" aria-hidden="true"></i>
                                <select name="status" class="table-toolbar-select" data-service-status aria-label="Status filter">
                                    @if($activeTab === 'caskets')
                                        <option value="available" @selected($status === 'available')>Available</option>
                                        <option value="unavailable" @selected(in_array($status, ['unavailable', 'out_of_stock'], true))>Unavailable</option>
                                        <option value="archived" @selected($status === 'archived')>Archived</option>
                                    @else
                                        <option value="active" @selected($status === 'active')>Active</option>
                                        <option value="archived" @selected($status === 'archived')>Archived</option>
                                    @endif
                                    <option value="all" @selected($status === 'all')>All</option>
                                </select>
                                <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="table-toolbar-reset-wrap">
                            <span class="table-toolbar-label opacity-0 select-none" aria-hidden="true">Actions</span>
                            <button type="button" class="btn btn-secondary btn-sm" data-service-reset>
                                <i class="bi bi-arrow-clockwise"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="service-viewbar">
                <div class="service-view-toggle is-visible" data-service-view-toggle>
                    <button type="button" class="service-view-option is-active" data-catalog-view="cards" aria-pressed="true">
                        <i class="bi bi-grid-3x3-gap-fill" aria-hidden="true"></i>
                        <span>Grid</span>
                    </button>
                    <button type="button" class="service-view-option" data-catalog-view="table" aria-pressed="false">
                        <i class="bi bi-table" aria-hidden="true"></i>
                        <span>Table</span>
                    </button>
                </div>
            </div>

            <div class="service-hub-panels">
                <div class="service-hub-panel {{ $activeTab === 'packages' ? 'is-active' : '' }}" data-service-panel="packages" role="tabpanel">
                    <div class="service-package-cards" data-catalog-view-panel="cards">
                        @forelse($packages as $package)
                            @php
                                $display = $packageDisplay($package);
                                $visibleInclusions = array_slice($display['inclusions'], 0, 3);
                                $visibleFreebies = array_slice($display['freebies'], 0, 3);
                            @endphp
                            <article class="service-package-card" data-service-row data-status="{{ $package->is_active ? 'active' : 'archived' }}" data-search="{{ strtolower($package->name . ' ' . $display['casket'] . ' ' . implode(' ', $display['inclusions']) . ' ' . implode(' ', $display['freebies']) . ' ' . ($package->short_description ?? '')) }}">
                                <div class="service-package-card__head">
                                    <div>
                                        <h3>{{ $package->name }}</h3>
                                        <p><i class="bi bi-box2-heart" aria-hidden="true"></i>{{ $display['casket'] }}</p>
                                    </div>
                                    <span class="service-status {{ $package->is_active ? 'is-active' : 'is-archived' }}">{{ $package->is_active ? 'Active' : 'Archived' }}</span>
                                </div>

                                <div class="service-package-card__price">
                                    <span>Base Price</span>
                                    <strong>{!! $money($package->price) !!}</strong>
                                    @if($package->promo_is_active)
                                        <small><i class="bi bi-tag" aria-hidden="true"></i>{{ $package->promo_label ?: 'Promo active' }}</small>
                                    @else
                                        <small>No active promo</small>
                                    @endif
                                </div>

                                <div class="service-package-card__lists">
                                    <div>
                                        <h4><i class="bi bi-check2-all" aria-hidden="true"></i> Inclusions</h4>
                                        @if($visibleInclusions)
                                            <ul>
                                                @foreach($visibleInclusions as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                            @if($display['inclusions_count'] > 3)
                                                <b>+{{ $display['inclusions_count'] - 3 }} more</b>
                                            @endif
                                        @else
                                            <em>None listed</em>
                                        @endif
                                    </div>
                                    <div>
                                        <h4><i class="bi bi-gift" aria-hidden="true"></i> Freebies</h4>
                                        @if($visibleFreebies)
                                            <ul>
                                                @foreach($visibleFreebies as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                            @if($display['freebies_count'] > 3)
                                                <b>+{{ $display['freebies_count'] - 3 }} more</b>
                                            @endif
                                        @else
                                            <em>None listed</em>
                                        @endif
                                    </div>
                                </div>

                                <div class="service-package-card__foot">
                                    <span><i class="bi bi-clock" aria-hidden="true"></i>{{ $package->updated_at?->diffForHumans() ?? '-' }}</span>
                                    <a href="{{ route('admin.packages.show', ['package' => $package, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-url="{{ route('admin.packages.show', ['package' => $package, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-title="View Package" onclick="window.openServiceCatalogModal?.(this, event)">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                        <span>View Package</span>
                                    </a>
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

                    <div class="table-system-wrap service-package-table" data-catalog-view-panel="table" hidden>
                        <table class="table-base table-system-table w-full">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Included Casket</th>
                                    <th class="table-col-number">Price</th>
                                    <th>Contents</th>
                                    <th>Status</th>
                                    <th class="table-col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($packages as $package)
                                    @php $display = $packageDisplay($package); @endphp
                                    <tr data-service-row data-status="{{ $package->is_active ? 'active' : 'archived' }}" data-search="{{ strtolower($package->name . ' ' . $display['casket'] . ' ' . implode(' ', $display['inclusions']) . ' ' . implode(' ', $display['freebies']) . ' ' . ($package->short_description ?? '')) }}">
                                        <td>
                                            <div class="font-semibold catalog-primary-cell">{{ $package->name }}</div>
                                            @if($package->promo_is_active)
                                                <div class="service-row-note"><i class="bi bi-tag"></i>{{ $package->promo_label ?: 'Promo active' }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $display['casket'] }}</td>
                                        <td class="table-col-number font-semibold">{!! $money($package->price) !!}</td>
                                        <td>{{ $display['inclusions_count'] }} inclusions / {{ $display['freebies_count'] }} freebies</td>
                                        <td><span class="service-status {{ $package->is_active ? 'is-active' : 'is-archived' }}">{{ $package->is_active ? 'Active' : 'Archived' }}</span></td>
                                        <td class="table-col-actions">
                                                <div class="row-hover-actions">
                                                    <a class="row-hover-action" href="{{ route('admin.packages.show', ['package' => $package, 'modal' => 1, 'return_to' => route('admin.service-management.index', ['tab' => 'packages'])]) }}" data-service-modal-url="{{ route('admin.packages.show', ['package' => $package, 'modal' => 1, 'return_to' => route('admin.service-management.index', ['tab' => 'packages'])]) }}" data-service-modal-title="View Package" onclick="window.openServiceCatalogModal?.(this, event)">
                                                        <i class="bi bi-eye"></i><span>View</span>
                                                    </a>
                                                    @if($canManage)<form method="POST" action="{{ route('admin.packages.toggleActive', $package) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="row-hover-action {{ $package->is_active ? 'is-danger' : '' }}" type="submit">
                                                            <i class="bi bi-{{ $package->is_active ? 'archive' : 'arrow-counterclockwise' }}"></i><span>{{ $package->is_active ? 'Archive' : 'Restore' }}</span>
                                                        </button>
                                                    </form>@endif
                                                </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-service-empty><td colspan="6" class="table-system-empty">No packages found.</td></tr>
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
                            <article class="service-package-card" data-service-row data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->type_or_material . ' ' . $catalog->description) }}">
                                <div class="service-package-card__head">
                                    <div>
                                        <h3>{{ $catalog->name }}</h3>
                                        <p><i class="bi bi-box2-heart" aria-hidden="true"></i>{{ $catalog->type_or_material ?: 'Material not set' }}</p>
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
                                    <span><i class="bi bi-clock" aria-hidden="true"></i>{{ $catalog->updated_at?->diffForHumans() ?? '-' }}</span>
                                    <a href="{{ route('admin.casket-catalogs.show', ['casket_catalog' => $catalog, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-url="{{ route('admin.casket-catalogs.show', ['casket_catalog' => $catalog, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-title="View Casket" onclick="window.openServiceCatalogModal?.(this, event)">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                        <span>View Casket</span>
                                    </a>
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
                                    <th>Name</th>
                                    <th>Material</th>
                                    <th class="table-col-number">Reference Value</th>
                                    <th>Status</th>
                                    <th class="table-col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($caskets as $catalog)
                                    <tr data-service-row data-status="{{ ! $catalog->is_active ? 'archived' : ($catalog->is_available ? 'available' : 'unavailable') }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->type_or_material) }}">
                                        <td class="font-semibold catalog-primary-cell">{{ $catalog->name }}</td>
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
                                                    <a class="row-hover-action" href="{{ route('admin.casket-catalogs.show', ['casket_catalog' => $catalog, 'modal' => 1, 'return_to' => route('admin.service-management.index', ['tab' => 'caskets'])]) }}" data-service-modal-url="{{ route('admin.casket-catalogs.show', ['casket_catalog' => $catalog, 'modal' => 1, 'return_to' => route('admin.service-management.index', ['tab' => 'caskets'])]) }}" data-service-modal-title="View Casket" onclick="window.openServiceCatalogModal?.(this, event)">
                                                        <i class="bi bi-eye"></i><span>View</span>
                                                    </a>
                                                    @if($canManage && $catalog->is_active)
                                                        <form method="POST" action="{{ route('admin.casket-catalogs.toggleAvailability', $catalog) }}">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="row-hover-action" type="submit">
                                                                <i class="bi bi-{{ $catalog->is_available ? 'pause-circle' : 'check-circle' }}"></i>
                                                                <span>{{ $catalog->is_available ? 'Mark Unavailable' : 'Mark Available' }}</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if($canManage)<form method="POST" action="{{ route('admin.casket-catalogs.toggleActive', $catalog) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="row-hover-action {{ $catalog->is_active ? 'is-danger' : '' }}" type="submit">
                                                            <i class="bi bi-{{ $catalog->is_active ? 'archive' : 'arrow-counterclockwise' }}"></i><span>{{ $catalog->is_active ? 'Archive' : 'Restore' }}</span>
                                                        </button>
                                                    </form>@endif
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
                            <article class="service-package-card" data-service-row data-status="{{ $catalog->is_active ? 'active' : 'archived' }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->category . ' ' . $catalog->unit . ' ' . $catalog->description) }}">
                                <div class="service-package-card__head">
                                    <div>
                                        <h3>{{ $catalog->name }}</h3>
                                        <p><i class="bi bi-plus-circle" aria-hidden="true"></i>{{ $catalog->category ?: 'General' }}</p>
                                    </div>
                                    <span class="service-status {{ $catalog->is_active ? 'is-active' : 'is-archived' }}">{{ $catalog->is_active ? 'Active' : 'Archived' }}</span>
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
                                    <span><i class="bi bi-clock" aria-hidden="true"></i>{{ $catalog->updated_at?->diffForHumans() ?? '-' }}</span>
                                    <a href="{{ route('admin.add-on-catalogs.show', ['add_on_catalog' => $catalog, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-url="{{ route('admin.add-on-catalogs.show', ['add_on_catalog' => $catalog, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-title="View Add-on" onclick="window.openServiceCatalogModal?.(this, event)">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                        <span>View Add-on</span>
                                    </a>
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
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th class="table-col-number">Standard Price</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <th class="table-col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($addOns as $catalog)
                                    <tr data-service-row data-status="{{ $catalog->is_active ? 'active' : 'archived' }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->category . ' ' . $catalog->unit . ' ' . $catalog->description) }}">
                                        <td>
                                            <div class="font-semibold catalog-primary-cell">{{ $catalog->name }}</div>
                                            @if($catalog->description)<div class="service-row-note">{{ $catalog->description }}</div>@endif
                                        </td>
                                        <td>{{ $catalog->category ?: 'General' }}</td>
                                        <td class="table-col-number font-semibold">{!! $money($catalog->price) !!}</td>
                                        <td>{{ $catalog->unit }}</td>
                                        <td><span class="service-status {{ $catalog->is_active ? 'is-active' : 'is-archived' }}">{{ $catalog->is_active ? 'Active' : 'Archived' }}</span></td>
                                        <td class="table-col-actions">
                                                <div class="row-hover-actions">
                                                    <a class="row-hover-action" href="{{ route('admin.add-on-catalogs.show', ['add_on_catalog' => $catalog, 'modal' => 1, 'return_to' => route('admin.service-management.index', ['tab' => 'addons'])]) }}" data-service-modal-url="{{ route('admin.add-on-catalogs.show', ['add_on_catalog' => $catalog, 'modal' => 1, 'return_to' => route('admin.service-management.index', ['tab' => 'addons'])]) }}" data-service-modal-title="View Add-on" onclick="window.openServiceCatalogModal?.(this, event)">
                                                        <i class="bi bi-eye"></i><span>View</span>
                                                    </a>
                                                    @if($canManage)<form method="POST" action="{{ route('admin.add-on-catalogs.toggleActive', $catalog) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="row-hover-action {{ $catalog->is_active ? 'is-danger' : '' }}" type="submit">
                                                            <i class="bi bi-{{ $catalog->is_active ? 'archive' : 'arrow-counterclockwise' }}"></i><span>{{ $catalog->is_active ? 'Archive' : 'Restore' }}</span>
                                                        </button>
                                                    </form>@endif
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
                            <article class="service-package-card" data-service-row data-status="{{ $catalog->is_active ? 'active' : 'archived' }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->default_unit) }}">
                                <div class="service-package-card__head">
                                    <div>
                                        <h3>{{ $catalog->name }}</h3>
                                        <p><i class="bi bi-gift" aria-hidden="true"></i>{{ $catalog->default_unit ?: 'item' }}</p>
                                    </div>
                                    <span class="service-status {{ $catalog->is_active ? 'is-active' : 'is-archived' }}">{{ $catalog->is_active ? 'Active' : 'Archived' }}</span>
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
                                    <span><i class="bi bi-clock" aria-hidden="true"></i>{{ $catalog->updated_at?->diffForHumans() ?? '-' }}</span>
                                    <a href="{{ route('admin.freebie-catalogs.show', ['freebie_catalog' => $catalog, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-url="{{ route('admin.freebie-catalogs.show', ['freebie_catalog' => $catalog, 'modal' => 1, 'return_to' => request()->fullUrl()]) }}" data-service-modal-title="View Freebie" onclick="window.openServiceCatalogModal?.(this, event)">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                        <span>View Freebie</span>
                                    </a>
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
                                    <th>Name</th>
                                    <th>Default Unit</th>
                                    <th>Used In Packages</th>
                                    <th>Status</th>
                                    <th class="table-col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($freebies as $catalog)
                                    <tr data-service-row data-status="{{ $catalog->is_active ? 'active' : 'archived' }}" data-search="{{ strtolower($catalog->name . ' ' . $catalog->default_unit) }}">
                                        <td class="font-semibold catalog-primary-cell">{{ $catalog->name }}</td>
                                        <td>{{ $catalog->default_unit ?: 'item' }}</td>
                                        <td>{{ (int) ($catalog->package_freebies_count ?? 0) }} package{{ (int) ($catalog->package_freebies_count ?? 0) === 1 ? '' : 's' }}</td>
                                        <td><span class="service-status {{ $catalog->is_active ? 'is-active' : 'is-archived' }}">{{ $catalog->is_active ? 'Active' : 'Archived' }}</span></td>
                                        <td class="table-col-actions">
                                                <div class="row-hover-actions">
                                                    <a class="row-hover-action" href="{{ route('admin.freebie-catalogs.show', ['freebie_catalog' => $catalog, 'modal' => 1, 'return_to' => route('admin.service-management.index', ['tab' => 'freebies'])]) }}" data-service-modal-url="{{ route('admin.freebie-catalogs.show', ['freebie_catalog' => $catalog, 'modal' => 1, 'return_to' => route('admin.service-management.index', ['tab' => 'freebies'])]) }}" data-service-modal-title="View Freebie" onclick="window.openServiceCatalogModal?.(this, event)">
                                                        <i class="bi bi-eye"></i><span>View</span>
                                                    </a>
                                                    @if($canManage)<form method="POST" action="{{ route('admin.freebie-catalogs.toggleActive', $catalog) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="row-hover-action {{ $catalog->is_active ? 'is-danger' : '' }}" type="submit">
                                                            <i class="bi bi-{{ $catalog->is_active ? 'archive' : 'arrow-counterclockwise' }}"></i><span>{{ $catalog->is_active ? 'Archive' : 'Restore' }}</span>
                                                        </button>
                                                    </form>@endif
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

                <div class="service-hub-empty" data-service-filter-empty hidden>
                    <i class="bi bi-search"></i>
                    <strong>No matching records</strong>
                    <span>Try another search or status filter.</span>
                </div>
            </div>
        </section>
    </div>
</div>

<div class="service-modal" data-service-modal hidden>
    <div class="service-modal__backdrop" data-service-modal-close></div>
    <section class="service-modal__panel" role="dialog" aria-modal="true" aria-labelledby="service-modal-title">
        <header class="service-modal__head">
            <div>
                <h2 id="service-modal-title" data-service-modal-title>Add Catalog Item</h2>
                <p data-service-modal-copy>Manage catalog details without leaving Service Management.</p>
            </div>
        </header>
        <button type="button" class="service-modal__floating-close" data-service-modal-close aria-label="Close modal">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
        <iframe class="service-modal__frame" data-service-modal-frame title="Service catalog form"></iframe>
    </section>
</div>

<style>
.service-management-hub .service-hub-tabs {
    display:flex;
    align-items:center;
    gap:12px;
}
.service-management-hub {
    color:#1F2D20;
    min-height:calc(100vh - 48px);
    padding-top:8px !important;
}
.service-management-hub > .mx-auto {
    max-width:1440px;
}
.service-hub-scope,
.service-status {
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:999px;
    padding:6px 10px;
    border:1px solid #B9CBB1;
    background:#EEF4E8;
    color:#2F5131;
    font-size:.78rem;
    font-weight:800;
    white-space:nowrap;
}
.service-row-note {
    color:#5F685F;
    font-weight:650;
}
.service-hub-card {
    overflow:hidden;
    border-radius:10px !important;
    background:#D3DEC9 !important;
}
.service-hub-nav {
    display:grid;
    grid-template-columns:minmax(0, 1fr);
    align-items:center;
    gap:18px;
    min-height:82px;
    padding:16px 22px;
    border-bottom:1px solid #C9D7C0;
    background:#D3DEC9;
}
.service-hub-tabs {
    grid-column:1;
    justify-content:flex-start;
    flex-wrap:wrap;
}
.service-hub-filter {
    display:grid !important;
    grid-template-columns:minmax(0, 1fr) auto !important;
    align-items:end;
    gap:14px !important;
    width:100%;
}
.service-search-field {
    width:min(100%, 440px);
    min-width:280px;
}
.service-hub-toolbar {
    padding:16px 22px !important;
    background:#DCE6D6 !important;
    border-bottom:0 !important;
}
.service-hub-toolbar .table-toolbar-field,
.service-hub-toolbar .table-toolbar-reset-wrap {
    min-width:0;
}
.service-hub-toolbar .table-toolbar-input-wrap,
.service-hub-toolbar .table-toolbar-select-wrap {
    position:relative;
    min-height:46px;
}
.service-hub-toolbar .table-toolbar-leading-icon {
    position:absolute;
    left:14px;
    top:50%;
    z-index:2;
    transform:translateY(-50%);
    color:#5F685F;
    pointer-events:none;
}
.service-hub-toolbar .table-toolbar-search {
    width:100%;
    max-width:none;
    padding-left:42px !important;
    padding-right:38px !important;
}
.service-hub-toolbar .table-toolbar-search::-webkit-search-cancel-button {
    display:none;
}
.service-hub-toolbar .table-toolbar-select {
    padding-left:42px !important;
}
.service-hub-toolbar .table-toolbar-select-wrap {
    width:100%;
}
.service-hub-toolbar .live-search-clear {
    position:absolute;
    right:12px;
    top:50%;
    z-index:3;
    transform:translateY(-50%);
    width:30px;
    height:30px;
    border:1px solid transparent;
    border-radius:6px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:transparent;
    color:#5F685F;
    cursor:pointer;
    transition:background-color .16s ease, border-color .16s ease, color .16s ease;
}
.service-hub-toolbar .live-search-clear:hover,
.service-hub-toolbar .live-search-clear:focus-visible {
    outline:none;
    background:#C7D5BE;
    border-color:#8EA083;
    color:#1F2D20;
}
.service-hub-toolbar .live-search-clear[hidden] {
    display:none !important;
}
.service-filter-actions {
    display:grid;
    grid-template-columns:minmax(200px, 260px) auto;
    align-items:end;
    justify-content:end;
    gap:14px;
    min-width:0;
}
.service-viewbar {
    display:flex;
    align-items:center;
    justify-content:center;
    padding:0 22px 16px;
    background:#DCE6D6;
    border-bottom:1px solid #C9D7C0;
}
.service-view-toggle {
    display:inline-flex;
    align-items:center;
    align-self:end;
    min-height:50px;
    width:max-content;
    overflow:hidden;
    border:1px solid #B9CBB1;
    border-radius:8px;
    background:#F7F8F1;
}
.service-view-option {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    min-height:50px;
    min-width:104px;
    padding:0 16px;
    border:0;
    background:transparent;
    color:#5F685F;
    font-weight:850;
    transition:background-color .16s ease, color .16s ease;
}
.service-view-option:hover,
.service-view-option:focus-visible {
    background:#E5EEDC;
    color:#1F2D20;
    outline:none;
}
.service-view-option.is-active {
    background:#2F5131;
    color:#F7F8F1;
}
.service-pagination {
    padding:14px 22px;
    border-top:1px solid #C9D7C0;
    background:#DCE6D6;
}
.service-pagination .table-paginator {
    margin:0;
    padding:0;
    border:0;
    background:transparent;
}
.service-hub-tab {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    min-height:50px;
    padding:0 20px;
    border:1px solid #C9C5BB;
    border-radius:8px;
    background:#F7F8F1;
    color:#5F685F;
    text-decoration:none;
    font-weight:850;
    transition:background-color .16s ease, border-color .16s ease, color .16s ease, box-shadow .16s ease;
}
.service-hub-tab b {
    min-width:24px;
    padding:2px 7px;
    border-radius:999px;
    background:#E1E7D9;
    color:#2F5131;
    font-size:.72rem;
}
.service-hub-tab:hover,
.service-hub-tab:focus-visible {
    background:#E5EEDC;
    border-color:#9FAF97;
    outline:none;
}
.service-hub-tab.is-active {
    position:relative;
    background:#C5D5B9;
    border-color:#2F5131;
    color:#1F2D20;
}
.service-hub-tab.is-active::after {
    content:"";
    position:absolute;
    left:22px;
    right:22px;
    bottom:-1px;
    height:4px;
    border-radius:999px;
    background:#2F5131;
}
.service-hub-panel {
    display:none;
}
.service-hub-panel.is-active {
    display:block;
}
.service-package-cards {
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:18px;
    padding:22px;
}
.service-package-card {
    display:flex;
    flex-direction:column;
    min-height:100%;
    overflow:hidden;
    border:1px solid #9FAF97;
    border-radius:8px;
    background:#DCE6D6;
    color:#1F2D20;
    transition:border-color .16s ease, box-shadow .16s ease, transform .16s ease;
}
.service-package-card:hover,
.service-package-card:focus-within {
    border-color:#2F5131;
    box-shadow:0 10px 24px rgba(31,45,32,.14);
    transform:translateY(-1px);
}
.service-package-card__head,
.service-package-card__price,
.service-package-card__lists,
.service-package-card__foot {
    padding-left:18px;
    padding-right:18px;
}
.service-package-card__head {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    min-height:86px;
    padding-top:18px;
    padding-bottom:14px;
    border-bottom:1px solid #C9D7C0;
}
.service-package-card__head h3 {
    margin:0;
    color:#1F2D20;
    font-size:1.02rem;
    line-height:1.25;
    font-weight:950;
}
.service-package-card__head p {
    display:flex;
    align-items:center;
    gap:6px;
    margin:8px 0 0;
    color:#5F685F;
    font-size:.82rem;
    font-weight:800;
}
.service-package-card__price {
    padding-top:16px;
    padding-bottom:16px;
    border-bottom:1px solid #C9D7C0;
}
.service-package-card__price span,
.service-package-card__lists h4 {
    display:flex;
    align-items:center;
    gap:6px;
    margin:0;
    color:#5F685F;
    font-size:.68rem;
    font-weight:900;
    letter-spacing:.07em;
    text-transform:uppercase;
}
.service-package-card__price strong {
    display:block;
    margin-top:4px;
    color:#1F2D20;
    font-size:1.45rem;
    line-height:1.1;
    font-weight:950;
}
.service-package-card__price small {
    display:flex;
    align-items:center;
    gap:5px;
    margin-top:10px;
    color:#5F685F;
    font-weight:800;
}
.service-package-card__lists {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    flex:1;
    padding-top:16px;
    padding-bottom:18px;
    border-bottom:1px solid #C9D7C0;
}
.service-package-card__lists.is-single {
    grid-template-columns:1fr;
}
.service-package-card__lists p {
    margin:10px 0 0;
    color:#52605A;
    font-size:.84rem;
    font-weight:650;
    line-height:1.45;
}
.service-package-card__lists ul {
    display:grid;
    gap:6px;
    margin:10px 0 0;
    padding:0;
    list-style:none;
}
.service-package-card__lists li {
    position:relative;
    padding-left:12px;
    color:#52605A;
    font-size:.82rem;
    font-weight:650;
    line-height:1.35;
}
.service-package-card__lists li::before {
    content:"";
    position:absolute;
    left:0;
    top:.62em;
    width:4px;
    height:4px;
    border-radius:50%;
    background:#2F9C72;
}
.service-package-card__lists div:nth-child(2) li::before {
    background:#B87922;
}
.service-package-card__lists b,
.service-package-card__lists em {
    display:block;
    margin-top:8px;
    color:#5F685F;
    font-size:.78rem;
    font-weight:800;
}
.service-package-card__foot {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    min-height:66px;
    padding-top:12px;
    padding-bottom:12px;
}
.service-package-card__foot > span {
    display:inline-flex;
    align-items:center;
    gap:6px;
    color:#82908A;
    font-size:.78rem;
    font-weight:750;
}
.service-package-card__foot a {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    min-height:40px;
    padding:0 14px;
    border-radius:8px;
    background:#2F5131;
    color:#F7F8F1;
    font-size:.85rem;
    font-weight:900;
    text-decoration:none;
    white-space:nowrap;
}
.service-package-card__foot a:hover {
    background:#223D25;
}
.service-package-table {
    margin:22px;
}
.service-package-table[hidden],
[data-catalog-view-panel][hidden] {
    display:none !important;
}
.service-status.is-active {
    background:#DDECD6;
    color:#2F5131;
}
.service-status.is-archived {
    background:#E9E2D4;
    color:#7A4B32;
}
.service-status.is-warning {
    background:#F2E4C8;
    color:#815B15;
}
.service-hub-empty {
    display:grid;
    justify-items:center;
    gap:6px;
    padding:42px 20px;
    color:#5F685F;
    border-top:1px solid #C9D7C0;
}
.service-hub-empty[hidden] {
    display:none !important;
}
.service-hub-empty i {
    font-size:1.8rem;
}
.service-hub-empty.is-inline {
    grid-column:1 / -1;
    border:1px dashed #B9CBB1;
    border-radius:10px;
    background:#EEF4E8;
}
html[data-theme='dark'] .service-package-card,
html[data-theme='dark'] .service-view-toggle,
html[data-theme='dark'] .service-view-option {
    border-color:rgba(199,213,190,.24);
}
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
    padding:28px;
    overscroll-behavior:contain;
    touch-action:none;
    opacity:1;
    visibility:visible;
}
.service-modal__backdrop {
    position:absolute;
    inset:0;
    background:rgba(31,45,32,.48);
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
@media (min-width: 1px) {
    .service-modal__floating-close {
        display:grid;
    }
}
@media (max-width: 900px) {
    .service-package-cards {
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }
    .service-hub-filter {
        grid-template-columns:1fr !important;
    }
    .service-search-field {
        width:100%;
        min-width:0;
    }
    .service-viewbar {
        padding:0 14px 14px;
    }
    .service-package-table {
        margin:14px;
    }
    .service-view-toggle {
        justify-self:stretch;
        width:100%;
        display:grid;
        grid-template-columns:1fr 1fr;
    }
    .service-view-option {
        width:100%;
    }
    .service-hub-nav {
        grid-template-columns:1fr;
    }
    .service-hub-tabs {
        grid-column:1;
        justify-self:stretch;
    }
    .service-filter-actions {
        width:100%;
        grid-template-columns:minmax(0, 1fr) auto;
        justify-self:stretch;
    }
}
@media (max-width: 640px) {
    .service-package-cards {
        grid-template-columns:1fr;
        padding:14px;
    }
    .service-hub-tabs,
    .service-hub-nav,
    .service-viewbar,
    .service-hub-toolbar,
    .service-pagination {
        padding-left:14px !important;
        padding-right:14px !important;
    }
    .service-package-card__lists {
        grid-template-columns:1fr;
    }
    .service-hub-tab {
        flex:1 1 100%;
    }
    .service-filter-actions {
        grid-template-columns:1fr;
    }
    .service-filter-actions .table-toolbar-reset-wrap,
    .service-filter-actions .table-toolbar-reset-wrap .btn {
        width:100%;
    }
    .service-modal {
        padding:10px;
        align-items:stretch;
    }
    .service-modal__panel {
        width:100%;
        height:calc(100vh - 20px);
        border-radius:12px;
    }
    .service-modal__head {
        padding:15px 58px 15px 16px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const hub = document.querySelector('[data-service-hub]');
    if (!hub) return;

    const filterForm = hub.querySelector('[data-service-filter-form]');
    const search = hub.querySelector('[data-service-search]');
    const status = hub.querySelector('[data-service-status]');
    const clearSearch = hub.querySelector('[data-service-clear-search]');
    const reset = hub.querySelector('[data-service-reset]');
    const empty = hub.querySelector('[data-service-filter-empty]');
    const viewButtons = [...hub.querySelectorAll('[data-catalog-view]')];
    const viewPanels = [...hub.querySelectorAll('[data-catalog-view-panel]')];
    const modal = document.querySelector('[data-service-modal]');
    const modalFrame = modal?.querySelector('[data-service-modal-frame]');
    const modalTitle = modal?.querySelector('[data-service-modal-title]');
    const modalCopy = modal?.querySelector('[data-service-modal-copy]');
    const pageContent = document.querySelector('.page-content');
    let catalogView = localStorage.getItem('service-catalog-view') || 'cards';
    let filterSubmitTimer = null;
    let modalScrollY = 0;
    if (modal && modal.parentElement !== document.body) document.body.appendChild(modal);
    const setCatalogView = (view) => {
        catalogView = view === 'table' ? 'table' : 'cards';
        localStorage.setItem('service-catalog-view', catalogView);

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
            const matchesText = !query || (row.dataset.search || '').includes(query);
            const matchesStatus = !currentStatus || currentStatus === 'all' || row.dataset.status === currentStatus;
            const visible = matchesText && matchesStatus;
            row.hidden = !visible;
            if (visible && !row.closest('[hidden]')) shown += 1;
        });

        if (clearSearch) clearSearch.hidden = (search?.value ?? '') === '';
        if (empty) empty.hidden = shown > 0 || panel.querySelector('[data-service-empty]');
    };

    viewButtons.forEach((button) => button.addEventListener('click', () => setCatalogView(button.dataset.catalogView)));
    search?.addEventListener('input', () => {
        applyFilters();
        window.clearTimeout(filterSubmitTimer);
        filterSubmitTimer = window.setTimeout(() => filterForm?.requestSubmit(), 450);
    });
    search?.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || search.value === '') return;
        event.preventDefault();
        window.clearTimeout(filterSubmitTimer);
        search.value = '';
        applyFilters();
        filterForm?.requestSubmit();
    });
    status?.addEventListener('change', () => filterForm?.requestSubmit());
    clearSearch?.addEventListener('click', () => {
        window.clearTimeout(filterSubmitTimer);
        search.value = '';
        search.focus();
        applyFilters();
        filterForm?.requestSubmit();
    });
    reset?.addEventListener('click', () => {
        window.location.assign(@js(route('admin.service-management.index', ['tab' => $activeTab])));
    });

    const closeModal = () => {
        if (!modal) return;
        modal.hidden = true;
        document.documentElement.classList.remove('service-modal-open');
        document.body.classList.remove('service-modal-open');
        if (pageContent) pageContent.scrollTop = modalScrollY;
        if (modalFrame) modalFrame.src = 'about:blank';
    };

    const openModal = (action, event) => {
        if (!modal || !modalFrame || !action) return;
        event?.preventDefault();
        const title = action.dataset.serviceModalTitle || 'Catalog Item';
        if (modalTitle) modalTitle.textContent = title;
        if (modalCopy) {
            modalCopy.textContent = title.toLowerCase().startsWith('add')
                ? 'Create a new catalog item without leaving Service Management.'
                : 'Review and update this catalog item without leaving Service Management.';
        }
        const modalUrl = new URL(action.dataset.serviceModalUrl || action.href, window.location.origin);
        modalUrl.searchParams.set('modal', '1');
        modalFrame.src = modalUrl.toString();
        modalScrollY = pageContent?.scrollTop || window.scrollY || document.documentElement.scrollTop || 0;
        modal.hidden = false;
        document.documentElement.classList.add('service-modal-open');
        document.body.classList.add('service-modal-open');
    };

    window.openServiceCatalogModal = openModal;
    window.closeServiceCatalogModal = closeModal;

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest?.('[data-service-modal-url]');
        if (!trigger) return;
        openModal(trigger, event);
    });

    modalFrame?.addEventListener('load', () => {
        try {
            const frameUrl = new URL(modalFrame.contentWindow.location.href);
            if (frameUrl.pathname === window.location.pathname && frameUrl.searchParams.has('tab')) {
                window.location.href = frameUrl.toString();
            }
        } catch (_) {
            // Ignore inaccessible or initial iframe states.
        }
    });

    modal?.querySelectorAll('[data-service-modal-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
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
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
    });

    setCatalogView(catalogView);
});
</script>
@endsection
