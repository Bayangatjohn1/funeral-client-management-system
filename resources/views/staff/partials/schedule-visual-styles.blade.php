<style>
/* Share the dashboard's fonts, surfaces and text hierarchy. */
body.panel-shell-body:has(.rp-wrap) .panel-page-header{background:var(--staff-card,#f4f8f5)!important;border-color:var(--staff-border,#aec7b7)!important}
body.panel-shell-body:has(.rp-wrap) .panel-page-header__copy h1{font-family:var(--font-heading);font-size:clamp(24px,2vw,32px)!important;font-weight:700;letter-spacing:-.025em;color:var(--staff-ink,#081d13)!important}
body.panel-shell-body:has(.rp-wrap) .panel-page-header__copy p{font-family:var(--font-body);font-size:14px!important;line-height:1.6;letter-spacing:0;color:var(--staff-muted,#1b3e2e)!important}
html[data-theme] body.panel-shell-body .rp-wrap .schedule-calendar{--cal-surface:var(--staff-card,#f4f8f5);--cal-card:var(--staff-card,#f4f8f5);--cal-ink:var(--staff-ink,#081d13);--cal-muted:var(--staff-muted,#1b3e2e);--cal-line:var(--staff-border,#aec7b7);--cal-soft:var(--staff-soft,#d0e2d6);font-family:var(--font-body);letter-spacing:0;border-radius:16px}
html[data-theme] body.panel-shell-body .rp-wrap .schedule-calendar :is(label,.schedule-section-range,.calendar-legend,.case-next-event){color:var(--cal-muted)!important;letter-spacing:0;line-height:1.6}
html[data-theme] body.panel-shell-body .rp-wrap .schedule-calendar :is(h2,h3){font-family:var(--font-heading);font-weight:700;letter-spacing:-.02em}
.calendar-event{gap:6px;padding:14px;border-radius:10px;line-height:1.5;letter-spacing:0}
.calendar-event-type{font-size:12px;font-weight:600}.calendar-event strong{font-family:'JetBrains Mono',Consolas,monospace;font-size:13px}.calendar-event-name{font-size:13px}.calendar-agenda-time strong{font-variant-numeric:tabular-nums}
html[data-theme] body.panel-shell-body .rp-wrap .calendar-event.wake-end{background:#f2edfa;border-color:#d2c3e6;border-left-color:#8b6bb1;color:#594071}
.calendar-dot.wake-end{background:#8b6bb1}
html[data-theme=dark] body.panel-shell-body .rp-wrap .calendar-event.wake-end{background:#2d243b;border-color:#65517f;border-left-color:#b59bd8;color:#e5d6f6}
html[data-theme] body.panel-shell-body .rp-wrap .calendar-event :is(span,strong,small){color:inherit!important}
html[data-theme=dark] body.panel-shell-body:has(.rp-wrap) .panel-page-header{background:#112218!important;border-color:#29523d!important}
html[data-theme=dark] body.panel-shell-body:has(.rp-wrap) .panel-page-header__copy h1{color:#f8fafc!important}
html[data-theme=dark] body.panel-shell-body:has(.rp-wrap) .panel-page-header__copy p{color:#b1c8b9!important}
html[data-theme=dark] body.panel-shell-body .rp-wrap .schedule-calendar{--cal-surface:#112218;--cal-card:#14261d;--cal-ink:#f8fafc;--cal-muted:#b1c8b9;--cal-line:#29523d;--cal-soft:#1b3527}
html[data-theme=dark] body.panel-shell-body:has(.rp-wrap) .rp-header-back{background:#14261d!important;color:#f8fafc!important;border-color:#29523d!important}
html[data-theme=dark] body.panel-shell-body .rp-wrap .rp-tabs-row{border-color:#29523d!important}
html[data-theme=dark] body.panel-shell-body .rp-wrap .rp-tab{color:#b1c8b9!important}
html[data-theme=dark] body.panel-shell-body .rp-wrap .rp-tab.is-active{color:#f8fafc!important;border-bottom-color:#86b495!important}
html[data-theme] body.panel-shell-body .case-schedule-dialog{--schedule-bg:var(--staff-card,#f4f8f5);--schedule-surface:var(--staff-soft,#d0e2d6);--schedule-text:var(--staff-ink,#081d13);--schedule-muted:var(--staff-muted,#1b3e2e);--schedule-line:var(--staff-border,#aec7b7);font-family:var(--font-body);letter-spacing:0;border-radius:16px}
html[data-theme=dark] body.panel-shell-body .case-schedule-dialog{--schedule-bg:#112218;--schedule-surface:#14261d;--schedule-text:#f8fafc;--schedule-muted:#b1c8b9;--schedule-line:#29523d}
.case-schedule-dialog h2{font-family:var(--font-heading);font-weight:700;letter-spacing:-.02em}.case-schedule-row{line-height:1.6}
.case-schedule-row>div:first-child>strong:before{content:'';display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:8px;background:#488ab1}
.case-schedule-row[data-case-event=wake_start_today]>div:first-child>strong:before{background:#6d8e6d}
.case-schedule-row[data-case-event=wake_end_today]>div:first-child>strong:before{background:#8b6bb1}
.case-schedule-row[data-case-event=interment_today]>div:first-child>strong:before{background:#a48240}
/* One aligned content column and one vertical page scroll. */
body.panel-shell-body .rp-wrap{max-width:none;padding:20px 28px 40px;gap:0}
body.panel-shell-body .rp-wrap .rp-tabs-row{align-items:center;gap:24px;margin:0;padding:0;min-height:52px}
body.panel-shell-body .rp-wrap .rp-tab{display:inline-flex;align-items:center;gap:8px;min-height:52px;padding:12px 4px;line-height:1.4}
html[data-theme] body.panel-shell-body .rp-wrap .schedule-calendar{margin:20px 0 0;width:100%;box-sizing:border-box}
.schedule-calendar .calendar-week{max-height:none;overflow-x:auto;overflow-y:visible;scroll-padding-top:0}
.schedule-calendar .calendar-days{position:static}
.schedule-calendar :is(.calendar-toolbar,.upcoming-tools,.schedule-section-tabs){padding-left:24px;padding-right:24px}
.schedule-calendar .calendar-controls{align-items:center}
@media(max-width:767px){body.panel-shell-body .rp-wrap{padding:12px 16px 28px}.schedule-calendar :is(.calendar-toolbar,.upcoming-tools,.schedule-section-tabs){padding-left:18px;padding-right:18px}body.panel-shell-body .rp-wrap .rp-tabs-row{gap:18px}}
html[data-theme] body.panel-shell-body:has(.rp-wrap) .panel-page-header{background:transparent!important;border-color:transparent!important;box-shadow:none!important}
html[data-theme] body.panel-shell-body:has(.rp-wrap) .panel-page-header::before,
html[data-theme] body.panel-shell-body:has(.rp-wrap) .panel-page-header::after{background:none!important}
html[data-theme] body.panel-shell-body:has(.rp-wrap) .panel-page-header{padding:20px 28px 8px!important;margin:0!important;min-height:0!important;align-items:center}
body.panel-shell-body:has(.rp-wrap) .panel-page-header__copy h1{margin:0!important;line-height:1.2}
body.panel-shell-body:has(.rp-wrap) .panel-page-header__copy p{margin:6px 0 0!important;line-height:1.5}
body.panel-shell-body .rp-wrap{padding-top:8px}
@media(max-width:767px){html[data-theme] body.panel-shell-body:has(.rp-wrap) .panel-page-header{padding:16px 16px 8px!important;gap:14px}body.panel-shell-body .rp-wrap{padding-top:4px}}
.schedule-calendar .calendar-toolbar{padding-top:16px;padding-bottom:12px}
.schedule-calendar .upcoming-tools{padding-top:0;padding-bottom:16px;gap:12px}
.schedule-calendar .schedule-section-range{margin-top:6px}
.schedule-calendar .calendar-toolbar p{margin-top:4px!important}
.schedule-calendar .case-list-intro{margin-bottom:12px!important}
.schedule-calendar .upcoming-case-card{margin-bottom:12px}
/* Compact vertical rhythm; reset inherited form/header margins. */
html[data-theme] body.panel-shell-body:has(.rp-wrap) .panel-page-header{padding-top:16px!important;padding-bottom:0!important}
body.panel-shell-body .rp-wrap{padding-top:0!important}
body.panel-shell-body .rp-wrap .rp-tabs-row{min-height:44px}
body.panel-shell-body .rp-wrap .rp-tab{min-height:44px;padding-top:10px;padding-bottom:10px}
html[data-theme] body.panel-shell-body .rp-wrap .schedule-calendar{margin-top:16px}
body.panel-shell-body .rp-wrap .schedule-section-tabs{padding-top:16px;margin:0}
body.panel-shell-body .rp-wrap .calendar-toolbar{margin:0!important;padding-top:12px!important;padding-bottom:10px!important;gap:12px}
body.panel-shell-body .rp-wrap .upcoming-tools{margin:0!important;padding-top:0!important;padding-bottom:12px!important;align-items:flex-end}
body.panel-shell-body .rp-wrap .schedule-search{margin:0!important;padding:0}
body.panel-shell-body .rp-wrap .schedule-search label{margin:0 0 6px!important}
body.panel-shell-body .rp-wrap .calendar-toolbar p{margin:4px 0 0!important;line-height:1.5}
body.panel-shell-body .rp-wrap .schedule-section-range{margin-top:4px;line-height:1.5}
body.panel-shell-body .rp-wrap .calendar-legend{margin:0!important;padding-bottom:14px}
@media(max-width:767px){body.panel-shell-body .rp-wrap .calendar-toolbar{padding-top:12px!important;padding-bottom:10px!important}}
.schedule-calendar .schedule-search>div{position:relative;flex-wrap:nowrap}
.schedule-calendar .schedule-search #schedule-search{flex:1;min-width:0;min-height:44px;padding:10px 38px 10px 13px;border:1px solid var(--cal-line);border-radius:10px;background:var(--cal-card);color:var(--cal-ink);font-size:13px}
.schedule-calendar .schedule-search-x{position:absolute;right:88px;top:5px;width:34px;height:34px;background:transparent;border:0;border-radius:6px;color:var(--cal-muted);font-size:22px;cursor:pointer}
.schedule-calendar .schedule-search-x[hidden]{display:none}
.schedule-calendar .schedule-search-x:hover{background:var(--staff-hover,var(--records-hover))}
html[data-theme] body.panel-shell-body .rp-wrap .schedule-search button[type=submit]{width:80px;background:#1b4332!important;color:#fff!important;border-color:#1b4332!important;flex-shrink:0}
html[data-theme] body.panel-shell-body .rp-wrap .schedule-search button[type=submit]:hover{background:#123325!important;color:#fff!important;border-color:#123325!important}
.schedule-calendar .upcoming-tools .calendar-controls{margin-left:auto}
.schedule-search-field{position:relative;flex:1;min-width:0;display:flex;align-items:center}
.schedule-calendar .schedule-search-field #schedule-search{width:100%;box-sizing:border-box}
.schedule-calendar .schedule-search-field .schedule-search-x{right:6px;top:50%;transform:translateY(-50%);display:inline-flex;align-items:center;justify-content:center;padding:0;line-height:1;font-size:13px;color:var(--cal-ink)!important}
.schedule-calendar .schedule-search-field .schedule-search-x[hidden]{display:none}
html[data-theme] body.panel-shell-body .rp-wrap .schedule-search button[type=submit]:is(:hover,:focus-visible){background:var(--ops-primary-hover,#123325)!important;color:#fff!important;border-color:var(--ops-primary-hover,#123325)!important}
.schedule-search-applied{display:flex;align-items:center;flex-wrap:wrap;gap:6px;margin:8px 0 0!important;font-size:12px!important;line-height:1.5;color:var(--cal-ink)!important}
.schedule-search-applied i{color:var(--cal-ink)!important}
body.panel-shell-body .rp-wrap .upcoming-tools{align-items:flex-start}
.upcoming-tools .calendar-controls{padding-top:26px}
.reminder-attention-heading{padding:20px 0 12px}
html[data-theme] body.panel-shell-body .rp-wrap .reminder-attention-heading h2{margin:0!important;font-family:var(--font-heading);font-size:22px;font-weight:700;letter-spacing:-.02em;color:var(--staff-ink,#081d13)!important}
html[data-theme] body.panel-shell-body .rp-wrap .reminder-attention-heading p{margin:6px 0 0!important;font-size:14px;line-height:1.5;color:var(--staff-muted,#1b3e2e)!important}
</style>
