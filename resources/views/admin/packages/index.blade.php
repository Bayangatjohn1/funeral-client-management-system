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
<div class="admin-table-page" x-data="pkgCatalog()">
<div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8 py-6">
<div class="space-y-6">

@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

{{-- Summary stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Total Packages</span>
        <span class="text-2xl font-bold text-slate-900">{{ $totalPackages }}</span>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Active</span>
        <span class="text-2xl font-bold text-emerald-600">{{ $activePackages }}</span>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">With Promo</span>
        <span class="text-2xl font-bold text-amber-500">{{ $promoPackages }}</span>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Highest Price</span>
        <span class="text-2xl font-bold text-slate-900">&#8369;{{ number_format($highestPrice, 2) }}</span>
    </div>
</div>

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
    <div x-show="view === 'card'" x-cloak class="p-6" style="border-top: 1px solid var(--border)">

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
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Card view pagination --}}
            <div class="mt-6">
                {{ $packages->links() }}
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
        </div>
        <div class="table-system-pagination">
            {{ $packages->links() }}
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
        <div id="packageModalContent" class="overflow-y-auto max-h-[84vh] p-0 bg-white">
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
// Package catalog: card/table view toggle persisted in localStorage
function pkgCatalog() {
    return {
        view: 'card',
        init() {
            const saved = localStorage.getItem('pkg_view');
            if (saved === 'card' || saved === 'table') this.view = saved;
        },
        setView(v) {
            this.view = v;
            localStorage.setItem('pkg_view', v);
        },
    };
}

// Package create/edit modal
(() => {
    const overlay = document.getElementById('packageModalOverlay');
    const sheet   = document.getElementById('packageModalSheet');
    const content = document.getElementById('packageModalContent');
    const closeBtn = document.getElementById('packageModalClose');
    const links = [...document.querySelectorAll('[data-package-modal-trigger]')];

    const normalizeEmbeddedForm = (form) => {
        form.classList.remove('mx-auto', 'max-w-4xl', 'max-w-3xl');
        form.classList.add('w-full');
        form.querySelectorAll('.modal-shell-card').forEach((card) => {
            card.classList.remove('rounded-2xl', 'border', 'border-slate-200', 'shadow-sm');
            card.classList.add('rounded-none', 'border-0', 'shadow-none', 'bg-transparent');
        });
    };

    const lockScroll = () => {
        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');
    };
    const unlockScroll = () => {
        document.documentElement.classList.remove('overflow-hidden');
        document.body.classList.remove('overflow-hidden');
    };

    const show = () => {
        overlay.classList.remove('hidden');
        lockScroll();
        requestAnimationFrame(() => {
            sheet.classList.remove('scale-95', 'opacity-0');
            sheet.classList.add('scale-100', 'opacity-100');
            overlay.classList.add('opacity-100');
        });
    };

    const hide = () => {
        sheet.classList.add('scale-95', 'opacity-0');
        sheet.classList.remove('scale-100', 'opacity-100');
        overlay.classList.remove('opacity-100');
        setTimeout(() => {
            overlay.classList.add('hidden');
            unlockScroll();
            content.innerHTML = `
                <div class="flex flex-col items-center justify-center py-16 gap-3">
                    <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                    <span class="text-sm text-slate-400">Loading...</span>
                </div>`;
        }, 180);
    };

    const load = async (url) => {
        content.innerHTML = `
            <div class="flex items-center justify-center py-8 text-slate-500 gap-2 text-sm">
                <i class="bi bi-arrow-repeat animate-spin"></i>
                <span>Loading...</span>
            </div>`;
        try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            const doc  = new DOMParser().parseFromString(html, 'text/html');
            const form = doc.querySelector('#packageCreateForm, #packageEditForm');
            if (form) {
                content.innerHTML = form.outerHTML;
                const embeddedForm = content.querySelector('#packageCreateForm, #packageEditForm');
                if (embeddedForm) normalizeEmbeddedForm(embeddedForm);
                [...doc.querySelectorAll('script')].forEach((old) => {
                    const s = document.createElement('script');
                    if (old.src) s.src = old.src; else s.textContent = old.textContent;
                    content.appendChild(s);
                });
            } else {
                content.innerHTML = html;
            }
        } catch {
            content.innerHTML = `<div class="p-4 text-sm text-rose-600">Unable to load content.</div>`;
        }
    };

    links.forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            show();
            load(link.dataset.url || link.href);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', hide);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) hide(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !overlay.classList.contains('hidden')) hide();
    });
})();
</script>
@endsection
