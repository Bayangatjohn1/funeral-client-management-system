<div class="reports-analytics-filter" x-show="isOwnerAnalytics()" x-cloak>
    <div class="reports-analytics-bar" role="group" aria-label="Owner branch analytics filters">
        <div class="reports-analytics-branch" :title="analyticsBranchLabel()">
            <i class="bi bi-building"></i>
            <select id="analytics_branch_id" x-model="filters.branch_id" class="reports-analytics-select" :disabled="isBranchAdmin" @change="queueOwnerAnalyticsPreview()">
                <template x-if="!isBranchAdmin">
                    <option value="">All Branches</option>
                </template>
                <template x-for="branch in branches" :key="branch.id">
                    <option :value="branch.id" x-text="branch.branch_code"></option>
                </template>
            </select>
            <i class="bi bi-chevron-down reports-analytics-select-chev"></i>
        </div>
        <span class="reports-scope-pill" x-show="isBranchAdmin" x-cloak>
            <i class="bi bi-lock-fill"></i>
            <span>Assigned Branch Only</span>
        </span>

        <div class="reports-analytics-seg" role="group" aria-label="Date preset filter">
            <button type="button" class="reports-analytics-seg-item" :class="{ 'active': datePreset === 'TODAY' }" @click="selectDatePreset('TODAY')">
                Today
            </button>
            <button type="button" class="reports-analytics-seg-item" :class="{ 'active': datePreset === 'THIS_MONTH' }" @click="selectDatePreset('THIS_MONTH')">
                This Month
            </button>
            <button type="button" class="reports-analytics-seg-item" :class="{ 'active': datePreset === 'THIS_YEAR' }" @click="selectDatePreset('THIS_YEAR')">
                This Year
            </button>

            <div class="reports-analytics-custom" @click.outside="customRangeOpen = false">
                <button
                    type="button"
                    class="reports-analytics-seg-item"
                    :class="{ 'active': datePreset === 'CUSTOM' }"
                    :aria-expanded="customRangeOpen.toString()"
                    aria-controls="analyticsDatePopover"
                    @click="customRangeOpen = !customRangeOpen"
                >
                    <i class="bi bi-calendar3"></i>
                    <span>Custom Range</span>
                    <i class="bi bi-chevron-down reports-analytics-date-chev"></i>
                </button>

                <div class="reports-analytics-popover" id="analyticsDatePopover" x-show="customRangeOpen" x-transition x-cloak>
                    <div class="reports-analytics-pop-label">Custom Date Range</div>
                    <div class="reports-analytics-pop-fields">
                        <div class="reports-analytics-pop-field">
                            <label for="analytics_date_from">Date From</label>
                            <input id="analytics_date_from" type="date" x-model="filters.date_from" @input="datePreset = 'CUSTOM'" class="reports-analytics-pop-input">
                        </div>
                        <div class="reports-analytics-pop-field">
                            <label for="analytics_date_to">Date To</label>
                            <input id="analytics_date_to" type="date" x-model="filters.date_to" @input="datePreset = 'CUSTOM'" class="reports-analytics-pop-input">
                        </div>
                    </div>
                    <div class="reports-analytics-pop-actions">
                        <button type="button" class="reports-analytics-pop-apply" @click="applyCustomRange()">Apply</button>
                        <button type="button" class="reports-analytics-pop-reset" @click="selectDatePreset('TODAY')">Reset</button>
                    </div>
                </div>
            </div>
        </div>

        <button type="button" class="reports-analytics-more" :class="{ 'active': advancedFiltersOpen }" @click="advancedFiltersOpen = !advancedFiltersOpen">
            <i class="bi bi-sliders"></i>
            <span>More Filters</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>

    <div class="reports-analytics-advanced" x-show="advancedFiltersOpen" x-transition x-cloak>
        <div class="reports-field">
            <label class="reports-label" for="analytics_interment_from">Interment From</label>
            <input id="analytics_interment_from" type="date" x-model="filters.interment_from" class="reports-input" @change="queueOwnerAnalyticsPreview()">
        </div>
        <div class="reports-field">
            <label class="reports-label" for="analytics_interment_to">Interment To</label>
            <input id="analytics_interment_to" type="date" x-model="filters.interment_to" class="reports-input" @change="queueOwnerAnalyticsPreview()">
        </div>
    </div>
</div>
