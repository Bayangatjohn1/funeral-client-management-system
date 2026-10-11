<div class="reports-analytics-filter uniform-record-filters" x-show="isOwnerAnalytics()" x-cloak>
    <div class="reports-analytics-advanced" x-show="advancedFiltersOpen" x-transition x-cloak>

        <div class="reports-field">
            <label class="reports-label" for="analytics_interment_from">Interment From</label>
            <span class="reports-field-control">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                <input id="analytics_interment_from" type="date" x-model="filters.interment_from" class="reports-input">
            </span>
        </div>

        <div class="reports-field">
            <label class="reports-label" for="analytics_interment_to">Interment To</label>
            <span class="reports-field-control">
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                <input id="analytics_interment_to" type="date" x-model="filters.interment_to" class="reports-input">
            </span>
        </div>
    </div>
</div>
