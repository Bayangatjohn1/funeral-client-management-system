@extends('layouts.panel')

@section('page_title','Clients')

@section('content')
@if(session('success'))
    <div class="mb-4 bg-green-50 border p-3 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

<div class="flex items-center justify-between gap-3">
    <form method="GET" action="{{ route('clients.index') }}" class="flex flex-wrap items-center gap-2">
        <input name="q" value="{{ request('q') }}" class="border rounded px-3 py-2 text-sm" placeholder="Search name..." pattern="[A-Za-z ]+" title="Letters and spaces only">
        <button class="border px-3 py-2 rounded text-sm">Search</button>
        <a href="{{ route('clients.index') }}" class="border px-3 py-2 rounded text-sm">Reset</a>
    </form>
</div>

<div class="mt-4 bg-white border rounded overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 border text-left">Client ID</th>
                <th class="p-2 border text-left">Name</th>
                <th class="p-2 border text-left">Contact</th>
                <th class="p-2 border text-left">Address</th>
                <th class="p-2 border text-left">No. of Cases</th>
                <th class="p-2 border text-left">Outstanding Cases</th>
                <th class="p-2 border text-left">Total Paid</th>
                <th class="p-2 border text-left">Date Added</th>
                <th class="p-2 border text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($clients as $client)
            @php
                $caseCount = $client->funeralCases->count();
                $unpaidCaseCount = $client->funeralCases->whereIn('payment_status', ['UNPAID', 'PARTIAL'])->count();
                $totalPaid = $client->funeralCases->flatMap->payments->sum('amount');
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="p-2 border font-semibold text-slate-700">{{ $client->client_code ?? 'CL-' . str_pad($client->id, 3, '0', STR_PAD_LEFT) }}</td>
                <td class="p-2 border">{{ $client->full_name }}</td>
                <td class="p-2 border">{{ $client->contact_number ?? '-' }}</td>
                <td class="p-2 border">{{ $client->address ?? '-' }}</td>
                <td class="p-2 border text-center">{{ $caseCount }}</td>
                <td class="p-2 border text-center">{{ $unpaidCaseCount }}</td>
                <td class="p-2 border">{{ number_format($totalPaid, 2) }}</td>
                <td class="p-2 border">{{ $client->created_at?->format('M d, Y') }}</td>
                <td class="p-2 border">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('clients.show', $client) }}" data-url="{{ route('clients.show', $client) }}" class="action-chip open-client-modal">
                            <i class="bi bi-eye"></i><span>View</span>
                        </a>
                        @if(auth()->user()?->role !== 'staff')
                        <a href="{{ route('clients.edit', $client) }}" data-url="{{ route('clients.edit', $client) }}" class="action-chip action-chip-primary open-client-modal">
                            <i class="bi bi-pencil-square"></i><span>Edit</span>
                        </a>
                        @endif
                        <a href="{{ route('payments.index', ['q' => $client->full_name]) }}" class="action-chip">
                            <i class="bi bi-cash-stack"></i><span>Payments</span>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="p-3 text-center text-gray-500">
                    No clients yet.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<!-- Client modal overlay -->
<div id="clientModalOverlay" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity duration-200">
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
                document.querySelectorAll('link[rel=\"stylesheet\"]').forEach((link) => {
                    if (link.href) doc.write(`<link rel=\"stylesheet\" href=\"${link.href}\">`);
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
@endsection

