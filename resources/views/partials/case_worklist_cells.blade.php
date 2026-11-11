<td data-label="Case ID">
    <div class="table-primary whitespace-nowrap records-case-code">{{ $case->case_code }}</div>
</td>
@if($showBranch ?? false)
    <td data-label="Branch">
        <div class="table-primary">{{ $case->branch?->branch_code ?? '-' }} · {{ $case->branch?->branch_name ?? '-' }}</div>
    </td>
@endif
<td data-label="Deceased">
    <div class="table-primary records-person-names">{{ $case->deceased?->full_name ?? '-' }}</div>
</td>
<td data-label="Interment Date">
    <div class="table-primary whitespace-nowrap">{{ $worklistInterment ? $worklistInterment->format('M d, Y') : '-' }}</div>
</td>
<td class="table-status-cell" data-label="Case Status">
    <x-status-badge class="case-status-text" :data-case-status="$case->case_status" :status="$case->case_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->case_status))" />
</td>
<td class="table-status-cell table-payment-status-cell" data-label="Payment Status">
    <x-status-badge :status="$case->payment_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $case->payment_status))" class="table-payment-status-badge case-status-text" />
</td>
<td class="table-col-number" data-label="Remaining Balance">
    <div class="table-primary table-financial-total whitespace-nowrap">₱{{ number_format((float) $case->balance_amount, 2) }}</div>
</td>
<td class="table-col-actions" data-label="Actions">
    @include('partials.case_record_actions', ['case' => $case])
</td>
