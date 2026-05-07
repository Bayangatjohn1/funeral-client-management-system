@extends('layouts.panel')

@section('page_title', 'Package Management')
@section('page_desc', 'Manage funeral service packages, pricing, inclusions, freebies, promos, and availability.')

@section('content')
@php
    $currentUser = auth()->user();
    $isBranchAdmin = $currentUser?->isBranchAdmin() ?? false;
    $isMainAdmin = $currentUser?->isMainAdmin() ?? false;
@endphp
<style>[x-cloak] { display: none !important; }</style>
<div class="admin-table-page admin-catalog-page package-management-page" x-data="pkgCatalog()">
<div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8 py-6">
<div class="space-y-6">

@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

{{-- Summary stats --}}
@php
    $isPromoFiltered   = request('promo') === 'with_promo';
    $isPriceSorted     = request('sort') === 'price_desc';
    $isNoFilter        = !request()->hasAny(['q', 'status', 'promo', 'sort']);
@endphp
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

    {{-- Total Packages — clickable: clears all filters --}}
    <a href="{{ route('admin.packages.index') }}" class="pkg-stat-card {{ $isNoFilter ? 'pkg-stat-card--active' : '' }}" title="View all packages">
        <div class="pkg-stat-card__inner">
            <span class="pkg-stat-card__icon" style="background:rgba(62,74,61,0.10);color:#3E4A3D;"><i class="bi bi-box-seam"></i></span>
            <div class="pkg-stat-card__body">
                <span class="pkg-stat-card__label">Total Packages</span>
                <span class="pkg-stat-card__value" style="color:#333333;">{{ $totalPackages }}</span>
                <span class="pkg-stat-card__desc">All service packages</span>
            </div>
            <span class="pkg-stat-card__action">View all</span>
        </div>
    </a>

    {{-- With Promo — clickable: filters promo=with_promo --}}
    <a href="{{ route('admin.packages.index', ['promo' => 'with_promo']) }}" class="pkg-stat-card {{ $isPromoFiltered ? 'pkg-stat-card--active' : '' }}" title="Filter packages with active promos">
        <div class="pkg-stat-card__inner">
            <span class="pkg-stat-card__icon" style="background:rgba(184,121,86,0.12);color:#B87956;"><i class="bi bi-tag-fill"></i></span>
            <div class="pkg-stat-card__body">
                <span class="pkg-stat-card__label">With Promo</span>
                <span class="pkg-stat-card__value" style="color:#B87956;">{{ $promoPackages }}</span>
                <span class="pkg-stat-card__desc">Packages with active promos</span>
            </div>
            <span class="pkg-stat-card__action">Filter promos</span>
        </div>
    </a>

    {{-- Highest Price — clickable: sorts by price_desc --}}
    <a href="{{ route('admin.packages.index', ['sort' => 'price_desc']) }}" class="pkg-stat-card {{ $isPriceSorted ? 'pkg-stat-card--active' : '' }}" title="Sort by highest price">
        <div class="pkg-stat-card__inner">
            <span class="pkg-stat-card__icon" style="background:rgba(62,74,61,0.10);color:#3E4A3D;"><i class="bi bi-arrow-up-circle"></i></span>
            <div class="pkg-stat-card__body">
                <span class="pkg-stat-card__label">Highest Price</span>
                <span class="pkg-stat-card__value" style="color:#333333;">&#8369;{{ number_format($highestPrice, 2) }}</span>
                <span class="pkg-stat-card__desc">Most expensive package</span>
            </div>
            <span class="pkg-stat-card__action">Sort highest</span>
        </div>
    </a>

</div>

<style>
.pkg-stat-card {
    display: flex;
    flex-direction: column;
    background: #FAFAF7;
    border: 1.5px solid #C9C5BB;
    border-radius: 10px;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}
.pkg-stat-card:hover {
    background: #F3F0E8;
    border-color: #3E4A3D;
    box-shadow: 0 2px 6px rgba(62,74,61,0.09);
}
.pkg-stat-card--active {
    background: rgba(139,154,139,0.15);
    border-color: #3E4A3D;
}
.pkg-stat-card--active:hover {
    background: rgba(139,154,139,0.22);
}
.pkg-stat-card__inner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.7rem 1rem;
    flex: 1;
}
.pkg-stat-card__icon {
    width: 2rem;
    height: 2rem;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    flex-shrink: 0;
}
.pkg-stat-card__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.08rem;
}
.pkg-stat-card__label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #5F685F;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
    line-height: 1.3;
}
.pkg-stat-card__value {
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.15;
    font-variant-numeric: tabular-nums;
}
.pkg-stat-card__desc {
    font-size: 0.63rem;
    color: #7A8577;
    font-weight: 500;
    line-height: 1.3;
    margin-top: 0.1rem;
}
.pkg-stat-card__action {
    font-size: 0.62rem;
    font-weight: 700;
    color: #5F685F;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    white-space: nowrap;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.15s ease;
}
.pkg-stat-card:hover .pkg-stat-card__action,
.pkg-stat-card--active .pkg-stat-card__action {
    opacity: 1;
}
.pkg-stat-card--active .pkg-stat-card__action {
    color: #3E4A3D;
}
</style>

{{-- Main card --}}
<section class="table-system-card admin-table-card">

    {{-- Header --}}
    <div class="table-system-head">
        <div class="admin-table-head-row">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="table-system-title">Service Packages</h2>
                    @if($isBranchAdmin)
                        <span class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-700">
                            <i class="bi bi-eye"></i>
                            Read-only access
                        </span>
                    @endif
                </div>
                <p class="admin-table-head-copy">
                    @if($isBranchAdmin)
                        Branch admins can view packages but cannot modify pricing or package details.
                    @else
                        Manage pricing, inclusions, freebies, promos, and availability.
                    @endif
                </p>
            </div>
            <div class="admin-table-head-actions">
                {{-- View toggle --}}
                <div class="inline-flex items-center rounded-lg border border-slate-200 overflow-hidden bg-white">
                    <button
                        type="button"
                        @click="setView('card')"
                        :class="view === 'card' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50'"
                        class="px-3 py-2 transition-colors flex items-center gap-1.5 font-medium"
                        title="Card view"
                    >
                        <i class="bi bi-grid-3x3-gap-fill text-xs"></i>
                        <span class="hidden sm:inline text-xs">Cards</span>
                    </button>
                    <button
                        type="button"
                        @click="setView('table')"
                        :class="view === 'table' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50'"
                        class="px-3 py-2 transition-colors flex items-center gap-1.5 font-medium border-l border-slate-200"
                        title="Table view"
                    >
                        <i class="bi bi-table text-xs"></i>
                        <span class="hidden sm:inline text-xs">Table</span>
                    </button>
                </div>

                @if($isMainAdmin)
                    {{-- Add Package --}}
                    <a
                        href="{{ route('admin.packages.create') }}"
                        data-package-modal-trigger
                        data-url="{{ route('admin.packages.create') }}"
                        class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white inline-flex items-center gap-2"
                    >
                        <i class="bi bi-plus-circle"></i>
                        <span>Add Package</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="table-system-toolbar">
        <form
            method="GET"
            action="{{ route('admin.packages.index') }}"
            class="table-toolbar"
            data-table-toolbar
            data-search-debounce="400"
        >
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Search</label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search packages..."
                    class="form-input table-toolbar-search"
                    data-table-search
                    autocomplete="off"
                >
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Status</label>
                <select name="status" class="form-select table-toolbar-select" data-table-auto-submit>
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Promo</label>
                <select name="promo" class="form-select table-toolbar-select" data-table-auto-submit>
                    <option value="">All Promos</option>
                    <option value="with_promo" {{ request('promo') === 'with_promo' ? 'selected' : '' }}>With Promo</option>
                    <option value="no_promo" {{ request('promo') === 'no_promo' ? 'selected' : '' }}>No Promo</option>
                </select>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Sort</label>
                <select name="sort" class="form-select table-toolbar-sort" data-table-sort>
                    <option value="name_asc" {{ request('sort', 'name_asc') === 'name_asc' ? 'selected' : '' }}>Name A&ndash;Z</option>
                    <option value="updated_desc" {{ request('sort') === 'updated_desc' ? 'selected' : '' }}>Latest Updated</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                    <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                </select>
            </div>
            <div class="table-toolbar-reset-wrap">
                <span class="table-toolbar-label opacity-0 select-none" aria-hidden="true">Actions</span>
                <div class="filter-actions">
                    <a href="{{ route('admin.packages.index') }}" class="btn-outline btn-filter-reset">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span>Reset</span>
                    </a>
                    <button type="submit" class="btn-secondary">
                        <i class="bi bi-funnel"></i>
                        <span>Apply</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ═══════════════════════════════════════════
         CARD VIEW
    ═══════════════════════════════════════════ --}}
    <div x-show="view === 'card'" x-cloak class="directory-card-view">

        @if($packages->isEmpty())
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center py-16 gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                    <i class="bi bi-box-seam text-2xl text-slate-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-sm">No packages found</h3>
                    <p class="text-xs text-slate-500 mt-1">Try changing the search or filters, or add a new package.</p>
                </div>
                @if($isMainAdmin)
                    <a
                        href="{{ route('admin.packages.create') }}"
                        data-package-modal-trigger
                        data-url="{{ route('admin.packages.create') }}"
                        class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] text-white inline-flex items-center gap-1.5 mt-1"
                    >
                        <i class="bi bi-plus-circle"></i>
                        Add Package
                    </a>
                @endif
            </div>

        @else
            {{-- Package cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($packages as $package)
                @php
                    $inclusionItems = $package->inclusionNames();
                    $freebieItems = $package->freebieNames();
                    $visibleInclusions = array_slice($inclusionItems, 0, 3);
                    $visibleFreebies = array_slice($freebieItems, 0, 3);
                @endphp
                <div
                    class="bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col"
                    x-data="{ editingPrice: false }"
                >
                    {{-- Card header: name + status --}}
                    <div class="p-5 flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-900 text-[15px] leading-snug">{{ $package->name }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $package->coffin_type ?: '—' }}
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            @if($package->is_active)
                                <span class="status-badge status-badge-success">Active</span>
                            @else
                                <span class="status-badge status-badge-neutral">Inactive</span>
                            @endif
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="px-5 pb-4 pt-4 border-t border-slate-100">
                        <div x-show="!editingPrice" class="flex items-center justify-between gap-2">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Package Price</p>
                                <p class="text-2xl font-bold text-slate-900 leading-none mt-0.5">&#8369;{{ number_format((float) $package->price, 2) }}</p>
                            </div>
                            @if($isMainAdmin)
                                <button
                                    type="button"
                                    @click="editingPrice = true"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-[var(--brand-mid)] hover:text-[var(--brand-hover)] transition-colors"
                                >
                                    <i class="bi bi-pencil-fill text-[10px]"></i>
                                    Update
                                </button>
                            @endif
                        </div>
                        @if($isMainAdmin)
                        <div x-show="editingPrice" x-cloak>
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-2">Update Price</p>
                            <form
                                method="POST"
                                action="{{ route('admin.packages.quickPrice', $package) }}"
                                class="flex items-center gap-2"
                            >
                                @csrf
                                @method('PATCH')
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="price"
                                    value="{{ number_format($package->price, 2, '.', '') }}"
                                    class="admin-table-input-inline flex-1 min-w-0"
                                    required
                                >
                                <button type="submit" class="admin-table-save-btn">Save</button>
                                <button
                                    type="button"
                                    @click="editingPrice = false"
                                    class="text-xs text-slate-400 hover:text-slate-700 px-1 transition-colors"
                                >
                                    Cancel
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>

                    {{-- Promo --}}
                    <div class="px-5 pb-4">
                        @if($package->promo_is_active && $package->promo_value_type && $package->promo_value)
                            <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full px-2.5 py-1 text-xs font-semibold">
                                <i class="bi bi-tag-fill text-[10px]"></i>
                                {{ $package->promo_label ?: 'Promo Active' }}
                                &mdash;
                                @if($package->promo_value_type === 'PERCENT')
                                    {{ number_format((float) $package->promo_value, 2) }}% off
                                @else
                                    &#8369;{{ number_format((float) $package->promo_value, 2) }} off
                                @endif
                            </span>
                            @if($package->promo_starts_at || $package->promo_ends_at)
                                <p class="text-[11px] text-slate-400 mt-1.5 ml-0.5">
                                    {{ $package->promo_starts_at?->format('M d, Y') ?? '—' }}
                                    &ndash;
                                    {{ $package->promo_ends_at?->format('M d, Y') ?? 'Ongoing' }}
                                </p>
                            @endif
                        @else
                            <span class="text-xs text-slate-400">No active promo</span>
                        @endif
                    </div>

                    {{-- Inclusions & Freebies --}}
                    <div class="px-5 pb-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-3 flex-1">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5 flex items-center gap-1">
                                <i class="bi bi-check2-all"></i> Inclusions
                            </p>
                            @if($inclusionItems)
                                <ul class="space-y-1 text-xs text-slate-600 leading-relaxed">
                                    @foreach($visibleInclusions as $item)
                                        <li class="flex gap-1.5"><span class="text-emerald-600">&bull;</span><span>{{ $item }}</span></li>
                                    @endforeach
                                </ul>
                                @if(count($inclusionItems) > 3)
                                    <p class="mt-1 text-[11px] font-semibold text-slate-400">+{{ count($inclusionItems) - 3 }} more</p>
                                @endif
                            @else
                                <p class="text-xs text-slate-400 italic">None listed</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5 flex items-center gap-1">
                                <i class="bi bi-gift"></i> Freebies
                            </p>
                            @if($freebieItems)
                                <ul class="space-y-1 text-xs text-slate-600 leading-relaxed">
                                    @foreach($visibleFreebies as $item)
                                        <li class="flex gap-1.5"><span class="text-amber-600">&bull;</span><span>{{ $item }}</span></li>
                                    @endforeach
                                </ul>
                                @if(count($freebieItems) > 3)
                                    <p class="mt-1 text-[11px] font-semibold text-slate-400">+{{ count($freebieItems) - 3 }} more</p>
                                @endif
                            @else
                                <p class="text-xs text-slate-400 italic">None listed</p>
                            @endif
                        </div>
                    </div>

                    {{-- Card footer --}}
                    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="text-[11px] text-slate-400">
                            <i class="bi bi-clock text-[10px] mr-0.5"></i>
                            {{ $package->updated_at?->diffForHumans() ?? '—' }}
                        </span>
                        @if($isMainAdmin)
                            <div class="row-action-menu" data-row-menu>
                                <button
                                    type="button"
                                    class="row-action-trigger"
                                    data-row-menu-trigger
                                    aria-haspopup="menu"
                                    aria-expanded="false"
                                    aria-label="Open row actions"
                                >
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="row-action-dropdown" role="menu">
                                    <a
                                        href="{{ route('admin.packages.edit', $package) }}"
                                        data-url="{{ route('admin.packages.edit', $package) }}"
                                        data-package-modal-trigger
                                        class="row-action-item"
                                        data-row-menu-item
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Edit Package</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.packages.toggleActive', $package) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="row-action-item" type="submit" data-row-menu-item>
                                            <i class="bi bi-toggle-{{ $package->is_active ? 'off' : 'on' }}"></i>
                                            <span>{{ $package->is_active ? 'Deactivate Package' : 'Activate Package' }}</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Card view pagination --}}
            <div class="table-system-pagination directory-card-pagination">
                @if($packages->hasPages()){{ $packages->links() }}@endif
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════
         TABLE VIEW
    ═══════════════════════════════════════════ --}}
    <div x-show="view === 'table'" x-cloak>
        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table">
                    <thead>
                        <tr>
                            <th class="text-left">Name</th>
                            <th class="text-left">Coffin Type</th>
                            <th class="text-left">Price</th>
                            <th class="text-left">Inclusions</th>
                            <th class="text-left">Freebies</th>
                            <th class="text-left">Promo</th>
                            <th class="text-left">Status</th>
                            @if($isMainAdmin)
                                <th class="table-col-actions">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($packages as $package)
                        @php
                            $inclusionItems = $package->inclusionNames();
                            $freebieItems = $package->freebieNames();
                        @endphp
                        <tr>
                            <td class="table-primary">{{ $package->name }}</td>
                            <td>{{ $package->coffin_type ?? '—' }}</td>
                            <td>
                                @if($isMainAdmin)
                                    <form method="POST" action="{{ route('admin.packages.quickPrice', $package) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" step="0.01" min="0" name="price" value="{{ number_format($package->price, 2, '.', '') }}" class="admin-table-input-inline">
                                        <button class="admin-table-save-btn" type="submit">Save</button>
                                    </form>
                                @else
                                    <span class="font-semibold text-slate-900">&#8369;{{ number_format((float) $package->price, 2) }}</span>
                                @endif
                            </td>
                            <td class="table-secondary">
                                @if($inclusionItems)
                                    <ul class="space-y-1">
                                        @foreach(array_slice($inclusionItems, 0, 3) as $item)
                                            <li>&bull; {{ $item }}</li>
                                        @endforeach
                                    </ul>
                                    @if(count($inclusionItems) > 3)
                                        <span class="text-[11px] font-semibold text-slate-400">+{{ count($inclusionItems) - 3 }} more</span>
                                    @endif
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="table-secondary">
                                @if($freebieItems)
                                    <ul class="space-y-1">
                                        @foreach(array_slice($freebieItems, 0, 3) as $item)
                                            <li>&bull; {{ $item }}</li>
                                        @endforeach
                                    </ul>
                                    @if(count($freebieItems) > 3)
                                        <span class="text-[11px] font-semibold text-slate-400">+{{ count($freebieItems) - 3 }} more</span>
                                    @endif
                                @else
                                    None listed
                                @endif
                            </td>
                            <td>
                                @if($package->promo_is_active && $package->promo_value_type && $package->promo_value)
                                    <div class="text-xs font-semibold text-emerald-700">{{ $package->promo_label ?: 'Promo' }}</div>
                                    <div class="table-secondary">
                                        {{ $package->promo_value_type === 'PERCENT'
                                            ? number_format((float) $package->promo_value, 2) . '%'
                                            : number_format((float) $package->promo_value, 2) }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ $package->promo_starts_at?->format('Y-m-d H:i') ?? 'No start' }}
                                        &ndash;
                                        {{ $package->promo_ends_at?->format('Y-m-d H:i') ?? 'No end' }}
                                    </div>
                                @else
                                    <span class="table-secondary">No active promo</span>
                                @endif
                            </td>
                            <td>
                                @if($package->is_active)
                                    <span class="status-badge status-badge-success">Active</span>
                                @else
                                    <span class="status-badge status-badge-neutral">Inactive</span>
                                @endif
                            </td>
                            @if($isMainAdmin)
                                <td class="table-col-actions">
                                    <div class="row-action-menu" data-row-menu>
                                        <button
                                            type="button"
                                            class="row-action-trigger"
                                            data-row-menu-trigger
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            aria-label="Open row actions"
                                        >
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <div class="row-action-dropdown" role="menu">
                                            <a
                                                href="{{ route('admin.packages.edit', $package) }}"
                                                data-url="{{ route('admin.packages.edit', $package) }}"
                                                data-package-modal-trigger
                                                class="row-action-item"
                                                data-row-menu-item
                                            >
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit Package</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.packages.toggleActive', $package) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="row-action-item" type="submit" data-row-menu-item>
                                                    <i class="bi bi-toggle-{{ $package->is_active ? 'off' : 'on' }}"></i>
                                                    <span>{{ $package->is_active ? 'Deactivate Package' : 'Activate Package' }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isMainAdmin ? 8 : 7 }}" class="table-system-empty">No packages found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-system-pagination">
                @if($packages->hasPages()){{ $packages->links() }}@endif
            </div>
        </div>
    </div>

</section>
</div>{{-- end space-y-6 --}}
</div>{{-- end centered container --}}
</div>{{-- end admin-table-page --}}

{{-- Package modal --}}
<div id="packageModalOverlay" class="fixed inset-0 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-200 font-ui-body" style="z-index: 1300;">
    <div id="packageModalSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200 font-ui-body">
        <button id="packageModalClose" type="button" class="absolute top-4 right-4 z-10 inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-colors focus:outline-none shadow-sm">
            <i class="bi bi-x-lg" style="font-size:.8rem"></i>
        </button>
        <div id="packageModalContent" class="overflow-y-auto max-h-[84vh]" style="padding:1.5rem 2rem;">
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-6 h-6 rounded-full animate-spin" style="border:2px solid #e2e8f0;border-top-color:#475569"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function pkgCatalog() {
        return {
            view: localStorage.getItem('pkg-view') || 'card',
            setView(v) {
                this.view = v;
                localStorage.setItem('pkg-view', v);
            },
        };
    }

    (function () {
        const overlay  = document.getElementById('packageModalOverlay');
        const sheet    = document.getElementById('packageModalSheet');
        const content  = document.getElementById('packageModalContent');
        const closeBtn = document.getElementById('packageModalClose');
        if (!overlay || !sheet || !content) return;

        function openModal() {
            overlay.classList.remove('hidden');
            requestAnimationFrame(() => requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                sheet.classList.remove('scale-95', 'opacity-0');
                sheet.classList.add('scale-100', 'opacity-100');
            }));
        }

        function closeModal() {
            sheet.classList.add('scale-95', 'opacity-0');
            sheet.classList.remove('scale-100', 'opacity-100');
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.classList.add('hidden');
                content.innerHTML = '';
            }, 200);
        }

        async function loadModal(url) {
            openModal();
            content.innerHTML = '<div class="flex flex-col items-center justify-center py-16 gap-3"><div class="w-6 h-6 rounded-full animate-spin" style="border:2px solid #e2e8f0;border-top-color:#475569"></div></div>';
            try {
                const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const doc  = (new DOMParser()).parseFromString(html, 'text/html');
                const main = doc.querySelector('.page-content') || doc.body;
                content.innerHTML = main.innerHTML;
                content.querySelectorAll('script').forEach(old => {
                    const s = document.createElement('script');
                    s.textContent = old.textContent;
                    content.appendChild(s);
                });
            } catch (err) {
                content.innerHTML = '<p class="text-center text-rose-600 py-10 text-sm font-semibold">Failed to load. Please try again.</p>';
            }
        }

        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('[data-package-modal-trigger]');
            if (!trigger) return;
            e.preventDefault();
            const url = trigger.dataset.url || trigger.getAttribute('href');
            if (url) loadModal(url);
        });

        closeBtn?.addEventListener('click', closeModal);
        overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    })();
</script>
@endsection
