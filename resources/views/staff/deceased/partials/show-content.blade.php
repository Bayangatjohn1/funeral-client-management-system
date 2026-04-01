@php
    use Illuminate\Support\Facades\Storage;
    $isPrint = $isPrint ?? false;
@endphp

<div id="deceasedViewContent" class="print-container max-w-3xl mx-auto p-4 md:p-8 space-y-8 bg-gray-50/30">

    <div class="text-center space-y-4 py-6 border-b-2 border-[#c5a059]/30">
        <div class="inline-block px-4 py-1 rounded-full border border-[#c5a059] text-[#a68648] text-[10px] font-bold uppercase tracking-[0.3em] mb-2">
            Official Record
        </div>
        <h2 class="text-4xl md:text-5xl font-serif text-slate-900 leading-tight">{{ $deceased->full_name }}</h2>
        <div class="flex justify-center gap-6 text-sm font-medium text-slate-500 uppercase tracking-widest">
            <span>{{ $deceased->born?->format('Y') ?? '—' }} – {{ ($deceased->died ?? $deceased->date_of_death)?->format('Y') ?? '—' }}</span>
            <span class="text-[#c5a059]">|</span>
            <span>Age: {{ $deceased->age ?? '—' }}</span>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden print-card">
        <div class="bg-slate-900 px-6 py-3">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Personal Biography</h3>
        </div>
        <div class="p-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-[#a68648] uppercase">Full Legal Name</p>
                    <p class="text-lg font-semibold text-slate-800">{{ $deceased->full_name ?? '—' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-[#a68648] uppercase">Record Identifier</p>
                    <p class="text-lg font-mono text-slate-600">{{ $deceased->deceased_code ?? 'DC-' . str_pad($deceased->id,3,'0',STR_PAD_LEFT) }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Date of Birth</p>
                    <p class="text-base text-slate-700">{{ $deceased->born?->format('F d, Y') ?? '—' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-rose-400 uppercase">Date of Passing</p>
                    <p class="text-base font-bold text-rose-900">{{ ($deceased->died ?? $deceased->date_of_death)?->format('F d, Y') ?? '—' }}</p>
                </div>
            </div>
            <div class="pt-4 border-t border-slate-50">
                <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Last Residence</p>
                <p class="text-base text-slate-600 italic leading-relaxed">"{{ $deceased->address ?? '—' }}"</p>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden print-card">
        <div class="bg-slate-900 px-6 py-3">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Senior Citizen Benefits</h3>
        </div>
        <div class="p-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Eligibility Status</p>
                    @if($deceased->senior_citizen_status)
                        <span class="inline-flex items-center px-3 py-1 rounded text-xs font-bold bg-green-50 text-green-700 border border-green-100">ELIGIBLE</span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">STANDARD</span>
                    @endif
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">ID Number</p>
                    <p class="text-base font-bold text-slate-800">{{ $deceased->senior_citizen_id_number ?? '—' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase font-serif">Documentation</p>
                    @if($deceased->senior_proof_path)
                        <a href="{{ Storage::url($deceased->senior_proof_path) }}" target="_blank" class="text-sm font-semibold text-slate-700 underline">View / Download</a>
                    @else
                        <p class="text-sm italic text-slate-500">Not Provided</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-sm border border-slate-200 rounded-xl overflow-hidden print-card">
        <div class="bg-slate-900 px-6 py-3">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Service Details</h3>
        </div>
        <div class="p-8 space-y-8">
            <div class="flex items-start gap-4">
                <div class="p-3 bg-slate-50 text-[#a68648] rounded-full border border-slate-100 print-hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Interment Schedule</p>
                    <p class="text-lg font-bold text-slate-800">
                        {{ $deceased->interment_at?->format('l, F d, Y') ?? $deceased->interment?->format('F d, Y') ?? '—' }}
                    </p>
                    <p class="text-sm font-medium text-[#a68648] uppercase tracking-tighter">
                        at {{ $deceased->interment_at?->format('h:i A') ?? 'Time not specified' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-6 border-t border-slate-50">
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Cemetery Location</p>
                    <p class="text-base font-semibold text-slate-700">{{ $deceased->place_of_cemetery ?? '—' }}</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Wake Duration</p>
                    <p class="text-base font-semibold text-slate-700">{{ $deceased->wake_days ?? '—' }} Days</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase">Coffin Size</p>
                    <p class="text-base font-semibold text-slate-700 uppercase">{{ $deceased->coffin_size ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-[#fcfaf6] border-l-4 border-[#c5a059] rounded-r-xl p-8 flex flex-col sm:flex-row justify-between items-center gap-6 print-card">
        <div class="text-center sm:text-left">
            <p class="text-[10px] font-bold text-[#a68648] uppercase tracking-[0.2em] mb-1">Administrative Link</p>
            <h4 class="text-xl font-bold text-slate-800">Case #{{ $deceased->funeralCase?->case_code ?? '—' }}</h4>
            <p class="text-sm text-slate-500">{{ $deceased->funeralCase?->service_package ?? '—' }}</p>
        </div>
        <div class="text-right">
             <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Financial Status</p>
             <p class="text-sm font-bold text-slate-800 uppercase tracking-widest">{{ $deceased->funeralCase?->payment_status ?? 'PENDING' }}</p>
        </div>
    </div>

    @unless($isPrint)
    <div class="flex flex-col items-center gap-4 pt-8 no-print">
        <button id="printDeceasedBtn" type="button" class="px-8 py-3 bg-slate-900 text-white rounded-lg font-bold text-sm shadow-lg">
            Print Official Transcript
        </button>
        <p class="text-[10px] text-slate-400 uppercase tracking-[0.5em] font-bold">Sabangan Funeral Home</p>
    </div>
    @endunless

</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&display=swap');
    .font-serif { font-family: 'Libre Baskerville', serif; }

    @media print {
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        body { zoom: 0.68; }
        #deceasedViewContent {
            font-size: 11px !important;
            line-height: 1.15 !important;
            padding: 0 !important;
            margin: 0 auto !important;
        }
        #deceasedViewContent h2 { font-size: 21px !important; }
        #deceasedViewContent h3 { font-size: 9px !important; }
        #deceasedViewContent .p-8 { padding: 10px !important; }
        #deceasedViewContent .py-6 { padding-top: 6px !important; padding-bottom: 6px !important; }
        #deceasedViewContent .space-y-8 > :not([hidden]) ~ :not([hidden]) { margin-top: 10px !important; }
        #deceasedViewContent .gap-8 { gap: 10px !important; }
        #deceasedViewContent .gap-6 { gap: 8px !important; }
        #deceasedViewContent .py-6.border-b-2 { border-bottom-width: 1px !important; padding-top: 6px !important; padding-bottom: 6px !important; }
        #deceasedViewContent .inline-block.px-4.py-1 { padding: 3px 6px !important; }
        #deceasedViewContent .text-4xl,
        #deceasedViewContent .text-5xl { font-size: 22px !important; }
        #deceasedViewContent .text-lg { font-size: 12px !important; }
        #deceasedViewContent .text-base { font-size: 11px !important; }
        #deceasedViewContent .text-sm { font-size: 10px !important; }
        #deceasedViewContent .text-xs { font-size: 9px !important; }
        .detail-hero,
        .print-card {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .print-container {
            padding: 0 !important;
            margin: 0 auto !important;
        }

        /* Force full document render */
        html, body {
            height: auto !important;
            min-height: auto !important;
            overflow: visible !important;
            background: white !important;
            -webkit-print-color-adjust: exact;
        }

        /* Kill modal/fixed scroll clipping */
        .fixed, .absolute, .overflow-y-auto, .overflow-y-hidden, .overflow-hidden {
            position: static !important;
            inset: auto !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }
        #deceasedViewContent,
        .print-container,
        .modal-container,
        .modal-content,
        .main-wrapper,
        .container,
        .mx-auto {
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            margin: 0 !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        #deceasedModalOverlay,
        #deceasedModalSheet,
        #deceasedModalContent {
            position: static !important;
            inset: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
            background: transparent !important;
            box-shadow: none !important;
            transform: none !important;
        }
        #deceasedViewContent * {
            max-height: none !important;
            overflow: visible !important;
        }
        /* keep entire record on one page */
        #deceasedViewContent {
            page-break-before: avoid;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        nav, aside, footer, .no-print {
            display: none !important;
        }

        .print-card {
            break-inside: avoid;
            border: 1px solid #e2e8f0 !important;
            margin-bottom: 1.5rem !important;
        }

        .bg-slate-900 { background-color: #0f172a !important; -webkit-print-color-adjust: exact; }
        .bg-[#fcfaf6] { background-color: #fcfaf6 !important; -webkit-print-color-adjust: exact; }
        .text-white { color: white !important; }
        
        .print-hidden { display: none !important; }
    }
</style>

@if(request('print'))
    <script>
        window.addEventListener('load', () => {
            window.print();
        });
    </script>
@else
    <script>
        (() => {
            const btn = document.getElementById('printDeceasedBtn');
            if (!btn) return;
            btn.addEventListener('click', () => {
                const source = document.getElementById('deceasedViewContent');
                if (!source) {
                    window.print();
                    return;
                }

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

                // copy existing stylesheets
                document.querySelectorAll('link[rel=\"stylesheet\"]').forEach((link) => {
                    if (link.href) {
                        doc.write(`<link rel=\"stylesheet\" href=\"${link.href}\">`);
                    }
                });
                // copy inline styles in this partial
                document.querySelectorAll('style').forEach((style) => {
                    if (style.innerHTML.includes('print-card') || style.innerHTML.includes('print-container')) {
                        doc.write('<style>' + style.innerHTML + '</style>');
                    }
                });

                doc.write('</head><body>');
                doc.write(source.outerHTML);
                doc.write('</body></html>');
                doc.close();

                iframe.onload = () => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    setTimeout(() => iframe.remove(), 1000);
                };
            });
        })();
    </script>
@endif
