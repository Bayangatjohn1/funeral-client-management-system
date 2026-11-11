<div class="staff-balances-panel">
    <div class="staff-balances-scroll" role="region" aria-label="Outstanding balances table" tabindex="0">
        <table class="staff-balances-table" aria-label="Outstanding Balances">
            <thead>
                <tr>
                    <th scope="col">Deceased Name</th>
                    <th scope="col">Case ID</th>
                    <th scope="col">Case Status</th>
                    <th scope="col" class="staff-balance-value">Balance</th>
                    <th scope="col" class="staff-balance-action">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($unpaidCases as $case)
                    <tr>
                        <th scope="row">
                            <a class="ref-balance-case" href="{{ route('funeral-cases.show', ['funeral_case' => $case->id, 'return_to' => request()->fullUrl()]) }}">
                                {{ $case->deceased?->full_name ?? 'Unknown' }}
                            </a>
                        </th>
                        <td class="staff-balance-code ref-mono">{{ $case->case_code }}</td>
                        <td><span class="staff-balance-status" data-case-status="{{ $case->case_status }}">{{ Str::headline(strtolower((string) $case->case_status)) ?: '—' }}</span></td>
                        <td class="staff-balance-value"><span class="ref-balance-amount">&#8369; {{ number_format((float) $case->balance_amount, 2) }}</span></td>
                        <td class="staff-balance-action">
                            <a href="{{ route('payments.history', ['case_id' => $case->id, 'record_payment' => 1, 'dashboard_search' => 1]) }}"
                               class="staff-link staff-balance-record" aria-label="Record Payment for {{ $case->case_code }}">Record</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="staff-empty">
                                <i class="bi bi-patch-check" aria-hidden="true"></i>
                                No cases with remaining balances.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <nav class="staff-balances-pagination" aria-label="Outstanding balances pages">
        <span>Showing {{ $unpaidCases->isEmpty() ? '0' : $unpaidCases->firstItem().'–'.$unpaidCases->lastItem() }} of {{ $unpaidCases->total() }} {{ Str::plural('case', $unpaidCases->total()) }}</span>
        <div>
            @if($unpaidCases->onFirstPage())
                <button type="button" disabled>Prev</button>
            @else
                <a href="{{ $unpaidCases->previousPageUrl() }}" rel="prev">Prev</a>
            @endif
            @if($unpaidCases->hasMorePages())
                <a href="{{ $unpaidCases->nextPageUrl() }}" rel="next">Next</a>
            @else
                <button type="button" disabled>Next</button>
            @endif
        </div>
    </nav>
</div>
