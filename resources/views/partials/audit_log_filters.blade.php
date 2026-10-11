@php
    $auditToday = now(config('app.audit_timezone', 'Asia/Manila'));
    $auditRanges = [
        'All Dates' => ['', ''],
        'Today' => [$auditToday->toDateString(), $auditToday->toDateString()],
        'This Week' => [$auditToday->copy()->startOfWeek()->toDateString(), $auditToday->copy()->endOfWeek()->toDateString()],
        'This Month' => [$auditToday->copy()->startOfMonth()->toDateString(), $auditToday->copy()->endOfMonth()->toDateString()],
        'This Year' => [$auditToday->copy()->startOfYear()->toDateString(), $auditToday->copy()->endOfYear()->toDateString()],
    ];
    $auditDateLabel = 'Custom Range';
    foreach ($auditRanges as $label => $range) {
        if (($filters['date_from'] ?? '') === $range[0] && ($filters['date_to'] ?? '') === $range[1]) $auditDateLabel = $label;
    }
@endphp
<form method="GET" action="{{ route('admin.audit-logs.index') }}" class="audit-compact-toolbar" data-audit-compact>
    @if(!empty($filters['audit_log_id']))
        <input type="hidden" name="audit_log_id" value="{{ $filters['audit_log_id'] }}">
        <span class="audit-filter-control">Viewing audit event #{{ $filters['audit_log_id'] }}</span>
    @endif
    <span class="audit-filter-control is-select"><i class="bi bi-person" aria-hidden="true"></i>
        <select name="user_id" class="audit-select" aria-label="Filter by user"><option value="">All Users</option>
            @foreach($users as $user)<option value="{{ $user->id }}" @selected((string)($filters['user_id'] ?? '') === (string)$user->id)>{{ $user->name }}</option>@endforeach
        </select>
    </span>
    <span class="audit-filter-control is-select"><i class="bi bi-lightning-charge" aria-hidden="true"></i>
        <select name="action_type" class="audit-select" aria-label="Filter by action type"><option value="">Any Action</option>
            @foreach($actionTypes as $type)<option value="{{ $type }}" @selected(($filters['action_type'] ?? '') === $type)>{{ ucfirst($type) }}</option>@endforeach
        </select>
    </span>
    <details class="audit-filter-menu" data-audit-date-menu>
        <summary><i class="bi bi-calendar3" aria-hidden="true"></i><span data-audit-date-label>{{ $auditDateLabel }}</span><i class="bi bi-chevron-down" aria-hidden="true"></i></summary>
        <div class="audit-filter-popover">
            <div class="audit-date-heading">Event date</div>
            @foreach($auditRanges as $label => $range)<button type="button" data-audit-range data-from="{{ $range[0] }}" data-to="{{ $range[1] }}" aria-pressed="{{ $auditDateLabel === $label ? 'true' : 'false' }}"><span>{{ $label }}</span><i class="bi bi-check2" aria-hidden="true"></i></button>@endforeach
            <button type="button" data-audit-custom aria-pressed="{{ $auditDateLabel === 'Custom Range' ? 'true' : 'false' }}"><span>Custom Range</span><i class="bi bi-check2" aria-hidden="true"></i></button>
        </div>
    </details>
    <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
    <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
    <label class="audit-page-size">Rows per page
        <span class="audit-filter-control is-select"><select name="per_page" class="audit-select">@foreach($perPageOptions as $size)<option value="{{ $size }}" @selected((int)($filters['per_page'] ?? 25) === $size)>{{ $size }}</option>@endforeach</select></span>
    </label>
    <div class="audit-compact-actions">
        <a href="{{ route('admin.audit-logs.index') }}" class="audit-action"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Clear</a>
        <a href="{{ route('admin.audit-logs.exportPdf', request()->query()) }}" class="audit-action" target="_blank" rel="noopener"><i class="bi bi-filetype-pdf" aria-hidden="true"></i>PDF</a>
        <noscript><button type="submit" class="audit-action audit-action-primary">Apply</button></noscript>
    </div>
</form>
<dialog class="audit-date-dialog" aria-labelledby="audit-date-title" data-audit-date-dialog>
    <div class="audit-date-dialog-head"><div><h2 id="audit-date-title">Custom Date Range</h2><p>Filter audit logs by event date.</p></div><button type="button" data-audit-date-close aria-label="Close custom date range"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
    <div class="audit-date-dialog-body">
        <div class="audit-date-fields">
            <label>From<input type="date" data-audit-from aria-describedby="audit-date-feedback"></label>
            <label>To<input type="date" data-audit-to aria-describedby="audit-date-feedback"></label>
        </div>
        <p id="audit-date-feedback" role="status" aria-live="polite">Choose both dates. Valid ranges apply automatically.</p>
    </div>
    <div class="audit-date-dialog-footer"><button type="button" data-audit-date-close>Cancel</button></div>
</dialog>
<script>
(() => {
    const form = document.querySelector('[data-audit-compact]');
    if (!form) return;
    const menu = form.querySelector('[data-audit-date-menu]');
    const from = form.elements.date_from, to = form.elements.date_to;
    const label = form.querySelector('[data-audit-date-label]');
    const dialog = document.querySelector('[data-audit-date-dialog]');
    const draftFrom = dialog.querySelector('[data-audit-from]'), draftTo = dialog.querySelector('[data-audit-to]');
    const feedback = dialog.querySelector('#audit-date-feedback');
    let applyTimer;
    const applyFilters = () => {
        window.clearTimeout(applyTimer);
        if (form.reportValidity()) form.requestSubmit();
    };
    form.querySelectorAll('select').forEach(select => select.addEventListener('change', applyFilters));
    form.querySelectorAll('[data-audit-range]').forEach(button => button.addEventListener('click', () => {
        from.value = button.dataset.from; to.value = button.dataset.to; to.min = from.value;
        label.textContent = button.textContent; menu.open = false;
        applyFilters();
    }));
    form.querySelector('[data-audit-custom]').addEventListener('click', () => {
        window.clearTimeout(applyTimer);
        draftFrom.value = from.value; draftTo.value = to.value; draftTo.min = draftFrom.value;
        feedback.textContent = 'Choose both dates. Valid ranges apply automatically.';
        menu.open = false; dialog.showModal(); document.body.classList.add('audit-date-open'); draftFrom.focus();
    });
    [draftFrom,draftTo].forEach(input => input.addEventListener('change', () => {
        draftTo.min = draftFrom.value;
        window.clearTimeout(applyTimer);
        if (!draftFrom.value || !draftTo.value || draftTo.value < draftFrom.value) {
            feedback.textContent = draftFrom.value && draftTo.value ? 'To date must be on or after From date.' : 'Choose both dates to apply this range.';
            return;
        }
        feedback.textContent = 'Applying date range…';
        applyTimer = window.setTimeout(() => { from.value = draftFrom.value; to.value = draftTo.value; applyFilters(); }, 700);
    }));
    dialog.querySelectorAll('[data-audit-date-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
    dialog.addEventListener('close', () => { window.clearTimeout(applyTimer); document.body.classList.remove('audit-date-open'); menu.querySelector('summary').focus(); });
    dialog.addEventListener('click', event => { if (event.target === dialog) { const rect = dialog.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close(); } });
    form.querySelectorAll('.audit-filter-menu').forEach(details => details.addEventListener('toggle', () => {
        if (details.open) form.querySelectorAll('.audit-filter-menu').forEach(other => { if (other !== details) other.open = false; });
    }));
    document.addEventListener('click', event => { if (!form.contains(event.target)) form.querySelectorAll('.audit-filter-menu').forEach(details => details.open = false); });
    form.addEventListener('keydown', event => { if (event.key === 'Escape') { const opened = form.querySelector('details[open]'); if (opened) { opened.open = false; opened.querySelector('summary').focus(); } } });
})();
</script>
