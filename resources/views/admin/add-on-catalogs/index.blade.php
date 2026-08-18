@extends('layouts.panel')

@section('page_title', 'Add-on Catalog')
@section('page_desc', 'Reusable optional add-ons for future intake selections.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $currentUser = auth()->user();
    $canManage = $currentUser?->isMainAdmin() ?? false;
    $categoryOptions = $categoryOptions ?? \App\Models\AddOnCatalog::CATEGORY_OPTIONS;
    $hasFilters = filled(request('q')) || filled(request('status')) || filled(request('category'));
@endphp
<div class="admin-table-page admin-catalog-page service-management-page add-on-catalog-page px-4 sm:px-6 lg:px-8 py-6">
    <div class="mx-auto max-w-[1200px] space-y-5">
        <div class="management-toast no-print" role="status" aria-live="polite">
            <i class="bi bi-plus-square" aria-hidden="true"></i>
            <span>You are viewing optional add-on records.</span>
        </div>

        @if(session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif

        <section class="table-system-card admin-table-card">
            <div class="table-system-head">
                <div class="admin-table-head-row">
                    <div>
                        <h2 class="table-system-title">Add-on Catalog</h2>
                        <p class="admin-table-head-copy">Reusable optional add-ons grouped by predefined type, separate from package inclusions.</p>
                    </div>
                    @if($canManage)
                        <a href="{{ route('admin.add-on-catalogs.create', ['return_to' => request()->fullUrl()]) }}" class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] text-white inline-flex items-center gap-2">
                            <i class="bi bi-plus-circle"></i> Add Add-on
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-system-toolbar">
                <form method="GET" class="table-toolbar" data-table-toolbar data-live-search-suggestions data-live-search-commit-only data-search-debounce="500" style="grid-template-columns:minmax(240px, 1.6fr) minmax(170px, .85fr) minmax(170px, .75fr) auto;">
                    <div class="table-toolbar-field">
                        <label class="table-toolbar-label">Search</label>
                        <div class="table-toolbar-input-wrap">
                            <i class="bi bi-search table-toolbar-leading-icon" aria-hidden="true"></i>
                            <input type="text" name="q" value="{{ request('q') }}" class="table-toolbar-search has-clear-action" data-table-search data-live-search-input placeholder="Search add-on, type, or unit" autocomplete="off">
                            <button type="button" class="live-search-clear" data-live-search-clear aria-label="Clear search" @if(!filled(request('q'))) hidden @endif>
                                <i class="bi bi-x"></i>
                            </button>
                            <div class="live-search-results" data-live-search-results hidden></div>
                        </div>
                    </div>
                    <div class="table-toolbar-field">
                        <label class="table-toolbar-label">Type</label>
                        <div class="table-toolbar-select-wrap">
                            <i class="bi bi-tags table-toolbar-leading-icon" aria-hidden="true"></i>
                            <select name="category" class="table-toolbar-select" data-table-auto-submit>
                                <option value="">All Types</option>
                                @foreach($categoryOptions as $category)
                                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                            <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
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
                                <a href="{{ route('admin.add-on-catalogs.create', ['return_to' => request()->fullUrl()]) }}" class="btn btn-primary-custom btn-sm">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>Add Add-on</span>
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
                            <th>Type</th>
                            <th class="table-col-number">Standard Price</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Legacy Mapping</th>
                            @if($canManage)
                                <th class="table-col-actions">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($catalogs as $catalog)
                            <tr data-live-search-row data-live-search-title="{{ $catalog->name }}" data-live-search-meta="{{ ($catalog->category ?: 'General') . ' / ' . $catalog->unit }}" data-live-search-text="{{ $catalog->name }} {{ $catalog->category }} {{ $catalog->unit }} {{ $catalog->description }} {{ $catalog->is_active ? 'Active' : 'Archived' }}">
                                <td>
                                    <div class="font-semibold catalog-primary-cell">
                                        <span data-catalog-cell="name">{{ $catalog->name }}</span>
                                    </div>
                                    @if($catalog->description)
                                        <div class="text-xs text-slate-500 mt-1" data-catalog-cell="description">{{ $catalog->description }}</div>
                                    @else
                                        <div class="text-xs text-slate-500 mt-1 hidden" data-catalog-cell="description"></div>
                                    @endif
                                </td>
                                <td data-catalog-cell="category">{{ $catalog->category ?: 'General' }}</td>
                                <td class="table-col-number font-semibold" data-catalog-cell="price">&#8369;{{ number_format((float) $catalog->price, 2) }}</td>
                                <td data-catalog-cell="unit">{{ $catalog->unit }}</td>
                                <td data-catalog-cell="status">{{ $catalog->is_active ? 'Active' : 'Archived' }}</td>
                                <td>
                                    @if($catalog->legacy_package_add_ons_count > 0)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">{{ $catalog->legacy_package_add_ons_count }} legacy record{{ $catalog->legacy_package_add_ons_count === 1 ? '' : 's' }}</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                @if($canManage)
                                    <td class="table-col-actions">
                                        <div class="row-hover-actions">
                                            <a class="row-hover-action" href="{{ route('admin.add-on-catalogs.edit', ['add_on_catalog' => $catalog, 'return_to' => request()->fullUrl()]) }}">
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.add-on-catalogs.toggleActive', $catalog) }}">
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
                            <tr><td colspan="{{ $canManage ? 7 : 6 }}" class="text-center py-6 text-slate-500">No add-ons found.</td></tr>
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
