<footer class="panel-page-footer no-print" aria-label="Page navigation">
<button type="button" class="panel-scroll-top" aria-label="Back to top" title="Back to top" hidden>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 19V5m-6 6 6-6 6 6" />
    </svg>
</button>
</footer>
<style>
    .panel-page-footer { display:flex; justify-content:center; padding:16px 0 max(16px, env(safe-area-inset-bottom)); }
    .panel-scroll-top { position:relative; display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; border-radius:50%; border:0; background:transparent; color:#374151; box-shadow:none; cursor:pointer; transition:background-color .15s ease,color .15s ease; }
    .panel-scroll-top svg { width:24px; height:24px; flex:none; }
    .panel-scroll-top[hidden] { display:none !important; }
    .panel-scroll-top:hover { background:rgba(100,116,139,.14); color:#111827; }
    .panel-scroll-top:focus-visible { background:rgba(100,116,139,.14); outline:2px solid #64748B; outline-offset:3px; }
    html[data-theme='dark'] .panel-scroll-top { background:transparent; color:#E5E7EB; box-shadow:none; }
    html[data-theme='dark'] .panel-scroll-top:hover, html[data-theme='dark'] .panel-scroll-top:focus-visible { background:rgba(148,163,184,.18); color:#F9FAFB; }
    html[data-theme='dark'] .panel-scroll-top:focus-visible { outline-color:#F8FAFC; }
    @media print { .panel-scroll-top { display:none !important; } }
</style>
<script>
    (() => {
        const scroller = document.querySelector('.page-content');
        const button = document.querySelector('.panel-scroll-top');
        if (!scroller || !button) return;
        let animation;
        const update = () => {
            const visibleOverlay = [...document.querySelectorAll('[role="dialog"][aria-modal="true"]:not([hidden]):not([aria-hidden="true"])')]
                .some(dialog => dialog.getClientRects().length > 0 && getComputedStyle(dialog).visibility !== 'hidden' && getComputedStyle(dialog).opacity !== '0');
            const shouldHide = scroller.scrollHeight <= scroller.clientHeight + 80 || visibleOverlay;
            if (button.hidden !== shouldHide) button.hidden = shouldHide;
        };
        const cancel = () => cancelAnimationFrame(animation);
        scroller.addEventListener('scroll', update, { passive:true });
        scroller.addEventListener('wheel', cancel, { passive:true });
        scroller.addEventListener('touchstart', cancel, { passive:true });
        window.addEventListener('resize', update, { passive:true });
        new MutationObserver(update).observe(scroller, { childList:true, subtree:true, attributes:true, attributeFilter:['class', 'hidden', 'aria-hidden'] });
        new MutationObserver(update).observe(document.body, { attributes:true, attributeFilter:['class'] });
        button.addEventListener('click', () => {
            cancel();
            const start = scroller.scrollTop;
            const started = performance.now();
            if (location.hash && document.querySelector('.case-section-nav')) history.replaceState(null, '', location.pathname + location.search);
            const focusTarget = document.querySelector('.case-section-nav a, .page-content h1');
            if (focusTarget) {
                if (!focusTarget.matches('a, button, [tabindex]')) focusTarget.setAttribute('tabindex', '-1');
                focusTarget.focus({ preventScroll:true });
            }
            if (matchMedia('(prefers-reduced-motion: reduce)').matches) { scroller.scrollTop = 0; update(); return; }
            const step = now => {
                const progress = Math.min(1, (now - started) / 1000);
                const eased = progress < .5 ? 4 * progress ** 3 : 1 - (-2 * progress + 2) ** 3 / 2;
                scroller.scrollTop = start * (1 - eased);
                if (progress < 1) animation = requestAnimationFrame(step);
                else update();
            };
            animation = requestAnimationFrame(step);
        });
        update();
    })();
</script>
