@extends('layouts.panel')

@section('page_title', 'Branch Management')
@section('page_desc', 'Manage branch details, status, and branch-wide settings.')

@section('content')
<style>[x-cloak] { display: none !important; }</style>
<div class="admin-table-page" x-data="branchCatalog()">
<div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8 py-6">
<div class="space-y-6">

@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        {{ $errors->first() }}
    </div>
@endif

{{-- Summary stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-5">
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Total Branches</span>
        <span class="text-2xl font-bold text-slate-900">{{ $totalBranches }}</span>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Active</span>
        <span class="text-2xl font-bold text-emerald-600">{{ $activeBranches }}</span>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Total Records</span>
        <span class="text-2xl font-bold text-slate-900">{{ number_format($totalRecords) }}</span>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm flex flex-col gap-1.5">
        <span class="text-[11px] font-semibold uppercase tracking-widest text-slate-400">Main Branch</span>
        <span class="text-lg font-bold text-slate-900 leading-tight truncate">
            {{ $mainBranch?->branch_name ?? '—' }}
        </span>
    </div>
</div>

{{-- Main section card --}}
<section class="table-system-card admin-table-card">

    {{-- Header --}}
    <div class="table-system-head">
        <div class="admin-table-head-row">
            <div>
                <h2 class="table-system-title">Branch Directory</h2>
                <p class="admin-table-head-copy">Manage branch profile, status, and encoded record count.</p>
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

                {{-- Add Branch --}}
                <button
                    id="openBranchCreateModal"
                    type="button"
                    class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white inline-flex items-center gap-2"
                >
                    <i class="bi bi-plus-circle"></i>
                    <span>Add Branch</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="table-system-toolbar">
        <form
            method="GET"
            action="{{ route('admin.branches.index') }}"
            class="table-toolbar"
            data-table-toolbar
            data-search-debounce="400"
            style="grid-template-columns: minmax(260px, 2.2fr) repeat(2, minmax(150px, 1fr)) auto;"
        >
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Search</label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Search branches..."
                    class="form-input table-toolbar-search"
                    data-table-search
                    autocomplete="off"
                >
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Status</label>
                <select name="status" class="form-select table-toolbar-select" data-table-auto-submit>
                    <option value="">All Status</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Sort</label>
                <select name="sort" class="form-select table-toolbar-sort" data-table-sort>
                    <option value="code_asc"     {{ request('sort', 'code_asc') === 'code_asc'    ? 'selected' : '' }}>Branch ID</option>
                    <option value="name_asc"     {{ request('sort') === 'name_asc'                 ? 'selected' : '' }}>Branch Name</option>
                    <option value="records_desc" {{ request('sort') === 'records_desc'             ? 'selected' : '' }}>Records Encoded</option>
                </select>
            </div>
            <div class="table-toolbar-reset-wrap">
                <span class="table-toolbar-label opacity-0 select-none" aria-hidden="true">Actions</span>
                <div class="filter-actions">
                    <a href="{{ route('admin.branches.index') }}" class="btn-outline btn-filter-reset">
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

        @if($branches->isEmpty())
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center py-16 gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                    <i class="bi bi-building text-2xl text-slate-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-sm">No branches found</h3>
                    <p class="text-xs text-slate-500 mt-1">Try changing the search or filters, or add a new branch.</p>
                </div>
                <button
                    type="button"
                    id="openBranchCreateModalEmpty"
                    class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] text-white inline-flex items-center gap-1.5 mt-1"
                >
                    <i class="bi bi-plus-circle"></i>
                    Add Branch
                </button>
            </div>

        @else
            {{-- Branch cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($branches as $branch)
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col">

                    {{-- Card header: code badge + name + status --}}
                    <div class="p-5 flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                <span class="inline-flex items-center rounded-lg bg-slate-100 text-slate-600 text-[10px] font-bold uppercase tracking-widest px-2 py-0.5">
                                    {{ $branch->branch_code }}
                                </span>
                                @if($branch->isMain())
                                    <span class="inline-flex items-center rounded-lg bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-widest px-2 py-0.5">
                                        <i class="bi bi-star-fill text-[8px] mr-1"></i>Main
                                    </span>
                                @endif
                            </div>
                            <h3 class="font-bold text-slate-900 text-[15px] leading-snug">{{ $branch->branch_name }}</h3>
                            <p class="text-xs text-slate-500 mt-0.5 flex items-start gap-1">
                                <i class="bi bi-geo-alt-fill text-[10px] mt-0.5 flex-shrink-0"></i>
                                <span class="truncate">{{ $branch->address ?: '—' }}</span>
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            @if($branch->is_active)
                                <span class="status-badge status-badge-success">Active</span>
                            @else
                                <span class="status-badge status-badge-danger">Inactive</span>
                            @endif
                        </div>
                    </div>

                    {{-- Records count --}}
                    <div class="px-5 pb-4 pt-4 border-t border-slate-100">
                        <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">Records Encoded</p>
                        <p class="text-2xl font-bold text-slate-900 leading-none mt-0.5">{{ number_format($branch->funeral_cases_count) }}</p>
                    </div>

                    {{-- Card footer --}}
                    <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between gap-2 mt-auto">
                        <span class="text-[11px] text-slate-400">
                            <i class="bi bi-clock text-[10px] mr-0.5"></i>
                            {{ $branch->updated_at?->diffForHumans() ?? '—' }}
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
                                    class="row-action-item open-branch-modal"
                                    data-row-menu-item
                                    data-url="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}"
                                    href="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Edit branch</span>
                                </a>
                                <form method="POST" action="{{ route('admin.branches.toggleStatus', $branch) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="row-action-item" type="submit" data-row-menu-item>
                                        <i class="bi bi-toggle-{{ $branch->is_active ? 'off' : 'on' }}"></i>
                                        <span>{{ $branch->is_active ? 'Disable branch' : 'Enable branch' }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Card view pagination --}}
            <div class="mt-6">
                {{ $branches->links() }}
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
                            <th class="text-left">Branch ID</th>
                            <th class="text-left">Branch Name</th>
                            <th class="text-left">Address</th>
                            <th class="text-left table-col-number">Total Records Encoded</th>
                            <th class="text-left">Status</th>
                            <th class="table-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td class="table-primary">
                                    {{ $branch->branch_code }}
                                    @if($branch->isMain())
                                        <span class="ml-1 inline-flex items-center rounded bg-amber-100 text-amber-700 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5">Main</span>
                                    @endif
                                </td>
                                <td>{{ $branch->branch_name }}</td>
                                <td class="table-secondary">{{ $branch->address ?? '—' }}</td>
                                <td class="table-col-number">{{ number_format($branch->funeral_cases_count) }}</td>
                                <td>
                                    @if($branch->is_active)
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
                                                class="row-action-item open-branch-modal"
                                                data-row-menu-item
                                                data-url="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}"
                                                href="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}"
                                            >
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit branch</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.branches.toggleStatus', $branch) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="row-action-item" type="submit" data-row-menu-item>
                                                    <i class="bi bi-toggle-{{ $branch->is_active ? 'off' : 'on' }}"></i>
                                                    <span>{{ $branch->is_active ? 'Disable branch' : 'Enable branch' }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-system-empty">No branches found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-system-pagination">
                {{ $branches->links() }}
            </div>
        </div>
    </div>

</section>
</div>{{-- end space-y-6 --}}
</div>{{-- end centered container --}}
</div>{{-- end admin-table-page --}}

{{-- Branch create modal --}}
<div id="branchCreateModalOverlay" class="fixed inset-0 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-200 font-ui-body" style="z-index: 1300;">
    <div id="branchCreateModalSheet" class="relative w-[92vw] max-w-3xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200 font-ui-body">
        <div class="overflow-y-auto max-h-[84vh] bg-slate-50">
            <form id="branchCreateForm" method="POST" action="{{ route('admin.branches.store') }}" class="max-w-3xl w-full mx-auto">
                @csrf
                <input type="hidden" name="return_to" value="{{ old('return_to', request()->fullUrl()) }}">
                <input type="hidden" name="form_context" value="branch_create_modal">

                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Create Branch</h2>
                                <p class="text-base text-slate-500">Register a new branch and configure branch information</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-semibold tracking-wide text-slate-700">
                                    {{ $nextCode }}
                                </span>
                                <button id="branchCreateModalClose" type="button" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-300 bg-white text-slate-400 hover:text-slate-700 focus:outline-none">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        <div>
                            <label class="label-section">Branch Code</label>
                            <input type="text" value="{{ $nextCode }}" class="form-input bg-slate-100 text-slate-700 font-semibold" readonly>
                            <div class="text-sm text-slate-500 mt-2">Branch code is auto-assigned and cannot be changed.</div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="label-section">Branch Name <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    id="branch_create_name"
                                    name="branch_name"
                                    value="{{ old('branch_name') }}"
                                    class="form-input"
                                    autocomplete="off"
                                    inputmode="text"
                                    required
                                >
                                @error('branch_name') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="text-sm text-slate-500 mt-2">Letters only. Numbers are auto-removed.</div>
                            </div>

                            <div>
                                <label class="label-section">Address</label>
                                <input type="text" name="address" value="{{ old('address') }}" class="form-input" placeholder="Street, City, Province">
                                @error('address') <div class="form-error">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="text-[1.1rem] leading-tight font-semibold text-slate-900">Branch Status</div>
                                <p class="text-sm text-slate-500">Active branches can process new cases and payments</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span id="branch-create-status-pill" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ old('is_active', 1) ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ old('is_active', 1) ? 'Active' : 'Inactive' }}
                                </span>
                                <input type="hidden" name="is_active" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input id="branch_create_is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-12 h-7 bg-slate-300 rounded-full peer peer-checked:bg-emerald-600 transition-colors"></div>
                                    <div class="absolute left-[3px] top-[3px] h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-slate-500">Ready to save new branch details.</div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="branchCreateModalCancel" class="btn btn-outline">Cancel</button>
                            <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
                                <i class="bi bi-save2"></i>
                                Save Branch
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Branch edit modal --}}
<div id="branchModalOverlay" class="fixed inset-0 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-200 font-ui-body" style="z-index: 1300;">
    <div id="branchModalSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200 font-ui-body">
        <button id="branchEditModalClose" type="button" class="absolute top-4 right-4 z-10 inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-colors focus:outline-none shadow-sm">
            <i class="bi bi-x-lg" style="font-size:.8rem"></i>
        </button>
        <div id="branchModalContent" class="overflow-y-auto max-h-[84vh] p-6 bg-slate-50">
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
// Branch directory: card/table view toggle persisted in localStorage
function branchCatalog() {
    return {
        view: 'card',
        init() {
            const saved = localStorage.getItem('branch_view');
            if (saved === 'card' || saved === 'table') this.view = saved;
        },
        setView(v) {
            this.view = v;
            localStorage.setItem('branch_view', v);
        },
    };
}

// Branch modals
(() => {
    const editOverlay  = document.getElementById('branchModalOverlay');
    const editSheet    = document.getElementById('branchModalSheet');
    const editContent  = document.getElementById('branchModalContent');
    const editLinks    = [...document.querySelectorAll('.open-branch-modal')];
    const editCloseBtn = document.getElementById('branchEditModalClose');

    const createOverlay   = document.getElementById('branchCreateModalOverlay');
    const createSheet     = document.getElementById('branchCreateModalSheet');
    const createOpenBtns  = [...document.querySelectorAll('#openBranchCreateModal, #openBranchCreateModalEmpty')];
    const createCloseBtn  = document.getElementById('branchCreateModalClose');
    const createCancelBtn = document.getElementById('branchCreateModalCancel');
    const createStatusToggle = document.getElementById('branch_create_is_active');
    const createStatusPill   = document.getElementById('branch-create-status-pill');
    const shouldOpenCreateModal = @json(old('form_context') === 'branch_create_modal');
    const branchNamePattern = /^[A-Za-z][A-Za-z\s'.&-]*$/;

    const normalizeBranchNameInput = (value) => String(value || '')
        .replace(/\d+/g, '')
        .replace(/[^A-Za-z\s'.&-]/g, '')
        .replace(/\s{2,}/g, ' ')
        .replace(/^\s+/, '');

    const bindBranchNameValidation = (input) => {
        if (!input || input.dataset.branchNameBound === '1') return;
        input.dataset.branchNameBound = '1';
        const sync = (trimEnd = false) => {
            const normalized = normalizeBranchNameInput(input.value);
            input.value = trimEnd ? normalized.trim() : normalized;
            const finalValue = input.value.trim();
            if (!finalValue) { input.setCustomValidity('Branch name is required.'); return; }
            if (!branchNamePattern.test(finalValue)) { input.setCustomValidity('Branch name must contain letters only (no numbers).'); return; }
            input.setCustomValidity('');
        };
        input.addEventListener('input', () => sync(false));
        input.addEventListener('blur', () => sync(true));
        sync(true);
    };

    const syncPageScrollLock = () => {
        const shouldLock = (createOverlay && !createOverlay.classList.contains('hidden'))
                        || (editOverlay   && !editOverlay.classList.contains('hidden'));
        document.documentElement.classList.toggle('overflow-hidden', shouldLock);
        document.body.classList.toggle('overflow-hidden', shouldLock);
    };

    const showModal = (overlay, sheet) => {
        if (!overlay || !sheet) return;
        overlay.classList.remove('hidden');
        syncPageScrollLock();
        requestAnimationFrame(() => {
            sheet.classList.remove('scale-95', 'opacity-0');
            sheet.classList.add('scale-100', 'opacity-100');
            overlay.classList.add('opacity-100');
        });
    };

    const hideModal = (overlay, sheet, content = null) => {
        if (!overlay || !sheet) return;
        sheet.classList.add('scale-95', 'opacity-0');
        sheet.classList.remove('scale-100', 'opacity-100');
        overlay.classList.remove('opacity-100');
        setTimeout(() => {
            overlay.classList.add('hidden');
            syncPageScrollLock();
            if (content) {
                content.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-16 gap-3">
                        <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                        <span class="text-sm text-slate-400">Loading...</span>
                    </div>`;
            }
        }, 180);
    };

    const loadEditForm = async (url) => {
        editContent.innerHTML = `
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>`;
        try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            const doc  = new DOMParser().parseFromString(html, 'text/html');
            const form = doc.querySelector('#branchEditForm');
            if (form) {
                editContent.innerHTML = form.outerHTML;
                bindBranchNameValidation(editContent.querySelector('input[name="branch_name"]'));
                const cancelLink = editContent.querySelector('.branch-modal-cancel');
                if (cancelLink) {
                    cancelLink.addEventListener('click', (evt) => {
                        evt.preventDefault();
                        hideModal(editOverlay, editSheet, editContent);
                    });
                }
                const closeBtn = editContent.querySelector('.branch-modal-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', (evt) => {
                        evt.preventDefault();
                        hideModal(editOverlay, editSheet, editContent);
                    });
                }
                [...doc.querySelectorAll('script')].forEach((old) => {
                    const s = document.createElement('script');
                    if (old.src) s.src = old.src; else s.textContent = old.textContent;
                    editContent.appendChild(s);
                });
            } else {
                editContent.innerHTML = html;
            }
        } catch (e) {
            editContent.innerHTML = `<div class="p-4 text-sm text-rose-600">Unable to load content.</div>`;
        }
    };

    createOpenBtns.forEach((btn) => {
        if (btn) btn.addEventListener('click', () => showModal(createOverlay, createSheet));
    });

    bindBranchNameValidation(document.getElementById('branch_create_name'));

    if (createStatusToggle && createStatusPill) {
        const syncCreateStatus = () => {
            const active = !!createStatusToggle.checked;
            createStatusPill.textContent = active ? 'Active' : 'Inactive';
            createStatusPill.className = `inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ${active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`;
        };
        createStatusToggle.addEventListener('change', syncCreateStatus);
        syncCreateStatus();
    }

    if (createCloseBtn)  createCloseBtn.addEventListener('click',  () => hideModal(createOverlay, createSheet));
    if (createCancelBtn) createCancelBtn.addEventListener('click', () => hideModal(createOverlay, createSheet));
    if (createOverlay)   createOverlay.addEventListener('click',   (e) => { if (e.target === createOverlay) hideModal(createOverlay, createSheet); });

    editLinks.forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            showModal(editOverlay, editSheet);
            loadEditForm(link.dataset.url || link.href);
        });
    });

    if (editCloseBtn) editCloseBtn.addEventListener('click', () => hideModal(editOverlay, editSheet, editContent));
    if (editOverlay)  editOverlay.addEventListener('click',  (e) => { if (e.target === editOverlay) hideModal(editOverlay, editSheet, editContent); });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (createOverlay && !createOverlay.classList.contains('hidden')) { hideModal(createOverlay, createSheet); return; }
        if (editOverlay   && !editOverlay.classList.contains('hidden'))   { hideModal(editOverlay, editSheet, editContent); }
    });

    if (shouldOpenCreateModal) showModal(createOverlay, createSheet);
    syncPageScrollLock();
})();
</script>
@endsection
