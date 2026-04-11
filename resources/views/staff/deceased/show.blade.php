@extends('layouts.panel')

@section('page_title', 'Deceased Information')
@section('page_desc', 'Review complete deceased profile and case linkage.')

@section('content')
@php use Illuminate\Support\Facades\Storage; @endphp

<div id="deceasedViewContent" class="print-container max-w-4xl mx-auto p-8 bg-white">
    
    <div class="flex items-center gap-4 border-b-2 border-black pb-4 mb-8">
        <img src="{{ asset('images/login-logo.png') }}" alt="Sabangan Caguioa Logo" class="h-14 w-auto">
        <div>
            <h1 class="text-2xl font-bold uppercase text-black tracking-wide">Sabangan Caguioa Funeral Home</h1>
            <p class="text-sm font-medium text-gray-600">Official Deceased Record</p>
        </div>
    </div>

    <div class="flex justify-between items-end border-b border-gray-300 pb-3 mb-8">
        <div>
            <span class="text-[10px] uppercase font-bold text-gray-500 block">Name of the Deceased</span>
            <h2 class="text-3xl font-bold text-black uppercase">{{ $deceased->full_name }}</h2>
        </div>
        <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-gray-500 block">Record ID</span>
            <span class="text-lg font-mono font-bold text-black">{{ $deceased->deceased_code ?? 'DC-' . str_pad($deceased->id, 3, '0', STR_PAD_LEFT) }}</span>
        </div>
    </div>

    <div class="space-y-12">
        
        <section>
            <h3 class="text-sm font-bold uppercase border-b border-black mb-4 pb-1">1. Personal Information</h3>
            <div class="grid grid-cols-2 gap-x-12 gap-y-3">
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Date of Birth:</span>
                    <span class="text-sm font-bold">{{ $deceased->born?->format('F d, Y') ?? '—' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Age:</span>
                    <span class="text-sm font-bold">{{ $deceased->age ?? '—' }} years</span>
                </div>

                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Date of Death:</span>
                    <span class="text-sm font-bold">{{ ($deceased->died ?? $deceased->date_of_death)?->format('F d, Y') ?? '—' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Address:</span>
                    <span class="text-sm font-bold italic text-right">{{ $deceased->address ?? '—' }}</span>
                </div>

                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Senior Citizen ID:</span>
                    <span class="text-sm font-bold">{{ $deceased->senior_citizen_id_number ?? 'Not provided' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Senior Citizen Proof:</span>
                    @if($deceased->senior_proof_path)
                        <a href="{{ Storage::url($deceased->senior_proof_path) }}" target="_blank" class="text-sm font-bold text-blue-700 underline">View File</a>
                    @else
                        <span class="text-sm font-bold">Not uploaded</span>
                    @endif
                </div>
            </div>
        </section>

        <section>
            <h3 class="text-sm font-bold uppercase border-b border-black mb-4 pb-1">2. Interment & Service Details</h3>
            <div class="grid grid-cols-2 gap-x-12 gap-y-3">
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Funeral Service Date:</span>
                    <span class="text-sm font-bold">{{ $deceased->funeralCase?->funeral_service_at?->format('F d, Y') ?? '—' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Service Type:</span>
                    <span class="text-sm font-bold">{{ $deceased->funeralCase?->service_type ?? '—' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Wake Location:</span>
                    <span class="text-sm font-bold">{{ $deceased->funeralCase?->wake_location ?? '—' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Wake Days:</span>
                    <span class="text-sm font-bold">{{ $deceased->wake_days ?? '—' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Interment Date:</span>
                    <span class="text-sm font-bold">{{ $deceased->interment_at?->format('F d, Y') ?? '—' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Interment Time:</span>
                    <span class="text-sm font-bold">{{ $deceased->interment_at?->format('h:i A') ?? '—' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Cemetery / Location:</span>
                    <span class="text-sm font-bold">{{ $deceased->place_of_cemetery ?? '—' }}</span>
                </div>
                <div class="py-1 flex justify-between">
                    <span class="text-sm text-gray-600">Case Status:</span>
                    <span class="text-sm font-bold">{{ $deceased->funeralCase?->case_status ?? '—' }}</span>
                </div>
            </div>
        </section>

        <section>
            <h3 class="text-sm font-bold uppercase border-b border-black mb-4 pb-1">3. Case & Payment Summary</h3>
            <div class="grid grid-cols-3 gap-6">
                <div>
                    <span class="text-[10px] text-gray-500 block uppercase font-bold">Case Code</span>
                    <span class="text-sm font-bold">#{{ $deceased->funeralCase?->case_code ?? 'â€”' }}</span>
                </div>
                <div>
                    <span class="text-[10px] text-gray-500 block uppercase font-bold">Package</span>
                    <span class="text-sm font-bold uppercase">{{ $deceased->funeralCase?->service_package ?? 'â€”' }}</span>
                </div>
                <div class="text-right">
                    <span class="text-[10px] text-gray-500 block uppercase font-bold">Payment Status</span>
                    <span class="text-sm font-bold">{{ $deceased->funeralCase?->payment_status ?? 'â€”' }}</span>
                </div>
            </div>
        </section>
    </div>

    <div class="mt-24 grid grid-cols-2 gap-20 print-only">
        <div class="text-center border-t border-black pt-2">
            <p class="text-xs font-bold uppercase">Staff Signature</p>
        </div>
        <div class="text-center border-t border-black pt-2">
            <p class="text-xs font-bold uppercase">Date Signed</p>
        </div>
    </div>

    <div class="mt-12 no-print flex justify-center">
        <button id="printDeceasedBtn" type="button" class="px-12 py-3 bg-black text-white font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors shadow-lg">
            Print Record
        </button>
    </div>
</div>

<style>
    /* Professional Clean Font */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap');
    body { font-family: 'Inter', sans-serif; }

    @media print {
        .print-only { display: grid !important; }
        /* FORCE BROWSER TO SHOW FULL CONTENT */
        html, body {
            height: auto !important;
            overflow: visible !important;
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
        }

        /* HIDE ALL WEBSITE ELEMENTS (SIDEBAR, NAV, ETC) */
        nav, aside, footer, header, .no-print, .modal-header, .close {
            display: none !important;
        }

        /* CONVERT MODAL TO A FULL PAGE DOCUMENT */
        .print-container, 
        .modal, 
        .modal-dialog, 
        .modal-content {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            height: auto !important;
            overflow: visible !important;
            display: block !important;
            padding: 1.5cm !important; /* Proper document margins */
            border: none !important;
            box-shadow: none !important;
            background: white !important;
        }

        /* ENSURE DARK TEXT AND VISIBLE BORDERS */
        .border-b { border-bottom: 1px solid #000 !important; }
        .text-black, .text-gray-600 { color: #000 !important; }
        
        /* FIX FOR BACKGROUND COLORS */
        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
    /* hide print-only blocks on screen */
    .print-only { display: none; }
</style>

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

            // include linked stylesheets (app.css, etc.)
            document.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
                if (link.href) {
                    doc.write(`<link rel="stylesheet" href="${link.href}">`);
                }
            });
            // include inline styles from this page
            document.querySelectorAll('style').forEach((style) => {
                doc.write('<style>' + style.innerHTML + '</style>');
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

@endsection

