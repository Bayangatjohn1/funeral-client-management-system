<script>
document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-dashboard-schedule]');
    if (trigger) {
        const dialog = document.getElementById('calendar-case-'+trigger.dataset.dashboardSchedule);
        if (!dialog) return;
        dialog.querySelectorAll('[data-case-event]').forEach(row => row.classList.toggle('is-picked', row.dataset.caseEvent === trigger.dataset.scheduleEvent.replace('upcoming_', '') || row.dataset.caseEvent === trigger.dataset.scheduleEvent.replace('upcoming_', '')+'_today'));
        document.body.append(dialog); dialog.showModal();
        document.documentElement.classList.add('schedule-modal-open');
        dialog.addEventListener('close', () => document.documentElement.classList.remove('schedule-modal-open'), {once:true});
    }
    const close = event.target.closest('[data-calendar-close]');
    if (close) close.closest('dialog').close();
});
</script>
<style>
.dashboard-activity-link{text-decoration:none;color:var(--staff-ink)!important;cursor:pointer}
.dashboard-activity-link:hover{background:var(--staff-hover)!important}
.dashboard-schedule-trigger{width:100%;text-align:left;font-family:inherit;cursor:pointer}
.dashboard-schedule-trigger:hover{background:var(--staff-hover)!important}
.dashboard-activity-link:focus-visible,.dashboard-schedule-trigger:focus-visible{outline:2px solid var(--staff-action);outline-offset:2px}
.case-schedule-dialog .case-schedule-rows{border:1px solid var(--schedule-line);border-radius:10px;overflow:hidden}
.case-schedule-dialog .case-schedule-row{display:flex;justify-content:space-between;gap:16px;padding:14px 18px;border-bottom:1px solid var(--schedule-line);color:var(--schedule-text);font-size:13px}
.case-schedule-dialog .case-schedule-row>div{display:flex;flex-direction:column;gap:5px}
.case-schedule-dialog .case-schedule-row>div:last-child{text-align:right}
.case-schedule-dialog .case-picked-label{display:none}
.case-schedule-dialog .case-schedule-row.is-picked{background:#fff4d8;box-shadow:inset 4px 0 #bc8f38}
.case-schedule-dialog .is-picked .case-picked-label{display:block;color:#805b16;font-size:11px}
html[data-theme=dark] .case-schedule-dialog .case-schedule-row.is-picked{background:#41381e}
html[data-theme=dark] .case-schedule-dialog .is-picked .case-picked-label{color:#f0d69b}
</style>
<style>
.dashboard-attention-actions{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-top:12px}
.dashboard-attention-actions .dashboard-view-icon{margin-left:auto}
</style>
<style>
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 .dashboard-view-icon{width:40px;height:40px;box-sizing:border-box;border-radius:10px;border:1px solid var(--staff-border);background:var(--staff-card);color:var(--staff-ink)!important;transition:background-color .15s ease,border-color .15s ease}
.dashboard-view-icon i{font-size:17px;line-height:1;color:inherit!important}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 .dashboard-view-icon:hover{background:var(--staff-hover);border-color:var(--staff-action)}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 .ref-schedule-card{display:grid;grid-template-columns:minmax(0,1fr) 40px;column-gap:14px;padding:12px 14px!important}
.ref-schedule-card>:not(.dashboard-view-icon){grid-column:1;min-width:0}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 .ref-schedule-card>.dashboard-view-icon{position:relative;inset:auto;grid-column:2;grid-row:1 / span 3;align-self:center;margin:0}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 .ref-activity-row>div:last-child{display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.ref-activity-row .dashboard-view-icon{margin-left:0}
@media(prefers-reduced-motion:reduce){.dashboard-view-icon{transition:none!important}}
</style>
<style>
.dashboard-view-icon{position:relative;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;flex-shrink:0;border:1px solid var(--staff-border);border-radius:8px;background:var(--staff-card);color:var(--staff-ink)!important;text-decoration:none;cursor:pointer;margin-left:8px;vertical-align:middle}
.dashboard-view-icon:hover{background:var(--staff-hover)}
.dashboard-view-icon:focus-visible{outline:2px solid var(--staff-action);outline-offset:2px}
.dashboard-view-icon::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 7px);right:0;padding:6px 9px;border-radius:6px;background:var(--staff-ink);color:var(--staff-card);font-size:11px;font-weight:500;white-space:nowrap;opacity:0;visibility:hidden;pointer-events:none;z-index:10}
.dashboard-view-icon:hover::after,.dashboard-view-icon:focus-visible::after{opacity:1;visibility:visible}
.ref-schedule-card{position:relative}
html[data-theme] body.panel-shell-body.staff-ui .staff-dashboard-v2 .ref-schedule-card{padding-right:60px}
.ref-schedule-card>.dashboard-view-icon{position:absolute;right:12px;bottom:12px;margin:0}
</style>
<style>
html[data-theme] body.panel-shell-body .case-schedule-dialog{width:min(620px,calc(100vw - 32px));max-height:calc(100dvh - 40px);padding:0;font-family:var(--font-body);color:var(--schedule-text)}
.case-schedule-dialog .schedule-dialog-head{padding:16px 20px}
.case-schedule-dialog .schedule-dialog-head h2{font-size:20px!important;margin:4px 0!important}
.case-schedule-dialog .schedule-dialog-head p{font-size:13px!important;margin:4px 0 0!important}
.case-schedule-dialog .schedule-dialog-body{padding:16px 20px}
.case-schedule-dialog .case-schedule-context{display:none}
.case-schedule-dialog .schedule-dialog-body dl{display:grid;grid-template-columns:55px minmax(0,1fr) 78px auto;gap:8px;margin:0 0 14px;font-size:13px;align-items:baseline}
.case-schedule-dialog .schedule-dialog-body dt{color:var(--schedule-muted)}
.case-schedule-dialog .schedule-dialog-body dd{margin:0;font-weight:600}
html[data-theme] body.panel-shell-body .case-schedule-dialog .case-schedule-row{padding:10px 14px;font-size:13px;line-height:1.4;gap:12px}
.case-schedule-dialog .case-schedule-row>div{gap:3px}
.case-schedule-dialog .case-schedule-row small{font-size:11px;color:var(--schedule-muted)}
.case-schedule-dialog footer{padding:12px 20px;gap:10px;align-items:center}
.case-schedule-dialog footer a{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:8px 12px;border:1px solid var(--schedule-line);border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;color:var(--schedule-text)!important}
.case-schedule-dialog footer a:hover{background:var(--staff-hover,var(--schedule-surface))}
@media(max-width:480px){.case-schedule-dialog .schedule-dialog-body dl{grid-template-columns:78px 1fr}.case-schedule-dialog .schedule-dialog-head,.case-schedule-dialog .schedule-dialog-body{padding:14px 16px}.case-schedule-dialog footer{padding:12px 16px}}
</style>
