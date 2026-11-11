@php
    $successToastId = $toastId ?? 'centered-success-toast';
    $successToastTitle = $title ?? 'Saved successfully';
    $successToastMessage = $message ?? 'Your changes have been saved.';
@endphp
<section id="{{ $successToastId }}" class="centered-success-toast" role="status" aria-live="polite" aria-atomic="true" data-centered-success-toast>
    <span class="centered-success-toast__icon" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
    <span class="centered-success-toast__copy">
        <strong>{{ $successToastTitle }}</strong>
        <span>{{ $successToastMessage }}</span>
    </span>
    <button type="button" class="centered-success-toast__close" data-centered-success-toast-close aria-label="Dismiss confirmation"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
</section>
<style>
    .centered-success-toast { position:fixed; z-index:2400; top:calc(var(--topbar-h,72px) + 14px); left:50%; display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:12px; width:min(500px,calc(100vw - 32px)); padding:14px 16px; border:1px solid color-mix(in srgb,#16834a 24%,var(--border)); border-radius:14px; background:color-mix(in srgb,#16834a 10%,var(--card)); color:var(--ink); box-shadow:0 10px 30px rgb(24 60 42 / 14%); opacity:0; transform:translate(-50%,-10px); transition:opacity .2s ease,transform .2s ease; }
    .centered-success-toast.is-visible { opacity:1; transform:translate(-50%,0); }
    .centered-success-toast.is-leaving { opacity:0; transform:translate(-50%,-8px); }
    .centered-success-toast__icon { display:grid; place-items:center; width:36px; height:36px; border-radius:11px; background:color-mix(in srgb,#16834a 18%,var(--card)); color:#12643b; font-size:17px; }
    .centered-success-toast__copy { display:grid; gap:3px; min-width:0; }
    .centered-success-toast__copy strong { font-size:14px; line-height:1.4; font-weight:700; }
    .centered-success-toast__copy > span { color:var(--ink-muted); font-size:12px; line-height:1.45; }
    .centered-success-toast__close { display:grid; place-items:center; width:32px; height:32px; padding:0; border:0; border-radius:8px; background:transparent; color:var(--ink-muted); cursor:pointer; }
    .centered-success-toast__close:hover { background:color-mix(in srgb,var(--brand) 14%,var(--card)); color:var(--ink); }
    .centered-success-toast__close:focus-visible { outline:2px solid #174a36; outline-offset:2px; }
    @media(max-width:640px) { .centered-success-toast { top:14px; gap:9px; padding:12px; } .centered-success-toast__icon { width:32px; height:32px; } .centered-success-toast__copy strong { font-size:13px; } .centered-success-toast__copy > span { font-size:11px; } }
    @media(prefers-reduced-motion:reduce) { .centered-success-toast { transition:none; } }
</style>
<script>
    (() => {
        const toast = document.getElementById(@json($successToastId));
        if (!toast) return;
        let timer;
        const dismiss = () => {
            if (!toast.isConnected || toast.classList.contains('is-leaving')) return;
            window.clearTimeout(timer);
            toast.classList.add('is-leaving');
            window.setTimeout(() => toast.remove(), 220);
        };
        const scheduleDismiss = () => {
            window.clearTimeout(timer);
            if (!toast.matches(':hover, :focus-within')) timer = window.setTimeout(dismiss, 4000);
        };
        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('is-visible')));
        toast.addEventListener('pointerenter', () => window.clearTimeout(timer));
        toast.addEventListener('pointerleave', scheduleDismiss);
        toast.addEventListener('focusin', () => window.clearTimeout(timer));
        toast.addEventListener('focusout', () => queueMicrotask(scheduleDismiss));
        toast.querySelector('[data-centered-success-toast-close]')?.addEventListener('click', dismiss);
        scheduleDismiss();
    })();
</script>
