@extends('layouts.panel')

@section('page_title', 'Branch Management')
@section('page_desc', 'Manage branch details and status.')
@section('hide_layout_topbar', '1')
@section('page_back')
    <span class="branch-header-icon" aria-hidden="true"><i class="bi bi-building"></i></span>
@endsection

@section('topbar_actions')
    <button id="openBranchCreateModal" type="button" class="btn btn-primary-custom btn-sm">
        <i class="bi bi-plus-circle" aria-hidden="true"></i>
        <span>Add Branch</span>
    </button>
@endsection

@section('content')
<style>[x-cloak] { display: none !important; }
.admin-table-page.branch-management-page {
    min-height:calc(100vh - 1rem);
    background:
        linear-gradient(90deg, rgba(73,87,69,0.04) 0 1px, transparent 1px),
        linear-gradient(180deg, rgba(73,87,69,0.034) 0 1px, transparent 1px),
        repeating-linear-gradient(135deg, rgba(73,87,69,0.02) 0 1px, transparent 1px 12px),
        #C4D2BE;
    background-size:44px 44px,44px 44px,16px 16px,auto;
}
.management-toast {
    position:fixed;
    top:1rem;
    right:1rem;
    z-index:1200;
    display:flex;
    align-items:center;
    gap:.55rem;
    max-width:calc(100vw - 2rem);
    border:1px solid #8EA083;
    border-radius:.75rem;
    background:#2F3A2E;
    color:#F7FAF3;
    padding:.72rem .9rem;
    font-size:.88rem;
    font-weight:650;
    line-height:1.35;
    box-shadow:none !important;
    pointer-events:none;
    animation:managementToastIn .18s ease-out, managementToastOut .22s ease-in 3.8s forwards;
}
.management-toast i {
    font-size:1rem;
    color:#DCE6D6;
}
@keyframes managementToastIn {
    from { opacity:0; transform:translateY(-.35rem); }
    to { opacity:1; transform:translateY(0); }
}
@keyframes managementToastOut {
    to { opacity:0; transform:translateY(-.35rem); visibility:hidden; }
}
.branch-management-page .table-system-card,
.branch-management-page .admin-table-card {
    background:transparent !important;
    border:0 !important;
    border-radius:0 !important;
    box-shadow:none !important;
    overflow:visible !important;
}
.branch-management-page .admin-table-card {
    display:flex;
    flex-direction:column;
    gap:0;
}
.branch-management-page .table-system-head {
    background:#D0DDC8 !important;
    border:1px solid #6F806B !important;
    border-bottom:0 !important;
    border-radius:.45rem .45rem 0 0 !important;
    padding:.75rem !important;
    order:2;
}
.branch-management-page .admin-table-head-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:1rem;
    flex-wrap:wrap;
}
.branch-management-page .admin-table-head-row {
    justify-content:flex-start;
}
.branch-management-page .table-system-title {
    color:var(--ink);
    font-size:1.25rem;
    font-weight:750;
    letter-spacing:0;
    line-height:1.15;
}
.branch-management-page .admin-table-head-copy {
    color:var(--ink-muted);
    font-size:.88rem;
    font-weight:600;
    margin-top:.2rem;
}
.branch-management-page .admin-table-head-actions,
.branch-management-page .filter-actions {
    display:flex;
    align-items:center;
    gap:.6rem;
    flex-wrap:wrap;
}
.branch-management-page .admin-table-head-actions {
    width:100%;
    justify-content:space-between;
}
.branch-management-page .table-system-toolbar {
    background:#D0DDC8 !important;
    border:1px solid #6F806B !important;
    border-radius:.45rem !important;
    padding:.75rem;
    margin-bottom:.85rem;
    order:1;
}
.branch-management-page .branch-directory-card-view,
.branch-management-page .branch-directory-table-view,
.branch-management-page .table-system-list {
    order:3;
}
.branch-management-page .branch-directory-table-view {
    margin-top:0 !important;
}
.branch-management-page .table-toolbar {
    display:flex;
    flex-wrap:wrap;
    gap:.65rem !important;
    align-items:center;
}
.branch-management-page .table-toolbar-label {
    display:none;
}
.branch-management-page .table-toolbar-search,
.branch-management-page .table-toolbar-select,
.branch-management-page .table-toolbar-sort {
    min-height:2.9rem !important;
    height:2.9rem !important;
    border-radius:.38rem !important;
    border:1px solid #6F806B !important;
    background:#E9F0E4 !important;
    background-color:#E9F0E4 !important;
    color:#263026 !important;
    font-size:.92rem !important;
    font-weight:660 !important;
    box-shadow:none !important;
    cursor:pointer;
    appearance:none !important;
    -webkit-appearance:none !important;
    -moz-appearance:none !important;
    background-image:none !important;
    transition:background-color .16s ease,border-color .16s ease,color .16s ease;
}
.branch-management-page .table-toolbar-input-wrap,
.branch-management-page .table-toolbar-select-wrap {
    position:relative;
}
.branch-management-page .table-toolbar-field:first-child {
    flex:1 1 20rem;
    min-width:min(20rem, 100%);
}
.branch-management-page .table-toolbar-field:not(:first-child) {
    flex:0 0 15.5rem;
    min-width:15.5rem;
}
.branch-management-page .table-toolbar-reset-wrap { display:none !important; }
.branch-management-page .table-toolbar-input-wrap,
.branch-management-page .table-toolbar-select-wrap,
.branch-management-page .table-toolbar-field,
.branch-management-page .table-toolbar-search,
.branch-management-page .table-toolbar-select,
.branch-management-page .table-toolbar-sort {
    width:100%;
}
.branch-management-page .table-toolbar-search,
.branch-management-page .table-toolbar-select,
.branch-management-page .table-toolbar-sort {
    padding-left:2.6rem !important;
    padding-right:2.45rem !important;
}
.branch-management-page .table-toolbar-search { cursor:text; }
.branch-management-page .table-toolbar-select-wrap,
.branch-management-page .table-toolbar-select-wrap *,
.branch-management-page .filter-actions *,
.branch-management-page .admin-table-head-actions * {
    cursor:pointer;
}
.branch-management-page .table-toolbar-select-icon {
    pointer-events:none;
    position:absolute;
    right:1rem;
    top:50%;
    transform:translateY(-50%);
    color:#4F5E4C;
    font-size:.9rem;
}
.branch-management-page .table-toolbar-leading-icon {
    position:absolute;
    left:1rem;
    top:50%;
    transform:translateY(-50%);
    color:#4F5E4C;
    font-size:1rem;
    pointer-events:none;
}
.branch-management-page .table-toolbar-search:hover,
.branch-management-page .table-toolbar-select:hover,
.branch-management-page .table-toolbar-sort:hover {
    background:#DFE9D9 !important;
    background-color:#DFE9D9 !important;
    border-color:#52654E !important;
}
.branch-management-page .table-toolbar-search:focus,
.branch-management-page .table-toolbar-select:focus,
.branch-management-page .table-toolbar-sort:focus {
    border-color:#344333 !important;
    background:#EEF5E9 !important;
    background-color:#EEF5E9 !important;
    box-shadow:none !important;
}
.branch-management-page .btn-secondary,
.branch-management-page .btn-primary-custom {
    min-height:2.9rem;
    height:2.9rem;
    border-radius:.4rem;
    padding:0 .95rem;
    font-size:.84rem;
    font-weight:650;
    box-shadow:none !important;
    cursor:pointer;
    transition:background-color .16s ease,border-color .16s ease,color .16s ease;
}
.branch-management-page .btn-secondary {
    min-width:5.8rem;
    white-space:nowrap;
}
.branch-management-page .btn-primary-custom:hover {
    background:#DDE8D6 !important;
    border-color:#8EA083 !important;
    color:var(--ink) !important;
}
.branch-management-page .btn-secondary,
.branch-management-page .btn-primary-custom {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:.45rem;
    background:#344333 !important;
    border:1px solid #344333 !important;
    color:#fff !important;
}
.branch-management-page .filter-actions {
    min-height:2.9rem;
    padding:0;
    border:0;
    background:transparent;
}
.branch-management-page .filter-actions .btn-primary-custom {
    min-width:8.75rem;
}
.branch-management-page .btn-secondary:hover,
.branch-management-page .btn-primary-custom:hover {
    background:#2F3A2E !important;
    border-color:#2F3A2E !important;
    color:#fff !important;
}
.branch-management-page .admin-table-head-actions .btn-primary-custom {
    min-width:8.75rem;
    min-height:2.7rem;
    background:#344333 !important;
    border-color:#344333 !important;
    color:#fff !important;
    font-weight:650;
}
.branch-management-page .admin-table-head-actions .btn-primary-custom i {
    color:#F7FAF3;
}
.branch-management-page .admin-table-head-actions > .inline-flex {
    background:#E1E7D9 !important;
    border:1px solid var(--border) !important;
    border-radius:.65rem !important;
    overflow:hidden;
    box-shadow:none !important;
    min-height:2.7rem;
}
.branch-management-page .admin-table-head-actions > .inline-flex button {
    min-height:2.7rem;
    color:var(--ink);
    font-weight:650;
    background:transparent !important;
    cursor:pointer;
}
.branch-management-page .admin-table-head-actions > .inline-flex button:hover {
    background:#8EA083 !important;
    color:#F7FAF3 !important;
}
.branch-management-page .admin-table-head-actions > .inline-flex .bg-slate-900 {
    background:#344333 !important;
    color:#fff !important;
}
.branch-management-page .admin-table-head-actions > .inline-flex .bg-slate-900:hover {
    background:#2F3A2E !important;
    color:#fff !important;
}
.branch-kpi-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.65rem;
    background:#D0DDC8;
    border:1px solid #6F806B;
    border-radius:.45rem;
    padding:.55rem;
    margin-top:0;
    margin-bottom:-.35rem;
}
@media (max-width: 1024px) {
    .branch-kpi-strip { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .branch-kpi-strip { grid-template-columns: 1fr; }
}
.branch-kpi-card {
    display: block;
    background: #DCE6D6;
    border: 1px solid #6F806B;
    border-radius: .38rem;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease;
    box-shadow:none !important;
}
.branch-kpi-card:hover {
    background: #C7D5BE;
    border-color: #52654E;
}
.branch-kpi-card__inner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.58rem 0.9rem;
}
.branch-kpi-card__icon {
    width: 2rem;
    height: 2rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    flex-shrink: 0;
    color: #2F3A2E;
}
.branch-kpi-card__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.08rem;
}
.branch-kpi-card__label {
    font-size: 0.65rem;
    font-weight: 640;
    color: #465344;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
    line-height: 1.3;
}
.branch-kpi-card__value {
    font-size: 1.15rem;
    font-weight: 730;
    line-height: 1.15;
    color: #263026;
    font-variant-numeric: tabular-nums;
}
.branch-kpi-card__desc {
    font-size: 0.63rem;
    color: #4F5E4C;
    font-weight: 610;
    line-height: 1.3;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 0.1rem;
}
.branch-kpi-card__action {
    font-size: 0.62rem;
    font-weight: 700;
    color: var(--ink);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    white-space: nowrap;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.15s ease;
}
.branch-kpi-card:hover .branch-kpi-card__action {
    opacity: 1;
}
.branch-management-page .branch-directory-card-view,
.branch-management-page .table-system-list {
    background:#D0DDC8 !important;
    border:1px solid #93A28C !important;
    border-top:0 !important;
    border-radius:0 0 .55rem .55rem !important;
    padding:.75rem !important;
    overflow:visible !important;
}
.branch-management-page .branch-directory-card-view > .grid {
    grid-template-columns:repeat(auto-fill, minmax(min(100%, 320px), 1fr));
    gap:18px;
}
.branch-management-page .directory-item-card {
    position:relative;
    background:#DCE6D6 !important;
    border:1px solid #7F917A !important;
    border-radius:.45rem !important;
    box-shadow:none !important;
    overflow:visible;
    min-height:13.25rem;
}
.branch-management-page .directory-item-card:hover {
    background:#C7D5BE !important;
    border-color:#52654E !important;
}
.branch-management-page .directory-item-card:hover .branch-card-identity {
    background:#BFD0B6;
}
.branch-management-page .directory-item-card:hover .branch-card-metric,
.branch-management-page .directory-item-card:hover .branch-card-footer {
    background:#C7D5BE;
}
.branch-management-page .branch-card-identity {
    background:#C7D5BE;
    border-bottom:1px solid #8EA083;
    border-radius:.4rem .4rem 0 0;
    text-align:center;
    padding-top:3.65rem !important;
}
.branch-management-page .branch-card-view-button { position:absolute; top:.75rem; right:.75rem; z-index:1; }
.branch-management-page .branch-code-pill {
    display:block;
    width:100%;
    color:#4B5949 !important;
    font-size:.72rem;
    font-weight:680;
    letter-spacing:.075em;
    line-height:1.2;
    text-transform:uppercase;
}
.branch-management-page .branch-main-pill {
    display:inline-flex;
    align-items:center;
    gap:.22rem;
    color:#684B1C !important;
    font-size:.68rem;
    font-weight:660;
    letter-spacing:.04em;
    line-height:1;
}
.branch-management-page .branch-card-name {
    color:#293229 !important;
    font-size:1rem;
    font-weight:680;
    line-height:1.25;
    margin-top:.7rem;
}
.branch-management-page .branch-card-address {
    color:#5E695C !important;
    font-size:.8rem;
    font-weight:590;
    line-height:1.35;
    justify-content:center;
    margin-top:.35rem;
}
.branch-management-page .branch-card-metric {
    background:#DCE6D6;
    text-align:center;
}
.branch-management-page .branch-card-metric-label {
    color:#5E695C !important;
    font-size:.68rem;
    font-weight:640;
    letter-spacing:.055em;
    text-transform:uppercase;
}
.branch-management-page .branch-card-metric-value {
    color:#293229 !important;
    font-size:1.9rem;
    font-weight:720;
    letter-spacing:0;
    line-height:1;
}
.branch-management-page .table-toolbar-select option,
.branch-management-page .table-toolbar-sort option {
    background:#E9F0E4;
    color:#263026;
    font-weight:620;
}
.branch-management-page .table-toolbar-select option:checked,
.branch-management-page .table-toolbar-sort option:checked {
    background:#C7D5BE;
    color:#263026;
}
.branch-management-page .branch-card-footer {
    min-height:3.65rem;
    background:#DCE6D6;
    border-radius:0 0 .4rem .4rem;
}
.branch-management-page .branch-card-actions {
    opacity:.72;
    visibility:visible;
    transform:translateY(0);
    transition:opacity .16s ease, transform .16s ease;
}
.branch-management-page .directory-item-card:hover .branch-card-actions,
.branch-management-page .directory-item-card:focus-within .branch-card-actions,
.branch-management-page .directory-item-card:has(.row-action-menu.is-open) .branch-card-actions,
.branch-management-page .branch-card-actions.is-open {
    opacity:1;
    visibility:visible;
    transform:translateY(-1px);
}
.branch-management-page .branch-card-actions.is-open {
    z-index:160;
}
.branch-management-page .branch-card-menu {
    position:relative;
    display:inline-flex;
    justify-content:flex-end;
}
.branch-management-page .branch-card-menu summary {
    list-style:none;
}
.branch-management-page .branch-card-menu summary::-webkit-details-marker {
    display:none;
}
.branch-management-page .branch-card-menu[open] {
    opacity:1;
    z-index:170;
}
.branch-management-page .branch-card-menu[open] .branch-card-dropdown {
    display:block;
}
.branch-management-page .branch-card-dropdown {
    position:absolute;
    right:0;
    bottom:calc(100% + .45rem);
    display:none;
    min-width:12rem;
    border:1px solid #93A28C;
    border-radius:.55rem;
    background:#E1E7D9;
    padding:.35rem;
    z-index:180;
}
.branch-management-page .directory-item-card .border-t {
    border-color:var(--border) !important;
}
.branch-management-page .directory-item-card h3,
.branch-management-page .table-primary {
    color:var(--ink) !important;
    font-weight:680;
}
.branch-management-page .directory-item-card p,
.branch-management-page .directory-item-card .text-slate-500,
.branch-management-page .directory-item-card .text-slate-400,
.branch-management-page .table-secondary {
    color:var(--ink-muted) !important;
    font-weight:600;
}
.branch-management-page .row-action-trigger {
    width:2.75rem !important;
    min-width:2.75rem;
    min-height:2.75rem;
    display:inline-grid;
    place-items:center;
    border-radius:.75rem !important;
    background:#E1E7D9 !important;
    border:1px solid var(--border) !important;
    color:var(--ink) !important;
    box-shadow:none !important;
    cursor:pointer;
    font-size:.78rem;
    font-weight:700;
    padding:0;
}
.branch-management-page .row-action-trigger:hover,
.branch-management-page .row-action-item:hover {
    background:#C7D5BE !important;
    color:var(--ink) !important;
}
.branch-management-page .row-action-dropdown {
    min-width:12rem;
    border:1px solid #B5C4AD !important;
    border-radius:.75rem !important;
    background:#E1E7D9 !important;
    box-shadow:none !important;
    padding:.35rem !important;
}
.branch-management-page .row-action-item {
    border-radius:.6rem !important;
    color:var(--ink) !important;
    font-size:.8rem !important;
    font-weight:680 !important;
    padding:.6rem .7rem !important;
}
.branch-management-page .row-action-item i {
    color:#566653 !important;
}
.branch-management-page .table-system-wrap,
.branch-management-page .table-system-table {
    background:#DCE6D6 !important;
    box-shadow:none !important;
}
.branch-management-page .table-system-wrap {
    margin:0 !important;
    border:1.25px solid var(--border);
    border-radius:.75rem;
    overflow:auto;
}
.branch-management-page .table-system-table thead th {
    background:#C7D5BE !important;
    color:var(--ink-muted) !important;
    font-weight:650;
    font-size:.72rem;
    letter-spacing:.05em;
}
.branch-management-page .directory-item-card [class*="tracking-widest"],
.branch-management-page .table-system-table tbody td {
    color:var(--ink) !important;
}
.branch-management-page .table-system-table tbody tr:hover {
    background:#C7D5BE !important;
}
@media(max-width:900px) {
    .branch-management-page .table-toolbar { grid-template-columns:1fr !important; }
    .branch-management-page .admin-table-head-actions,
    .branch-management-page .filter-actions { width:100%; }
    .branch-management-page .filter-actions > * { flex:1; }
}
#branchCreateModalSheet,
#branchModalSheet {
    background:#D3DEC9 !important;
    border:1px solid var(--border) !important;
    border-radius:.75rem !important;
    box-shadow:none !important;
}
#branchCreateModalSheet .bg-white,
#branchCreateModalSheet .bg-slate-50,
#branchModalSheet .bg-white,
#branchModalSheet .bg-slate-50,
#branchModalContent {
    background:#D3DEC9 !important;
}
#branchCreateModalSheet .rounded-2xl {
    border-radius:.75rem !important;
    border-color:var(--border) !important;
    box-shadow:none !important;
}
#branchCreateModalSheet .rounded-2xl,
#branchModalContent .rounded-2xl {
    background:#D3DEC9 !important;
}
#branchCreateModalSheet .border-b,
#branchCreateModalSheet .border-t,
#branchCreateModalSheet .border-slate-200,
#branchModalSheet .border-slate-200 {
    border-color:var(--border) !important;
}
#branchCreateModalSheet .px-6.py-5,
#branchModalContent .px-6.py-5 {
    background:#C7D5BE !important;
    border-bottom:1px solid #AEBFA6 !important;
    padding:1rem 1.15rem !important;
}
#branchCreateModalSheet h2,
#branchModalContent h2 {
    color:var(--ink) !important;
    font-size:1.25rem !important;
    font-weight:760 !important;
    letter-spacing:0 !important;
}
#branchCreateModalSheet p,
#branchCreateModalSheet .text-slate-500,
#branchCreateModalSheet .text-slate-700,
#branchModalContent p,
#branchModalContent .text-slate-500,
#branchModalContent .text-slate-700 {
    color:var(--ink-muted) !important;
    font-weight:600;
}
#branchCreateModalSheet .p-6,
#branchModalContent .p-6 {
    padding:1rem !important;
}
#branchCreateModalSheet .grid,
#branchModalContent .grid {
    gap:.8rem !important;
}
#branchCreateModalSheet .label-section,
#branchModalContent .label-section {
    color:#566653 !important;
    font-size:.76rem !important;
    font-weight:680 !important;
}
#branchCreateModalSheet .form-input,
#branchModalContent .form-input,
#branchModalContent select,
#branchModalContent textarea {
    min-height:2.65rem;
    border-radius:.65rem;
    border:1px solid #AEBFA6 !important;
    background:#E9F0E4 !important;
    color:var(--ink) !important;
    font-weight:650;
    box-shadow:none !important;
}
#branchCreateModalSheet .form-input:focus,
#branchModalContent .form-input:focus,
#branchModalContent select:focus,
#branchModalContent textarea:focus {
    border-color:#8EA083;
    background:#F4F8EF !important;
    box-shadow:none !important;
}
#branchCreateModalSheet .rounded-xl,
#branchModalContent .rounded-xl {
    border-color:#AEBFA6 !important;
    background:#DDE8D6 !important;
    box-shadow:none !important;
}
#branchCreateModalSheet .text-slate-500.mt-2,
#branchModalContent .text-slate-500.mt-2 {
    margin-top:.45rem !important;
    font-size:.82rem !important;
}
#branchCreateModalSheet button,
#branchModalSheet button {
    cursor:pointer;
    box-shadow:none !important;
}
#branchCreateModalClose,
#branchEditModalClose {
    background:#E9F0E4 !important;
    border:1px solid #AEBFA6 !important;
    color:var(--ink) !important;
    border-radius:.55rem !important;
}
#branchCreateModalClose:hover,
#branchEditModalClose:hover {
    background:#DDE8D6 !important;
    color:var(--ink) !important;
}
#branchCreateModalSheet .btn-outline,
#branchModalContent .btn-outline {
    min-height:2.55rem;
    border:1px solid #AEBFA6 !important;
    border-radius:.55rem !important;
    background:#E9F0E4 !important;
    color:#3E4A3D !important;
    box-shadow:none !important;
}
#branchCreateModalSheet .btn-outline:hover,
#branchModalContent .btn-outline:hover {
    background:#DDE8D6 !important;
    color:var(--ink) !important;
}
#branchCreateModalSheet .btn-primary-custom,
#branchModalContent .btn-primary-custom {
    min-height:2.55rem;
    border-radius:.55rem !important;
    background:#344333 !important;
    border-color:#344333 !important;
    color:#fff !important;
    box-shadow:none !important;
}
#branchCreateModalSheet .btn-primary-custom:hover,
#branchModalContent .btn-primary-custom:hover {
    background:#2F3A2E !important;
    border-color:#2F3A2E !important;
}
.branch-detail-body { padding:1.25rem; background:var(--card); color:var(--ink); }
.branch-detail-header { display:flex; align-items:flex-start; justify-content:space-between; gap:1.5rem; padding:1.1rem 1.25rem; background:var(--surface-muted); border-bottom:1px solid var(--border); }
.branch-detail-header__heading { display:flex; align-items:flex-start; gap:.75rem; min-width:0; }
.branch-detail-header__copy { min-width:0; }
.branch-detail-header__icon { display:grid; place-items:center; flex:none; width:2.5rem; height:2.5rem; border-radius:.7rem; background:#1b4332; color:#fff; font-size:1.1rem; }
.branch-detail-header [data-branch-close] { flex:none; width:2.75rem; height:2.75rem; padding:0; display:grid; place-items:center; }
.branch-detail-header h2 { margin:0; font-size:1.3rem; color:var(--ink); }
.branch-detail-header p { margin:.2rem 0 0; color:var(--ink-muted); }
.branch-detail-fields { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
.branch-detail-fields > div { border-bottom:1px solid var(--border); padding:.55rem 0; min-width:0; }
.branch-detail-fields dt { font-size:.76rem; color:var(--ink-muted); text-transform:uppercase; letter-spacing:.04em; }
.branch-detail-fields dd { margin:.25rem 0 0; color:var(--ink); overflow-wrap:anywhere; }
.branch-detail-actions { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.25rem; border-top:1px solid var(--border); background:var(--card); }
.branch-detail-actions-group { display:flex; flex-wrap:wrap; align-items:center; gap:.65rem; }
.branch-detail-actions :is(button,a) { min-height:2.65rem; display:inline-flex; align-items:center; justify-content:center; gap:.4rem; padding:.55rem .9rem; border:1px solid var(--border); border-radius:.6rem; color:var(--ink); background:var(--surface-muted); text-decoration:none; }
.branch-detail-actions .branch-detail-danger { color:var(--danger,#a43f3f); border-color:currentColor; }
.branch-detail-actions .branch-detail-primary { background:var(--brand-mid,#284d3d); color:#fff; }
.branch-detail-actions :is(button,a):focus-visible, .branch-detail-header [data-branch-close]:focus-visible { outline:2px solid var(--brand-mid,#284d3d); outline-offset:3px; }
.branch-detail-notice { border-left:3px solid var(--danger,#a43f3f); padding:.75rem 1rem; background:var(--surface-muted); color:var(--ink); }
.branch-detail-notice p { margin:.3rem 0 0; }
.branch-overview-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
.branch-overview-panel { min-width:0; padding:1rem; border:1px solid var(--border); border-radius:.75rem; background:var(--surface-muted); }
.branch-overview-panel h3, .branch-overview-cases h3 { margin:0 0 .75rem; color:var(--ink); font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
.branch-overview-panel h3 i { margin-right:.4rem; }
.branch-overview-panel p { margin:0; color:var(--ink); font-size:.875rem; overflow-wrap:anywhere; }
.branch-overview-panel .branch-main-note { margin-top:.7rem; color:var(--ink-muted); font-size:.75rem; }
.branch-overview-cases { margin-top:1.25rem; }
.branch-overview-outstanding { margin:0 0 .8rem; color:var(--ink-muted); font-size:.82rem; }
.branch-overview-outstanding strong { color:var(--ink); font-family:'JetBrains Mono',Consolas,monospace; }
.branch-overview-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:.75rem; }
.branch-overview-metrics > div { display:flex; flex-direction:column; gap:.25rem; align-items:center; padding:.85rem; border:1px solid var(--border); border-radius:.7rem; background:var(--surface-muted); }
.branch-overview-metrics span { color:var(--ink-muted); font-size:.75rem; text-transform:uppercase; }
.branch-overview-metrics strong { color:var(--ink); font-size:1.2rem; font-family:'JetBrains Mono',Consolas,monospace; }
@media(max-width:600px) { .branch-detail-fields { grid-template-columns:1fr; } .branch-detail-actions { align-items:stretch; } .branch-detail-actions-group { width:100%; } .branch-detail-actions-group :is(button,a) { flex:1; } }
@media(max-width:600px) { .branch-overview-grid { grid-template-columns:minmax(0,1fr); } .branch-overview-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); } }

/* Branch directory adopts the staff page foundation; semantic status colors stay intact. */
html[data-theme] body.system-admin-ui .branch-management-page {
    --bm-canvas:#DCE8E0;
    --bm-card:#F4F8F5;
    --bm-soft:#D0E2D6;
    --bm-hover:#C2DAC9;
    --bm-border:#AEC7B7;
    --bm-ink:#081D13;
    --bm-muted:#1B3E2E;
    --bm-action:#1B4332;
    min-height:100%;
    background:var(--bm-canvas);
    color:var(--bm-ink);
    font-family:var(--font-body);
}
html[data-theme='dark'] body.system-admin-ui .branch-management-page {
    --bm-canvas:#07130E;
    --bm-card:#112218;
    --bm-soft:#14261D;
    --bm-hover:#1B3527;
    --bm-border:#29523D;
    --bm-ink:#F8FAFC;
    --bm-muted:#94A3B8;
    --bm-action:#34D399;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-strip {
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:1rem;
    padding:0;
    margin:0 0 1.25rem;
    background:transparent;
    border:0;
    border-radius:0;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card {
    background:var(--bm-card);
    border:1px solid var(--bm-border);
    border-radius:.85rem;
    box-shadow:0 2px 5px rgb(8 29 19 / 4%) !important;
}
html[data-theme] body.panel-shell-body.system-admin-ui .branch-management-page .branch-kpi-card { background:var(--bm-card) !important; border-color:var(--bm-border) !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card:hover,
html[data-theme] body.system-admin-ui .branch-management-page .directory-item-card:hover {
    background:var(--bm-hover) !important;
    border-color:var(--bm-border) !important;
}
html[data-theme] body.panel-shell-body.system-admin-ui .branch-management-page :is(.branch-kpi-card:hover,.directory-item-card:hover) { background:var(--bm-hover) !important; border-color:var(--bm-border) !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__inner { align-items:flex-start; gap:.85rem; padding:1.15rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__body { min-width:0; flex:1; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__icon {
    width:2.5rem; height:2.5rem; border:1px solid var(--bm-border); border-radius:.6rem;
    background:var(--bm-soft); color:var(--bm-action); font-size:1rem;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__label {
    color:var(--bm-muted); font-size:.73rem; white-space:normal;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__value {
    margin-top:.3rem; color:var(--bm-ink); font-size:clamp(1.2rem,1.6vw,1.5rem); font-weight:650;
    line-height:1.25; overflow-wrap:anywhere;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__desc {
    margin-top:.35rem; color:var(--bm-muted); font-size:.76rem; font-weight:400;
    white-space:normal; overflow:visible;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__action {
    align-self:end; color:var(--bm-action); opacity:1; font-size:.7rem;
}
html[data-theme] body.system-admin-ui .branch-management-page .table-system-head {
    order:0; margin-bottom:.75rem; padding:0; border:0 !important;
    border-radius:0 !important; background:transparent !important;
}
html[data-theme] body.system-admin-ui .branch-management-page .admin-table-head-row { justify-content:flex-end; }
html[data-theme] body.system-admin-ui .branch-management-page .table-system-title {
    color:var(--bm-ink); font-family:var(--font-heading); font-size:1.15rem; font-weight:700;
}
html[data-theme] body.system-admin-ui .branch-management-page .admin-table-head-copy {
    color:var(--bm-muted); font-size:.8rem; font-weight:400;
}
html[data-theme] body.system-admin-ui .branch-management-page .admin-table-head-actions { width:auto; }
html[data-theme] body.system-admin-ui .branch-management-page .table-system-toolbar {
    order:1; margin-bottom:1rem; padding:1rem; border:1px solid var(--bm-border) !important;
    border-radius:.85rem !important; background:var(--bm-card) !important;
    display:flex; align-items:center; gap:1rem;
}
html[data-theme] body.system-admin-ui .branch-management-page .table-system-toolbar .table-toolbar { flex:1; min-width:0; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-view-toggle { flex:none; min-height:2.75rem; background:var(--bm-soft) !important; border-color:var(--bm-border) !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-view-toggle button { position:relative; width:2.75rem; min-height:2.75rem; padding:0; justify-content:center; color:var(--bm-ink); }
html[data-theme] body.system-admin-ui .branch-management-page .branch-view-toggle button::after { content:attr(aria-label); position:absolute; top:calc(100% + .5rem); right:0; z-index:30; width:max-content; padding:.35rem .55rem; border-radius:.4rem; background:var(--ink); color:var(--card); font-size:.75rem; line-height:1.4; pointer-events:none; opacity:0; visibility:hidden; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-view-toggle button:is(:hover,:focus-visible)::after { opacity:1; visibility:visible; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-view-toggle button:focus-visible { outline:2px solid var(--bm-action); outline-offset:-3px; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-view-toggle button:hover { background:var(--bm-hover); }
html[data-theme] body.system-admin-ui .branch-management-page .branch-view-toggle .bg-slate-900 { background:var(--bm-card) !important; color:var(--bm-action) !important; }
html[data-theme] body.panel-shell-body.system-admin-ui .branch-management-page .table-system-toolbar { background:var(--bm-card) !important; border-color:var(--bm-border) !important; }
html[data-theme] body.system-admin-ui .branch-management-page .table-toolbar { gap:.75rem !important; }
html[data-theme] body.system-admin-ui .branch-management-page .table-toolbar-field:first-child { flex:2 1 18rem; min-width:0; }
html[data-theme] body.system-admin-ui .branch-management-page .table-toolbar-field:not(:first-child) { flex:1 1 12rem; min-width:0; }
html[data-theme] body.system-admin-ui .branch-management-page :is(.table-toolbar-search,.table-toolbar-select,.table-toolbar-sort) {
    min-height:2.75rem !important; height:2.75rem !important; border:1px solid var(--bm-border) !important;
    border-radius:.5rem !important; background:var(--bm-soft) !important; color:var(--bm-ink) !important;
    font-size:.875rem !important; font-weight:600 !important;
}
html[data-theme] body.system-admin-ui .branch-management-page :is(.table-toolbar-search,.table-toolbar-select,.table-toolbar-sort):hover {
    background:var(--bm-hover) !important;
}
html[data-theme] body.system-admin-ui .branch-management-page :is(.table-toolbar-search,.table-toolbar-select,.table-toolbar-sort):focus-visible {
    outline:2px solid var(--bm-action); outline-offset:2px;
}
html[data-theme] body.system-admin-ui .branch-management-page :is(.table-toolbar-leading-icon,.table-toolbar-select-icon) { color:var(--bm-muted); }
html[data-theme] body.system-admin-ui .branch-management-page .admin-table-head-actions > .inline-flex {
    min-height:2.75rem; background:var(--bm-soft) !important; border-color:var(--bm-border) !important;
}
html[data-theme] body.system-admin-ui .branch-management-page .admin-table-head-actions > .inline-flex button {
    min-height:2.75rem; color:var(--bm-ink);
}
html[data-theme] body.system-admin-ui .branch-management-page .admin-table-head-actions > .inline-flex button:hover {
    background:var(--bm-hover) !important; color:var(--bm-ink) !important;
}
html[data-theme] body.system-admin-ui .branch-management-page .admin-table-head-actions > .inline-flex .bg-slate-900 {
    background:var(--bm-action) !important; color:#fff !important;
}
html[data-theme='dark'] body.system-admin-ui .branch-management-page .admin-table-head-actions > .inline-flex .bg-slate-900 {
    background:#1B4332 !important;
}
html[data-theme] body.system-admin-ui .branch-management-page :is(.branch-directory-card-view,.table-system-list) {
    padding:0 !important; background:transparent !important; border:0 !important; border-radius:0 !important;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-directory-card-view > .grid { gap:1rem; }
html[data-theme] body.system-admin-ui .branch-management-page .directory-item-card {
    min-height:13.5rem; background:var(--bm-card) !important; border:1px solid var(--bm-border) !important;
    border-radius:.85rem !important; box-shadow:0 2px 5px rgb(8 29 19 / 4%) !important;
}
html[data-theme] body.system-admin-ui .branch-management-page .directory-item-card[data-highlighted="true"] {
    box-shadow:inset 0 0 0 3px var(--bm-action), 0 2px 5px rgb(8 29 19 / 4%) !important;
}
html[data-theme] body.panel-shell-body.system-admin-ui .branch-management-page .directory-item-card { background:var(--bm-card) !important; border-color:var(--bm-border) !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-identity,
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-metric {
    background:transparent !important; border-color:var(--bm-border) !important;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-identity { text-align:left; padding:1.25rem 4.25rem 1rem 1.25rem !important; border-bottom:0; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-code-pill { display:inline-flex; width:auto; padding:.25rem .5rem; background:var(--bm-soft); border:1px solid var(--bm-border); border-radius:.4rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-main-pill { padding:.25rem .5rem; border-radius:.4rem; background:#f5e9c9; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-address { justify-content:flex-start; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-metric { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.65rem; padding:1rem 1.25rem !important; margin-top:auto; text-align:center; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-metric > div { min-width:0; padding:.7rem; background:var(--bm-soft); border-radius:.65rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-metric-value { font-size:1.15rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-status,
.branch-detail-status { display:inline-flex; align-items:center; padding:.22rem .55rem; border:1px solid; border-radius:999px; font-size:.7rem; font-weight:700; line-height:1.3; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-status.is-active,
.branch-detail-status.is-active { background:#d9efe2; border-color:#9bc8ab; color:#155c36; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-status.is-inactive,
.branch-detail-status.is-inactive { background:#f8e3e3; border-color:#e2b0b0; color:#9c3333; }
html[data-theme='dark'] body.system-admin-ui .branch-management-page .branch-card-status.is-active,
html[data-theme='dark'] .branch-detail-status.is-active { background:#123c2b; border-color:#287b56; color:#a7ebc4; }
html[data-theme='dark'] body.system-admin-ui .branch-management-page .branch-card-status.is-inactive,
html[data-theme='dark'] .branch-detail-status.is-inactive { background:#4a2024; border-color:#874047; color:#ffd0d0; }
html[data-theme] body.system-admin-ui .branch-management-page .directory-item-card:hover :is(.branch-card-identity,.branch-card-metric) { background:transparent !important; }
html[data-theme] body.system-admin-ui .branch-management-page :is(.branch-code-pill,.branch-card-name,.branch-card-metric-value) { color:var(--bm-ink) !important; }
html[data-theme] body.system-admin-ui .branch-management-page :is(.branch-card-address,.branch-card-metric-label) { color:var(--bm-muted) !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-code-pill { font-family:'JetBrains Mono',Consolas,monospace; font-size:.76rem; letter-spacing:.02em; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-name { font-weight:650; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-metric-value { font-size:1.55rem; font-weight:650; }
html[data-theme] body.system-admin-ui .branch-management-page .row-action-trigger {
    background:var(--bm-soft) !important; border-color:var(--bm-border) !important; color:var(--bm-action) !important;
    border-radius:.55rem !important;
}
html[data-theme] body.system-admin-ui .branch-management-page .row-action-trigger:hover { background:var(--bm-hover) !important; }
html[data-theme] body.system-admin-ui .branch-management-page .row-action-trigger:focus-visible,
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card:focus-visible {
    outline:2px solid var(--bm-action); outline-offset:3px;
}
html[data-theme] body.system-admin-ui .branch-management-page .table-system-wrap {
    border:1px solid var(--bm-border); border-radius:.85rem; background:var(--bm-card);
}
html[data-theme] body.system-admin-ui .branch-management-page .table-system-table thead th {
    background:var(--bm-soft) !important; color:var(--bm-muted) !important; font-weight:650;
}
html[data-theme] body.system-admin-ui .branch-management-page .table-system-table :is(th,td).table-col-number { text-align:right; font-variant-numeric:tabular-nums; }
html[data-theme] body.system-admin-ui .branch-management-page .table-system-table tbody td { color:var(--bm-ink) !important; border-color:var(--bm-border); }
html[data-theme] body.system-admin-ui .branch-management-page .table-system-table tbody tr:hover { background:var(--bm-hover) !important; }
html[data-theme] body.system-admin-ui .branch-management-page .table-system-table tbody tr[data-highlighted="true"] td {
    background:var(--bm-soft) !important;
}
html[data-theme] body.system-admin-ui .branch-management-page .table-system-table tbody tr[data-highlighted="true"] td:first-child {
    box-shadow:inset 5px 0 0 var(--bm-action);
}
html[data-theme] body.system-admin-ui .branch-management-page .table-system-table .table-col-actions {
    width:8.5rem; min-width:8.5rem; text-align:right;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-table-actions {
    display:flex; align-items:center; justify-content:flex-end; gap:.15rem; white-space:nowrap;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-table-actions :is(.row-action-trigger,.branch-reference-icon) {
    display:grid; place-items:center; flex:none; width:2.5rem !important; min-width:2.5rem; height:2.5rem; min-height:2.5rem;
    padding:0; border:0 !important; border-radius:.45rem !important; background:transparent !important;
    box-shadow:none !important; font-size:1rem;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-table-actions :is(.row-action-trigger,.branch-reference-icon):hover {
    background:transparent !important; color:var(--bm-action) !important;
}
html[data-theme] body.system-admin-ui .branch-management-page .branch-table-actions .is-danger { color:#b91c1c !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-table-actions .is-enable { color:#047857 !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-table-actions .is-protected { opacity:.4; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-table-actions :is(.row-action-trigger,.branch-reference-icon):focus-visible {
    outline:2px solid var(--bm-action); outline-offset:1px;
}
html[data-theme] body.system-admin-ui .branch-management-page .table-secondary { color:var(--bm-muted) !important; font-weight:400; }
html[data-theme] body.system-admin-ui .branch-management-page .table-system-pagination { margin-top:1rem; background:transparent; border:0; }
@media(max-width:700px) {
    html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-strip { grid-template-columns:repeat(2,minmax(0,1fr)); }
    html[data-theme] body.system-admin-ui .branch-management-page .admin-table-head-row { align-items:flex-start; }
    html[data-theme] body.system-admin-ui .branch-management-page .table-toolbar-field { flex:1 1 100% !important; }
    html[data-theme] body.system-admin-ui .branch-management-page .table-system-toolbar { flex-wrap:wrap; }
    html[data-theme] body.system-admin-ui .branch-management-page .branch-view-toggle { margin-left:auto; }
}
@media(max-width:440px) {
    html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-strip { grid-template-columns:minmax(0,1fr); }
}
/* Branch directory composition follows the supplied BranchManagementView reference. */
html[data-theme] body.system-admin-ui:has(.branch-management-page) .panel-page-header {
    width:100% !important; margin:0; padding:1rem var(--panel-content-inline) 0; background:transparent !important;
    border:0 !important; border-radius:0 !important; box-shadow:none !important;
}
html[data-theme] body.system-admin-ui:has(.branch-management-page) .panel-page-header__leading { display:flex; align-items:center; gap:.85rem; }
html[data-theme] body.system-admin-ui:has(.branch-management-page) .branch-header-icon { display:grid; place-items:center; flex:none; width:3rem; height:3rem; border-radius:.8rem; background:transparent; color:var(--ink-muted); font-size:1.35rem; }
html[data-theme] body.system-admin-ui:has(.branch-management-page) .panel-page-header__copy h1 { font-size:1.9rem; line-height:1.2; }
html[data-theme] body.system-admin-ui:has(.branch-management-page) .panel-page-header__copy p { font-size:.8rem; }
html[data-theme] body.system-admin-ui:has(.branch-management-page) .panel-page-header__actions .btn-primary-custom { min-height:2.65rem; padding:.55rem 1.15rem; border-radius:.7rem; background:#1b4332 !important; border-color:#1b4332 !important; color:#fff !important; }
html[data-theme] body.system-admin-ui .branch-management-page { padding-inline:var(--panel-content-inline); }
html[data-theme] body.system-admin-ui .branch-management-page > .mx-auto { max-width:none; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-strip { grid-template-columns:repeat(2,minmax(0,1fr)); gap:1.1rem; margin-bottom:1.5rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card { min-height:8.3rem; background:#fff !important; }
html[data-theme] body.panel-shell-body.system-admin-ui .branch-management-page .branch-kpi-card { background:#fff !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__inner { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1.35rem 1.5rem; height:100%; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__body { display:block; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__label { display:flex; align-items:center; gap:.4rem; color:var(--ink) !important; font-size:.76rem; font-weight:700; }
.branch-kpi-dot { width:.5rem; height:.5rem; border-radius:50%; background:#68d0ad; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__value { display:block; margin:.35rem 0 .15rem; color:var(--ink) !important; font-size:2rem; font-weight:800; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__value small { font-size:.82rem; color:var(--ink-muted); font-weight:600; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__desc { display:inline; margin:0; color:var(--ink-muted); font-size:.74rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__action { display:inline-flex; margin-left:.45rem; color:var(--bm-action); font-size:.74rem; font-weight:700; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card__icon { width:3rem; height:3rem; border:0; border-radius:.85rem; background:#ecf7f1; color:#0f8a5b; font-size:1.35rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-kpi-card:nth-child(2) .branch-kpi-card__icon { background:#eef1ff; color:#4338ca; }
html[data-theme='dark'] body.system-admin-ui .branch-management-page .branch-kpi-card__icon { background:#123c2b; color:#75dbab; }
html[data-theme='dark'] body.system-admin-ui .branch-management-page .branch-kpi-card:nth-child(2) .branch-kpi-card__icon { background:#252c57; color:#b6baff; }
html[data-theme] body.system-admin-ui .branch-management-page .table-system-toolbar { padding:1rem 1.1rem; margin-bottom:1.5rem; background:#fff !important; }
html[data-theme] body.panel-shell-body.system-admin-ui .branch-management-page .table-system-toolbar { background:#fff !important; }
html[data-theme] body.system-admin-ui .branch-management-page .table-system-toolbar .table-toolbar { display:flex !important; align-items:center; gap:.7rem !important; }
html[data-theme] body.system-admin-ui .branch-management-page .table-toolbar-field:first-of-type { flex:1 1 22rem; max-width:34rem; }
html[data-theme] body.system-admin-ui .branch-management-page .table-toolbar-field:not(:first-of-type) { flex:0 1 13rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-status-segments { display:flex; align-items:center; flex:none; gap:.1rem; margin-left:auto; padding:.25rem; border:1px solid var(--border); border-radius:.7rem; background:var(--surface-muted); }
html[data-theme] body.system-admin-ui .branch-management-page .branch-status-segments button { min-height:2.1rem; padding:.35rem .75rem; border-radius:.5rem; color:var(--ink-muted); font-size:.76rem; font-weight:600; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-status-segments button.is-selected { background:var(--card); color:var(--ink); box-shadow:0 1px 3px rgb(8 29 19 / 10%); }
html[data-theme] body.system-admin-ui .branch-management-page .branch-status-segments button:focus-visible { outline:2px solid var(--bm-action); outline-offset:2px; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-view-toggle { margin-left:0; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-directory-card-view > .grid { grid-template-columns:repeat(3,minmax(0,1fr)) !important; gap:1.3rem; }
html[data-theme] body.system-admin-ui .branch-management-page .directory-item-card { min-height:20rem; background:#fff !important; border-radius:1rem !important; }
html[data-theme] body.panel-shell-body.system-admin-ui .branch-management-page .directory-item-card { background:#fff !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-identity { padding:1.2rem 1.25rem .8rem !important; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-topline { display:flex; align-items:flex-start; justify-content:space-between; gap:.5rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-main-pill { border:1px solid #f0c951; color:#8b5300 !important; background:#fff4cf; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-name { margin-top:1rem; font-size:1rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-address { margin-top:.65rem; font-size:.8rem; font-weight:400; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-metric { margin:0 1.25rem; padding:.8rem 0 !important; border-top:1px solid var(--bm-border); gap:.55rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-metric > div { background:#f7f9fb; padding:.6rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-metric-label { color:var(--ink-muted) !important; font-size:.65rem; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-card-metric-value { color:var(--ink) !important; font-size:1.05rem; font-weight:700; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-reference-actions { display:flex; align-items:center; gap:.55rem; margin:0 1.25rem; padding:.85rem 0; border-top:1px solid var(--bm-border); }
html[data-theme] body.system-admin-ui .branch-management-page .branch-reference-actions .branch-card-view-button { position:static; display:inline-flex; flex:1; width:auto !important; min-width:0; height:2.3rem; gap:.45rem; background:#f1f4f8 !important; color:var(--ink) !important; font-size:.76rem; font-weight:700; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-reference-icon { display:grid; place-items:center; flex:none; width:2.2rem; height:2.2rem; border-radius:.55rem; color:var(--ink-muted); }
html[data-theme] body.system-admin-ui .branch-management-page .branch-reference-icon:hover { background:var(--surface-muted); }
html[data-theme] body.system-admin-ui .branch-management-page .branch-reference-icon.is-danger { color:#be123c; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-reference-icon.is-enable { color:#0d9561; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-reference-icon.is-protected { opacity:.4; cursor:not-allowed; }
html[data-theme] body.system-admin-ui .branch-management-page .branch-reference-icon:focus-visible { outline:2px solid var(--bm-action); outline-offset:2px; }
html[data-theme='dark'] body.system-admin-ui .branch-management-page .branch-kpi-card,
html[data-theme='dark'] body.system-admin-ui .branch-management-page .table-system-toolbar,
html[data-theme='dark'] body.system-admin-ui .branch-management-page .directory-item-card { background:#112218 !important; }
html[data-theme='dark'] body.panel-shell-body.system-admin-ui .branch-management-page .branch-kpi-card,
html[data-theme='dark'] body.panel-shell-body.system-admin-ui .branch-management-page .table-system-toolbar { background:#112218 !important; }
html[data-theme='dark'] body.panel-shell-body.system-admin-ui .branch-management-page .directory-item-card { background:#112218 !important; }
html[data-theme='dark'] body.system-admin-ui .branch-management-page .branch-card-metric > div,
html[data-theme='dark'] body.system-admin-ui .branch-management-page .branch-reference-actions .branch-card-view-button { background:var(--surface-muted) !important; }
@media(max-width:1200px) { html[data-theme] body.system-admin-ui .branch-management-page .branch-directory-card-view > .grid { grid-template-columns:repeat(2,minmax(0,1fr)) !important; } }
@media(max-width:750px) { html[data-theme] body.system-admin-ui .branch-management-page .branch-directory-card-view > .grid { grid-template-columns:minmax(0,1fr) !important; } }
@media(max-width:1100px) { html[data-theme] body.system-admin-ui .branch-management-page .table-system-toolbar .table-toolbar { flex-wrap:wrap; } html[data-theme] body.system-admin-ui .branch-management-page .table-toolbar-field:first-of-type { max-width:none; } }
@media(max-width:700px) { html[data-theme] body.system-admin-ui:has(.branch-management-page) .panel-page-header { margin:0; padding:.75rem var(--panel-content-inline) 0; } html[data-theme] body.system-admin-ui .branch-management-page .table-toolbar-field:first-of-type, html[data-theme] body.system-admin-ui .branch-management-page .table-toolbar-field:not(:first-of-type) { flex:1 1 100%; max-width:none; } html[data-theme] body.system-admin-ui .branch-management-page .branch-status-segments { width:100%; } html[data-theme] body.system-admin-ui .branch-management-page .branch-status-segments button { flex:1; } }
/* Compact Add Branch form, scoped so the existing branch-details modal is unchanged. */
#branchCreateModalSheet { width:min(640px,calc(100vw - 32px)); max-height:calc(100dvh - 32px); border-radius:1rem !important; }
#branchCreateModalSheet .branch-create-scroll { max-height:calc(100dvh - 34px); overflow-y:auto; }
#branchCreateModalSheet .branch-create-header.px-6.py-5 { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:1.25rem 1.5rem !important; }
#branchCreateModalSheet .branch-create-heading { display:flex; align-items:flex-start; gap:.75rem; min-width:0; }
#branchCreateModalSheet .branch-create-heading-icon { display:grid; place-items:center; flex:none; width:2.5rem; height:2.5rem; border-radius:.7rem; background:#1b4332; color:#fff; font-size:1.1rem; }
#branchCreateModalSheet .branch-create-heading h2 { margin:0; font-size:1.15rem !important; line-height:1.3; }
#branchCreateModalSheet .branch-create-heading p { margin:.2rem 0 0; font-size:.8rem; line-height:1.45; font-weight:400; }
#branchCreateModalSheet .branch-create-body { display:grid; gap:1rem; padding:1.5rem !important; }
#branchCreateModalSheet .branch-create-fields { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,2fr); gap:1rem !important; }
#branchCreateModalSheet .branch-create-field { min-width:0; }
#branchCreateModalSheet .branch-create-field--wide { grid-column:1/-1; }
#branchCreateModalSheet .branch-create-field .label-section { display:block; margin:0 0 .45rem; color:var(--ink) !important; font-size:.75rem !important; }
#branchCreateModalSheet .branch-create-field .form-input { width:100%; min-height:2.85rem; padding:.65rem .85rem; background:#fff !important; border-color:var(--border) !important; font-weight:500; }
#branchCreateModalSheet .branch-create-field .form-input:focus { border-color:#1b4332 !important; background:#fff !important; outline:2px solid rgb(27 67 50 / 18%); outline-offset:1px; }
#branchCreateModalSheet .branch-create-code { font-family:'JetBrains Mono',Consolas,monospace; background:var(--surface-muted) !important; }
#branchCreateModalSheet .branch-create-help { margin:.4rem 0 0; color:var(--ink-muted); font-size:.72rem; line-height:1.45; font-weight:400; }
#branchCreateModalSheet .branch-create-status { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.1rem; border:1px solid var(--border); border-radius:.75rem; background:var(--surface-muted); }
#branchCreateModalSheet .branch-create-status-title { display:block; color:var(--ink); font-size:.85rem; font-weight:700; }
#branchCreateModalSheet .branch-create-status p { margin:.25rem 0 0; font-size:.75rem; line-height:1.45; font-weight:400; }
#branchCreateModalSheet .branch-create-status-control { display:flex; align-items:center; gap:.7rem; flex:none; }
#branchCreateModalSheet .branch-create-status-pill { padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700; }
#branchCreateModalSheet .branch-create-status-pill.is-active { background:#d8f7e7; color:#07613f; }
#branchCreateModalSheet .branch-create-status-pill.is-inactive { background:#fde7e7; color:#9f3030; }
#branchCreateModalSheet .branch-create-footer { display:flex; align-items:center; justify-content:flex-end; gap:.6rem; padding:1rem 1.5rem; background:var(--card); border-top:1px solid var(--border); }
#branchCreateModalSheet .branch-create-footer button { min-width:6.5rem; min-height:2.7rem; }
#branchCreateModalSheet :is(button,input):focus-visible { outline:2px solid #1b4332; outline-offset:2px; }
#branchCreateModalSheet .branch-create-status-control label:has(input:focus-visible) { outline:2px solid #1b4332; outline-offset:3px; border-radius:999px; }
html[data-theme='dark'] #branchCreateModalSheet,
html[data-theme='dark'] #branchCreateModalSheet :is(.branch-create-scroll,.branch-create-panel,.branch-create-body,.branch-create-footer) { background:#112218 !important; }
html[data-theme='dark'] #branchCreateModalSheet .branch-create-header.px-6.py-5 { background:#1b3527 !important; border-color:#29523d !important; }
html[data-theme='dark'] #branchCreateModalSheet .branch-create-status { background:#14261d; border-color:#29523d; }
html[data-theme='dark'] #branchCreateModalSheet .branch-create-field .form-input { background:#14261d !important; color:#f8fafc !important; border-color:#41634f !important; }
html[data-theme='dark'] #branchCreateModalSheet .branch-create-code { background:#1b3527 !important; }
html[data-theme='dark'] #branchCreateModalSheet .branch-create-status-pill.is-active { background:#123c2b; color:#a7ebc4; }
html[data-theme='dark'] #branchCreateModalSheet .branch-create-status-pill.is-inactive { background:#432128; color:#fecaca; }
@media(max-width:560px) { #branchCreateModalSheet { width:calc(100vw - 20px); } #branchCreateModalSheet .branch-create-header,#branchCreateModalSheet .branch-create-body,#branchCreateModalSheet .branch-create-footer { padding:1rem !important; } #branchCreateModalSheet .branch-create-fields { grid-template-columns:minmax(0,1fr); } #branchCreateModalSheet .branch-create-status { align-items:flex-start; flex-direction:column; } #branchCreateModalSheet .branch-create-status-control { justify-content:space-between; width:100%; } #branchCreateModalSheet .branch-create-footer button { flex:1; } }
</style>
@include('admin.branches._edit_form_styles')
<style>
/* The edit form supplies its own frame; keep the shared shell for branch details/status. */
#branchModalSheet.branch-edit-mode {
    width:min(640px, calc(100vw - 2rem));
    max-width:none;
    border:0 !important;
    background:transparent !important;
    box-shadow:none !important;
}
#branchModalSheet.branch-edit-mode #branchModalContent {
    background:transparent !important;
    max-height:92vh;
}
#branchModalSheet.branch-edit-mode .branch-edit-form {
    max-width:none !important;
}
</style>
<div class="admin-table-page directory-page admin-catalog-page branch-management-page" x-data="branchCatalog()">
<div class="mx-auto w-full px-4 sm:px-6 lg:px-8 py-4">
<div class="space-y-4">

@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        {{ $errors->first() }}
    </div>
@endif

<div class="management-toast no-print" role="status" aria-live="polite" data-page-context-toast>
    <i class="bi bi-building"></i>
    <span>You are viewing Branch Management.</span>
</div>

@php
    $highlightBranchId = request('highlight_branch', request('branch_id'));
@endphp

{{-- KPI insights --}}
<div class="branch-kpi-strip">
    @foreach(['activeCoverage', 'monthlyActivity'] as $kpiKey)
        @php
            $kpi = $branchKpis[$kpiKey];
        @endphp
        <a href="{{ $kpi['href'] }}" class="branch-kpi-card" title="{{ $kpi['label'] }}: {{ $kpi['value'] }}">
            <div class="branch-kpi-card__inner">
                <div class="branch-kpi-card__body">
                    <span class="branch-kpi-card__label">{{ $kpi['label'] }} @if($kpiKey === 'activeCoverage')<span class="branch-kpi-dot" aria-hidden="true"></span>@endif</span>
                    <span class="branch-kpi-card__value">{{ $kpiKey === 'activeCoverage' ? number_format($activeBranches) : $kpi['value'] }}
                        @if($kpiKey === 'activeCoverage')<small>/ {{ number_format($totalBranches) }} total</small>@endif
                    </span>
                    <span class="branch-kpi-card__desc">{{ $kpiKey === 'activeCoverage' ? ($activeBranches === $totalBranches ? 'All branches are active' : number_format($totalBranches - $activeBranches).' inactive branches') : 'Cases recorded this month' }}</span>
                    @if($kpiKey === 'monthlyActivity')<span class="branch-kpi-card__action">View cases <i class="bi bi-arrow-right-short" aria-hidden="true"></i></span>@endif
                </div>
                <span class="branch-kpi-card__icon"><i class="bi {{ $kpiKey === 'monthlyActivity' ? 'bi-file-earmark-text' : 'bi-building' }}" aria-hidden="true"></i></span>
            </div>
        </a>
    @endforeach
</div>

{{-- Main section card --}}
<section class="table-system-card admin-table-card">

    {{-- Toolbar --}}
    <div class="table-system-toolbar">
        <form
            method="GET"
            action="{{ route('admin.branches.index') }}"
            class="table-toolbar uniform-record-filters"
            data-table-toolbar
            data-live-search-suggestions
            data-live-search-commit-only
            data-search-debounce="400"
            style="grid-template-columns: minmax(260px, 2.2fr) repeat(2, minmax(150px, 1fr)) auto;"
        >
            @if($highlightBranchId)
                <input type="hidden" name="highlight_branch" value="{{ $highlightBranchId }}">
            @endif
            <input type="hidden" name="status" value="{{ request('status', '') }}">
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Search</label>
                <div class="table-toolbar-input-wrap">
                    <i class="bi bi-search table-toolbar-leading-icon" aria-hidden="true"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search by code, name, or address..."
                        class="form-input table-toolbar-search has-clear-action"
                        data-table-search
                        data-live-search-input
                        autocomplete="off"
                    >
                    <button type="button" class="live-search-clear" data-live-search-clear aria-label="Clear search" @if(!filled(request('q'))) hidden @endif>
                        <i class="bi bi-x"></i>
                    </button>
                    <div class="live-search-results" data-live-search-results hidden></div>
                </div>
            </div>
            <div class="branch-status-segments" role="group" aria-label="Filter branches by status">
                <button type="button" data-branch-status-filter="" class="{{ !in_array(request('status'), ['active', 'inactive'], true) ? 'is-selected' : '' }}" aria-pressed="{{ !in_array(request('status'), ['active', 'inactive'], true) ? 'true' : 'false' }}">All</button>
                <button type="button" data-branch-status-filter="active" class="{{ request('status') === 'active' ? 'is-selected' : '' }}" aria-pressed="{{ request('status') === 'active' ? 'true' : 'false' }}">Active</button>
                <button type="button" data-branch-status-filter="inactive" class="{{ request('status') === 'inactive' ? 'is-selected' : '' }}" aria-pressed="{{ request('status') === 'inactive' ? 'true' : 'false' }}">Inactive</button>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Sort</label>
                <div class="table-toolbar-select-wrap">
                    <i class="bi bi-sort-alpha-down table-toolbar-leading-icon" aria-hidden="true"></i>
                    <select name="sort" class="form-select table-toolbar-sort" data-table-sort>
                        <option value="code_asc"     {{ request('sort', 'code_asc') === 'code_asc'    ? 'selected' : '' }}>Branch ID</option>
                        <option value="name_asc"     {{ request('sort') === 'name_asc'                 ? 'selected' : '' }}>Branch Name</option>
                        <option value="records_desc" {{ request('sort') === 'records_desc'             ? 'selected' : '' }}>Total Records</option>
                        <option value="records_asc"  {{ request('sort') === 'records_asc'              ? 'selected' : '' }}>Lowest Records</option>
                        <option value="revenue_desc" {{ request('sort') === 'revenue_desc'             ? 'selected' : '' }}>Highest Sales</option>
                        <option value="revenue_asc"  {{ request('sort') === 'revenue_asc'              ? 'selected' : '' }}>Lowest Sales</option>
                    </select>
                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                </div>
            </div>
            <div class="table-toolbar-reset-wrap">
            </div>
        </form>
        <div class="branch-view-toggle inline-flex items-center rounded-lg border border-slate-200 bg-white" role="group" aria-label="Branch display mode">
            <button type="button" @click="setView('card')" :class="view === 'card' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50'" :aria-pressed="view === 'card'" class="px-3 py-2 transition-colors flex items-center gap-1.5 font-medium rounded-l-lg" aria-label="Grid view">
                <i class="bi bi-grid-3x3-gap-fill" aria-hidden="true"></i>
            </button>
            <button type="button" @click="setView('table')" :class="view === 'table' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50'" :aria-pressed="view === 'table'" class="px-3 py-2 transition-colors flex items-center gap-1.5 font-medium border-l border-slate-200 rounded-r-lg" aria-label="Table view">
                <i class="bi bi-table" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         CARD VIEW
    ═══════════════════════════════════════════ --}}
    <div
        x-show="view === 'card'"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="branch-directory-card-view"
    >

        @if($branches->isEmpty())
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center py-16 gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                    <i class="bi bi-building text-2xl text-slate-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-sm">No branches found</h3>
                    <p class="text-xs text-slate-500 mt-1">Try changing the search or filters, or add a new branch.</p>
                </div>
                <button
                    type="button"
                    id="openBranchCreateModalEmpty"
                    class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] text-white inline-flex items-center gap-1.5 mt-1"
                >
                    <i class="bi bi-plus-circle"></i>
                    Add Branch
                </button>
            </div>

        @else
            {{-- Branch cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($branches as $branch)
                @php
                    $isHighlightedBranch = (string) $highlightBranchId === (string) $branch->id;
                @endphp
                <div
                    class="directory-item-card bg-white border border-slate-200 rounded-2xl transition-colors duration-200 flex flex-col"
                    data-highlighted="{{ $isHighlightedBranch ? 'true' : 'false' }}"
                    data-live-search-row
                    data-live-search-title="{{ $branch->branch_name }}"
                    data-live-search-meta="{{ $branch->branch_code }} / {{ $branch->address ?: 'No address' }}"
                    data-live-search-text="{{ $branch->branch_code }} {{ $branch->branch_name }} {{ $branch->address }} {{ $branch->is_active ? 'Active' : 'Inactive' }}"
                >

                    {{-- Branch identity and status --}}
                    <div class="branch-card-identity p-5">
                        <div class="min-w-0">
                            <div class="branch-card-topline">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="branch-code-pill">{{ $branch->branch_code }}</span>
                                    @if($branch->isMain())
                                        <span class="branch-main-pill">Main Branch</span>
                                    @endif
                                </div>
                                <span class="branch-card-status {{ $branch->is_active ? 'is-active' : 'is-inactive' }}">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                            <h3 class="branch-card-name">{{ $branch->branch_name }}</h3>
                            <p class="branch-card-address flex items-start gap-1">
                                <i class="bi bi-geo-alt text-[12px] mt-0.5 flex-shrink-0" aria-hidden="true"></i>
                                <span>{{ $branch->address ?: 'No address provided' }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="branch-card-metric px-5 py-4 border-t border-slate-100">
                        <div><p class="branch-card-metric-label">Total Cases</p><p class="branch-card-metric-value mt-1">{{ number_format($branch->funeral_cases_count) }}</p></div>
                        <div><p class="branch-card-metric-label">This Month</p><p class="branch-card-metric-value mt-1">{{ number_format($branch->current_month_cases_count) }}</p></div>
                    </div>

                    <div class="branch-reference-actions">
                        <button type="button" class="row-action-trigger open-branch-view branch-card-view-button" aria-label="View branch details for {{ $branch->branch_name }}" title="View branch details"
                            data-code="{{ $branch->branch_code }}" data-name="{{ $branch->branch_name }}" data-address="{{ $branch->address }}"
                            data-status="{{ $branch->is_active ? 'Active' : 'Inactive' }}" data-records="{{ $branch->funeral_cases_count }}" data-month-cases="{{ $branch->current_month_cases_count }}"
                            data-draft-cases="{{ $branch->draft_cases_count }}" data-active-cases="{{ $branch->active_cases_count }}" data-completed-cases="{{ $branch->completed_cases_count }}" data-outstanding="{{ $branch->outstanding_balance_sum ?? 0 }}"
                            data-cases-url="{{ route('admin.cases.index', ['branch_id' => $branch->id]) }}"
                            data-edit-url="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}"
                            data-check-url="{{ route('admin.branches.disableCheck', $branch) }}"
                            data-toggle-url="{{ route('admin.branches.toggleStatus', $branch) }}">
                            <i class="bi bi-eye" aria-hidden="true"></i><span>View Details</span>
                        </button>
                        <button type="button" class="branch-reference-icon" data-branch-card-action="edit" aria-label="Edit {{ $branch->branch_name }}" title="Edit branch"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                        <button type="button" class="branch-reference-icon {{ $branch->isMain() ? 'is-protected' : ($branch->is_active ? 'is-danger' : 'is-enable') }}" data-branch-card-action="status" @disabled($branch->isMain()) aria-label="{{ $branch->is_active ? 'Disable' : 'Enable' }} {{ $branch->branch_name }}" title="{{ $branch->isMain() ? 'Main branch cannot be disabled' : ($branch->is_active ? 'Disable branch' : 'Enable branch') }}"><i class="bi {{ $branch->is_active ? 'bi-slash-circle' : 'bi-check-circle' }}" aria-hidden="true"></i></button>
                    </div>

                </div>
                @endforeach
            </div>

            {{-- Card view pagination --}}
            <div class="table-system-pagination branch-card-pagination">
                @if($branches->hasPages()){{ $branches->links() }}@endif
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════
         TABLE VIEW
    ═══════════════════════════════════════════ --}}
    <div
        x-show="view === 'table'"
        x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="branch-directory-table-view"
>
        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table">
                    <thead>
                        <tr>
                            <th class="text-left">Branch ID</th>
                            <th class="text-left">Branch Name</th>
                            <th class="text-left">Address</th>
                            <th class="table-col-number">Total Records</th>
                            <th class="table-col-number">This Month</th>
                            <th class="text-left">Status</th>
                            <th class="table-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            @php
                                $isHighlightedBranch = (string) $highlightBranchId === (string) $branch->id;
                            @endphp
                            <tr
                                class="{{ $isHighlightedBranch ? 'bg-amber-50' : '' }}"
                                data-highlighted="{{ $isHighlightedBranch ? 'true' : 'false' }}"
                                data-live-search-row
                                data-live-search-title="{{ $branch->branch_name }}"
                                data-live-search-meta="{{ $branch->branch_code }} / {{ $branch->address ?: 'No address' }}"
                                data-live-search-text="{{ $branch->branch_code }} {{ $branch->branch_name }} {{ $branch->address }} {{ $branch->is_active ? 'Active' : 'Inactive' }}"
                            >
                                <td class="table-primary">
                                    {{ $branch->branch_code }}
                                    @if($branch->isMain())
                                        <span class="ml-1 inline-flex items-center rounded bg-amber-100 text-amber-700 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5">Main</span>
                                    @endif
                                </td>
                                <td>{{ $branch->branch_name }}</td>
                                <td class="table-secondary">{{ $branch->address ?? '—' }}</td>
                                <td class="table-col-number">{{ number_format($branch->funeral_cases_count) }}</td>
                                <td class="table-col-number">{{ number_format($branch->current_month_cases_count) }}</td>
                                <td>
                                    @if($branch->is_active)
                                        <span class="status-badge status-badge-success">Active</span>
                                    @else
                                        <span class="status-badge status-badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="table-col-actions">
                                    <div class="branch-table-actions">
                                    <button type="button" class="row-action-trigger open-branch-view" aria-label="View branch details for {{ $branch->branch_name }}" title="View branch details"
                                        data-code="{{ $branch->branch_code }}" data-name="{{ $branch->branch_name }}" data-address="{{ $branch->address }}"
                                        data-status="{{ $branch->is_active ? 'Active' : 'Inactive' }}" data-records="{{ $branch->funeral_cases_count }}" data-month-cases="{{ $branch->current_month_cases_count }}"
                                        data-draft-cases="{{ $branch->draft_cases_count }}" data-active-cases="{{ $branch->active_cases_count }}" data-completed-cases="{{ $branch->completed_cases_count }}" data-outstanding="{{ $branch->outstanding_balance_sum ?? 0 }}"
                                        data-cases-url="{{ route('admin.cases.index', ['branch_id' => $branch->id]) }}"
                                        data-edit-url="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}"
                                        data-check-url="{{ route('admin.branches.disableCheck', $branch) }}"
                                        data-toggle-url="{{ route('admin.branches.toggleStatus', $branch) }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="branch-reference-icon" data-branch-card-action="edit" aria-label="Edit {{ $branch->branch_name }}" title="Edit branch"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>
                                    <button type="button" class="branch-reference-icon {{ $branch->isMain() ? 'is-protected' : ($branch->is_active ? 'is-danger' : 'is-enable') }}" data-branch-card-action="status" @disabled($branch->isMain()) aria-label="{{ $branch->is_active ? 'Disable' : 'Enable' }} {{ $branch->branch_name }}" title="{{ $branch->isMain() ? 'Main branch cannot be disabled' : ($branch->is_active ? 'Disable branch' : 'Enable branch') }}"><i class="bi {{ $branch->is_active ? 'bi-slash-circle' : 'bi-check-circle' }}" aria-hidden="true"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-system-empty">No branches found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-system-pagination">
                @if($branches->hasPages()){{ $branches->links() }}@endif
            </div>
        </div>
    </div>

</section>
</div>{{-- end space-y-6 --}}
</div>{{-- end centered container --}}
</div>{{-- end admin-table-page --}}

{{-- Branch create modal --}}
<dialog aria-labelledby="branchCreateTitle" id="branchCreateModalOverlay" class="fixed inset-0 hidden flex items-center justify-center backdrop-blur-sm transition-opacity duration-200 font-ui-body" style="z-index: 1300; background:rgba(15, 23, 42, 0.72);">
    <div id="branchCreateModalSheet" class="relative w-[92vw] max-w-3xl max-h-[92vh] bg-white rounded-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200 font-ui-body">
        <div class="branch-create-scroll overflow-y-auto max-h-[84vh] bg-slate-50">
            <form id="branchCreateForm" method="POST" action="{{ route('admin.branches.store') }}" class="max-w-3xl w-full mx-auto">
                @csrf
                <input type="hidden" name="return_to" value="{{ old('return_to', request()->fullUrl()) }}">
                <input type="hidden" name="form_context" value="branch_create_modal">

                <div class="branch-create-panel rounded-2xl border border-slate-200 bg-white overflow-hidden">
                    <div class="branch-create-header px-6 py-5 border-b border-slate-200">
                        <div class="branch-create-heading">
                            <span class="branch-create-heading-icon"><i class="bi bi-building-add" aria-hidden="true"></i></span>
                            <div>
                                <h2 id="branchCreateTitle" class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Add Branch</h2>
                                <p class="text-base text-slate-500">Enter the branch name, address, and operating status.</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button id="branchCreateModalClose" aria-label="Close Create Branch" type="button" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-300 bg-white text-slate-400 hover:text-slate-700 focus:outline-none">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="branch-create-body p-6">
                        <div class="branch-create-fields">
                            <div class="branch-create-field">
                                <label for="branch_create_code" class="label-section">Branch Code</label>
                                <input type="text" id="branch_create_code" value="{{ $nextCode }}" class="form-input branch-create-code" readonly aria-describedby="branch-create-code-help">
                                <p id="branch-create-code-help" class="branch-create-help">Branch code is auto-assigned and cannot be changed.</p>
                            </div>
                            <div class="branch-create-field">
                                <label for="branch_create_name" class="label-section">Branch Name <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    id="branch_create_name"
                                    name="branch_name"
                                    value="{{ old('branch_name') }}"
                                    class="form-input"
                                    placeholder="Caguioa Sabangan Funeral Home"
                                    autocomplete="off"
                                    inputmode="text"
                                    aria-describedby="branch-create-name-error"
                                    required
                                >
                                @error('branch_name') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="form-error hidden" id="branch-create-name-error" data-field-error="branch_name"></div>
                            </div>

                            <div class="branch-create-field branch-create-field--wide">
                                <label for="branch_create_address" class="label-section">Address <span class="text-rose-500">*</span></label>
                                <input type="text" id="branch_create_address" name="address" value="{{ old('address') }}" class="form-input" placeholder="Street, City, Province" aria-describedby="branch-create-address-error" required>
                                @error('address') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="form-error hidden" id="branch-create-address-error" data-field-error="address"></div>
                            </div>
                        </div>

                        <div class="branch-create-status">
                            <div>
                                <span id="branch-create-status-title" class="branch-create-status-title">Operating status</span>
                                <p class="text-sm text-slate-500">Active branches can process new cases and payments</p>
                            </div>
                            <div class="branch-create-status-control">
                                <span id="branch-create-status-pill" class="branch-create-status-pill {{ old('is_active', 1) ? 'is-active' : 'is-inactive' }}">
                                    {{ old('is_active', 1) ? 'Active' : 'Inactive' }}
                                </span>
                                <input type="hidden" name="is_active" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input id="branch_create_is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="sr-only peer" aria-labelledby="branch-create-status-title">
                                    <div class="w-12 h-7 bg-slate-300 rounded-full peer peer-checked:bg-emerald-600 transition-colors"></div>
                                    <div class="absolute left-[3px] top-[3px] h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="branch-create-footer px-6 py-4 border-t border-slate-200">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="branchCreateModalCancel" class="btn btn-outline">Cancel</button>
                            <button class="btn btn-primary-custom bg-[var(--brand-mid)] border-[var(--brand-mid)] hover:bg-[var(--brand-hover)] hover:border-[var(--brand-hover)] text-white px-5">
                                <i class="bi bi-save2"></i>
                                Save Branch
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</dialog>

{{-- Branch edit modal --}}
<dialog aria-label="Edit Branch" id="branchModalOverlay" class="fixed inset-0 hidden flex items-center justify-center backdrop-blur-sm transition-opacity duration-200 font-ui-body" style="z-index: 1300; background:rgba(15, 23, 42, 0.72);">
    <div id="branchModalSheet" class="relative w-[92vw] max-w-3xl max-h-[92vh] bg-white rounded-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200 font-ui-body">
        <div id="branchModalContent" class="overflow-y-auto max-h-[84vh] bg-slate-50">
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>
        </div>
    </div>
</dialog>

<script>
// Branch directory: card/table view toggle persisted in localStorage
function branchCatalog() {
    return {
        view: 'card',
        init() {
            const saved = localStorage.getItem('branch_view');
            if (saved === 'card' || saved === 'table') this.view = saved;
        },
        setView(v) {
            this.view = v;
            localStorage.setItem('branch_view', v);
        },
    };
}

// Branch modals
(() => {
    const editOverlay  = document.getElementById('branchModalOverlay');
    const editSheet    = document.getElementById('branchModalSheet');
    const editContent  = document.getElementById('branchModalContent');
    const editLinks    = [...document.querySelectorAll('.open-branch-modal')];
    const viewLinks    = [...document.querySelectorAll('.open-branch-view')];
    let activeBranchView = null;
    let editOpenedFromDetails = false;

    const createOverlay   = document.getElementById('branchCreateModalOverlay');
    const createSheet     = document.getElementById('branchCreateModalSheet');
    const createOpenBtns  = [...document.querySelectorAll('#openBranchCreateModal, #openBranchCreateModalEmpty')];
    const createCloseBtn  = document.getElementById('branchCreateModalClose');
    const createCancelBtn = document.getElementById('branchCreateModalCancel');
    const createStatusToggle = document.getElementById('branch_create_is_active');
    const createStatusPill   = document.getElementById('branch-create-status-pill');
    const shouldOpenCreateModal = @json(old('form_context') === 'branch_create_modal');
    const branchNamePattern = /^[\p{L}\p{M}][\p{L}\p{M}\s'.&-]*$/u;
    const invalidClass = ['border-rose-300', 'bg-rose-50', 'focus:border-rose-500', 'focus:ring-rose-500'];

    [createOverlay, editOverlay].forEach((overlay) => {
        if (overlay && overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }
    });

    [createOverlay, editOverlay].forEach((overlay) => overlay?.addEventListener('cancel', (event) => {
        event.preventDefault();
        hideModal(overlay, overlay === createOverlay ? createSheet : editSheet);
    }));

    const normalizeBranchNameInput = (value) => String(value || '')
        .replace(/\s+/g, ' ')
        .trim();

    const showFieldError = (form, field, message) => {
        const input = form?.querySelector(`[name="${field}"]`);
        const error = form?.querySelector(`[data-field-error="${field}"]`);
        if (input) input.classList.add(...invalidClass);
        if (error) {
            error.textContent = message;
            error.classList.remove('hidden');
        }
    };

    const clearFieldError = (form, field) => {
        const input = form?.querySelector(`[name="${field}"]`);
        const error = form?.querySelector(`[data-field-error="${field}"]`);
        if (input) input.classList.remove(...invalidClass);
        if (error) {
            error.textContent = '';
            error.classList.add('hidden');
        }
    };

    const bindBranchNameValidation = (input) => {
        if (!input || input.dataset.branchNameBound === '1') return;
        input.dataset.branchNameBound = '1';
        const sync = (trimEnd = false) => {
            const normalized = normalizeBranchNameInput(input.value);
            input.value = normalized;
            const finalValue = input.value.trim();
            if (!finalValue) { input.setCustomValidity('Branch name is required.'); return; }
            if (/\d/.test(finalValue) || !branchNamePattern.test(finalValue)) { input.setCustomValidity('Branch name must contain letters only.'); return; }
            input.setCustomValidity('');
        };
        input.addEventListener('input', () => {
            clearFieldError(input.form, 'branch_name');
            sync(false);
        });
        input.addEventListener('blur', () => sync(true));
        sync(true);
    };

    const bindBranchFormValidation = (form) => {
        if (!form || form.dataset.branchValidationBound === '1') return;
        form.dataset.branchValidationBound = '1';
        const address = form.querySelector('[name="address"]');
        if (address) {
            address.addEventListener('input', () => clearFieldError(form, 'address'));
        }
        form.addEventListener('submit', (event) => {
            const input = form.querySelector('[name="branch_name"]');
            let valid = true;
            clearFieldError(form, 'branch_name');
            clearFieldError(form, 'address');
            if (input) input.value = normalizeBranchNameInput(input.value);
            if (address) address.value = String(address.value || '').replace(/\s+/g, ' ').trim();
            if (!input?.value) {
                valid = false;
                showFieldError(form, 'branch_name', 'Branch name is required.');
            } else if (/\d/.test(input.value) || !branchNamePattern.test(input.value)) {
                valid = false;
                showFieldError(form, 'branch_name', 'Branch name must contain letters only.');
            }
            if (!address?.value || !/[\p{L}\p{M}]/u.test(address.value) || /^\d+$/.test(address.value)) {
                valid = false;
                showFieldError(form, 'address', 'Address must include a valid place name.');
            }
            if (!valid) event.preventDefault();
        });
    };

    const syncPageScrollLock = () => {
        const shouldLock = (createOverlay && !createOverlay.classList.contains('hidden'))
                        || (editOverlay   && !editOverlay.classList.contains('hidden'));
        document.documentElement.classList.toggle('overflow-hidden', shouldLock);
        document.body.classList.toggle('overflow-hidden', shouldLock);
        document.body.classList.toggle('admin-dialog-open', shouldLock);
    };

    const showModal = (overlay, sheet) => {
        if (!overlay || !sheet) return;
        overlay.classList.remove('hidden');
        if (!overlay.open) overlay.showModal();
        syncPageScrollLock();
        requestAnimationFrame(() => {
            sheet.classList.remove('scale-95', 'opacity-0');
            sheet.classList.add('scale-100', 'opacity-100');
            overlay.classList.add('opacity-100');
        });
    };

    const hideModal = (overlay, sheet, content = null) => {
        if (!overlay || !sheet) return;
        sheet.classList.add('scale-95', 'opacity-0');
        sheet.classList.remove('scale-100', 'opacity-100');
        overlay.classList.remove('opacity-100');
        setTimeout(() => {
            overlay.close();
            overlay.classList.add('hidden');
            syncPageScrollLock();
            if (overlay === editOverlay && activeBranchView?.isConnected) {
                activeBranchView.focus({ preventScroll: true });
            }
            if (content) {
                content.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-16 gap-3">
                        <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                        <span class="text-sm text-slate-400">Loading...</span>
                    </div>`;
            }
        }, 180);
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    const viewShell = (title, subtitle, body, actions) => {
        const icon = title.startsWith('Disable') || title.includes('cannot be disabled') ? 'bi-slash-circle'
            : title.startsWith('Enable') ? 'bi-check-circle'
            : title.startsWith('Checking') ? 'bi-hourglass-split'
            : title.startsWith('Unable') ? 'bi-exclamation-triangle'
            : 'bi-building';
        return `
        <div class="branch-detail-header"><div class="branch-detail-header__heading"><span class="branch-detail-header__icon" aria-hidden="true"><i class="bi ${icon}"></i></span><div class="branch-detail-header__copy"><h2>${title}</h2><p>${subtitle}</p></div></div>
            <button type="button" data-branch-close aria-label="Close branch details" class="btn btn-outline"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
        <div class="branch-detail-body">${body}</div><div class="branch-detail-actions">${actions}</div>`;
    };
    const renderBranchOverview = () => {
        if (!activeBranchView) return;
        editSheet.classList.remove('branch-edit-mode');
        const item = activeBranchView.dataset;
        const mainBranch = item.code.toUpperCase() === 'BR001';
        editContent.innerHTML = viewShell('Branch details', `${escapeHtml(item.code)} · ${escapeHtml(item.name)}`,
            `<div class="branch-overview-grid">
                <section class="branch-overview-panel"><h3><i class="bi bi-geo-alt" aria-hidden="true"></i> Location</h3><p>${escapeHtml(item.address || 'No address provided')}</p></section>
                <section class="branch-overview-panel"><h3><i class="bi bi-activity" aria-hidden="true"></i> Operating status</h3><span class="branch-detail-status ${item.status === 'Active' ? 'is-active' : 'is-inactive'}">${escapeHtml(item.status)}</span>${mainBranch ? '<p class="branch-main-note">Main branch · cannot be disabled</p>' : ''}</section>
            </div>
            <section class="branch-overview-cases"><h3>Case records overview · ${Number(item.records || 0).toLocaleString()} total</h3>
                <p class="branch-overview-outstanding">Outstanding balance: <strong>PHP ${Number(item.outstanding || 0).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2})}</strong></p>
                <div class="branch-overview-metrics">
                    <div><span>Draft</span><strong>${Number(item.draftCases || 0).toLocaleString()}</strong></div>
                    <div><span>Active</span><strong>${Number(item.activeCases || 0).toLocaleString()}</strong></div>
                    <div><span>Completed</span><strong>${Number(item.completedCases || 0).toLocaleString()}</strong></div>
                    <div><span>This month</span><strong>${Number(item.monthCases || 0).toLocaleString()}</strong></div>
                </div>
            </section>`,
            `<div class="branch-detail-actions-group"><a href="${escapeHtml(item.casesUrl)}">View branch cases</a></div>
             <div class="branch-detail-actions-group"><button type="button" data-branch-edit><i class="bi bi-pencil-square" aria-hidden="true"></i>Edit branch</button>
             ${mainBranch ? '' : `<button type="button" data-branch-status class="${item.status === 'Active' ? 'branch-detail-danger' : ''}">${item.status === 'Active' ? 'Disable branch' : 'Enable branch'}</button>`}</div>`);
    };
    const renderBranchStatus = async () => {
        if (!activeBranchView) return;
        editSheet.classList.remove('branch-edit-mode');
        const item = activeBranchView.dataset;
        editContent.innerHTML = viewShell('Checking branch status', 'Please wait a moment.', '<p>Checking cases and outstanding balances…</p>', '');
        try {
            const result = item.status === 'Active'
                ? await fetch(item.checkUrl, { headers: { 'Accept': 'application/json' } }).then((response) => { if (!response.ok) throw new Error('Unable to check branch status.'); return response.json(); })
                : { can_disable: true };
            if (!result.can_disable) {
                const explanation = result.is_main_branch
                    ? 'The main branch must remain active.'
                    : 'Please resolve these cases before disabling this branch.';
                editContent.innerHTML = viewShell('This branch cannot be disabled', `${escapeHtml(item.code)} · ${escapeHtml(item.name)}`,
                    `<div class="branch-detail-notice"><strong>${explanation}</strong>
                        ${result.is_main_branch ? '' : `<p>${Number(result.unfinished_cases)} unfinished ${Number(result.unfinished_cases) === 1 ? 'case' : 'cases'} (Draft or Active)</p>
                        <p>${Number(result.completed_with_balance)} completed ${Number(result.completed_with_balance) === 1 ? 'case' : 'cases'} with a remaining balance</p>`}</div>`,
                    `<div class="branch-detail-actions-group"><a href="${escapeHtml(item.casesUrl)}">View branch cases</a></div><div class="branch-detail-actions-group"><button type="button" data-branch-back>Back to branch details</button></div>`);
                return;
            }
            const disabling = item.status === 'Active';
            editContent.innerHTML = viewShell(disabling ? 'Disable this branch?' : 'Enable this branch?', `${escapeHtml(item.code)} · ${escapeHtml(item.name)}`,
                `<p>${disabling
                    ? 'This branch will no longer be available for new case intake or new user assignments. Existing records will not be deleted.'
                    : 'This branch will be available for new case intake and user assignments again.'}</p>`,
                `<div class="branch-detail-actions-group"><button type="button" data-branch-back>Cancel</button></div>
                 <div class="branch-detail-actions-group"><form method="POST" action="${escapeHtml(item.toggleUrl)}"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="PATCH">
                    <button type="submit" class="${disabling ? 'branch-detail-danger' : 'branch-detail-primary'}">Confirm ${disabling ? 'Disable' : 'Enable'}</button></form></div>`);
        } catch (error) {
            editContent.innerHTML = viewShell('Unable to check branch status', `${escapeHtml(item.code)} · ${escapeHtml(item.name)}`,
                '<p>Please try again. No branch status was changed.</p>', '<button type="button" data-branch-back>Back to branch details</button>');
        }
    };

    const loadEditForm = async (url) => {
        editSheet.classList.add('branch-edit-mode');
        editContent.innerHTML = `
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>`;
        try {
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const html = await res.text();
            const doc  = new DOMParser().parseFromString(html, 'text/html');
            const form = doc.querySelector('#branchEditForm');
            if (form) {
                editContent.innerHTML = form.outerHTML;
                bindBranchNameValidation(editContent.querySelector('input[name="branch_name"]'));
                bindBranchFormValidation(editContent.querySelector('form'));
                editContent.querySelector('input[name=branch_name]')?.focus();
                const cancelLink = editContent.querySelector('.branch-modal-cancel');
                if (cancelLink) {
                    cancelLink.addEventListener('click', (evt) => {
                        evt.preventDefault();
                        if (editOpenedFromDetails && activeBranchView) renderBranchOverview();
                        else hideModal(editOverlay, editSheet, editContent);
                    });
                }
                const closeBtn = editContent.querySelector('.branch-modal-close');
                if (closeBtn) {
                    closeBtn.addEventListener('click', (evt) => {
                        evt.preventDefault();
                        hideModal(editOverlay, editSheet, editContent);
                    });
                }
                [...doc.querySelectorAll('script')].forEach((old) => {
                    const s = document.createElement('script');
                    if (old.src) s.src = old.src; else s.textContent = old.textContent;
                    editContent.appendChild(s);
                });
            } else {
                editContent.innerHTML = html;
            }
        } catch (e) {
            editContent.innerHTML = `<div class="p-4 text-sm text-rose-600">Unable to load content.</div>`;
        }
    };

    createOpenBtns.forEach((btn) => {
        if (btn) btn.addEventListener('click', () => showModal(createOverlay, createSheet));
    });

    document.querySelectorAll('[data-branch-status-filter]').forEach((button) => {
        button.addEventListener('click', () => {
            const form = button.closest('form');
            const statusInput = form?.querySelector('input[name="status"]');
            if (!form || !statusInput) return;
            statusInput.value = button.dataset.branchStatusFilter;
            form.requestSubmit();
        });
    });

    bindBranchNameValidation(document.getElementById('branch_create_name'));
    bindBranchFormValidation(document.getElementById('branchCreateForm'));

    if (createStatusToggle && createStatusPill) {
        const syncCreateStatus = () => {
            const active = !!createStatusToggle.checked;
            createStatusPill.textContent = active ? 'Active' : 'Inactive';
            createStatusPill.className = `branch-create-status-pill ${active ? 'is-active' : 'is-inactive'}`;
        };
        createStatusToggle.addEventListener('change', syncCreateStatus);
        syncCreateStatus();
    }

    if (createCloseBtn)  createCloseBtn.addEventListener('click',  () => hideModal(createOverlay, createSheet));
    if (createCancelBtn) createCancelBtn.addEventListener('click', () => hideModal(createOverlay, createSheet));
    if (createOverlay)   createOverlay.addEventListener('click',   (e) => { if (e.target === createOverlay) hideModal(createOverlay, createSheet); });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.branch-card-menu[open]').forEach((menu) => {
            if (!menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    editLinks.forEach((link) => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            editOpenedFromDetails = false;
            showModal(editOverlay, editSheet);
            loadEditForm(link.dataset.url);
        });
    });

    viewLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            activeBranchView = link;
            renderBranchOverview();
            showModal(editOverlay, editSheet);
            editContent.querySelector('[data-branch-close]')?.focus();
        });
    });

    document.querySelectorAll('[data-branch-card-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const viewButton = button.closest('.directory-item-card, tr')?.querySelector('.open-branch-view');
            if (!viewButton || button.disabled) return;
            activeBranchView = viewButton;
            editOpenedFromDetails = false;
            if (button.dataset.branchCardAction !== 'edit') renderBranchOverview();
            showModal(editOverlay, editSheet);
            if (button.dataset.branchCardAction === 'edit') loadEditForm(viewButton.dataset.editUrl);
            else renderBranchStatus();
        });
    });

    editContent.addEventListener('click', (event) => {
        if (event.target.closest('[data-branch-close]')) {
            hideModal(editOverlay, editSheet, editContent);
        } else if (event.target.closest('[data-branch-back]')) {
            renderBranchOverview();
        } else if (event.target.closest('[data-branch-edit]') && activeBranchView) {
            editOpenedFromDetails = true;
            loadEditForm(activeBranchView.dataset.editUrl);
        } else if (event.target.closest('[data-branch-status]')) {
            renderBranchStatus();
        }
    });

    if (editOverlay)  editOverlay.addEventListener('click',  (e) => { if (e.target === editOverlay) hideModal(editOverlay, editSheet, editContent); });

    if (shouldOpenCreateModal) showModal(createOverlay, createSheet);
    syncPageScrollLock();
})();
</script>
@endsection
