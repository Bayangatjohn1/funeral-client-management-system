<div class="schedule-advisory-launch" data-advisory-field="{{ $field }}" hidden>
    <button type="button" aria-controls="{{ $field }}-advisory-panel">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <span data-advisory-trigger-label aria-live="polite">View schedule advisory</span>
    </button>
    <div class="service-field-popover schedule-advisory-panel" id="{{ $field }}-advisory-panel" aria-label="{{ $label }} advisory" popover hidden>
        <div data-schedule-advisory="match" hidden></div>
        <div data-schedule-advisory="other" hidden></div>
        <p data-advisory-guidance hidden>Check if your branch can handle these events at the same time. You can continue if staff and resources are available.</p>
    </div>
</div>
