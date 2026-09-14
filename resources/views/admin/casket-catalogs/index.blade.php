@extends('layouts.panel')

@section('page_title', 'Casket Catalog')
@section('page_desc', 'Casket and coffin options used when building service packages.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $currentUser = auth()->user();
    $canManage = $currentUser?->isMainAdmin() ?? false;
    $hasFilters = filled(request('q')) || filled(request('status'));
@endphp
<div class="admin-table-page admin-catalog-page service-management-page casket-catalog-page px-4 sm:px-6 lg:px-8 py-6">
    <div class="mx-auto max-w-[1200px] space-y-5">
        <div class="management-toast no-print" role="status" aria-live="polite" data-page-context-toast>
            <i class="bi bi-box2-heart" aria-hidden="true"></i>
            <span>You are viewing casket and coffin records.</span>
        </div>

        @if(session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif

        @if($needsPrice->isNotEmpty())
            <div class="service-catalog-alert">
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                <span>Price not configured: {{ $needsPrice->pluck('display_name')->join(', ') }}</span>
            </div>
        @endif

        <section class="table-system-card admin-table-card">
            <div class="table-system-head">
                <div class="admin-table-head-row">
                    <div>
                        <h2 class="table-system-title">Casket Catalog</h2>
                        <p class="admin-table-head-copy">Casket and coffin options admins can select when building service packages.</p>
                    </div>
                    @if($canManage)
                        <a href="{{ route('admin.casket-catalogs.create', ['return_to' => request()->fullUrl()]) }}" class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] text-white inline-flex items-center gap-2">
                            <i class="bi bi-plus-circle"></i> Add Casket
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-system-toolbar">
                <form method="GET" class="table-toolbar" data-table-toolbar data-live-search-suggestions data-live-search-commit-only data-search-debounce="500" style="grid-template-columns:minmax(260px, 1.8fr) minmax(170px, .75fr) auto;">
                    <div class="table-toolbar-field">
                        <label class="table-toolbar-label">Search</label>
                        <div class="table-toolbar-input-wrap">
                            <i class="bi bi-search table-toolbar-leading-icon" aria-hidden="true"></i>
                            <input type="text" name="q" value="{{ request('q') }}" class="table-toolbar-search has-clear-action" data-table-search data-live-search-input placeholder="Search casket or material" autocomplete="off">
                            <button type="button" class="live-search-clear" data-live-search-clear aria-label="Clear search" @if(!filled(request('q'))) hidden @endif>
                                <i class="bi bi-x"></i>
                            </button>
                            <div class="live-search-results" data-live-search-results hidden></div>
                        </div>
                    </div>
                    <div class="table-toolbar-field">
                        <label class="table-toolbar-label">Status</label>
                        <div class="table-toolbar-select-wrap">
                            <i class="bi bi-activity table-toolbar-leading-icon" aria-hidden="true"></i>
                            <select name="status" class="table-toolbar-select" data-table-auto-submit>
                                <option value="">All Status</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>Archived</option>
                            </select>
                            <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="table-toolbar-reset-wrap">
                        <span class="table-toolbar-label opacity-0 select-none" aria-hidden="true">Actions</span>
                        <div class="filter-actions">
                            @if($canManage)
                                <a href="{{ route('admin.casket-catalogs.create', ['return_to' => request()->fullUrl()]) }}" class="btn btn-primary-custom btn-sm">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>Add Casket</span>
                                </a>
                            @endif
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
                            <th class="table-col-number">Reference Value</th>
                            <th>Status</th>
                            @if($canManage)
                                <th class="table-col-actions">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($catalogs as $catalog)
                            <tr data-live-search-row data-live-search-title="{{ $catalog->name }}" data-live-search-meta="{{ $catalog->type_or_material ?: 'No material specified' }}" data-live-search-text="{{ $catalog->name }} {{ $catalog->type_or_material }} {{ $catalog->is_active ? 'Active' : 'Archived' }}">
                                <td class="font-semibold catalog-primary-cell">
                                    <span data-catalog-cell="name">{{ $catalog->name }}</span>
                                </td>
                                <td>{{ $catalog->type_or_material ?: '—' }}</td>
                                <td class="table-col-number font-semibold" data-catalog-cell="price">
                                    @if((float) $catalog->standard_price > 0)
                                        &#8369;{{ number_format((float) $catalog->standard_price, 2) }}
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">Price not configured</span>
                                    @endif
                                </td>
                                <td data-catalog-cell="status">{{ $catalog->is_active ? 'Active' : 'Archived' }}</td>
                                @if($canManage)
                                    <td class="table-col-actions">
                                        <div class="row-hover-actions">
                                            <a class="row-hover-action" href="{{ route('admin.casket-catalogs.edit', ['casket_catalog' => $catalog, 'return_to' => request()->fullUrl()]) }}">
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.casket-catalogs.toggleActive', $catalog) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="row-hover-action {{ $catalog->is_active ? 'is-danger' : '' }}" type="submit">
                                                    <i class="bi bi-{{ $catalog->is_active ? 'archive' : 'arrow-counterclockwise' }}"></i>
                                                    <span>{{ $catalog->is_active ? 'Archive' : 'Restore' }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canManage ? 5 : 4 }}" class="text-center py-6 text-slate-500">No caskets found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="table-system-pagination">
                {{ $catalogs->links('pagination::simple-tailwind') }}
            </div>
        </section>
    </div>
</div>
@endsection
