@extends('layouts.panel')

@section('page_title', 'Deceased Records')
@section('page_desc', 'Browse deceased records linked to funeral cases.')

@section('content')
<div class="records-page">
@if(session('success'))
    <div class="flash-success">
        {{ session('success') }}
    </div>
@endif

<section class="table-system-card">
    <div class="table-system-toolbar">
        <form id="deceasedFilterForm" method="GET" action="{{ route('deceased.index') }}" class="table-toolbar" data-table-toolbar data-search-debounce="400">
            <div class="table-toolbar-field">
                <label for="deceased-filter-q" class="table-toolbar-label">Search</label>
                <input id="deceased-filter-q" name="q" value="{{ request('q') }}" class="form-input table-toolbar-search" data-table-search placeholder="Search deceased name...">
            </div>

            <div class="table-toolbar-field">
                <label for="deceased-type-filter" class="table-toolbar-label">Status / Type</label>
                <select id="deceased-type-filter" name="type_filter" class="form-select table-toolbar-select">
                    <option value="all" @selected(request('type_filter','all') === 'all')>All</option>
                    <option value="needs_attention" @selected(request('type_filter') === 'needs_attention')>Needs Attention</option>
                    <option value="recent" @selected(request('type_filter') === 'recent')>Recent</option>
                    <option value="active" @selected(request('type_filter') === 'active')>Active</option>
                    <option value="completed" @selected(request('type_filter') === 'completed')>Completed</option>
                    <option value="with_balance" @selected(request('type_filter') === 'with_balance')>With Balance</option>
                </select>
            </div>

            <div class="table-toolbar-field">
                <label for="deceased-date-range" class="table-toolbar-label">Date</label>
                <select id="deceased-date-range" name="date_range" class="form-select table-toolbar-select">
                    <option value="any" @selected(request('date_range','any') === 'any')>Any Time</option>
                    <option value="today" @selected(request('date_range') === 'today')>Today</option>
                    <option value="7d" @selected(request('date_range') === '7d')>Last 7 Days</option>
                    <option value="30d" @selected(request('date_range') === '30d')>Last 30 Days</option>
                    <option value="this_month" @selected(request('date_range') === 'this_month')>This Month</option>
                    <option value="custom" @selected(request('date_range') === 'custom')>Custom Range</option>
                </select>
            </div>

            <div class="table-toolbar-field" data-custom-date-field @if(request('date_range') !== 'custom') hidden @endif>
                <label for="died-from" class="table-toolbar-label">Date From</label>
                <input id="died-from" type="date" name="died_from" value="{{ request('died_from') }}" class="form-input table-toolbar-select" data-custom-date-input @if(request('date_range') !== 'custom') disabled @endif>
            </div>

            <div class="table-toolbar-field" data-custom-date-field @if(request('date_range') !== 'custom') hidden @endif>
                <label for="died-to" class="table-toolbar-label">Date To</label>
                <input id="died-to" type="date" name="died_to" value="{{ request('died_to') }}" class="form-input table-toolbar-select" data-custom-date-input @if(request('date_range') !== 'custom') disabled @endif>
            </div>

            <div class="table-toolbar-field">
                <label for="deceased-sort" class="table-toolbar-label">Sort</label>
                <select id="deceased-sort" name="sort" class="form-select table-toolbar-sort" data-table-sort>
                    <option value="newest" @selected(request('sort','newest') === 'newest')>Newest</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                    <option value="name_asc" @selected(request('sort') === 'name_asc')>Name A-Z</option>
                    <option value="name_desc" @selected(request('sort') === 'name_desc')>Name Z-A</option>
                    <option value="death_recent" @selected(request('sort') === 'death_recent')>Death Date (Latest)</option>
                    <option value="death_oldest" @selected(request('sort') === 'death_oldest')>Death Date (Oldest)</option>
                </select>
            </div>

            <div class="table-toolbar-reset-wrap">
                <span class="table-toolbar-label opacity-0 select-none">Actions</span>
                <div class="filter-actions">
                    <a href="{{ route('deceased.index') }}" class="btn-outline btn-filter-reset">
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

        <div class="table-quick-tabs table-system-quick-tabs">
            <a href="{{ route('deceased.index', array_merge(request()->except('type_filter'), ['type_filter' => 'all'])) }}"
               class="table-quick-tab {{ request('type_filter','all') === 'all' ? 'table-quick-tab-active' : '' }}">All</a>
            <a href="{{ route('deceased.index', array_merge(request()->except('type_filter'), ['type_filter' => 'needs_attention'])) }}"
               class="table-quick-tab {{ request('type_filter') === 'needs_attention' ? 'table-quick-tab-active' : '' }}">Needs Attention</a>
            <a href="{{ route('deceased.index', array_merge(request()->except('type_filter'), ['type_filter' => 'recent'])) }}"
               class="table-quick-tab {{ request('type_filter') === 'recent' ? 'table-quick-tab-active' : '' }}">Recent</a>
            <a href="{{ route('deceased.index', array_merge(request()->except('type_filter'), ['type_filter' => 'with_balance'])) }}"
               class="table-quick-tab {{ request('type_filter') === 'with_balance' ? 'table-quick-tab-active' : '' }}">With Balance</a>
        </div>
    </div>

    <div class="table-system-list">
        <div class="table-system-list-header">
            <div>
                <div class="table-system-list-title">Deceased Records</div>
                <div class="table-system-list-copy">Track case linkages, service timeline, and status for each record.</div>
            </div>
        </div>

        <div class="table-wrapper table-system-wrap">
            <table class="table-base table-system-table">
                <thead>
                    <tr>
                        <th class="text-left">Deceased Name</th>
                        <th class="text-left">Case ID</th>
                        <th class="table-col-center">Age</th>
                        <th class="text-left">Date of Death</th>
                        <th class="text-left">Service / Interment Date</th>
                        <th class="text-left">Linked Client</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Payment</th>
                        <th class="table-col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($deceaseds as $deceased)
                    <tr>
                        <td>
                            <div class="table-primary">{{ $deceased->full_name }}</div>
                            <div class="table-secondary">{{ $deceased->deceased_code ?? 'DC-' . str_pad($deceased->id, 3, '0', STR_PAD_LEFT) }}</div>
                        </td>
                        <td>{{ $deceased->funeralCase?->case_code ?? '-' }}</td>
                        <td class="table-col-center">{{ $deceased->age ?? '-' }}</td>
                        <td>{{ ($deceased->died ?? $deceased->date_of_death)?->format('Y-m-d') ?? '-' }}</td>
                        <td>{{ $deceased->interment_at?->format('Y-m-d') ?? $deceased->interment?->format('Y-m-d') ?? '-' }}</td>
                        <td>{{ $deceased->client?->full_name ?? '-' }}</td>
                        <td>
                            @if($deceased->funeralCase?->case_status)
                                <x-status-badge :status="$deceased->funeralCase->case_status" />
                            @else
                                <span class="table-secondary">-</span>
                            @endif
                        </td>
                        <td>
                            @if($deceased->funeralCase?->payment_status)
                                <x-status-badge :status="$deceased->funeralCase->payment_status" />
                            @else
                                <span class="table-secondary">-</span>
                            @endif
                        </td>
                        <td class="table-col-actions">
                            <div class="table-row-actions">
                                <div class="row-action-menu" data-row-menu>
                                    <button
                                        type="button"
                                        class="row-action-trigger"
                                        data-row-menu-trigger
                                        aria-label="Open row actions"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                    >
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <div class="row-action-dropdown" role="menu">
                                        <a
                                            href="{{ route('deceased.show', $deceased) }}"
                                            data-url="{{ route('deceased.show', $deceased) }}"
                                            class="row-action-item open-deceased-modal"
                                            data-row-menu-item
                                        >
                                            <i class="bi bi-eye"></i>
                                            <span>View</span>
                                        </a>
                                        @if(auth()->user()?->role !== 'staff')
                                            <a
                                                href="{{ route('deceased.edit', $deceased) }}"
                                                data-url="{{ route('deceased.edit', $deceased) }}"
                                                class="row-action-item open-deceased-modal"
                                                data-row-menu-item
                                            >
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="table-system-empty">
                            No records yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-system-pagination">
        {{ $deceaseds->links() }}
    </div>
</section>

<div id="deceasedModalOverlay" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-200 panel-overlay-content">
    <div id="deceasedModalSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200">
        <button id="deceasedModalClose" type="button" class="absolute top-4 right-4 z-10 inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-50 transition-colors focus:outline-none shadow-sm">
            <i class="bi bi-x-lg" style="font-size:.8rem"></i>
        </button>
        <div id="deceasedModalContent" class="overflow-y-auto max-h-[84vh] p-6 bg-slate-50">
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const filterForm = document.getElementById('deceasedFilterForm');
        if (filterForm) {
            const dateRangeSelect = filterForm.querySelector('select[name="date_range"]');
            const customDateFields = filterForm.querySelectorAll('[data-custom-date-field]');
            const customDateInputs = filterForm.querySelectorAll('[data-custom-date-input]');

            const updateCustomDateVisibility = () => {
                const isCustom = dateRangeSelect && dateRangeSelect.value === 'custom';
                customDateFields.forEach((field) => {
                    field.hidden = !isCustom;
                });
                customDateInputs.forEach((input) => {
                    input.disabled = !isCustom;
                    if (!isCustom) {
                        input.value = '';
                    }
                });
            };

            if (dateRangeSelect) {
                dateRangeSelect.addEventListener('change', updateCustomDateVisibility);
            }

            updateCustomDateVisibility();
        }

        const overlay = document.getElementById('deceasedModalOverlay');
        const sheet = document.getElementById('deceasedModalSheet');
        const content = document.getElementById('deceasedModalContent');
        const closeBtn = document.getElementById('deceasedModalClose');
        const links = [...document.querySelectorAll('.open-deceased-modal')];
        const transitionMs = 180;
        let hideTimer = null;
        let activeRequestId = 0;

        const loadingMarkup = `
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>`;

        const dispatchUiReset = () => {
            document.dispatchEvent(new CustomEvent('panel-ui:reset'));
        };

        const syncPageScrollLock = (isOpen) => {
            document.documentElement.classList.toggle('overflow-hidden', !!isOpen);
            document.body.classList.toggle('overflow-hidden', !!isOpen);
        };

        const resetContent = () => {
            if (content) {
                content.innerHTML = loadingMarkup;
            }
        };

        const show = () => {
            if (!overlay || !sheet) return;
            window.clearTimeout(hideTimer);
            dispatchUiReset();
            overlay.classList.remove('hidden');
            syncPageScrollLock(true);
            requestAnimationFrame(() => {
                sheet.classList.remove('scale-95', 'opacity-0');
                sheet.classList.add('scale-100', 'opacity-100');
                overlay.classList.add('opacity-100');
            });
        };

        const hide = () => {
            if (!overlay || !sheet) return;
            activeRequestId += 1;
            window.clearTimeout(hideTimer);
            sheet.classList.add('scale-95', 'opacity-0');
            sheet.classList.remove('scale-100', 'opacity-100');
            overlay.classList.remove('opacity-100');
            syncPageScrollLock(false);
            dispatchUiReset();
            hideTimer = window.setTimeout(() => {
                overlay.classList.add('hidden');
                syncPageScrollLock(false);
                resetContent();
            }, transitionMs);
        };

        const attachPrintHandler = () => {
            const btn = content.querySelector('#printDeceasedBtn');
            const view = content.querySelector('#deceasedViewContent');
            if (!btn || !view) return;
            btn.addEventListener('click', () => {
                const iframe = document.createElement('iframe');
                iframe.style.position = 'fixed';
                iframe.style.right = '0';
                iframe.style.bottom = '0';
                iframe.style.width = '0';
                iframe.style.height = '0';
                iframe.style.border = '0';
                document.body.appendChild(iframe);

                const doc = iframe.contentWindow.document;
                doc.open();
                doc.write('<!doctype html><html><head>');
                document.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
                    if (link.href) doc.write(`<link rel="stylesheet" href="${link.href}">`);
                });
                content.querySelectorAll('style').forEach((style) => {
                    doc.write('<style>' + style.innerHTML + '</style>');
                });
                doc.write('</head><body>');
                doc.write(view.outerHTML);
                doc.write('</body></html>');
                doc.close();
                iframe.onload = () => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    setTimeout(() => iframe.remove(), 500);
                };
            });
        };

        const load = async (url) => {
            if (!content) return;
            const requestId = ++activeRequestId;
            resetContent();
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                if (requestId !== activeRequestId || overlay.classList.contains('hidden')) return;
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const view = doc.querySelector('#deceasedViewContent');
                const form = doc.querySelector('#deceasedEditForm');
                const payload = view || form;
                if (payload) {
                    content.innerHTML = payload.outerHTML;
                    doc.querySelectorAll('style').forEach((style) => {
                        if (style.innerHTML.includes('deceased') || style.innerHTML.includes('print')) {
                            const s = document.createElement('style');
                            s.textContent = style.innerHTML;
                            content.appendChild(s);
                        }
                    });
                    attachPrintHandler();
                } else {
                    content.innerHTML = html;
                }
            } catch (e) {
                if (requestId !== activeRequestId || overlay.classList.contains('hidden')) return;
                content.innerHTML = `<div class="p-4 text-sm text-rose-600">Unable to load content.</div>`;
            }
        };

        links.forEach((link) => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const url = link.dataset.url || link.href;
                show();
                load(url);
            });
        });

        if (closeBtn) closeBtn.addEventListener('click', hide);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) hide();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !overlay.classList.contains('hidden')) hide();
        });
    })();
</script>
</div>
@endsection
