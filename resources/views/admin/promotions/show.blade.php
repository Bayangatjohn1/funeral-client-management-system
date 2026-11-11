@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')
@section('page_title', 'Promotion Details')
@section('hide_layout_topbar', '1')
@section('suppress_layout_page_header', '1')
@section('content')
@php
    $issues = $status === 'Incomplete Setup' ? $promotion->reviewFields($promotion->package) : [];
    $statusIcon = match ($status) {
        'Active' => 'check-circle',
        'Scheduled' => 'calendar-event',
        'Expired' => 'calendar-x',
        'Turned Off' => 'power',
        'Incomplete Setup' => 'exclamation-triangle',
        default => 'info-circle',
    };
    $discountType = strtoupper((string) $promotion->discount_type);
    $discountValue = is_numeric($promotion->discount_value)
        ? ($discountType === 'PERCENT' ? number_format((float) $promotion->discount_value, 2) . '%' : '₱' . number_format((float) $promotion->discount_value, 2))
        : 'Not configured';
@endphp

@include('admin.service-management.partials.detail-styles')
<style>
    .svc-detail-card.promotion-detail { max-width:760px; margin-inline:auto; }
    .promotion-detail .svc-detail-overview { display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .promotion-detail .svc-detail-overview-price { text-align:left; }
    .promotion-detail .svc-detail-overview-price strong { overflow-wrap:anywhere; }
    .promotion-detail .svc-detail-stat strong { overflow-wrap:anywhere; }
    .promotion-detail .promotion-status-note { display:flex; align-items:flex-start; gap:9px; margin:14px 24px 0; padding:12px 14px; border:1px solid var(--border); border-radius:9px; background:var(--surface-muted); color:var(--ink-muted); font-size:13px; line-height:1.55; }
    .promotion-detail .promotion-status-note i { margin-top:2px; color:var(--brand); }
    .promotion-detail .promotion-issues { margin:14px 24px 0; padding:12px 14px; border:1px solid color-mix(in srgb,var(--danger,#b5473c) 35%,transparent); border-radius:9px; background:color-mix(in srgb,var(--danger,#b5473c) 8%,var(--card)); color:var(--ink); font-size:13px; }
    .promotion-detail .promotion-issues ul { margin:6px 0 0; padding-left:20px; }
    .promotion-detail .promotion-issues li { margin-top:3px; }
    .promotion-detail .svc-detail-badge.is-review { color:#9A5B00; }
    html[data-theme='dark'] .promotion-detail .svc-detail-badge.is-review { color:#F4C66A; }
    .promotion-detail .svc-detail-actions form { margin:0; }
    .promotion-detail .svc-detail-actions .is-warning { border-color:#B5473C; background:#B5473C; color:#fff; }
    .promotion-detail .svc-detail-actions .is-restore { border-color:var(--brand); background:var(--brand); color:#fff; }
    .promotion-confirm-dialog { width:min(400px,calc(100vw - 32px)); max-height:calc(100dvh - 32px); padding:16px; border:1px solid var(--border); border-radius:14px; background:var(--card); color:var(--ink); box-shadow:0 20px 60px rgb(15 23 42 / .28); }
    .promotion-confirm-dialog::backdrop { background:rgb(15 23 42 / .48); }
    .promotion-confirm-dialog__body { display:grid; justify-items:center; gap:7px; text-align:center; }
    .promotion-confirm-dialog__icon { display:grid; width:40px; height:40px; place-items:center; border-radius:11px; background:var(--surface-muted); color:var(--brand); font-size:17px; }
    .promotion-confirm-dialog[data-feedback-kind='warning'] .promotion-confirm-dialog__icon { background:var(--feedback-warning-bg); color:var(--feedback-warning-fg); }
    .promotion-confirm-dialog[data-feedback-kind='success'] .promotion-confirm-dialog__icon { background:var(--feedback-success-bg); color:var(--feedback-success-fg); }
    .promotion-confirm-dialog h2 { margin:0; font-size:1rem; font-weight:700; }
    .promotion-confirm-dialog p { margin:4px 0 0; color:var(--ink-muted); font-size:13px; line-height:1.45; }
    .promotion-confirm-dialog footer { display:flex; justify-content:center; gap:8px; margin-top:12px; }
    .promotion-confirm-dialog :is(button):focus-visible { outline:2px solid var(--brand); outline-offset:2px; }
    .promotion-confirm-dialog [data-promotion-confirm-submit] { border-color:var(--brand); background:var(--brand); color:#fff; }
    .promotion-confirm-dialog [data-promotion-confirm-submit].is-warning { border-color:#B5473C; background:#B5473C; }
    @media(max-width:600px) {
        .promotion-detail .promotion-status-note,.promotion-detail .promotion-issues { margin-inline:16px; }
        .promotion-detail .svc-detail-overview { align-items:flex-start; flex-direction:column; }
    }
    @media(max-width:480px) { .promotion-confirm-dialog footer { flex-direction:column-reverse; } .promotion-confirm-dialog footer button { width:100%; } }
</style>

<section class="svc-detail">
    @include('admin.service-management.partials.status-feedback')
    <article class="svc-detail-card promotion-detail">
        @include('admin.service-management.partials.detail-header', [
            'detailTitle' => 'Promotion Details',
            'detailRecord' => 'Promotion ID: ' . $promotion->getKey(),
            'detailName' => $promotion->name ?: 'Promotion',
            'detailActive' => $status === 'Active',
            'detailAvailable' => null,
            'detailPromo' => null,
            'detailSubtitle' => 'Promotion Details',
            'detailStatusLabel' => $status,
            'detailStatusClass' => $status === 'Active' ? 'is-active' : ($status === 'Incomplete Setup' ? 'is-review' : 'is-muted'),
            'detailStatusIcon' => $statusIcon,
        ])

        <div class="svc-detail-overview">
            <div class="svc-detail-overview-price"><span>Discount</span><strong>{{ $discountValue }}</strong></div>
            @if($status === 'Turned Off')
                <span class="svc-detail-note"><i class="bi bi-power" aria-hidden="true"></i> This promotion was manually turned off by a System Admin. Its configuration remains saved and it will not apply to new cases.</span>
            @elseif($status === 'Expired')
                <span class="svc-detail-note"><i class="bi bi-calendar-x" aria-hidden="true"></i> This promotion has passed its End Date and will not apply to new cases. Update its schedule before turning it on again.</span>
            @elseif($status === 'Scheduled')
                <span class="svc-detail-note"><i class="bi bi-calendar-event" aria-hidden="true"></i> This promotion is scheduled to become active on its Start Date, unless it is turned off first.</span>
            @elseif($status === 'Active')
                <span class="svc-detail-note"><i class="bi bi-check-circle" aria-hidden="true"></i> This promotion is currently eligible under the existing discount rules. Saved case prices are not changed.</span>
            @endif
        </div>

        @if($status === 'Incomplete Setup')
            <div class="promotion-issues" role="alert"><strong>Incomplete Setup</strong><p class="mb-0">This legacy configuration is retained but cannot be assigned or applied until corrected.</p><ul>@foreach($issues as $issue)<li>{{ $issue }}</li>@endforeach</ul></div>
        @endif

        <div class="svc-detail-stat-grid">
            <div class="svc-detail-stat"><span>Discount Type</span><strong>{{ $discountType === 'PERCENT' ? 'Percentage' : ($discountType === 'AMOUNT' ? 'Fixed Amount' : 'Not configured') }}</strong></div>
            <div class="svc-detail-stat"><span>Assigned Package</span><strong>{{ $promotion->package?->name ?? 'Unassigned' }}</strong></div>
            @if($promotion->package)<div class="svc-detail-stat"><span>Package Base Price</span><strong>₱{{ number_format((float) $promotion->package->price, 2) }}</strong></div>@endif
        </div>

        <section class="svc-detail-section">
            <h3>Promotion Schedule</h3>
            <div class="svc-detail-stat-grid">
                <div class="svc-detail-stat"><span>Start Date</span><strong>{{ $promotion->starts_at?->format('F d, Y') ?? 'No start date' }}</strong></div>
                <div class="svc-detail-stat"><span>End Date</span><strong>{{ $promotion->ends_at?->format('F d, Y') ?? 'No end date' }}</strong></div>
                @if($promotion->manually_stopped_at)<div class="svc-detail-stat"><span>Turned Off</span><strong>{{ $promotion->manually_stopped_at->timezone('Asia/Manila')->format('M d, Y g:i A') }}{{ $promotion->stoppedBy ? ' · '.$promotion->stoppedBy->name : '' }}</strong></div>@endif
            </div>
        </section>

        @unless(request()->boolean('modal'))
        <footer class="svc-detail-actions">
            @if(auth()->user()?->isMainAdmin())
                <a class="svc-detail-secondary" href="{{ route('admin.promotions.edit', ['promotion'=>$promotion, 'modal'=>1, '_service_modal'=>1]) }}" data-service-modal-url="{{ route('admin.promotions.edit', ['promotion'=>$promotion, 'modal'=>1, '_service_modal'=>1]) }}" data-service-modal-title="Edit Promotion"><i class="bi bi-pencil" aria-hidden="true"></i> Edit Promotion</a>
                @if(in_array($status, ['Active', 'Scheduled'], true))
                    <form method="POST" action="{{ route('admin.promotions.stop', $promotion) }}" data-promotion-confirm="stop" data-promotion-name="{{ $promotion->name }}">@csrf @method('PATCH')<input type="hidden" name="confirmed" value="1"><button type="submit" class="svc-detail-secondary is-warning" aria-label="Turn Off Promotion"><i class="bi bi-power" aria-hidden="true"></i> Turn Off</button></form>
                @elseif($status === 'Turned Off')
                    <form method="POST" action="{{ route('admin.promotions.reactivate', $promotion) }}" data-promotion-confirm="on" data-promotion-name="{{ $promotion->name }}">@csrf @method('PATCH')<button type="submit" class="svc-detail-secondary is-restore" aria-label="Turn On Promotion"><i class="bi bi-power" aria-hidden="true"></i> Turn On</button></form>
                @endif
            @endif
            @unless(request()->boolean('modal'))<a class="svc-detail-secondary" href="{{ route('admin.service-management.index', ['tab' => 'promotions']) }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Promotions</a>@endunless
        </footer>
        @endunless
    </article>

    <dialog class="promotion-confirm-dialog service-feedback-dialog" data-promotion-confirm-dialog aria-labelledby="promotion-confirm-title" aria-describedby="promotion-confirm-copy">
        <div class="promotion-confirm-dialog__body"><span class="promotion-confirm-dialog__icon" aria-hidden="true"><i class="bi bi-exclamation-circle"></i></span><div><h2 id="promotion-confirm-title" data-promotion-confirm-title>Turn Off Promotion?</h2><p id="promotion-confirm-copy" data-promotion-confirm-copy></p></div></div>
        <footer><button type="button" class="svc-detail-secondary" data-promotion-confirm-cancel>Cancel</button><button type="button" class="svc-detail-primary" data-promotion-confirm-submit>Turn Off Promotion</button></footer>
    </dialog>
</section>
<script>
(() => {
    const dialog = document.querySelector('[data-promotion-confirm-dialog]');
    if (!dialog || dialog.dataset.ready) return;
    dialog.dataset.ready = '1';
    const title = dialog.querySelector('[data-promotion-confirm-title]');
    const copy = dialog.querySelector('[data-promotion-confirm-copy]');
    const confirm = dialog.querySelector('[data-promotion-confirm-submit]');
    const cancel = dialog.querySelector('[data-promotion-confirm-cancel]');
    let pendingForm = null;
    let trigger = null;
    const confirmedForms = new WeakSet();
    document.addEventListener('submit', event => {
        const form = event.target.closest?.('form[data-promotion-confirm]');
        if (!form) return;
        if (confirmedForms.has(form)) { confirmedForms.delete(form); return; }
        event.preventDefault();
        pendingForm = form;
        trigger = event.submitter || form.querySelector('button[type="submit"]');
        const turnOn = form.dataset.promotionConfirm === 'on';
        dialog.dataset.feedbackKind = turnOn ? 'success' : 'warning';
        dialog.dataset.confirmTone = turnOn ? 'success' : 'warning';
        dialog.querySelector('.promotion-confirm-dialog__icon i').className = `bi bi-${turnOn ? 'check-circle' : 'exclamation-triangle'}`;
        const name = form.dataset.promotionName || 'this promotion';
        title.textContent = turnOn ? 'Turn On Promotion?' : 'Turn Off Promotion?';
        copy.textContent = turnOn
            ? `Turn on “${name}” using its saved schedule? It will be Scheduled or Active according to its dates. Existing case prices will remain unchanged.`
            : `Turn off “${name}”? It will immediately stop applying to new cases. Its configuration and schedule will be retained. Existing case prices will remain unchanged.`;
        confirm.textContent = turnOn ? 'Turn On Promotion' : 'Turn Off Promotion';
        confirm.classList.toggle('is-warning', !turnOn);
        cancel.focus();
        dialog.showModal();
    });
    const clearPending = () => { const previous = trigger; pendingForm = trigger = null; if (previous?.isConnected) previous.focus({ preventScroll:true }); };
    cancel.addEventListener('click', () => dialog.close('cancel'));
    dialog.addEventListener('cancel', event => { event.preventDefault(); dialog.close('cancel'); });
    dialog.addEventListener('close', () => { if (dialog.returnValue !== 'confirm') clearPending(); });
    confirm.addEventListener('click', () => {
        const form = pendingForm;
        if (!form?.isConnected) { dialog.close('cancel'); return; }
        confirm.disabled = true;
        confirm.textContent = form.dataset.promotionConfirm === 'on' ? 'Turning on...' : 'Turning off...';
        form.setAttribute('aria-busy', 'true');
        confirmedForms.add(form);
        dialog.close('confirm');
        form.requestSubmit(trigger || undefined);
    });
})();
</script>
@endsection
