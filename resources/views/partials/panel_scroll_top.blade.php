<button type="button" class="panel-scroll-top no-print" aria-label="Scroll to top" hidden>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M12 19V5m-6 6 6-6 6 6" />
    </svg>
</button>
<style>
    .panel-scroll-top { position:fixed; left:50%; bottom:max(24px, env(safe-area-inset-bottom)); transform:translateX(-50%); z-index:40; display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; border-radius:50%; border:1px solid #123525; background:#1B4332; color:#fff; box-shadow:0 6px 20px rgba(8,29,19,.32); cursor:pointer; }
    .panel-scroll-top svg { width:24px; height:24px; flex:none; }
    .panel-scroll-top[hidden] { display:none !important; }
    .panel-scroll-top:hover { background:#123525; box-shadow:0 8px 24px rgba(8,29,19,.4); }
    .panel-scroll-top:focus-visible { outline:3px solid #34D399; outline-offset:3px; }
    html[data-theme='dark'] .panel-scroll-top { border-color:#6EE7B7; background:#34D399; color:#07130E; box-shadow:0 6px 20px rgba(0,0,0,.5); }
    html[data-theme='dark'] .panel-scroll-top:hover { background:#6EE7B7; }
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
            button.hidden = scroller.scrollHeight <= scroller.clientHeight + 80 || scroller.scrollTop <= 80 || visibleOverlay;
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
