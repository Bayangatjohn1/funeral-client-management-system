@php
    $summarySnapshot = app(\App\Support\CaseSnapshotDisplayService::class)->data($funeral_case);
    $summaryInterment = $funeral_case->interment_at ?? $funeral_case->deceased?->interment_at ?? $funeral_case->serviceDetail?->internment_date ?? $funeral_case->deceased?->interment;
    $summaryDate = fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('M d, Y') : 'Not set';
    $summarySchedule = fn ($date, $time) => $date ? $summaryDate($date).($time ? ' · '.\Illuminate\Support\Carbon::parse($time)->format('h:i A') : '') : 'Not set';
@endphp
<div id="caseRecordSummary" class="record-summary">
    <section class="record-summary-identity">
        <span class="record-summary-code">Case ID: {{ $funeral_case->case_code }}</span>
        <h3>{{ $funeral_case->deceased?->full_name ?: 'Deceased not recorded' }}</h3>
        <p><i class="bi bi-building" aria-hidden="true"></i><span>{{ $funeral_case->branch?->branch_code }} · {{ $funeral_case->branch?->branch_name ?: 'Branch not recorded' }}</span></p>
        <dl class="record-summary-fields">
            <div><dt>Case status</dt><dd><x-status-badge class="case-status-text" :data-case-status="$funeral_case->case_status" :status="$funeral_case->case_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $funeral_case->case_status))" /></dd></div>
            <div><dt>Payment status</dt><dd><x-status-badge class="case-status-text" :status="$funeral_case->payment_status" :label="\Illuminate\Support\Str::headline(strtolower((string) $funeral_case->payment_status))" /></dd></div>
        </dl>
    </section>
    <section class="record-summary-section" aria-label="Client summary">
        <h3><i class="bi bi-person" aria-hidden="true"></i> Client</h3>
        <dl class="record-summary-fields">
            <div><dt>Name</dt><dd>{{ $funeral_case->client?->full_name ?: 'Not provided' }}</dd></div>
            <div><dt>Contact number</dt><dd>{{ $funeral_case->client?->contact_number ?: 'Not provided' }}</dd></div>
        </dl>
    </section>
    <section class="record-summary-section" aria-label="Service and schedule summary">
        <h3><i class="bi bi-calendar3" aria-hidden="true"></i> Service &amp; schedule</h3>
        <dl class="record-summary-fields">
            <div><dt>Package</dt><dd>{{ $summarySnapshot['package_name'] }}</dd></div>
            <div><dt>Service type</dt><dd>{{ $funeral_case->service_type ?: 'Not provided' }}</dd></div>
            @if($funeral_case->viewing_type)
                <div><dt>Viewing type</dt><dd>{{ $funeral_case->viewing_type === 'chapel' ? 'Funeral Home Chapel Viewing' : 'Home Viewing' }}</dd></div>
            @endif
            <div class="is-wide"><dt>{{ match ($funeral_case->viewing_type) { 'chapel' => 'Chapel name', 'home' => 'Wake address', default => 'Wake location' } }}</dt><dd>{{ $funeral_case->wake_location ?: 'Not set' }}</dd></div>
            @if($funeral_case->viewing_type === 'chapel' && $funeral_case->chapel_address)
                <div class="is-wide"><dt>Chapel address</dt><dd>{{ $funeral_case->chapel_address }}</dd></div>
            @endif
            <div><dt>Funeral service</dt><dd>{{ $summarySchedule($funeral_case->funeral_service_at, $funeral_case->funeral_service_time) }}</dd></div>
            @if($ceremonyLocation = $funeral_case->ceremonyLocation())
                <div><dt>Funeral Ceremony Location</dt><dd>{{ $ceremonyLocation }}</dd></div>
            @endif
            <div><dt>Interment</dt><dd>{{ $summarySchedule($summaryInterment, $funeral_case->interment_time ?? ($summaryInterment ? \Illuminate\Support\Carbon::parse($summaryInterment)->format('H:i') : null)) }}</dd></div>
        </dl>
    </section>
    <section class="record-summary-section" aria-label="Payment summary">
        <h3><i class="bi bi-wallet2" aria-hidden="true"></i> Payment summary</h3>
        <dl class="record-summary-money">
            <div><dt>Total amount</dt><dd>₱{{ number_format((float) $funeral_case->total_amount, 2) }}</dd></div>
            <div class="is-paid"><dt>Paid</dt><dd>₱{{ number_format((float) $funeral_case->total_paid, 2) }}</dd></div>
            <div class="{{ (float) $funeral_case->balance_amount > 0 ? 'is-balance' : 'is-paid' }}"><dt>Remaining balance</dt><dd>₱{{ number_format((float) $funeral_case->balance_amount, 2) }}</dd></div>
        </dl>
    </section>
    <section class="record-summary-section" aria-label="Record information">
        <h3><i class="bi bi-file-earmark-text" aria-hidden="true"></i> Record information</h3>
        <dl class="record-summary-fields">
            <div><dt>Encoded by</dt><dd>{{ $funeral_case->encodedBy?->name ?: 'Not recorded' }}</dd></div>
            <div><dt>Date encoded</dt><dd>{{ $funeral_case->created_at?->format('M d, Y · h:i A') ?: 'Not recorded' }}</dd></div>
        </dl>
    </section>
</div>
