@extends('layouts.panel')

@section('page_title', 'Freebies Configuration')
@section('page_desc', 'Manage reusable freebies for service package setup.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $currentUser = auth()->user();
    $canManage = $currentUser?->isMainAdmin() ?? false;
    $hasFilters = filled(request('q')) || filled(request('status')) || filled(request('sort'));
@endphp

<div class="admin-table-page admin-catalog-page service-management-page freebie-catalog-page px-4 sm:px-6 lg:px-8 py-6">
    <div class="mx-auto max-w-[1100px] space-y-5">
        <div class="management-toast no-print" role="status" aria-live="polite">
            <i class="bi bi-gift" aria-hidden="true"></i>
            <span>You are viewing Freebies Configuration.</span>
        </div>

        @if(session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="flash-error">{{ $errors->first() }}</div>
        @endif

        <section class="table-system-card admin-table-card">
            <div class="table-system-head">
                <div class="admin-table-head-row">
                    <div>
                        <h2 class="table-system-title">Freebies Configuration</h2>
                        <p class="admin-table-head-copy">Reusable freebie items that admins can select when creating or editing service packages.</p>
                    </div>
                    @if($canManage)
                        <a href="{{ route('admin.freebie-catalogs.create', ['return_to' => request()->fullUrl()]) }}" class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] text-white inline-flex items-center gap-2">
                            <i class="bi bi-plus-circle"></i>
                            <span>Add Freebie</span>
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-system-toolbar">
                <form method="GET" class="table-toolbar" data-table-toolbar data-live-search-suggestions data-live-search-commit-only data-search-debounce="500" style="grid-template-columns:minmax(260px, 1.8fr) minmax(150px, .75fr) minmax(150px, .75fr) auto;">
                    <div class="table-toolbar-field">
                        <label class="table-toolbar-label">Search</label>
                        <div class="table-toolbar-input-wrap">
                            <i class="bi bi-search table-toolbar-leading-icon" aria-hidden="true"></i>
                            <input type="text" name="q" value="{{ request('q') }}" class="table-toolbar-search has-clear-action" data-table-search data-live-search-input placeholder="Search freebie or unit" autocomplete="off">
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
                    <div class="table-toolbar-field">
                        <label class="table-toolbar-label">Sort</label>
                        <div class="table-toolbar-select-wrap">
                            <i class="bi bi-sort-alpha-down table-toolbar-leading-icon" aria-hidden="true"></i>
                            <select name="sort" class="table-toolbar-select" data-table-auto-submit>
                                <option value="name_asc" @selected(request('sort', 'name_asc') === 'name_asc')>Name</option>
                                <option value="latest" @selected(request('sort') === 'latest')>Newest</option>
                                <option value="usage_desc" @selected(request('sort') === 'usage_desc')>Most Used</option>
                            </select>
                            <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div class="table-toolbar-reset-wrap">
                        <span class="table-toolbar-label opacity-0 select-none" aria-hidden="true">Actions</span>
                        <div class="filter-actions">
                            @if($canManage)
                                <a href="{{ route('admin.freebie-catalogs.create', ['return_to' => request()->fullUrl()]) }}" class="btn btn-primary-custom btn-sm">
                                    <i class="bi bi-plus-circle"></i>
                                    <span>Add Freebie</span>
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
                            <th>Default Unit</th>
                            <th>Used In Packages</th>
                            <th>Status</th>
                            @if($canManage)
                                <th class="table-col-actions">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($catalogs as $catalog)
                            <tr data-live-search-row data-live-search-title="{{ $catalog->name }}" data-live-search-meta="{{ ($catalog->default_unit ?: 'item') . ' / ' . ($catalog->is_active ? 'Active' : 'Archived') }}" data-live-search-text="{{ $catalog->name }} {{ $catalog->default_unit }} {{ $catalog->is_active ? 'Active' : 'Archived' }}">
                                <td>
                                    <div class="font-semibold catalog-primary-cell">
                                        <i class="bi bi-gift text-[#3E4A3D] mr-2"></i>
                                        {{ $catalog->name }}
                                    </div>
                                </td>
                                <td>{{ $catalog->default_unit ?: 'item' }}</td>
                                <td>
                                    @if($catalog->package_freebies_count > 0)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">
                                            {{ $catalog->package_freebies_count }} package{{ $catalog->package_freebies_count === 1 ? '' : 's' }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">Not used yet</span>
                                    @endif
                                </td>
                                <td>{{ $catalog->is_active ? 'Active' : 'Archived' }}</td>
                                @if($canManage)
                                    <td class="table-col-actions">
                                        <div class="row-hover-actions">
                                            <a class="row-hover-action" href="{{ route('admin.freebie-catalogs.edit', ['freebie_catalog' => $catalog, 'return_to' => request()->fullUrl()]) }}">
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.freebie-catalogs.toggleActive', $catalog) }}">
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
                            <tr>
                                <td colspan="{{ $canManage ? 5 : 4 }}" class="text-center py-6 text-slate-500">No freebies found.</td>
                            </tr>
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
