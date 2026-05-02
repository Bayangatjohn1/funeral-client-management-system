@extends('layouts.panel')

@section('page_title','User Management')
@section('page_desc', 'Manage system users, roles, and account access.')

@section('content')
<style>[x-cloak] { display: none !important; }</style>

<div class="admin-table-page directory-page" x-data="userCatalog()">
<div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8 py-6">
<div class="space-y-6">

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
                    class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white inline-flex items-center gap-2"
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
            data-search-debounce="400"
            style="grid-template-columns: minmax(260px, 2.2fr) repeat(3, minmax(150px, 1fr)) auto;"
        >
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Search</label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search users..."
                    class="form-input table-toolbar-search"
                    data-table-search
                    autocomplete="off"
                >
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Role</label>
                <div class="table-toolbar-select-wrap">
                    <select name="role" class="form-select table-toolbar-select" data-table-auto-submit>
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                </div>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Status</label>
                <div class="table-toolbar-select-wrap">
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
                <span class="table-toolbar-label opacity-0 select-none" aria-hidden="true">Actions</span>
                <div class="filter-actions">
                    <a href="{{ route('admin.users.index') }}" class="btn-outline btn-filter-reset">
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

    <div
        x-show="view === 'card'"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="mt-4 p-6"
        style="border-top: 1px solid var(--border)"
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
                    <div class="directory-item-card bg-white border border-slate-200 rounded-2xl transition-colors duration-200 flex flex-col">
                        <div class="p-5 flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5 mb-2">
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
                            <div class="flex-shrink-0">
                                @if($user->is_active)
                                    <span class="status-badge status-badge-success">Active</span>
                                @else
                                    <span class="status-badge status-badge-danger">Inactive</span>
                                @endif
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

                        <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-2 mt-auto">
                            <span class="text-[11px] text-slate-400">
                                <i class="bi bi-clock text-[10px] mr-0.5"></i>
                                {{ $user->updated_at?->diffForHumans() ?? '-' }}
                            </span>
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
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <div
        x-show="view === 'table'"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="mt-4"
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
                            <tr>
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
                {{ $users->links() }}
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

</script>
@endsection
