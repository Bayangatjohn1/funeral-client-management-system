<colgroup>
    <col class="records-col-case">
    @if($showBranch ?? false)<col class="records-col-branch">@endif
    <col class="records-col-deceased">
    <col class="records-col-interment">
    <col class="records-col-case-status">
    <col class="records-col-payment-status">
    <col class="records-col-balance">
    <col class="records-col-actions">
</colgroup>
<thead>
    <tr>
        <th class="text-left">Case ID</th>
        @if($showBranch ?? false)<th class="text-left">Branch</th>@endif
        <th class="text-left">Deceased</th>
        <th class="text-left">Interment Date</th>
        <th class="table-status-col">Case Status</th>
        <th class="table-status-col table-payment-status-col">Payment Status</th>
        <th class="table-col-number">Remaining Balance</th>
        <th class="table-col-actions">Actions</th>
    </tr>
</thead>
