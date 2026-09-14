@extends('layouts.panel')

@section('page_title','User Management')
@section('page_desc', 'Manage system users, roles, and account access.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $isBranchAdminView = auth()->user()?->isBranchAdmin() ?? false;
@endphp
<style>
[x-cloak] { display: none !important; }
.admin-table-page.directory-page {
    min-height:calc(100vh - 1rem);
    overflow:visible;
    background:
        linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
        repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px),
        #C4D2BE;
    background-size:44px 44px,44px 44px,16px 16px,auto;
}
.management-toast {
    position:fixed;
    top:1rem;
    right:1rem;
    z-index:1200;
    display:flex;
    align-items:center;
    gap:.55rem;
    max-width:calc(100vw - 2rem);
    border:1px solid #8EA083;
    border-radius:.75rem;
    background:#2F3A2E;
    color:#F7FAF3;
    padding:.72rem .9rem;
    font-size:.88rem;
    font-weight:650;
    line-height:1.35;
    box-shadow:none !important;
    pointer-events:none;
    animation:managementToastIn .18s ease-out, managementToastOut .22s ease-in 3.8s forwards;
}
.management-toast i {
    font-size:1rem;
    color:#DCE6D6;
}
@keyframes managementToastIn {
    from { opacity:0; transform:translateY(-.35rem); }
    to { opacity:1; transform:translateY(0); }
}
@keyframes managementToastOut {
    to { opacity:0; transform:translateY(-.35rem); visibility:hidden; }
}
.directory-page .table-system-card,
.directory-page .admin-table-card {
    background:transparent !important;
    border:0 !important;
    border-radius:0 !important;
    box-shadow:none !important;
    overflow:visible !important;
}
.directory-page .admin-table-card {
    display:flex;
    flex-direction:column;
    gap:0;
}
.directory-page .table-system-head {
    background:#D0DDC8 !important;
    border:1px solid #52654E !important;
    border-bottom:0 !important;
    border-radius:.35rem .35rem 0 0 !important;
    padding:.75rem !important;
    order:2;
}
.directory-page .admin-table-head-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:1rem;
    flex-wrap:wrap;
}
.directory-page .admin-table-head-row > div:first-child {
    display:none;
}
.directory-page .admin-table-head-row {
    justify-content:flex-start;
}
.directory-page .table-system-title {
    color:var(--ink);
    font-size:1.25rem;
    font-weight:750;
    letter-spacing:0;
    line-height:1.15;
}
.directory-page .admin-table-head-copy {
    color:var(--ink-muted);
    font-size:.88rem;
    font-weight:600;
    margin-top:.2rem;
}
.directory-page .admin-table-head-actions,
.directory-page .filter-actions {
    display:flex;
    align-items:center;
    gap:.6rem;
    flex-wrap:wrap;
}
.directory-page .admin-table-head-actions {
    width:100%;
    justify-content:space-between;
}
.directory-page .table-system-toolbar {
    background:#D0DDC8 !important;
    border:1px solid #52654E !important;
    border-radius:.35rem !important;
    padding:.75rem;
    margin-bottom:.85rem;
    order:1;
}
.directory-page .directory-card-view,
.directory-page .directory-table-view,
.directory-page .table-system-list {
    order:3;
    overflow:visible !important;
}
.directory-page .directory-table-view {
    margin-top:0 !important;
}
.directory-page .table-toolbar {
    display:flex;
    flex-wrap:wrap;
    gap:.65rem !important;
    align-items:center;
}
.directory-page .table-toolbar-label {
    display:none;
}
.directory-page .table-toolbar-search,
.directory-page .table-toolbar-select,
.directory-page .table-toolbar-sort {
    min-height:2.9rem !important;
    height:2.9rem !important;
    border-radius:.32rem !important;
    border:1px solid #52654E !important;
    background:#E9F0E4 !important;
    background-color:#E9F0E4 !important;
    color:#263026 !important;
    font-size:.92rem !important;
    font-weight:660 !important;
    box-shadow:none !important;
    cursor:pointer;
    appearance:none !important;
    -webkit-appearance:none !important;
    -moz-appearance:none !important;
    background-image:none !important;
    transition:background-color .16s ease,border-color .16s ease,color .16s ease;
}
.directory-page .table-toolbar-input-wrap,
.directory-page .table-toolbar-select-wrap {
    position:relative;
}
.directory-page .table-toolbar-field:first-child {
    flex:1 1 20rem;
    min-width:min(20rem, 100%);
}
.directory-page .table-toolbar-field:not(:first-child) {
    flex:0 0 14.75rem;
    min-width:14.75rem;
}
.directory-page .table-toolbar-reset-wrap { display:none !important; }
.directory-page .table-toolbar-input-wrap,
.directory-page .table-toolbar-select-wrap,
.directory-page .table-toolbar-field,
.directory-page .table-toolbar-search,
.directory-page .table-toolbar-select,
.directory-page .table-toolbar-sort {
    width:100%;
}
.directory-page .table-toolbar-search,
.directory-page .table-toolbar-select,
.directory-page .table-toolbar-sort {
    padding-left:2.6rem !important;
    padding-right:2.45rem !important;
}
.directory-page .table-toolbar-search { cursor:text; }
.directory-page .table-toolbar-select-wrap,
.directory-page .table-toolbar-select-wrap *,
.directory-page .filter-actions *,
.directory-page .admin-table-head-actions * {
    cursor:pointer;
}
.directory-page .table-toolbar-select-icon {
    pointer-events:none;
    position:absolute;
    right:1rem;
    top:50%;
    transform:translateY(-50%);
    color:#4F5E4C;
    font-size:.9rem;
}
.directory-page .table-toolbar-leading-icon {
    position:absolute;
    left:1rem;
    top:50%;
    transform:translateY(-50%);
    color:#4F5E4C;
    font-size:1rem;
    pointer-events:none;
}
.directory-page .table-toolbar-search:hover,
.directory-page .table-toolbar-select:hover,
.directory-page .table-toolbar-sort:hover {
    background:#DFE9D9 !important;
    background-color:#DFE9D9 !important;
    border-color:#2F3A2E !important;
}
.directory-page .table-toolbar-search:focus,
.directory-page .table-toolbar-select:focus,
.directory-page .table-toolbar-sort:focus {
    border-color:#1F2A20 !important;
    background:#EEF5E9 !important;
    background-color:#EEF5E9 !important;
    box-shadow:none !important;
}
.directory-page .btn-secondary,
.directory-page .btn-filter-reset,
.directory-page .btn-primary-custom {
    min-height:2.9rem;
    height:2.9rem;
    border-radius:.5rem;
    padding:0 .95rem;
    font-size:.84rem;
    font-weight:650;
    box-shadow:none !important;
    cursor:pointer;
    transition:background-color .16s ease,border-color .16s ease,color .16s ease;
}
.directory-page .btn-filter-reset {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.45rem;
    background:#E7EDE1;
    border:1px solid #B8C7AF;
    color:var(--ink);
    text-decoration:none;
}
.directory-page .btn-filter-reset,
.directory-page .btn-secondary {
    min-width:5.8rem;
    white-space:nowrap;
}
.directory-page .btn-filter-reset:hover,
.directory-page .btn-primary-custom:hover {
    background:#DDE8D6 !important;
    border-color:#8EA083 !important;
    color:var(--ink) !important;
}
.directory-page .btn-secondary,
.directory-page .btn-primary-custom {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.45rem;
    background:#344333 !important;
    border:1px solid #344333 !important;
    color:#fff !important;
}
.directory-page .filter-actions {
    min-height:2.9rem;
    padding:0;
    border:0;
    background:transparent;
}
.directory-page .filter-actions .btn-primary-custom {
    min-width:8.25rem;
}
.directory-page .btn-secondary:hover,
.directory-page .btn-primary-custom:hover {
    background:#2F3A2E !important;
    border-color:#2F3A2E !important;
    color:#fff !important;
}
.directory-page .admin-table-head-actions .btn-primary-custom {
    min-width:8.25rem;
    min-height:2.7rem;
    background:#344333 !important;
    border-color:#344333 !important;
    color:#fff !important;
    font-weight:650;
}
.directory-page .admin-table-head-actions .btn-primary-custom i {
    color:#F7FAF3;
}
.directory-page .admin-table-head-actions > .inline-flex {
    background:#E1E7D9 !important;
    border:1px solid var(--border) !important;
    border-radius:.65rem !important;
    overflow:hidden;
    box-shadow:none !important;
    min-height:2.7rem;
}
.directory-page .admin-table-head-actions > .inline-flex button {
    min-height:2.7rem;
    color:var(--ink);
    cursor:pointer;
    font-weight:650;
    background:transparent !important;
}
.directory-page .admin-table-head-actions > .inline-flex button:hover {
    background:#8EA083 !important;
    color:#F7FAF3 !important;
}
.directory-page .admin-table-head-actions > .inline-flex .bg-slate-900 {
    background:#344333 !important;
    color:#fff !important;
}
.directory-page .admin-table-head-actions > .inline-flex .bg-slate-900:hover {
    background:#2F3A2E !important;
    color:#fff !important;
}
.directory-page .directory-card-view,
.directory-page .table-system-list {
    background:#D0DDC8 !important;
    border:1px solid #93A28C !important;
    border-top:0 !important;
    border-radius:0 0 .55rem .55rem !important;
    padding:.75rem !important;
    overflow:visible !important;
}
.directory-page .directory-card-view > .grid {
    grid-template-columns:repeat(auto-fill, minmax(min(100%, 320px), 1fr));
    gap:18px;
}
.directory-page .directory-item-card {
    background:#DCE6D6 !important;
    border:1px solid #52654E !important;
    border-radius:.35rem !important;
    box-shadow:none !important;
    cursor:pointer;
    overflow:visible;
    position:relative;
}
.directory-page .directory-item-card::before {
    display:none !important;
}
.directory-page .directory-item-card:hover {
    background:#C7D5BE !important;
    border-color:#2F3A2E !important;
}
.directory-page .directory-user-card-head {
    background:#C7D5BE;
    border-bottom:1px solid #6F806B;
    border-radius:.3rem .3rem 0 0;
    margin:-1px -1px 0;
    padding:1rem 1rem .95rem;
}
.directory-page .directory-user-card-head h3 {
    font-size:1rem;
    line-height:1.25;
}
.directory-page .directory-user-role-strip {
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:.45rem;
    margin-bottom:.55rem;
}
.directory-page .directory-user-role-strip span {
    background:#E9F0E4 !important;
    border:1px solid #AEBFA6;
    color:#4D5A4A !important;
    font-size:.66rem !important;
    font-weight:650 !important;
    letter-spacing:.04em !important;
}
.directory-page .directory-user-card-actions {
    opacity:.72;
    visibility:visible;
    transform:translateY(0);
    transition:opacity .16s ease, transform .16s ease;
}
.directory-page .directory-item-card:hover .directory-user-card-actions,
.directory-page .directory-item-card:focus-within .directory-user-card-actions,
.directory-page .directory-user-card-menu[open] {
    opacity:1;
    visibility:visible;
    transform:translateY(-1px);
}
.directory-page .directory-user-card-menu {
    position:relative;
    display:inline-flex;
    justify-content:flex-end;
}
.directory-page .directory-user-card-menu summary {
    list-style:none;
}
.directory-page .directory-user-card-menu summary::-webkit-details-marker {
    display:none;
}
.directory-page .directory-user-card-menu[open] {
    z-index:170;
}
.directory-page .directory-user-card-menu[open] .directory-user-card-dropdown {
    display:block;
}
.directory-page .directory-user-card-dropdown {
    position:absolute;
    right:0;
    bottom:calc(100% + .45rem);
    display:none;
    min-width:12rem;
    border:1px solid #93A28C;
    border-radius:.55rem;
    background:#E1E7D9;
    padding:.35rem;
    z-index:180;
}
.directory-page .directory-item-card .border-t {
    border-color:var(--border) !important;
}
.directory-page .directory-item-card h3,
.directory-page .table-primary {
    color:var(--ink) !important;
    font-weight:680;
}
.directory-page .directory-item-card p,
.directory-page .directory-item-card .text-slate-500,
.directory-page .directory-item-card .text-slate-400,
.directory-page .table-secondary {
    color:var(--ink-muted) !important;
    font-weight:600;
}
.directory-page .row-action-trigger {
    width:auto !important;
    min-width:6rem;
    min-height:2.35rem;
    gap:.45rem;
    border-radius:.75rem !important;
    background:#E1E7D9 !important;
    border:1px solid var(--border) !important;
    color:var(--ink) !important;
    box-shadow:none !important;
    cursor:pointer;
    font-size:.78rem;
    font-weight:700;
    padding:0 .75rem;
}
.directory-page .row-action-trigger::after {
    content:"Actions";
}
.directory-page .row-action-trigger:hover,
.directory-page .row-action-item:hover {
    background:#C7D5BE !important;
    color:var(--ink) !important;
}
.directory-page .row-action-dropdown {
    min-width:12rem;
    border:1px solid #B5C4AD !important;
    border-radius:.75rem !important;
    background:#E1E7D9 !important;
    box-shadow:none !important;
    padding:.35rem !important;
}
.directory-page .row-action-item {
    border-radius:.6rem !important;
    color:var(--ink) !important;
    font-size:.8rem !important;
    font-weight:680 !important;
    padding:.6rem .7rem !important;
}
.directory-page .row-action-item i {
    color:#566653 !important;
}
.directory-page .table-system-wrap,
.directory-page .table-system-table {
    background:#DCE6D6 !important;
    box-shadow:none !important;
}
.directory-page .table-system-wrap {
    margin:0 !important;
    border:1.25px solid var(--border);
    border-radius:.75rem;
    overflow-x:auto;
    overflow-y:visible;
}
.directory-page .table-system-table thead th {
    background:#C7D5BE !important;
    color:var(--ink-muted) !important;
    font-weight:650;
    font-size:.72rem;
    letter-spacing:.05em;
}
.directory-page .directory-item-card [class*="tracking-widest"],
.directory-page .table-system-table tbody td {
    color:var(--ink) !important;
}
.directory-page .table-system-table tbody tr:hover {
    background:#C7D5BE !important;
}
@media(max-width:900px) {
    .directory-page .table-toolbar { grid-template-columns:1fr !important; }
    .directory-page .admin-table-head-actions,
    .directory-page .filter-actions { width:100%; }
    .directory-page .filter-actions > * { flex:1; }
}
</style>

<div class="admin-table-page directory-page" x-data="userCatalog()">
<div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8 pt-4 pb-6">
<div class="space-y-4">

@if (session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="flash-error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        {{ $errors->first() }}
    </div>
@endif

<div class="management-toast no-print" role="status" aria-live="polite" data-page-context-toast>
    <i class="bi bi-people"></i>
    <span>You are viewing User Management.</span>
</div>

<section class="table-system-card admin-table-card">
    <div class="table-system-head">
        <div class="admin-table-head-row">
            <div>
                <h2 class="table-system-title">User Directory</h2>
                <p class="admin-table-head-copy">Manage user profiles, role access, branch assignment, and activation status.</p>
            </div>
            <div class="admin-table-head-actions">
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
                <a
                    href="{{ route('admin.users.create', ['return_to' => request()->fullUrl()]) }}"
                    class="btn btn-primary-custom btn-sm"
                >
                    <i class="bi bi-plus-circle"></i>
                    <span>Add User</span>
                </a>
            </div>
        </div>
    </div>

    <div class="table-system-toolbar">
        <form
            method="GET"
            action="{{ route('admin.users.index') }}"
            class="table-toolbar"
            data-table-toolbar
            data-live-search-suggestions
            data-live-search-commit-only
            data-search-debounce="400"
            style="grid-template-columns: minmax(260px, 2.2fr) repeat(4, minmax(150px, 1fr)) auto;"
        >
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Search</label>
                <div class="table-toolbar-input-wrap">
                    <i class="bi bi-search table-toolbar-leading-icon" aria-hidden="true"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search users..."
                        class="form-input table-toolbar-search has-clear-action"
                        data-table-search
                        data-live-search-input
                        autocomplete="off"
                    >
                    <button type="button" class="live-search-clear" data-live-search-clear aria-label="Clear search" @if(!filled(request('q'))) hidden @endif>
                        <i class="bi bi-x"></i>
                    </button>
                    <div class="live-search-results" data-live-search-results hidden></div>
                </div>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Branch</label>
                <div class="table-toolbar-select-wrap">
                    <i class="bi bi-building table-toolbar-leading-icon" aria-hidden="true"></i>
                    @if(($branches ?? collect())->isNotEmpty())
                        <select name="branch_id" class="form-select table-toolbar-select" data-table-auto-submit>
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) ($selectedBranchId ?? '') === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->branch_code }} - {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <select class="form-select table-toolbar-select" disabled>
                            <option>
                                {{ trim(($assignedBranch?->branch_code ?? 'Assigned') . ' - ' . ($assignedBranch?->branch_name ?? 'Branch')) }}
                            </option>
                        </select>
                    @endif
                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                </div>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Role</label>
                <div class="table-toolbar-select-wrap">
                    <i class="bi bi-person-badge table-toolbar-leading-icon" aria-hidden="true"></i>
                    @if($isBranchAdminView)
                        <select class="form-select table-toolbar-select" disabled aria-label="Role filter locked to staff">
                            <option>Staff Only</option>
                        </select>
                    @else
                        <select name="role" class="form-select table-toolbar-select" data-table-auto-submit>
                            <option value="">All Roles</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                    @endif
                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                </div>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Status</label>
                <div class="table-toolbar-select-wrap">
                    <i class="bi bi-activity table-toolbar-leading-icon" aria-hidden="true"></i>
                    <select name="status" class="form-select table-toolbar-select" data-table-auto-submit>
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                </div>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Sort</label>
                <div class="table-toolbar-select-wrap">
                    <i class="bi bi-sort-alpha-down table-toolbar-leading-icon" aria-hidden="true"></i>
                    <select name="sort" class="form-select table-toolbar-sort" data-table-sort>
                        <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Newest</option>
                        <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>Name</option>
                        <option value="role_asc" {{ request('sort') === 'role_asc' ? 'selected' : '' }}>Role</option>
                        <option value="branch_asc" {{ request('sort') === 'branch_asc' ? 'selected' : '' }}>Branch</option>
                    </select>
                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                </div>
            </div>
            <div class="table-toolbar-reset-wrap">
            </div>
        </form>
    </div>

    <div
        x-show="view === 'card'"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="directory-card-view"
    >
        @if($users->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                    <i class="bi bi-people text-2xl text-slate-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-sm">No users found</h3>
                    <p class="text-xs text-slate-500 mt-1">Try changing the search or filters, or add a new user.</p>
                </div>
                <a
                    href="{{ route('admin.users.create', ['return_to' => request()->fullUrl()]) }}"
                    class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] text-white inline-flex items-center gap-1.5 mt-1"
                >
                    <i class="bi bi-plus-circle"></i>
                    Add User
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($users as $user)
                    <div
                        class="directory-item-card bg-white border border-slate-200 rounded-2xl transition-colors duration-200 flex flex-col"
                        data-live-search-row
                        data-live-search-title="{{ $user->name }}"
                        data-live-search-meta="{{ $user->roleLabel() }} / {{ $user->branch->branch_name ?? 'No branch assigned' }}"
                        data-live-search-text="{{ $user->name }} {{ $user->email }} {{ $user->roleLabel() }} {{ $user->branch->branch_name ?? '' }} {{ $user->position }} {{ $user->contact_number }} {{ $user->is_active ? 'Active' : 'Inactive' }}"
                    >
                        <div class="directory-user-card-head flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="directory-user-role-strip">
                                    <span class="inline-flex items-center rounded-lg bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-widest px-2 py-0.5">
                                        {{ $user->roleLabel() }}
                                    </span>
                                    @if($user->admin_scope === 'main')
                                        <span class="inline-flex items-center rounded-lg bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-widest px-2 py-0.5">
                                            <i class="bi bi-star-fill text-[8px] mr-1"></i>Main
                                        </span>
                                    @endif
                                </div>
                                <h3 class="font-bold text-slate-900 text-[15px] leading-snug truncate">{{ $user->name }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5 truncate">{{ $user->email }}</p>
                            </div>
                        </div>

                        <div class="px-5 pb-4 pt-4 border-t border-slate-100 space-y-2 text-xs text-slate-500">
                            <div class="flex items-start gap-2">
                                <i class="bi bi-building text-[11px] mt-0.5 text-slate-400"></i>
                                <span class="truncate">{{ $user->branch->branch_name ?? 'No branch assigned' }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="bi bi-person-badge text-[11px] mt-0.5 text-slate-400"></i>
                                <span class="truncate">{{ $user->position ?? 'No position set' }}</span>
                            </div>
                            <div class="flex items-start gap-2">
                                <i class="bi bi-telephone text-[11px] mt-0.5 text-slate-400"></i>
                                <span class="truncate">{{ $user->contact_number ?? 'No contact number' }}</span>
                            </div>
                        </div>

                        <div class="directory-user-card-actions px-5 py-3 border-t border-slate-100 flex items-center justify-end gap-2 mt-auto">
                            <details class="directory-user-card-menu" data-row-menu>
                                <summary
                                    class="row-action-trigger"
                                    aria-haspopup="menu"
                                    aria-label="Open user actions"
                                >
                                    <i class="bi bi-three-dots-vertical"></i>
                                </summary>
                                <div class="directory-user-card-dropdown" role="menu">
                                    <a
                                        class="row-action-item"
                                        data-row-menu-item
                                        href="{{ route('admin.users.edit', ['user' => $user, 'return_to' => request()->fullUrl()]) }}"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Edit user</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.users.toggleActive', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="row-action-item" type="submit" data-row-menu-item>
                                            <i class="bi bi-toggle-{{ $user->is_active ? 'off' : 'on' }}"></i>
                                            <span>{{ $user->is_active ? 'Deactivate user' : 'Activate user' }}</span>
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="table-system-pagination directory-card-pagination">
                @if($users->hasPages()){{ $users->links() }}@endif
            </div>
        @endif
    </div>

    <div
        x-show="view === 'table'"
        x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="directory-table-view"
>
        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table">
                    <thead>
                        <tr>
                            <th class="text-left">Name</th>
                            <th class="text-left">Email</th>
                            <th class="text-left">Role</th>
                            <th class="text-left">Branch</th>
                            <th class="text-left">Position</th>
                            <th class="text-left">Contact</th>
                            <th class="text-left">Status</th>
                            <th class="table-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr
                                data-live-search-row
                                data-live-search-title="{{ $user->name }}"
                                data-live-search-meta="{{ $user->roleLabel() }} / {{ $user->branch->branch_name ?? 'No branch assigned' }}"
                                data-live-search-text="{{ $user->name }} {{ $user->email }} {{ $user->roleLabel() }} {{ $user->branch->branch_name ?? '' }} {{ $user->position }} {{ $user->contact_number }} {{ $user->is_active ? 'Active' : 'Inactive' }}"
                            >
                                <td class="table-primary">{{ $user->name }}</td>
                                <td class="table-secondary">{{ $user->email }}</td>
                                <td>{{ $user->roleLabel() }}</td>
                                <td>{{ $user->branch->branch_name ?? '-' }}</td>
                                <td class="table-secondary">{{ $user->position ?? '-' }}</td>
                                <td>{{ $user->contact_number ?? '-' }}</td>
                                <td>
                                    @if($user->is_active)
                                        <span class="status-badge status-badge-success">Active</span>
                                    @else
                                        <span class="status-badge status-badge-danger">Inactive</span>
                                    @endif
                                </td>
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
                                                class="row-action-item"
                                                data-row-menu-item
                                                href="{{ route('admin.users.edit', ['user' => $user, 'return_to' => request()->fullUrl()]) }}"
                                            >
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit user</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.users.toggleActive', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="row-action-item" type="submit" data-row-menu-item>
                                                    <i class="bi bi-toggle-{{ $user->is_active ? 'off' : 'on' }}"></i>
                                                    <span>{{ $user->is_active ? 'Deactivate user' : 'Activate user' }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="table-system-empty">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-system-pagination">
                @if($users->hasPages()){{ $users->links() }}@endif
            </div>
        </div>
    </div>
</section>

</div>
</div>
</div>

<script>
function userCatalog() {
    return {
        view: 'card',
        init() {
            const saved = localStorage.getItem('user_view');
            if (saved === 'card' || saved === 'table') this.view = saved;
        },
        setView(v) {
            this.view = v;
            localStorage.setItem('user_view', v);
        },
    };  
}

document.addEventListener('click', (event) => {
    document.querySelectorAll('.directory-user-card-menu[open]').forEach((menu) => {
        if (!menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
});

</script>
@endsection
