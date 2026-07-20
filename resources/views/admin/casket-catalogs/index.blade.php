@extends('layouts.panel')

@section('page_title', 'Casket Catalog')
@section('page_desc', 'Casket reference information used for packages and upgrades.')

@section('content')
@php
    $currentUser = auth()->user();
    $canManage = $currentUser?->isMainAdmin() ?? false;
    $hasFilters = filled(request('q')) || filled(request('status'));
@endphp
<div class="admin-table-page admin-catalog-page px-4 sm:px-6 lg:px-8 py-6">
    <div class="mx-auto max-w-[1200px] space-y-5">
        @if(session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif

        @if($needsPrice->isNotEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                Price not configured:
                {{ $needsPrice->pluck('display_name')->join(', ') }}
            </div>
        @endif

        <section class="table-system-card admin-table-card">
            <div class="table-system-head">
                <div class="admin-table-head-row">
                    <div>
                        <h2 class="table-system-title">Casket Catalog</h2>
                        <p class="admin-table-head-copy">Casket reference information used for packages and upgrades.</p>
                    </div>
                    @if($canManage)
                        <button type="button" class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] text-white inline-flex items-center gap-2" data-service-catalog-create data-catalog-kind="casket" data-store-url="{{ route('admin.casket-catalogs.store', absolute: false) }}">
                            <i class="bi bi-plus-circle"></i> Add Casket
                        </button>
                    @endif
                </div>
            </div>

            <div class="table-system-toolbar">
                <form method="GET" class="table-toolbar">
                    <div class="table-toolbar-field">
                        <label class="table-toolbar-label">Search</label>
                        <input type="text" name="q" value="{{ request('q') }}" class="table-toolbar-search" placeholder="Search casket or material">
                    </div>
                    <div class="table-toolbar-field">
                        <label class="table-toolbar-label">Status</label>
                        <select name="status" class="table-toolbar-select">
                            <option value="">All status</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Archived</option>
                        </select>
                    </div>
                    <div class="table-toolbar-reset-wrap">
                        <span class="table-toolbar-label opacity-0 select-none" aria-hidden="true">Actions</span>
                        <div class="filter-actions">
                            @if($hasFilters)
                                <a href="{{ route('admin.casket-catalogs.index') }}" class="btn-outline btn-filter-reset"><i class="bi bi-arrow-counterclockwise"></i><span>Reset</span></a>
                            @else
                                <button type="button" class="btn-outline btn-filter-reset opacity-50 cursor-not-allowed" disabled aria-disabled="true"><i class="bi bi-arrow-counterclockwise"></i><span>Reset</span></button>
                            @endif
                            <button class="btn btn-secondary btn-sm"><i class="bi bi-funnel"></i><span>Apply</span></button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-system-wrap">
                <table class="table-base table-system-table w-full">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Material</th>
                            <th class="text-right">Reference Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($catalogs as $catalog)
                            <tr tabindex="0" data-catalog-row data-catalog-kind="casket" data-id="{{ $catalog->id }}" data-name="{{ $catalog->name }}" data-material="{{ $catalog->type_or_material }}" data-price="{{ (float) $catalog->standard_price }}" data-description="{{ $catalog->description }}" data-status="{{ $catalog->is_active ? 'Active' : 'Archived' }}" data-active="{{ $catalog->is_active ? '1' : '0' }}" data-update-url="{{ route('admin.casket-catalogs.update', $catalog, absolute: false) }}" data-toggle-url="{{ route('admin.casket-catalogs.toggleActive', $catalog, absolute: false) }}">
                                <td class="font-semibold catalog-primary-cell">
                                    <span data-catalog-cell="name">{{ $catalog->name }}</span>
                                    <button type="button" class="catalog-view-btn" data-catalog-view aria-label="View {{ $catalog->name }}"><i class="bi bi-eye"></i> View</button>
                                </td>
                                <td>{{ $catalog->type_or_material ?: '—' }}</td>
                                <td class="text-right font-semibold" data-catalog-cell="price">
                                    @if((float) $catalog->standard_price > 0)
                                        &#8369;{{ number_format((float) $catalog->standard_price, 2) }}
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">Price not configured</span>
                                    @endif
                                </td>
                                <td data-catalog-cell="status">{{ $catalog->is_active ? 'Active' : 'Archived' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-6 text-slate-500">No caskets found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-system-pagination">
                {{ $catalogs->links() }}
            </div>
        </section>
    </div>
</div>
@include('admin.shared._catalog-modal', ['canManage' => $canManage])
@endsection
