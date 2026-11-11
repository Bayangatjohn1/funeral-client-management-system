import { skeletonProfile } from './skeleton-profiles';
import { renderSkeletonTemplate, renderMeasuredSkeleton } from './skeleton-components';
import { createSkeletonLifecycle } from './skeleton-lifecycle';
import '../css/panel-skeleton.css';

const entries = new Map();
const observed = new Map();
const exits = new WeakMap();
function clearNestedSkeletons(target) {
    // A genuine document navigation supersedes every result-level loader in
    // the outgoing pane. Leaving an exiting inner surface in place can put it
    // above the new destination template for a frame (or longer on a slow
    // device), which looks like the arriving content shifts or flickers.
    for (const [nestedTarget, nestedEntry] of [...entries]) {
        if (nestedTarget === target || !target.contains(nestedTarget)) continue;
        nestedEntry.lifecycle.reset();
        clear(nestedTarget, nestedEntry, { immediate:true });
    }
    for (const surface of target.querySelectorAll('.panel-skeleton')) {
        const parent = surface.parentElement;
        if (!(parent instanceof HTMLElement) || parent === target) continue;
        const finish = exits.get(parent);
        if (finish) finish();
        else surface.remove();
    }
}
function sizeSurface(target, surface, entry) {
    surface.style.top = `${target.scrollTop}px`;
    surface.style.left = `${target.scrollLeft}px`;
    surface.style.width = `${target.clientWidth}px`;
    surface.style.height = `${Math.max(100, target.clientHeight)}px`;
    surface.querySelectorAll('[data-skeleton-component]').forEach(node => {
        if (node.parentElement === surface) node.remove();
    });
    surface.classList.remove('is-measured');
    if (entry.variant === 'navigation') {
        renderSkeletonTemplate(surface, entry.profile);
    } else {
        if (renderMeasuredSkeleton(target, surface)) surface.classList.add('is-measured');
        else renderSkeletonTemplate(surface, entry.profile || {kind:entry.variant === 'form' ? 'form' : 'detail'}, {header:false});
    }
}

function paint(target, entry) {
    if (!target.isConnected || !target.getClientRects().length) return;
    exits.get(target)?.();
    const surface = document.createElement('div');
    surface.className = entry.variant === 'navigation' ? 'panel-skeleton is-navigation' : 'panel-skeleton';
    if (entry.variant === 'navigation') {
        surface.classList.add('is-destination');
        surface.dataset.skeletonProfile = entry.profile?.kind || 'detail';
        try { surface.dataset.skeletonDestination = new URL(entry.destination, location.href).pathname; } catch {}
    }
    surface.setAttribute('role', 'status'); surface.setAttribute('aria-live', 'polite');
    surface.innerHTML = '<span class="sr-only">Loading content…</span>';
    entry.position = target.style.position;
    if (getComputedStyle(target).position === 'static') target.style.position = 'relative';
    entry.ownedBusy = target.getAttribute('aria-busy') !== 'true';
    entry.previousBusy = target.getAttribute('aria-busy');
    if (entry.ownedBusy) { target.dataset.panelSkeletonOwnedBusy = ''; target.setAttribute('aria-busy', 'true'); }
    // Table headers remain readable and usable while only result rows refresh.
    const coveredChildren = [...target.children].flatMap(child => entry.variant !== 'navigation' && child.matches('table') ? [...child.tBodies, child.tFoot].filter(Boolean) : [child]);
    entry.inertChildren = coveredChildren.map(child => [child, child.inert]);
    entry.inertChildren.forEach(([child]) => child.inert = true);
    entry.surface = surface;
    sizeSurface(target, surface, entry);
    target.append(surface); target.classList.add('panel-skeleton-host');
    entry.resize = new ResizeObserver(() => sizeSurface(target, surface, entry)); entry.resize.observe(target);
    entry.scroll = () => sizeSurface(target, surface, entry);
    target.addEventListener('scroll', entry.scroll, {passive:true});
}

function clear(target, entry, { immediate = false } = {}) {
    entry.resize?.disconnect();
    if (entry.scroll) target.removeEventListener('scroll', entry.scroll);
    const surface = entry.surface;
    entry.surface = null;
    (entry.inertChildren || []).forEach(([child, previous]) => child.inert = previous);
    if (entry.ownedBusy) {
        delete target.dataset.panelSkeletonOwnedBusy;
        if (entry.previousBusy === null) target.removeAttribute('aria-busy');
        else target.setAttribute('aria-busy', entry.previousBusy);
    }
    if (entries.get(target) === entry) entries.delete(target);
    if (entry.variant === 'navigation') delete target.dataset.panelSkeletonNavigation;
    if (!surface) return;

    let timer;
    const finish = () => {
        clearTimeout(timer);
        surface.remove();
        if (!target.querySelector(':scope > .panel-skeleton')) {
            target.classList.remove('panel-skeleton-host', 'panel-skeleton-revealing');
            if (entry.position !== undefined) target.style.position = entry.position;
        }
        if (exits.get(target) === finish) exits.delete(target);
    };
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (immediate || reduceMotion || !target.isConnected || !surface.isConnected) {
        finish();
        return;
    }

    target.classList.add('panel-skeleton-revealing');
    surface.classList.add('is-leaving');
    exits.set(target, finish);
    surface.addEventListener('animationend', finish, { once: true });
    timer = window.setTimeout(finish, 260);
}

function begin(target, options = {}) {
    if (document.body.matches('.service-catalog-form-frame')) return () => {};
    if (!(target instanceof HTMLElement) || !target.isConnected || target.closest('[data-skeleton="off"]')) return () => {};
    if (options.variant === 'navigation') {
        clearNestedSkeletons(target);
        target.dataset.panelSkeletonNavigation = '';
    }
    let entry = entries.get(target);
    if (!entry) {
        entry = { variant: options.variant };
        if (options.variant === 'navigation') {
            entry.destination = options.destination || location.href;
            const role = document.body.classList.contains('owner-ui') ? 'owner' : document.body.classList.contains('system-admin-ui') ? 'system' : document.body.classList.contains('branch-admin-ui') ? 'branch' : 'staff';
            let catalogView = 'cards';
            let directoryView = 'card';
            try { const tab = new URL(options.destination || location.href, location.href).searchParams.get('tab') || 'packages'; catalogView = localStorage.getItem(`service-catalog-view-${tab}`) || 'cards'; } catch {}
            try { const path=new URL(options.destination || location.href,location.href).pathname; directoryView=localStorage.getItem(path.endsWith('/branches')?'branch_view':'user_view') || 'card'; } catch {}
            entry.profile = skeletonProfile(entry.destination, {role,catalogView,directoryView,narrow:window.matchMedia('(max-width:767px)').matches});
        }
        entry.lifecycle = createSkeletonLifecycle(() => paint(target, entry), () => clear(target, entry), {delay:options.immediate ? 0 : 160});
        entries.set(target, entry);
    } else if (entry.surface && !entry.surface.isConnected) {
        // A second modal request may replace its spinner/content while the
        // first is pending. Reattach without losing either request's lease.
        clear(target, entry); entries.set(target, entry); paint(target, entry);
    }
    const release = entry.lifecycle.begin();
    return () => { release(); if (!entry.lifecycle.pending && entries.get(target) === entry) entries.delete(target); };
}

function targetFor(source) {
    if (!(source instanceof HTMLElement)) return null;
    if (source.matches('.rp-wrap')) return [...source.querySelectorAll('.calendar-week, .calendar-agenda, .upcoming-case-list, .ongoing-wake-list, .rp-list, .rp-empty')].find(node => node.getClientRects().length) || source.querySelector('.rp-body') || source;
    if (source.matches('[data-reports-module-shell], .ba-shell')) return source.querySelector('.ba-panel.active, .reports-preview-body') || source.querySelector('.ba-panels') || source;
    if (source.matches('iframe')) return source.closest('dialog')?.querySelector('[data-service-modal-loading]');
    if (source.matches('.records-page, .master-records-page, .owner-master-records-page, .table-system-card')) return source.querySelector('.table-system-wrap') || source;
    if (source.matches('[data-service-hub]')) return source.querySelector('.service-hub-panel.is-active') || source;
    if (source.matches('.pm-page')) {
        const results = [...source.querySelectorAll('.pm-records-body, .pc-requests')].find(node => node.getClientRects().length);
        return results || [...source.querySelectorAll('.table-system-wrap, .table-wrapper, .pm-panel')].find(node => node.getClientRects().length) || source;
    }
    return source;
}

let scanQueued = false;
function scan() {
    scanQueued = false;
    const desired = new Map();
    document.querySelectorAll('.is-updating, .pm-page.is-loading, .case-records-tab-loading, [aria-busy="true"]').forEach(source => {
        if (source.matches('button, input, select, a, form') || source.closest('.panel-skeleton, [data-panel-skeleton-ignore]')) return;
        if (source.closest('.page-content[data-panel-skeleton-navigation]')) return;
        if (source.hasAttribute('data-panel-skeleton-owned-busy') && !source.matches('.is-updating, .pm-page.is-loading, .case-records-tab-loading')) return;
        // Only the authenticated shell and its existing dialogs use this system.
        if (!source.closest('.page-content, dialog, .panel-overlay-content')) return;
        const target = targetFor(source);
        if (target instanceof HTMLElement) desired.set(source, target);
    });
    if (document.documentElement.classList.contains('ba-soft-loading')) {
        const root = document.querySelector('[data-reports-module-shell], .ba-shell');
        if (root) desired.set(document.documentElement, targetFor(root));
    }
    for (const [source, state] of observed) {
        if (desired.get(source) !== state.target || !source.isConnected) { state.release(); observed.delete(source); }
    }
    for (const [source, target] of desired) {
        if (!observed.has(source)) observed.set(source, {target, release: begin(target)});
    }
    for (const [target, entry] of entries) if (!target.isConnected) { entry.lifecycle.reset(); clear(target, entry); }
}
function scheduleScan() {
    if (scanQueued) return;
    scanQueued = true; queueMicrotask(scan);
}

function reset() {
    for (const state of observed.values()) state.release();
    observed.clear();
    for (const [target, entry] of entries) {
        entry.lifecycle.reset();
        exits.get(target)?.();
        clear(target, entry, {immediate:true});
    }
}

if (typeof document !== 'undefined') {
    window.PanelSkeleton = { begin, reset, contentTarget: targetFor };
    const install = () => {
        if (!document.body.classList.contains('panel-shell-body') || document.body.matches('.service-catalog-form-frame')) return;
        new MutationObserver(scheduleScan).observe(document.body, {subtree:true, childList:true, attributes:true, attributeFilter:['class','aria-busy']});
        new MutationObserver(mutations => {
            if (mutations.some(change => change.attributeName === 'data-theme')) {
                for (const [target, entry] of entries) if (entry.surface) sizeSurface(target, entry.surface, entry);
            }
            scheduleScan();
        }).observe(document.documentElement, {attributes:true, attributeFilter:['class','data-theme']});
        window.addEventListener('pageshow', reset);
        window.addEventListener('pagehide', reset);
        window.addEventListener('beforeprint', reset);
        scan();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', install, {once:true}); else install();
}
