<div class="case-compact-seg case-compact-date-filter" role="group" aria-label="Date encoded preset filter">
    <i class="bi bi-calendar3 case-compact-date-icon"></i>
    @if($useDateDisplayLabel)
        <span class="case-compact-date-label" aria-hidden="true">{{ $dateControlLabel }}</span>
    @endif
    <select name="date_preset" class="case-compact-date-select" data-case-date-preset-select aria-label="Date encoded filter">
        <option value="" @selected(blank($datePreset))>All Dates</option>
        <option value="TODAY" @selected($datePreset === 'TODAY')>Today</option>
        <option value="THIS_MONTH" @selected($datePreset === 'THIS_MONTH')>This Month</option>
        <option value="THIS_YEAR" @selected($datePreset === 'THIS_YEAR')>This Year</option>
        <option value="CUSTOM" @selected($datePreset === 'CUSTOM')>Custom Range</option>
    </select>
    <i class="bi bi-chevron-down case-compact-date-chev"></i>

    <div class="case-compact-custom">
        <div class="case-compact-popover" data-case-custom-panel hidden>
            <div class="case-compact-pop-fields">
                <div class="case-compact-pop-field">
                    <label>From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}" class="case-compact-pop-input">
                </div>
                <div class="case-compact-pop-field">
                    <label>To</label>
                    <input type="date" name="date_to" value="{{ $dateTo ?? '' }}" class="case-compact-pop-input">
                </div>
            </div>
            <div class="case-compact-pop-actions">
                <button type="submit" name="date_preset" value="CUSTOM" class="case-compact-pop-apply">Apply</button>
                <a href="{{ $resetUrl }}" class="case-compact-pop-reset">Reset</a>
            </div>
        </div>
    </div>
</div>
