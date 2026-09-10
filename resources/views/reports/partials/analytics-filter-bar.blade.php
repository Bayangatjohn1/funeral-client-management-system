<div class="reports-analytics-filter" x-show="isOwnerAnalytics()" x-cloak>
    <div class="reports-analytics-bar" role="group" aria-label="Branch performance report filters">
        <span class="reports-scope-pill" x-show="isBranchAdmin" x-cloak>
            <i class="bi bi-lock-fill"></i>
            <span>Assigned Branch Only</span>
        </span>

        <button type="button" class="reports-btn reports-btn-neutral reports-filter-reset" @click="resetFilters">
            <i class="bi bi-arrow-counterclockwise"></i>
            <span>Reset Filters</span>
        </button>
    </div>

    <div class="reports-analytics-advanced" x-show="advancedFiltersOpen || datePreset === 'CUSTOM'" x-transition x-cloak>
        <template x-if="datePreset === 'CUSTOM'">
            <div class="reports-field">
                <label class="reports-label" for="analytics_date_from">Date From</label>
                <span class="reports-field-control">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <input id="analytics_date_from" type="date" x-model="filters.date_from" class="reports-input">
                </span>
            </div>
        </template>

        <template x-if="datePreset === 'CUSTOM'">
            <div class="reports-field">
                <label class="reports-label" for="analytics_date_to">Date To</label>
                <span class="reports-field-control">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>
                    <input id="analytics_date_to" type="date" x-model="filters.date_to" class="reports-input">
                </span>
            </div>
        </template>

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
