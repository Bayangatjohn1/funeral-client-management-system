@extends('layouts.panel')

@section('page_title', 'Client Directory')
@section('page_desc', 'Family representatives, contact details, and linked case history.')

@section('content')
@include('partials.worklist_consistency_styles')
<div class="records-page staff-clients-page" data-record-results-page="clients" data-worklist-consistent>
@if(session('success'))
    <div class="flash-success">
        {{ session('success') }}
    </div>
@endif

<section class="table-system-card">
    <div class="table-system-toolbar">
        @include('partials.directory_toolbar', ['directoryKind' => 'clients'])
    </div>

    <div class="table-system-list" data-record-results="table">
        <div class="table-wrapper table-system-wrap" role="region" aria-label="Client records" tabindex="0">
            <table class="table-base table-system-table records-worklist-table" aria-label="Client Directory">
                <thead>
                    <tr>
                        <th class="text-left">Family Representative</th>
                        <th class="text-left">Contact</th>
                        <th class="text-left">Linked Cases</th>
                        <th class="text-left">Latest Activity</th>
                        <th class="table-col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($clients as $client)
                    @php
                        $latestCase = $client->latestFuneralCase;
                        $linkedCasesUrl = route('funeral-cases.index', [
                            'tab' => 'all',
                            'record_scope' => 'main',
                            'client_id' => $client->id,
                            'return_to' => request()->fullUrl(),
                        ]);
                    @endphp
                    <tr data-worklist-search-id="{{ $client->id }}" data-worklist-name="{{ $client->full_name }}"
                        data-worklist-detail="{{ $client->contact_number }}"
                        data-worklist-fields="{{ json_encode([$client->full_name, $client->contact_number, $client->address]) }}">
                        <td data-label="Family Representative">
                            <div class="table-primary">{{ $client->full_name }}</div>
                            <div class="table-secondary staff-records-mono">{{ $client->directoryCode() }}</div>
                        </td>
                        <td data-label="Contact">
                            <div class="table-primary staff-records-mono">{{ $client->contact_number ?: '-' }}</div>
                            <div class="table-secondary client-directory-address" title="{{ $client->address }}">{{ $client->address ?: 'Address not recorded' }}</div>
                        </td>
                        <td data-label="Linked Cases">
                            <div class="table-primary">{{ $client->funeral_cases_count }} linked {{ \Illuminate\Support\Str::plural('case', $client->funeral_cases_count) }}</div>
                            <div class="table-secondary">{{ $latestCase?->deceased?->full_name ?: 'No linked case yet' }}</div>
                        </td>
                        <td data-label="Latest Activity">
                            @if($latestCase)
                                <div class="table-primary staff-records-mono">{{ $latestCase->case_code ?? 'Latest case' }}</div>
                                <div class="table-secondary">
                                    {{ $latestCase->created_at?->format('M d, Y') ?? 'Date pending' }}
                                    @if($latestCase->case_status)
                                        &middot; {{ \Illuminate\Support\Str::headline(strtolower($latestCase->case_status)) }}
                                    @endif
                                </div>
                            @else
                                <div class="table-primary">No activity yet</div>
                                <div class="table-secondary">Created {{ $client->created_at?->format('M d, Y') ?? 'recently' }}</div>
                            @endif
                        </td>
                        <td class="table-col-actions" data-label="Actions">
                            <div class="table-row-actions">
                                <a
                                    href="{{ route('clients.show', $client) }}"
                                    class="primary-view-action record-action-control open-client-modal"
                                    data-modal-title="Client Profile"
                                    data-action-tooltip="View profile"
                                    aria-label="View profile for {{ $client->full_name }}"
                                    aria-haspopup="dialog"
                                    aria-controls="clientModalSheet"
                                ><i class="bi bi-eye" aria-hidden="true"></i></a>
                                <div class="row-action-menu compact-action-menu" data-row-menu>
                                    <button
                                        type="button"
                                        class="row-action-trigger compact-action-trigger record-action-control"
                                        data-row-menu-trigger
                                        data-action-tooltip="More actions"
                                        aria-label="More actions for {{ $client->full_name }}"
                                        aria-haspopup="menu"
                                        aria-expanded="false"
                                    >
                                        <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                                    </button>
                                    <div class="row-action-dropdown" role="menu">
                                        <a
                                            href="{{ $linkedCasesUrl }}"
                                            class="row-action-item compact-action-item"
                                            data-row-menu-item
                                        >
                                            <i class="bi bi-folder2-open" aria-hidden="true"></i>
                                            <span>View Linked Cases</span>
                                        </a>
                                        @if(auth()->user()?->role !== 'staff')
                                            <a
                                                href="{{ route('clients.edit', $client) }}"
                                                data-url="{{ route('clients.edit', $client) }}"
                                                class="row-action-item compact-action-item open-client-modal"
                                                data-row-menu-item
                                            >
                                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                <span>Edit Contact</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="table-system-empty">
                            <div class="client-directory-empty">
                                <i class="bi bi-people" aria-hidden="true"></i>
                                <strong>No family representatives found</strong>
                                <span>{{ request()->filled('q') || (request()->filled('date_range') && request('date_range') !== 'any') || request()->filled('added_from') || request()->filled('added_to') || in_array(request('type_filter'), ['needs_attention', 'recent', 'with_balance'], true) ? 'Try a different search or clear the filters.' : 'Client profiles appear here after a case is recorded.' }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</section>
<div class="table-system-pagination worklist-footer {{ $clients->hasPages() ? '' : 'is-pagination-hidden' }}" data-record-results="footer" @if(!$clients->hasPages()) aria-hidden="true" @endif>
    {{ $clients->links('components.pagination.table') }}
</div>

<dialog id="clientModalSheet" class="client-directory-dialog client-profile-surface" aria-labelledby="clientModalTitle">
    <header>
        <h2 id="clientModalTitle">Client Profile</h2>
        <button id="clientModalClose" type="button" aria-label="Close client profile" autofocus><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </header>
    <div id="clientModalContent"></div>
</dialog>

<script>
    (() => {
        const sheet = document.getElementById('clientModalSheet');
        const content = document.getElementById('clientModalContent');
        const closeBtn = document.getElementById('clientModalClose');
        const root = sheet.closest('.staff-clients-page');
        let activeRequest;
        let returnFocus;

        const loadingMarkup = `
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>`;

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

        const withFragment = (url) => {
            try {
                const target = new URL(url, window.location.href);
                target.searchParams.set('fragment', 'modal');
                return target.toString();
            } catch (error) {
                return url;
            }
        };

        const load = async (url) => {
            activeRequest?.abort();
            const request = new AbortController();
            activeRequest = request;
            content.innerHTML = loadingMarkup;
            const finishSkeleton = window.PanelSkeleton?.begin(content, {variant:'form'});
            try {
                const res = await fetch(withFragment(url), {
                    signal: request.signal,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-Client-Fragment': 'modal',
                    },
                });
                if (!res.ok) {
                    throw new Error(`Request failed with ${res.status}`);
                }
                const html = await res.text();
                if (request.signal.aborted || !sheet.open) return;
                content.innerHTML = html;
                const hasModalPayload = content.querySelector('#clientEditForm') || content.querySelector('#clientViewContent');
                if (!hasModalPayload) {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const payload = doc.querySelector('#clientEditForm') || doc.querySelector('#clientViewContent');
                    if (payload) {
                        content.innerHTML = payload.outerHTML;
                        doc.querySelectorAll('style').forEach((style) => {
                            if (style.innerHTML.includes('client') || style.innerHTML.includes('print')) {
                                const s = document.createElement('style');
                                s.textContent = style.innerHTML;
                                content.appendChild(s);
                            }
                        });
                    }
                }
                attachPrintHandler();
            } catch (e) {
                if (request.signal.aborted || !sheet.open) return;
                content.innerHTML = `<p class="flash-error" role="alert">Unable to load the client profile. Close this window and try again.</p>`;
            } finally { finishSkeleton?.(); }
        };

        root.addEventListener('click', event => {
            const link = event.target.closest('.open-client-modal');
            if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            returnFocus = link.closest('[data-row-menu]')?.querySelector('[data-row-menu-trigger]') ?? link;
            document.getElementById('clientModalTitle').textContent = link.dataset.modalTitle ?? link.textContent.trim();
            sheet.showModal();
            load(link.dataset.url || link.href);
        });

        closeBtn.addEventListener('click', () => sheet.close());
        sheet.addEventListener('click', event => {
            if (event.target !== sheet) return;
            const rect = sheet.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) sheet.close();
        });
        sheet.addEventListener('close', () => {
            activeRequest?.abort();
            (returnFocus?.isConnected ? returnFocus : root.querySelector('[data-worklist-search]'))?.focus({preventScroll:true});
        });
    })();
</script>
</div>
@include('staff.clients.partials.directory-styles')
@endsection
