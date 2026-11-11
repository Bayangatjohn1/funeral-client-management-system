<style>
.branch-case-drawer {
    --drawer-surface:#F7FAF8;
    --drawer-soft:#EAF2ED;
    --drawer-divider:#B8CEC0;
    --drawer-accent:#1B4332;
    --drawer-muted:#3C5045;
    --drawer-wake:#216342;
    --drawer-start:#854D16;
    --drawer-end:#315A77;
    --drawer-interment:#A02C36;
    --drawer-gutter:clamp(16px,4vw,24px);
    box-sizing:border-box; position:fixed; inset:0 0 0 auto; margin:0;
    width:min(540px,100vw); max-width:100vw; height:100dvh; max-height:100dvh;
    padding:0; border:0; border-left:1px solid var(--drawer-divider);
    background:var(--drawer-surface); color:var(--ink); font:400 14px/1.5 var(--font-body);
}
html[data-theme='dark'] .branch-case-drawer {
    --drawer-surface:#15251C;
    --drawer-soft:#22392C;
    --drawer-divider:#41634F;
    --drawer-accent:#A9C9B5;
    --drawer-muted:#C2D3C9;
    --drawer-wake:#8AD6A6;
    --drawer-start:#EBC381;
    --drawer-end:#A2C9E2;
    --drawer-interment:#F5A6AB;
}
.branch-case-drawer[open] { display:flex; flex-direction:column; }
.branch-case-drawer::backdrop { background:var(--modal-scrim,rgba(15,23,42,.72)); }
.branch-case-drawer > header {
    flex:none; display:flex; align-items:center; gap:12px; padding:14px var(--drawer-gutter);
    border-bottom:1px solid var(--drawer-divider);
}
.branch-case-drawer h2 { flex:1; margin:0; font:700 18px/1.35 var(--font-body); }
.branch-case-drawer__icon, .branch-case-drawer > header button {
    display:grid; place-items:center; flex:0 0 44px; height:44px; border-radius:9px;
}
.branch-case-drawer__icon { background:var(--drawer-soft); color:var(--drawer-accent); }
.branch-case-drawer > header button { border:0; background:transparent; color:inherit; cursor:pointer; }
.branch-case-drawer > header button:hover { background:var(--drawer-soft); }
.branch-case-drawer__body {
    flex:1; min-height:0; padding:20px var(--drawer-gutter); overflow-y:auto;
    overscroll-behavior:contain; scrollbar-width:thin;
}
.branch-case-drawer__purpose { color:var(--drawer-muted); margin:0 0 14px; font-size:13px; }
.branch-case-drawer > footer {
    flex:none; display:flex; flex-wrap:wrap; justify-content:flex-end; gap:10px;
    padding:14px var(--drawer-gutter) max(14px,env(safe-area-inset-bottom)); border-top:1px solid var(--drawer-divider);
}
.branch-case-drawer > footer a {
    display:inline-flex; justify-content:center; align-items:center; gap:8px; min-height:44px;
    padding:8px 14px; border-radius:8px; background:#A9C9B5; color:#123524; font-weight:650; text-decoration:none;
}
.branch-case-drawer > footer a[data-branch-summary-schedule] { background:var(--drawer-soft); color:var(--ink); }
.branch-case-drawer > footer a:hover { background:#92B89F; }
.branch-case-drawer > footer a[data-branch-summary-schedule]:hover { background:color-mix(in srgb,var(--drawer-accent) 18%,var(--drawer-surface)); }
.branch-case-drawer > footer a[hidden] { display:none; }
.branch-case-drawer :is(a,button):focus-visible { outline:2px solid var(--drawer-accent); outline-offset:2px; }
.branch-balance-summary__highlight {
    display:flex; flex-wrap:wrap; justify-content:space-between; align-items:start; gap:10px 20px;
    margin:0 0 14px; padding:14px; border-radius:10px;
    background:color-mix(in srgb,var(--drawer-start) 10%,var(--drawer-surface));
}
.branch-balance-summary__highlight dt { color:var(--drawer-muted); font-size:12px; font-weight:600; }
.branch-balance-summary__highlight dd { margin:3px 0 0; font-weight:700; }
.branch-balance-summary__highlight > div:first-child dd {
    color:var(--drawer-start); font-size:clamp(19px,4.8vw,26px);
    font-variant-numeric:tabular-nums; overflow-wrap:anywhere;
}
.branch-balance-summary__status[data-status='partial'] { color:var(--drawer-start); }
.branch-balance-summary__status[data-status='unpaid'] { color:var(--drawer-interment); }
.branch-balance-summary { margin:0; }
.branch-balance-summary__row {
    display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:12px;
    align-items:baseline; padding:11px 0; border-bottom:1px solid var(--drawer-divider);
}
.branch-balance-summary__row dd { margin:0; text-align:end; font-weight:650; overflow-wrap:anywhere; font-variant-numeric:tabular-nums; }
.branch-balance-summary__row time, .branch-balance-summary__muted { color:var(--drawer-muted); font-weight:400; }
.branch-balance-summary__row time { display:block; font-size:12px; }
.branch-balance-summary__notice {
    display:flex; align-items:baseline; gap:8px; margin:16px 0 0; padding:10px 12px; border-radius:8px;
    background:color-mix(in srgb,var(--drawer-start) 10%,var(--drawer-surface)); color:var(--drawer-start);
    font-size:13px; font-weight:650;
}
.branch-timeline-identity {
    display:grid; gap:3px; margin-bottom:20px; padding:12px 14px;
    background:var(--drawer-soft); border-radius:10px; overflow-wrap:anywhere;
}
.branch-timeline-identity strong { font-size:17px; line-height:1.35; }
.branch-timeline-identity span { color:var(--drawer-muted); font-size:12px; font-weight:650; }
.branch-timeline-heading { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:6px 12px; margin-bottom:14px; }
.branch-timeline-heading h3 { margin:0; font-size:15px; font-weight:700; }
.branch-timeline-duration { padding:4px 9px; border-radius:6px; background:var(--drawer-soft); color:var(--drawer-wake); font-size:12px; font-weight:650; }
.branch-timeline { list-style:none; margin:0; padding:0; }
.branch-timeline__step {
    --event-tone:var(--drawer-wake);
    position:relative; display:grid; grid-template-columns:30px minmax(0,1fr); gap:0 10px; padding:0 0 8px;
}
.branch-timeline__step[data-timeline-event='wake_start'] { --event-tone:var(--drawer-start); }
.branch-timeline__step[data-timeline-event='wake_end'] { --event-tone:var(--drawer-end); }
.branch-timeline__step[data-timeline-event='interment'] { --event-tone:var(--drawer-interment); }
.branch-timeline__step:not(:last-child)::before {
    content:''; position:absolute; inset-block:21px -10px; inset-inline-start:14px;
    width:2px; background:var(--drawer-divider);
}
.branch-timeline__marker {
    position:relative; display:grid; place-items:center; width:30px; height:30px;
    margin-block-start:8px; border-radius:50%; color:var(--event-tone);
    background:color-mix(in srgb,var(--event-tone) 13%,var(--drawer-surface)); font-size:14px;
}
.branch-timeline__step--day .branch-timeline__marker { background:var(--drawer-surface); font-size:8px; }
.branch-timeline__content { min-width:0; padding:8px 10px; border-radius:8px; overflow-wrap:anywhere; }
.branch-timeline__step--day .branch-timeline__content { padding-block:6px; }
.branch-timeline__step[aria-current='step'] .branch-timeline__marker { background:var(--event-tone); color:var(--drawer-surface); }
.branch-timeline__step[aria-current='step'] .branch-timeline__content { background:color-mix(in srgb,var(--event-tone) 13%,var(--drawer-surface)); }
.branch-timeline__event-heading { display:flex; flex-wrap:wrap; align-items:baseline; gap:3px 8px; }
.branch-timeline__event-heading strong { font-weight:700; }
.branch-timeline__step--day .branch-timeline__event-heading strong { font-size:13px; font-weight:650; }
.branch-timeline__detail { display:block; margin-top:2px; color:var(--drawer-muted); font-size:13px; }
.branch-timeline__detail i { margin-inline-end:5px; }
.branch-timeline__focus { color:var(--event-tone); font-size:11px; font-weight:700; }
.branch-timeline__focus:empty { display:none; }
@media(max-width:420px) {
    .branch-case-drawer > footer a { flex:1 1 auto; }
}
</style>
<dialog class="branch-case-drawer is-record-drawer" id="branchCaseSummary" aria-labelledby="branchCaseSummaryTitle">
<header><span class="branch-case-drawer__icon" aria-hidden="true"><i class="bi bi-calendar2-week"></i></span><h2 id="branchCaseSummaryTitle">Case summary</h2><button type="button" data-branch-summary-close aria-label="Close summary" autofocus><i class="bi bi-x-lg" aria-hidden="true"></i></button></header>
<div class="branch-case-drawer__body"><p class="branch-case-drawer__purpose" data-branch-summary-purpose></p><div data-branch-summary-body></div></div>
<footer><a data-branch-summary-schedule hidden>View in Schedule</a><a data-branch-summary-destination href="#">View Case Details</a></footer>
</dialog>
@php
    $timelineService = app(\App\Services\ReminderService::class);
    $timelineIcons = ['wake_start' => 'moon-stars', 'wake_end' => 'hourglass-bottom', 'service' => 'flower1', 'interment' => 'geo-alt'];
@endphp
@foreach($scheduleCases as $case)
    @php
        $schedule = $timelineService->caseSchedule($case)->keyBy('event_kind');
        $startDay = $schedule->get('wake_start')['date']?->copy()->startOfDay();
        $endDay = $schedule->get('wake_end')['date']?->copy()->startOfDay();
        $wakeDays = $startDay && $endDay && $endDay->gte($startDay) ? (int) $startDay->diffInDays($endDay) + 1 : 0;
        $wakeNights = \App\Support\WakeDuration::nightsFromDays($wakeDays);
    @endphp
    <template id="branch-schedule-{{ $case->id }}">
        <div class="branch-timeline-identity"><span>Case {{ $case->case_code }}</span><strong>{{ $case->deceased?->full_name ?? 'Deceased not recorded' }}</strong></div>
        <div class="branch-timeline-heading">
            <h3>Case Schedule</h3>
            @if($wakeDays)
                <span class="branch-timeline-duration">{{ $wakeDays }} {{ Str::plural('wake day', $wakeDays) }} · {{ $wakeNights }} {{ Str::plural('night', $wakeNights) }}</span>
            @endif
        </div>
        <ol class="branch-timeline">
            @foreach($branchTimelineLabels as $kind => $label)
                @if($kind === 'wake_end')
                    @for($day = 2; $day < min($wakeDays, 365); $day++)
                        @php $wakeDate = $startDay->copy()->addDays($day - 1); @endphp
                        <li class="branch-timeline__step branch-timeline__step--day" data-wake-date="{{ $wakeDate->toDateString() }}">
                            <span class="branch-timeline__marker" aria-hidden="true"><i class="bi bi-circle-fill"></i></span>
                            <div class="branch-timeline__content">
                                <div class="branch-timeline__event-heading"><strong>Wake Day {{ $day }}</strong><span class="branch-timeline__focus" data-timeline-focus></span></div>
                                <time class="branch-timeline__detail" datetime="{{ $wakeDate->toDateString() }}">{{ $wakeDate->format('D, M j, Y') }}</time>
                            </div>
                        </li>
                    @endfor
                    @if($wakeDays > 365)
                        <li class="branch-timeline__step branch-timeline__step--day">
                            <span class="branch-timeline__marker" aria-hidden="true"><i class="bi bi-three-dots"></i></span>
                            <div class="branch-timeline__content">{{ number_format($wakeDays - 365) }} more wake dates before Wake End</div>
                        </li>
                    @endif
                @endif
                @php
                    $milestone = $schedule->get($kind);
                    $date = $milestone['date'];
                    $wakeDay = $kind === 'wake_start' ? 1 : ($kind === 'wake_end' ? $wakeDays : null);
                @endphp
                <li class="branch-timeline__step" data-timeline-event="{{ $kind }}" @if($wakeDay && $date) data-wake-date="{{ $date->toDateString() }}" @endif>
                    <span class="branch-timeline__marker" aria-hidden="true"><i class="bi bi-{{ $timelineIcons[$kind] }}"></i></span>
                    <div class="branch-timeline__content">
                        <div class="branch-timeline__event-heading"><strong>{{ $label }}</strong><span class="branch-timeline__focus" data-timeline-focus></span></div>
                        @if($date)
                            <time class="branch-timeline__detail" datetime="{{ $milestone['has_time'] ? $date->format('Y-m-d\TH:i') : $date->toDateString() }}">{{ $date->format('D, M j, Y') }} · {{ $milestone['has_time'] ? $date->format('g:i A') : 'Time not set' }}</time>
                        @else
                            <span class="branch-timeline__detail">Date not set</span>
                        @endif
                        @if($milestone['location'])<span class="branch-timeline__detail"><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $milestone['location'] }}</span>@endif
                    </div>
                </li>
            @endforeach
        </ol>
    </template>
@endforeach
<script>
(() => {
    const drawer = document.getElementById('branchCaseSummary');
    const drawerBody = drawer.querySelector('.branch-case-drawer__body');
    let trigger = null;
    let previousOverflow = '';
    let closing = false;
    const scroller = document.querySelector('.page-content');
    const tabs = [...document.querySelectorAll('.branch-schedule-tabs [role="tab"]')];
    const selectTab = selected => {
        tabs.forEach(tab => {
            const active = tab === selected;
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
            document.getElementById(tab.getAttribute('aria-controls')).hidden = !active;
        });
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => selectTab(tab));
        tab.addEventListener('keydown', event => {
            const next = { ArrowRight: (index + 1) % tabs.length, ArrowLeft: (index + tabs.length - 1) % tabs.length, Home: 0, End: tabs.length - 1 }[event.key];
            if (next === undefined) return;
            event.preventDefault();
            selectTab(tabs[next]);
            tabs[next].focus();
        });
    });
    document.querySelectorAll('.branch-admin-view .branch-record-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', event => { if (!event.target.closest('a,button,input,select,textarea')) card.querySelector('.branch-row-destination')?.click(); });
    });
    document.querySelectorAll('[data-branch-case-summary]').forEach(link => link.addEventListener('click', event => {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        if (drawer.open) return;
        trigger = link;
        const panel = link.closest('.branch-panel');
        const section = panel?.querySelector('h3')?.textContent || 'Case summary';
        const balance = !!link.querySelector('template');
        drawer.querySelector('h2').textContent = balance ? 'Case Balance' : section;
        drawer.querySelector('.branch-case-drawer__icon i').className = balance ? 'bi bi-receipt' : 'bi bi-calendar2-week';
        drawer.querySelector('[data-branch-summary-purpose]').textContent = balance ? 'Payment position for this case. Amounts reflect payments recorded to date.' : 'Full schedule for this case.';
        const body = drawer.querySelector('[data-branch-summary-body]');
        body.replaceChildren();
        drawerBody.scrollTop = 0;
        let selectedStep = null;
        if (balance) body.append(link.querySelector('template').content.cloneNode(true));
        else {
            const schedule = document.getElementById(`branch-schedule-${link.dataset.scheduleCase}`);
            if (schedule) {
                const content = schedule.content.cloneNode(true);
                const wakeDay = link.dataset.wakeDate
                    ? [...content.querySelectorAll('[data-wake-date]')].find(item => item.dataset.wakeDate === link.dataset.wakeDate)
                    : null;
                selectedStep = wakeDay ?? [...content.querySelectorAll('[data-timeline-event]')]
                    .find(item => item.dataset.timelineEvent === link.dataset.scheduleEvent);
                if (selectedStep) {
                    selectedStep.setAttribute('aria-current', 'step');
                    selectedStep.querySelector('[data-timeline-focus]').textContent = link.dataset.wakeProgress
                        ? `In progress · ${link.dataset.wakeProgress}` : 'Selected event';
                }
                body.append(content);
            }
        }
        const destination = drawer.querySelector('[data-branch-summary-destination]');
        destination.href = link.href; destination.textContent = balance ? 'View in Payment Monitoring' : 'View Case Details';
        const scheduleDestination = drawer.querySelector('[data-branch-summary-schedule]');
        scheduleDestination.hidden = !link.dataset.scheduleUrl;
        if (link.dataset.scheduleUrl) scheduleDestination.href = link.dataset.scheduleUrl;
        else scheduleDestination.removeAttribute('href');
        if (scroller) { previousOverflow = scroller.style.overflow; scroller.style.overflow = 'hidden'; }
        if (window.recordDialogMotion) window.recordDialogMotion.open(drawer);
        else drawer.showModal();
        if (selectedStep) requestAnimationFrame(() => {
            if (!drawer.open || !selectedStep.isConnected) return;
            const overflow = selectedStep.getBoundingClientRect().bottom - drawerBody.getBoundingClientRect().bottom;
            if (overflow > 0) drawerBody.scrollTop += overflow + 12;
        });
    }));
    const closeDrawer = () => {
        if (!drawer.open || closing) return;
        closing = true;
        if (window.recordDialogMotion) window.recordDialogMotion.close(drawer);
        else drawer.close();
    };
    drawer.querySelector('[data-branch-summary-close]').addEventListener('click', closeDrawer);
    drawer.addEventListener('cancel', event => { event.preventDefault(); closeDrawer(); });
    drawer.addEventListener('click', event => {
        const bounds = drawer.getBoundingClientRect();
        if (event.target === drawer && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) closeDrawer();
    });
    drawer.addEventListener('close', () => {
        closing = false;
        if (scroller) scroller.style.overflow = previousOverflow;
        trigger?.focus({ preventScroll: true });
    });
})();
</script>
