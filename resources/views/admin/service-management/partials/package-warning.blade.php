@php($warningId = 'package-warning-'.$package->id.'-'.$warningView)
<button type="button" class="package-warning-trigger" popovertarget="{{ $warningId }}" aria-label="Package warnings for {{ $package->name }}" data-package-warning-trigger>
    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
</button>
<div id="{{ $warningId }}" popover="auto" class="package-warning-popover" role="status">
    <strong>{{ $package->name }}</strong>
    <ul>@foreach($intakeBlockers as $warning)<li>{{ $warning }}</li>@endforeach</ul>
</div>
@once
<style>
    .package-title-with-warning { display:flex; align-items:flex-start; gap:6px; }
    .package-title-with-warning > :first-child { min-width:0; }
    .package-title-with-warning h3 { margin:0; }
    .package-warning-trigger { flex-shrink:0; margin-top:-2px; display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; padding:0; border:0; background:transparent; color:#a96508; cursor:pointer; vertical-align:middle; }
    .package-warning-trigger:focus-visible { outline:2px solid currentColor; outline-offset:2px; border-radius:50%; }
    .package-warning-popover { position:fixed; inset:auto; margin:0; width:340px; max-width:calc(100vw - 24px); padding:16px; border:1px solid var(--border); border-radius:12px; background:var(--surface, #fff); color:var(--ink, #142b21); box-shadow:0 8px 28px #0003; font:inherit; }
    .package-warning-popover::backdrop { background:transparent; pointer-events:none; }
    .package-warning-popover ul { margin:8px 0 0; padding-left:18px; }
    .package-warning-popover li + li { margin-top:8px; }
    html[data-theme='dark'] .package-warning-trigger { color:#f4bb56; }
    html[data-theme='dark'] .package-warning-popover { background:#14231b; color:#f4f7f5; border-color:#345144; }
</style>
<script>
document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-package-warning-trigger]');
    if (!trigger) return;
    const panel = document.getElementById(trigger.getAttribute('popovertarget'));
    const rect = trigger.getBoundingClientRect();
    const width = Math.min(340, window.innerWidth - 24);
    panel.style.left = Math.max(12, Math.min(rect.left, window.innerWidth - width - 12)) + 'px';
    panel.style.top = Math.max(12, Math.min(rect.bottom + 8, window.innerHeight - 240)) + 'px';
    panel.style.maxHeight = (window.innerHeight - 36) + 'px';
    panel.style.overflowY = 'auto';
});
const closePackageWarnings = event => {
    // Keep long warning lists scrollable; dismiss on page/table scrolling.
    if (event?.target instanceof Element && event.target.closest('.package-warning-popover')) return;
    document.querySelectorAll('.package-warning-popover:popover-open').forEach(panel => panel.hidePopover());
};
document.addEventListener('scroll', closePackageWarnings, { capture:true, passive:true });
window.addEventListener('resize', closePackageWarnings, { passive:true });
</script>
@endonce
