@extends('layouts.panel')

@section('page_title', 'Clients')
@section('page_desc', 'Browse and manage all client records.')

@section('content')
<div class="records-page">
@if(session('success'))
    <div class="flash-success">
        {{ session('success') }}
    </div>
@endif

<section class="table-system-card">
    <div class="table-system-toolbar">
        <form id="clientFilterForm" method="GET" action="{{ route('clients.index') }}" class="table-toolbar" data-table-toolbar data-search-debounce="400">
            <div class="table-toolbar-field">
                <label for="client-filter-q" class="table-toolbar-label">Search</label>
                <input id="client-filter-q" name="q" value="{{ request('q') }}" class="form-input table-toolbar-search" data-table-search placeholder="Search client name...">
            </div>

            <div class="table-toolbar-field">
                <label for="client-date-range" class="table-toolbar-label">Date</label>
                <select id="client-date-range" name="date_range" class="form-select table-toolbar-select">
                    <option value="any" @selected(request('date_range','any') === 'any')>Any Time</option>
                    <option value="today" @selected(request('date_range') === 'today')>Today</option>
                    <option value="7d" @selected(request('date_range') === '7d')>Last 7 Days</option>
                    <option value="30d" @selected(request('date_range') === '30d')>Last 30 Days</option>
                    <option value="this_month" @selected(request('date_range') === 'this_month')>This Month</option>
                    <option value="custom" @selected(request('date_range') === 'custom')>Custom Range</option>
                </select>
            </div>

            <div class="table-toolbar-field" data-custom-date-field @if(request('date_range') !== 'custom') hidden @endif>
                <label for="added-from" class="table-toolbar-label">Date From</label>
                <input id="added-from" type="date" name="added_from" value="{{ request('added_from') }}" class="form-input table-toolbar-select" data-custom-date-input @if(request('date_range') !== 'custom') disabled @endif>
            </div>

            <div class="table-toolbar-field" data-custom-date-field @if(request('date_range') !== 'custom') hidden @endif>
                <label for="added-to" class="table-toolbar-label">Date To</label>
                <input id="added-to" type="date" name="added_to" value="{{ request('added_to') }}" class="form-input table-toolbar-select" data-custom-date-input @if(request('date_range') !== 'custom') disabled @endif>
            </div>

            <div class="table-toolbar-field">
                <label for="client-sort" class="table-toolbar-label">Sort</label>
                <select id="client-sort" name="sort" class="form-select table-toolbar-sort" data-table-sort>
                    <option value="newest" @selected(request('sort','newest') === 'newest')>Newest</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                    <option value="name_asc" @selected(request('sort') === 'name_asc')>Name A-Z</option>
                    <option value="name_desc" @selected(request('sort') === 'name_desc')>Name Z-A</option>
                </select>
            </div>

            <div class="table-toolbar-reset-wrap">
                <span class="table-toolbar-label opacity-0 select-none">Actions</span>
                <div class="filter-actions">
                    <a href="{{ route('clients.index') }}" class="btn-outline btn-filter-reset">
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

    <div class="table-system-list">
        <div class="table-system-list-header">
            <div>
                <div class="table-system-list-title">Client Directory</div>
                <div class="table-system-list-copy">View client contact records and recent case activity.</div>
            </div>
        </div>

        <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table">
                <thead>
                    <tr>
                        <th class="text-left">Client Name</th>
                        <th class="text-left">Relationship</th>
                        <th class="text-left">Deceased Name</th>
                        <th class="text-left">Service</th>
                        <th class="text-left">Status</th>
                        <th class="text-left">Payment</th>
                        <th class="table-col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>
                            <div class="table-primary">{{ $client->full_name }}</div>
                            <div class="table-secondary">{{ $client->client_code ?? 'CL-' . str_pad($client->id, 3, '0', STR_PAD_LEFT) }}</div>
                        </td>
                        <td>{{ $client->relationship_to_deceased ?? '-' }}</td>
                        <td>{{ $client->latest_deceased_name ?: '-' }}</td>
                        <td>{{ $client->latest_service_package ?: '-' }}</td>
                        <td>
                            @if($client->latest_case_status)
                                <x-status-badge :status="$client->latest_case_status" />
                            @else
                                <span class="table-secondary">-</span>
                            @endif
                        </td>
                        <td>
                            @if($client->latest_payment_status)
                                <x-status-badge :status="$client->latest_payment_status" />
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
                                            href="{{ route('clients.show', $client) }}"
                                            data-url="{{ route('clients.show', $client) }}"
                                            class="row-action-item open-client-modal"
                                            data-row-menu-item
                                        >
                                            <i class="bi bi-eye"></i>
                                            <span>View</span>
                                        </a>
                                        @if(auth()->user()?->role !== 'staff')
                                            <a
                                                href="{{ route('clients.edit', $client) }}"
                                                data-url="{{ route('clients.edit', $client) }}"
                                                class="row-action-item open-client-modal"
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
                        <td colspan="7" class="table-system-empty">
                            No clients yet.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-system-pagination">
        {{ $clients->links() }}
    </div>
</section>

<div id="clientModalOverlay" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity duration-200 panel-overlay-content">
    <div id="clientModalSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-100">
        <button id="clientModalClose" type="button" class="absolute top-3 right-3 z-10 inline-flex items-center justify-center w-9 h-9 rounded-full bg-white shadow border text-slate-400 hover:text-black focus:outline-none">
            <i class="bi bi-x-lg"></i>
        </button>
        <div id="clientModalContent" class="overflow-y-auto max-h-[84vh] p-5 bg-slate-50">
            <div class="flex items-center justify-center py-8 text-slate-500 gap-2 text-sm">
                <i class="bi bi-arrow-repeat animate-spin"></i>
                <span>Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const filterForm = document.getElementById('clientFilterForm');
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

        const overlay = document.getElementById('clientModalOverlay');
        const sheet = document.getElementById('clientModalSheet');
        const content = document.getElementById('clientModalContent');
        const closeBtn = document.getElementById('clientModalClose');
        const links = [...document.querySelectorAll('.open-client-modal')];

        const show = () => {
            overlay.classList.remove('hidden');
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
                content.innerHTML = `
                    <div class="flex items-center justify-center py-8 text-slate-500 gap-2 text-sm">
                        <i class="bi bi-arrow-repeat animate-spin"></i>
                        <span>Loading...</span>
                    </div>`;
            }, 180);
        };

        const attachPrintHandler = () => {
            const btn = content.querySelector('#printClientBtn');
            const view = content.querySelector('#clientViewContent');
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
            content.innerHTML = `
                <div class="flex items-center justify-center py-8 text-slate-500 gap-2 text-sm">
                    <i class="bi bi-arrow-repeat animate-spin"></i>
                    <span>Loading...</span>
                </div>`;
            try {
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const html = await res.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const form = doc.querySelector('#clientEditForm');
                const view = doc.querySelector('#clientViewContent');
                const payload = form || view;
                if (payload) {
                    content.innerHTML = payload.outerHTML;
                    doc.querySelectorAll('style').forEach((style) => {
                        if (style.innerHTML.includes('client') || style.innerHTML.includes('print')) {
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
