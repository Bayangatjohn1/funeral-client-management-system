// Destination structure only: never fetch/prefetch records to draw placeholders.
export function skeletonProfile(href, { role = 'staff', catalogView = 'cards', directoryView = 'card', narrow = false } = {}) {
    let url;
    try { url = new URL(href, 'https://skeleton.invalid'); } catch { return {kind:'detail'}; }
    const path = url.pathname.replace(/\/+$/, '');
    if (/\/(?:staff|admin|owner|dashboard)$/.test(path)) return {kind:'dashboard', metrics:role === 'system' ? 0 : 4, sections:role === 'system' ? 'access' : 'operations'};
    if (/\/(?:intake|create|edit|profile)(?:\/|$)/.test(path)) return {kind:'form'};
    if (/\/service-management$/.test(path)) return {kind:catalogView === 'table' ? 'table' : 'catalog', tabs:true, sectionIntro:true, catalog:url.searchParams.get('tab') || 'packages', columns:url.searchParams.get('tab') === 'promotions' ? ['text','text','text','text','text','badge','actions'] : ['text','text','text','badge','actions']};
    if (/\/(?:branch-analytics|analytics|reports)$/.test(path)) return {kind:'analytics',metrics:4};
    if (/\/(?:cases|funeral-cases|case-history|completed-cases)$/.test(path)) return {kind:'table', tabs:true, actions:role === 'owner' ? 2 : 3, columns:role === 'owner' ? ['text','text','person','text','badge','badge','amount','actions'] : ['text','person','text','badge','badge','amount','actions']};
    if (/\/(?:users)$/.test(path)) return {kind:directoryView === 'table' ? 'table' : 'directory',avatar:true,metrics:role === 'branch' ? 3 : 4,metricsFirst:true,columns:['avatar','text','text','text','text','badge','actions']};
    if (/\/(?:branches)$/.test(path)) return {kind:directoryView === 'table' ? 'table' : 'directory',metrics:2,metricsFirst:true,columns:['text','text','text','amount','amount','badge','actions']};
    if (/\/(?:payments|history|collections)$/.test(path)) {
        const corrections = url.searchParams.get('tab') === 'corrections';
        if (corrections) return {kind:'list',tabs:role === 'branch',toolbar:false,listColumns:3};
        return {kind:'list',tabs:role === 'branch',metrics:role === 'staff' ? 0 : 4,metricsFirst:true,listColumns:5};
    }
    if (/\/reminders$/.test(path)) {
        const section = url.searchParams.get('schedule_section') || (url.searchParams.get('date') === 'current_wake' ? 'ongoing' : url.searchParams.get('date') === 'upcoming' ? 'upcoming' : 'today');
        const view = url.searchParams.get('upcoming_view') || 'auto';
        if (url.searchParams.get('tab') === 'warnings' || section === 'ongoing' || (section === 'upcoming' && (view === 'cases' || (view === 'auto' && narrow)))) return {kind:'list',tabs:true};
        return {kind:'schedule',tabs:true,view:section === 'upcoming' ? url.searchParams.get('calendar_view') || 'week' : 'day'};
    }
    if (/\/audit-logs$/.test(path)) return {kind:'table',columns:['text','text','text','text','badge','actions']};
    if (/\/(?:retention|record-retention)$/.test(path)) return {kind:'table',metrics:4,metricsFirst:true,notice:true,columns:['text','text','text','text','badge','actions']};
    if (/\/backup-recovery$/.test(path)) return {kind:'table',metrics:4,metricsFirst:true,toolbar:false,columns:['text','text','badge','text','text','actions']};
    if (/\/(?:clients|deceased|reminders)$/.test(path)) return {kind:'table',columns:['text','text','text','badge','actions']};
    return {kind:'detail'};
}
