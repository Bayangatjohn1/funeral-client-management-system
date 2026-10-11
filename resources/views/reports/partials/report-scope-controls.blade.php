<div class="reports-module-controls" aria-label="Report scope controls">
            <label class="reports-module-control-wrap" for="reportsHeaderDatePreset">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <select
                    id="reportsHeaderDatePreset"
                    class="reports-module-control"
                    aria-label="Date range"
                    x-model="datePreset"
                    @change="changeReportDatePreset(datePreset)"
                >
                    <option value="">All Dates</option>
                    <option value="TODAY">Today</option>
                    <option value="THIS_WEEK">This Week</option>
                    <option value="THIS_MONTH">This Month</option>
                    <option value="THIS_QUARTER">This Quarter</option>
                    <option value="FIRST_HALF">Semi-Annual — First Half</option>
                    <option value="SECOND_HALF">Semi-Annual — Second Half</option>
                    <option value="THIS_YEAR">This Year</option>
                    <option value="CUSTOM">Custom Range</option>
                </select>
            </label>

            <label class="reports-module-control-wrap" for="reportsHeaderBranch" x-show="!isBranchAdmin" x-cloak>
                <i class="bi bi-building" aria-hidden="true"></i>
                <select
                    id="reportsHeaderBranch"
                    class="reports-module-control is-branch"
                    aria-label="Branch"
                    x-model="filters.branch_id"
                    :disabled="isBranchAdmin"
                    @change="loadPreview()"
                >
                    <template x-if="!isBranchAdmin">
                        <option value="">All Branches</option>
                    </template>
                    <template x-for="branch in branches" :key="branch.id">
                        <option :value="branch.id" x-text="`${branch.branch_code} - ${branch.branch_name}`"></option>
                    </template>
                </select>
            </label>

            <label class="reports-module-control-wrap" for="reportsHeaderPaymentStatus" x-show="shows('payment_status')" x-cloak>
                <i class="bi bi-wallet2" aria-hidden="true"></i>
                <select
                    id="reportsHeaderPaymentStatus"
                    class="reports-module-control is-compact"
                    aria-label="Payment status"
                    x-model="filters.payment_status"
                    @change="loadPreview()"
                >
                    <option value="">All Payment Statuses</option>
                    <option value="PAID">Paid</option>
                    <option value="PARTIAL">Partial</option>
                    <option value="UNPAID">Unpaid</option>
                </select>
            </label>

            <label class="reports-module-control-wrap" for="reportsHeaderPaymentMethod" x-show="shows('payment_method')" x-cloak>
                <i class="bi bi-credit-card" aria-hidden="true"></i>
                <select id="reportsHeaderPaymentMethod" class="reports-module-control is-compact" aria-label="Payment method" x-model="filters.payment_method" @change="loadPreview()">
                    <option value="">All Payment Methods</option>
                    <option value="cash">Cash</option>
                    <option value="cashless">Cashless</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
            </label>

            <label class="reports-module-control-wrap" for="reportsHeaderAuditUser" x-show="shows('audit_user') && auditOptions.supports_user" x-cloak>
                <i class="bi bi-person" aria-hidden="true"></i>
                <select
                    id="reportsHeaderAuditUser"
                    class="reports-module-control is-compact"
                    aria-label="Audit user"
                    x-model="filters.user_id"
                    @change="loadPreview()"
                >
                    <option value="">All Users</option>
                    <template x-for="user in users" :key="user.id">
                        <option :value="user.id" x-text="user.name"></option>
                    </template>
                </select>
            </label>

            
        </div>
