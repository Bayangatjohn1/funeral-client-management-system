@extends('layouts.panel')

@section('page_title','Deceased Records')

@section('content')
@if(session('success'))
    <div class="mb-4 bg-green-50 border p-3 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

<div class="flex items-center justify-between gap-3">
    <form method="GET" action="{{ route('deceased.index') }}" class="flex flex-wrap items-center gap-2">
        <input name="q" value="{{ request('q') }}" class="border rounded px-3 py-2 text-sm" placeholder="Search name..." pattern="[A-Za-z ]+" title="Letters and spaces only">
        <button class="border px-3 py-2 rounded text-sm">Search</button>
        <a href="{{ route('deceased.index') }}" class="border px-3 py-2 rounded text-sm">Reset</a>
    </form>
</div>

<div class="mt-4 bg-white border rounded overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 border text-left">Deceased ID</th>
                <th class="p-2 border text-left">Name</th>
                <th class="p-2 border text-left">Age</th>
                <th class="p-2 border text-left">Date of Death</th>
                <th class="p-2 border text-left">Interment Date</th>
                <th class="p-2 border text-left">Place of Interment</th>
                <th class="p-2 border text-left">Linked Client</th>
                <th class="p-2 border text-left">Case Status</th>
                <th class="p-2 border text-left">Payment</th>
                <th class="p-2 border text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($deceaseds as $deceased)
            <tr class="hover:bg-gray-50">
                <td class="p-2 border font-semibold text-slate-700">{{ $deceased->deceased_code ?? 'DC-' . str_pad($deceased->id, 3, '0', STR_PAD_LEFT) }}</td>
                <td class="p-2 border">{{ $deceased->full_name }}</td>
                <td class="p-2 border">{{ $deceased->age ?? '-' }}</td>
                <td class="p-2 border">{{ ($deceased->died ?? $deceased->date_of_death)?->format('Y-m-d') ?? '-' }}</td>
                <td class="p-2 border">{{ $deceased->interment_at?->format('Y-m-d') ?? $deceased->interment?->format('Y-m-d') ?? '-' }}</td>
                <td class="p-2 border">{{ $deceased->place_of_cemetery ?? '-' }}</td>
                <td class="p-2 border">{{ $deceased->client?->full_name ?? '-' }}</td>
                <td class="p-2 border">
                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold uppercase tracking-wide
                        {{ $deceased->funeralCase?->case_status === 'COMPLETED' ? 'bg-green-100 text-green-700' : ($deceased->funeralCase?->case_status === 'ACTIVE' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600') }}">
                        {{ $deceased->funeralCase?->case_status ?? '—' }}
                    </span>
                </td>
                <td class="p-2 border">
                    @if($deceased->funeralCase)
                        <span class="text-sm font-semibold
                            {{ $deceased->funeralCase->payment_status === 'PAID' ? 'text-green-600' : ($deceased->funeralCase->payment_status === 'PARTIAL' ? 'text-amber-700' : 'text-red-600') }}">
                            {{ $deceased->funeralCase->payment_status }}
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td class="p-2 border">
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('deceased.show', $deceased) }}" data-url="{{ route('deceased.show', $deceased) }}" class="action-chip open-deceased-modal">
                            <i class="bi bi-eye"></i><span>View</span>
                        </a>
                        <a href="{{ route('deceased.edit', $deceased) }}" data-url="{{ route('deceased.edit', $deceased) }}" class="action-chip action-chip-primary open-deceased-modal">
                            <i class="bi bi-pencil-square"></i><span>Edit</span>
                        </a>
                        @if($deceased->funeralCase)
                            <a href="{{ route('payments.history', ['q' => $deceased->funeralCase->case_code]) }}" class="action-chip">
                                <i class="bi bi-cash-stack"></i><span>View Payment</span>
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="p-3 text-center text-gray-500">
                    No records yet.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

<!-- Deceased modal overlay -->
<div id="deceasedModalOverlay" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity duration-200">
    <div id="deceasedModalSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] bg-white rounded-2xl shadow-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-100">
        <button id="deceasedModalClose" type="button" class="absolute top-3 right-3 z-10 inline-flex items-center justify-center w-9 h-9 rounded-full bg-white shadow border text-slate-400 hover:text-black focus:outline-none">
            <i class="bi bi-x-lg"></i>
        </button>
        <div id="deceasedModalContent" class="overflow-y-auto max-h-[84vh] p-5 bg-slate-50">
            <div class="flex items-center justify-center py-8 text-slate-500 gap-2 text-sm">
                <i class="bi bi-arrow-repeat animate-spin"></i>
                <span>Loading...</span>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const overlay = document.getElementById('deceasedModalOverlay');
        const sheet = document.getElementById('deceasedModalSheet');
        const content = document.getElementById('deceasedModalContent');
        const closeBtn = document.getElementById('deceasedModalClose');
        const links = [...document.querySelectorAll('.open-deceased-modal')];

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
                // copy linked styles
                document.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
                    if (link.href) doc.write(`<link rel="stylesheet" href="${link.href}">`);
                });
                // copy inline styles (from loaded view)
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
                // Prefer the clean print-friendly view if present
                const view = doc.querySelector('#deceasedViewContent');
                const form = doc.querySelector('#deceasedEditForm');
                const payload = view || form;
                if (payload) {
                    content.innerHTML = payload.outerHTML;
                    // copy inline styles that target print/show content
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

