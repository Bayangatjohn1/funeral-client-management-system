<div id="clientViewContent" class="print-container max-w-4xl mx-auto p-8 bg-white space-y-10">
    <div class="client-print-brand flex items-center gap-4 border-b-2 border-black pb-4">
        <img src="{{ asset('images/login-logo.png') }}" alt="Sabangan Caguioa Logo" class="h-14 w-auto">
        <div>
            <h1 class="text-2xl font-bold uppercase text-black tracking-wide">Sabangan Caguioa Funeral Home</h1>
            <p class="text-sm font-medium text-gray-600">Client Summary</p>
        </div>
    </div>

    <div class="client-profile-identity flex justify-between items-end border-b border-gray-300 pb-3">
        <div>
            <span class="text-[10px] uppercase font-bold text-gray-500 block">Family Representative</span>
            <h2 class="text-3xl font-bold text-black uppercase">{{ $client->full_name }}</h2>
        </div>
        <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-gray-500 block">Directory ID</span>
            <span class="text-lg font-mono font-bold text-black">{{ $client->directoryCode() }}</span>
        </div>
    </div>

    <section>
        <h3 class="text-sm font-bold uppercase border-b border-black mb-3 pb-1">Contact Information</h3>
        <div class="client-profile-contacts grid grid-cols-2 gap-x-12 gap-y-3">
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
        <h3 class="text-sm font-bold uppercase border-b border-black mb-3 pb-1">Linked Cases</h3>
        <div class="client-profile-table" role="region" aria-label="Linked cases" tabindex="0">
            <table class="w-full text-sm">
                <thead class="text-left">
                    <tr>
                        <th scope="col">Case Code</th>
                        <th scope="col">Deceased</th>
                        <th scope="col">Case Status</th>
                        <th scope="col">Payment Status</th>
                        <th scope="col" class="client-profile-balance">Remaining Balance</th>
                        <th scope="col" class="no-print text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($client->funeralCases as $case)
                    <tr>
                        <td>{{ $case->case_code }}</td>
                        <td>
                            {{ $case->deceased?->full_name ?? 'Not recorded' }}
                            @if($relationship = $case->clientRelationship())
                                <small class="block text-gray-600">{{ $relationship }}</small>
                            @endif
                        </td>
                        <td>{{ $case->case_status ? str($case->case_status)->lower()->headline() : 'Not recorded' }}</td>
                        <td>{{ $case->payment_status === 'PARTIAL' ? 'Partially paid' : ($case->payment_status ? str($case->payment_status)->lower()->headline() : 'Not recorded') }}</td>
                        <td class="client-profile-balance">PHP {{ number_format((float) $case->balance_amount, 2) }}</td>
                        <td class="no-print text-right">
                            @can('view', $case)
                                <a class="client-profile-case-link" href="{{ route('funeral-cases.show', [
                                    'funeral_case' => $case,
                                    'return_to' => route('clients.show', $client),
                                ]) }}" aria-label="View case {{ $case->case_code }}">View Case</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-slate-500">No linked cases yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="client-print-signature mt-20 grid grid-cols-2 gap-20 print-only">
        <div class="text-center border-t border-black pt-2">
            <p class="text-xs font-bold uppercase">Staff Signature</p>
        </div>
        <div class="text-center border-t border-black pt-2">
            <p class="text-xs font-bold uppercase">Date Signed</p>
        </div>
    </div>

    <div class="mt-8 flex justify-center no-print">
        <button id="printClientBtn" type="button" class="px-12 py-3 bg-black text-white font-bold uppercase tracking-widest hover:bg-gray-800 transition-colors shadow-lg rounded">
            <i class="bi bi-printer" aria-hidden="true"></i>
            Print Client Summary
        </button>
    </div>
</div>

<style>
    #clientViewContent .client-profile-table :is(th, td) { padding: 12px; }
    #clientViewContent .client-profile-balance { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .print-only { display: none; }
    @media print {
        nav, aside, footer, header, .no-print { display: none !important; }
        html, body { height: auto !important; overflow: visible !important; background: white !important; }
        #clientViewContent { max-width: 100% !important; }
        .print-only { display: grid !important; }
    }
</style>
