<dialog id="staff-intake-dialog" aria-label="Add New Case">
    <div class="staff-intake-dialog-body">
        <iframe data-intake-frame title="Case Intake Form"></iframe>
    </div>
    <button type="button" class="intake-steps-close" data-intake-close aria-label="Close Add New Case">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="m4 4 8 8m0-8-8 8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
    </button>
</dialog>

<dialog id="staff-intake-confirmation" class="service-feedback-dialog" data-confirm-tone="success"
    aria-labelledby="staff-intake-confirmation-title" aria-describedby="staff-intake-confirmation-description">
    <span class="service-discard-dialog__icon" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor" focusable="false">
            <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5z"/>
            <path d="M8 5.75v4" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
            <circle cx="8" cy="12" r=".75"/>
        </svg>
    </span>
    <h2 id="staff-intake-confirmation-title">Save this intake before leaving?</h2>
    <p id="staff-intake-confirmation-description">You have unsaved changes. Save them as a draft to continue later, or leave without saving.</p>
    <footer class="staff-intake-confirmation-actions">
        <button type="button" data-intake-decision="draft" data-confirm-submit><i class="bi bi-save2" aria-hidden="true"></i> Save as Draft</button>
        <button type="button" data-intake-decision="keep" autofocus>Keep Editing</button>
        <button type="button" data-intake-decision="discard" data-discard>Leave without saving</button>
    </footer>
</dialog>
