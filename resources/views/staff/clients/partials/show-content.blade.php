@php
    $payments = $client->funeralCases->flatMap(function ($case) {
        return $case->payments->map(function ($payment) use ($case) {
            $payment->case_code = $case->case_code;
            return $payment;
        });
    });
@endphp

<div id="clientViewContent" class="print-container max-w-4xl mx-auto p-8 bg-white space-y-10">
    <div class="flex items-center gap-4 border-b-2 border-black pb-4">
        <img src="{{ asset('images/login-logo.png') }}" alt="Sabangan Caguioa Logo" class="h-14 w-auto">
        <div>
            <h1 class="text-2xl font-bold uppercase text-black tracking-wide">Sabangan Caguioa Funeral Home</h1>
            <p class="text-sm font-medium text-gray-600">Official Client Record</p>
        </div>
    </div>

    <div class="flex justify-between items-end border-b border-gray-300 pb-3">
        <div>
            <span class="text-[10px] uppercase font-bold text-gray-500 block">Client Name</span>
            <h2 class="text-3xl font-bold text-black uppercase">{{ $client->full_name }}</h2>
        </div>
        <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-gray-500 block">Client ID</span>
            <span class="text-lg font-mono font-bold text-black">{{ $client->client_code ?? 'CL-' . str_pad($client->id,3,'0',STR_PAD_LEFT) }}</span>
        </div>
    </div>

    <section>
        <h3 class="text-sm font-bold uppercase border-b border-black mb-3 pb-1">1. Contact Information</h3>
        <div class="grid grid-cols-2 gap-x-12 gap-y-3">
            <div class="py-1 flex justify-between">
                <span class="text-sm text-gray-600">Contact Number:</span>
                <span class="text-sm font-bold">{{ $client->contact_number ?? '-' }}</span>
            </div>
            <div class="py-1 flex justify-between">
                <span class="text-sm text-gray-600">Date Added:</span>
                <span class="text-sm font-bold">{{ $client->created_at?->format('M d, Y') ?? '-' }}</span>
            </div>
            <div class="col-span-2 py-1">
                <span class="text-sm text-gray-600 block mb-1">Address:</span>
                <span class="text-sm font-bold italic">{{ $client->address ?? '-' }}</span>
            </div>
        </div>
    </section>

    <section>
        <h3 class="text-sm font-bold uppercase border-b border-black mb-3 pb-1">2. Linked Deceased Records</h3>
        <div class="overflow-hidden border border-slate-200 rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-left">
                    <tr>
                        <th class="p-2">Name</th>
                        <th class="p-2">Address</th>
                        <th class="p-2">Born</th>
                        <th class="p-2">Age</th>
                        <th class="p-2">Died</th>
                        <th class="p-2">Interment</th>
                        <th class="p-2">Cemetery</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($client->deceaseds as $deceased)
                    <tr class="border-t border-slate-100">
                        <td class="p-2">{{ $deceased->full_name }}</td>
                        <td class="p-2">{{ $deceased->address ?? '-' }}</td>
                        <td class="p-2">{{ $deceased->born?->format('Y-m-d') ?? '-' }}</td>
                        <td class="p-2">{{ $deceased->age ?? '-' }}</td>
                        <td class="p-2">{{ ($deceased->died ?? $deceased->date_of_death)?->format('Y-m-d') ?? '-' }}</td>
                        <td class="p-2">{{ $deceased->interment?->format('Y-m-d') ?? '-' }}</td>
                        <td class="p-2">{{ $deceased->place_of_cemetery ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-slate-500">No deceased records.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h3 class="text-sm font-bold uppercase border-b border-black mb-3 pb-1">3. Funeral Cases</h3>
        <div class="overflow-hidden border border-slate-200 rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-left">
                    <tr>
                        <th class="p-2">Case Code</th>
                        <th class="p-2">Deceased</th>
                        <th class="p-2">Package</th>
                        <th class="p-2">Total</th>
                        <th class="p-2">Payment Status</th>
                        <th class="p-2">Paid At</th>
                        <th class="p-2">Case Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($client->funeralCases as $case)
                    <tr class="border-t border-slate-100">
                        <td class="p-2">{{ $case->case_code }}</td>
                        <td class="p-2">{{ $case->deceased?->full_name ?? '-' }}</td>
                        <td class="p-2">{{ $case->service_package ?? '-' }}</td>
                        <td class="p-2">{{ number_format((float) $case->total_amount, 2) }}</td>
                        <td class="p-2">{{ $case->payment_status ?? '-' }}</td>
                        <td class="p-2">{{ $case->paid_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="p-2">{{ $case->case_status ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-slate-500">No funeral cases.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <h3 class="text-sm font-bold uppercase border-b border-black mb-3 pb-1">4. Payment History</h3>
        <div class="overflow-hidden border border-slate-200 rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-left">
                    <tr>
                        <th class="p-2">Case Code</th>
                        <th class="p-2">Method</th>
                        <th class="p-2">Amount</th>
                        <th class="p-2">Paid Date/Time</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr class="border-t border-slate-100">
                        <td class="p-2">{{ $payment->case_code }}</td>
                        <td class="p-2">{{ $payment->method }}</td>
                        <td class="p-2">{{ number_format((float) $payment->amount, 2) }}</td>
                        <td class="p-2">{{ $payment->paid_at?->format('Y-m-d H:i') ?? $payment->paid_date?->format('Y-m-d') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-4 text-center text-slate-500">No payments yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-20 grid grid-cols-2 gap-20 print-only">
        <div class="text-center border-t border-black pt-2">
            <p class="text-xs font-bold uppercase">Staff Signature</p>
        </div>
        <div class="text-center border-t border-black pt-2">
            <p class="text-xs font-bold uppercase">Date Signed</p>
        </div>
    </div>

    <div class="mt-8 flex justify-center no-print">
        <button id="printClientBtn" type="button" class="px-12 py-3 bg-black text-white font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors shadow-lg rounded">
            Print Record
        </button>
    </div>
</div>

<style>
    .print-only { display: none; }
    @media print {
        nav, aside, footer, header, .no-print { display: none !important; }
        html, body { height: auto !important; overflow: visible !important; background: white !important; }
        #clientViewContent { max-width: 100% !important; }
        .print-only { display: grid !important; }
    }
</style>
