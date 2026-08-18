<div class="reports-analytics-filter" x-show="isOwnerAnalytics()" x-cloak>
    <div class="reports-analytics-bar" role="group" aria-label="Owner branch analytics filters">
        <div class="reports-field reports-field-wide">
            <label class="reports-label" for="analytics_branch_id">Branch</label>
            <select id="analytics_branch_id" x-model="filters.branch_id" class="reports-input" :disabled="isBranchAdmin">
                <template x-if="!isBranchAdmin">
                    <option value="">All Branches</option>
                </template>
                <template x-for="branch in branches" :key="branch.id">
                    <option :value="branch.id" x-text="`${branch.branch_code} - ${branch.branch_name}`"></option>
                </template>
            </select>
        </div>

        <span class="reports-scope-pill" x-show="isBranchAdmin" x-cloak>
            <i class="bi bi-lock-fill"></i>
            <span>Assigned Branch Only</span>
        </span>

        <div class="reports-field">
            <label class="reports-label" for="analytics_date_preset">Date Range</label>
            <select id="analytics_date_preset" x-model="datePreset" class="reports-input" @change="selectDatePreset(datePreset || 'TODAY')">
                <option value="TODAY">Today</option>
                <option value="THIS_MONTH">This Month</option>
                <option value="THIS_YEAR">This Year</option>
                <option value="CUSTOM">Custom Range</option>
            </select>
        </div>

        <button
            type="button"
            class="reports-analytics-more"
            :class="{ 'active': advancedFiltersOpen }"
            @click="advancedFiltersOpen = !advancedFiltersOpen"
        >
            <i class="bi bi-sliders"></i>
            <span>More Filters</span>
            <i class="bi bi-chevron-down"></i>
        </button>
    </div>

    <div class="reports-analytics-advanced" x-show="advancedFiltersOpen || datePreset === 'CUSTOM'" x-transition x-cloak>
        <template x-if="datePreset === 'CUSTOM'">
            <div class="reports-field">
                <label class="reports-label" for="analytics_date_from">Date From</label>
                <input id="analytics_date_from" type="date" x-model="filters.date_from" class="reports-input">
            </div>
        </template>

        <template x-if="datePreset === 'CUSTOM'">
            <div class="reports-field">
                <label class="reports-label" for="analytics_date_to">Date To</label>
                <input id="analytics_date_to" type="date" x-model="filters.date_to" class="reports-input">
            </div>
        </template>

        <div class="reports-field">
            <label class="reports-label" for="analytics_interment_from">Interment From</label>
            <input id="analytics_interment_from" type="date" x-model="filters.interment_from" class="reports-input">
        </div>

        <div class="reports-field">
            <label class="reports-label" for="analytics_interment_to">Interment To</label>
            <input id="analytics_interment_to" type="date" x-model="filters.interment_to" class="reports-input">
        </div>
    </div>
</div>
