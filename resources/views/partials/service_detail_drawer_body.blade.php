<script>
document.addEventListener('DOMContentLoaded', () => {
    if (!document.body.matches('.service-catalog-modal-frame:not(.service-catalog-form-frame)')) return;
    document.querySelectorAll('.svc-detail-card').forEach(card => {
        if (card.querySelector(':scope > .record-service-drawer-body')) return;
        const header = card.querySelector(':scope > .svc-detail-head');
        const footer = card.querySelector(':scope > .svc-detail-actions');
        if (!header) return;
        const body = document.createElement('div');
        body.className = 'record-service-drawer-body';
        // Existing feedback belongs in the scroll body so it cannot push the footer offscreen.
        card.parentElement?.querySelectorAll(':scope > .svc-status-feedback').forEach(message => body.append(message));
        [...card.children].filter(child => child !== header && child !== footer).forEach(child => body.append(child));
        header.after(body);
        card.classList.add('record-service-drawer-card');
    });
});
</script>
