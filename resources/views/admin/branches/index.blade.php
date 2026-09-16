@extends('layouts.panel')

@section('page_title', 'Branch Management')
@section('page_desc', 'Manage branch details, status, and branch-wide settings.')
@section('hide_layout_topbar', '1')

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
.branch-management-page .admin-table-head-row > div:first-child {
    display:none;
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
}
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
.branch-management-page .branch-main-pill,
.branch-management-page .branch-highlight-pill {
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
    width:auto !important;
    min-width:6rem;
    min-height:2.35rem;
    gap:.45rem;
    border-radius:.75rem !important;
    background:#E1E7D9 !important;
    border:1px solid var(--border) !important;
    color:var(--ink) !important;
    box-shadow:none !important;
    cursor:pointer;
    font-size:.78rem;
    font-weight:700;
    padding:0 .75rem;
}
.branch-management-page .row-action-trigger::after {
    content:"Actions";
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
</style>
<div class="admin-table-page directory-page admin-catalog-page branch-management-page" x-data="branchCatalog()">
<div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8 py-4">
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
    $highlightBranchId = request('highlight_branch');
@endphp

{{-- KPI insights --}}
<div class="branch-kpi-strip">
    @foreach($branchKpis as $kpi)
        <a href="{{ $kpi['href'] }}" class="branch-kpi-card" title="{{ $kpi['label'] }}: {{ $kpi['value'] }}">
            <div class="branch-kpi-card__inner">
                <span class="branch-kpi-card__icon"><i class="bi {{ $kpi['icon'] }}"></i></span>
                <div class="branch-kpi-card__body">
                    <span class="branch-kpi-card__label">{{ $kpi['label'] }}</span>
                    <span class="branch-kpi-card__value">{{ $kpi['value'] }}</span>
                    <span class="branch-kpi-card__desc">{{ $kpi['insight'] }}</span>
                </div>
                <span class="branch-kpi-card__action">{{ $kpi['action'] ?? 'View' }} <i class="bi bi-arrow-right-short"></i></span>
            </div>
        </a>
    @endforeach
</div>

{{-- Main section card --}}
<section class="table-system-card admin-table-card">

    {{-- Header --}}
    <div class="table-system-head">
        <div class="admin-table-head-row">
            <div>
                <h2 class="table-system-title">Branch Directory</h2>
                <p class="admin-table-head-copy">Manage branch profile, status, and encoded record count.</p>
            </div>
            <div class="admin-table-head-actions">
                {{-- View toggle --}}
                <div class="inline-flex items-center rounded-lg border border-slate-200 overflow-hidden bg-white">
                    <button
                        type="button"
                        @click="setView('card')"
                        :class="view === 'card' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50'"
                        class="px-3 py-2 transition-colors flex items-center gap-1.5 font-medium"
                        title="Card view"
                    >
                        <i class="bi bi-grid-3x3-gap-fill text-xs"></i>
                        <span class="hidden sm:inline text-xs">Cards</span>
                    </button>
                    <button
                        type="button"
                        @click="setView('table')"
                        :class="view === 'table' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-50'"
                        class="px-3 py-2 transition-colors flex items-center gap-1.5 font-medium border-l border-slate-200"
                        title="Table view"
                    >
                        <i class="bi bi-table text-xs"></i>
                        <span class="hidden sm:inline text-xs">Table</span>
                    </button>
                </div>

                <button
                    id="openBranchCreateModal"
                    type="button"
                    class="btn btn-primary-custom btn-sm"
                >
                    <i class="bi bi-plus-circle"></i>
                    <span>Add Branch</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="table-system-toolbar">
        <form
            method="GET"
            action="{{ route('admin.branches.index') }}"
            class="table-toolbar"
            data-table-toolbar
            data-live-search-suggestions
            data-live-search-commit-only
            data-search-debounce="400"
            style="grid-template-columns: minmax(260px, 2.2fr) repeat(2, minmax(150px, 1fr)) auto;"
        >
            @if(request()->filled('branch_id'))
                <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
                <input type="hidden" name="highlight_branch" value="{{ request('highlight_branch', request('branch_id')) }}">
            @endif
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Search</label>
                <div class="table-toolbar-input-wrap">
                    <i class="bi bi-search table-toolbar-leading-icon" aria-hidden="true"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search branches..."
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
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Status</label>
                <div class="table-toolbar-select-wrap">
                    <i class="bi bi-activity table-toolbar-leading-icon" aria-hidden="true"></i>
                    <select name="status" class="form-select table-toolbar-select" data-table-auto-submit>
                        <option value="">All Status</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                </div>
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
                    class="directory-item-card bg-white border {{ $isHighlightedBranch ? 'border-[var(--brand-mid)] ring-2 ring-[var(--brand-mid)]/20' : 'border-slate-200' }} rounded-2xl transition-colors duration-200 flex flex-col cursor-pointer focus:outline-none focus:ring-2 focus:ring-[var(--brand-mid)] focus:ring-offset-2"
                    data-live-search-row
                    data-live-search-title="{{ $branch->branch_name }}"
                    data-live-search-meta="{{ $branch->branch_code }} / {{ $branch->address ?: 'No address' }}"
                    data-live-search-text="{{ $branch->branch_code }} {{ $branch->branch_name }} {{ $branch->address }} {{ $branch->is_active ? 'Active' : 'Inactive' }}"
                    data-branch-card-href="{{ route('admin.cases.index', ['branch_id' => $branch->id]) }}"
                    role="link"
                    tabindex="0"
                    aria-label="View master case records for {{ $branch->branch_name }}"
                >

                    {{-- Card header: code badge + name --}}
                    <div class="branch-card-identity p-5">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <span class="branch-code-pill">
                                    {{ $branch->branch_code }}
                                </span>
                                @if($branch->isMain())
                                    <span class="branch-main-pill">
                                        <i class="bi bi-star-fill text-[8px]"></i>Main
                                    </span>
                                @endif
                                @if($isHighlightedBranch)
                                    <span class="branch-highlight-pill">
                                        Highlighted
                                    </span>
                                @endif
                            </div>
                            <h3 class="branch-card-name">{{ $branch->branch_name }}</h3>
                            <p class="branch-card-address flex items-start gap-1">
                                <i class="bi bi-geo-alt-fill text-[10px] mt-0.5 flex-shrink-0"></i>
                                <span class="truncate">{{ $branch->address ?: '—' }}</span>
                            </p>
                        </div>
                    </div>

                    {{-- Records count --}}
                    <div class="branch-card-metric px-5 py-4 border-t border-slate-100">
                        <p class="branch-card-metric-label">Total Records</p>
                        <p class="branch-card-metric-value mt-1">{{ number_format($branch->funeral_cases_count) }}</p>
                    </div>

                    {{-- Card footer --}}
                    <div class="branch-card-footer px-5 py-3 border-t border-slate-100 flex items-center justify-end gap-2 mt-auto">
                        <details class="branch-card-menu branch-card-actions" data-row-menu>
                            <summary
                                class="row-action-trigger"
                                aria-haspopup="menu"
                                aria-label="Open branch actions"
                            >
                                <i class="bi bi-three-dots-vertical"></i>
                            </summary>
                            <div class="branch-card-dropdown" role="menu">
                                <button
                                    type="button"
                                    class="row-action-item open-branch-modal"
                                    data-row-menu-item
                                    data-url="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}"
                                >
                                    <i class="bi bi-pencil-square"></i>
                                    <span>Edit branch</span>
                                </button>
                                <form method="POST" action="{{ route('admin.branches.toggleStatus', $branch) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="row-action-item" type="submit" data-row-menu-item>
                                        <i class="bi bi-toggle-{{ $branch->is_active ? 'off' : 'on' }}"></i>
                                        <span>{{ $branch->is_active ? 'Disable branch' : 'Enable branch' }}</span>
                                    </button>
                                </form>
                            </div>
                        </details>
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
                            <th class="text-left table-col-number">Total Records</th>
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
                                <td>
                                    @if($branch->is_active)
                                        <span class="status-badge status-badge-success">Active</span>
                                    @else
                                        <span class="status-badge status-badge-danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="table-col-actions">
                                    <div class="row-action-menu" data-row-menu>
                                        <button
                                            type="button"
                                            class="row-action-trigger"
                                            data-row-menu-trigger
                                            aria-haspopup="menu"
                                            aria-expanded="false"
                                            aria-label="Open row actions"
                                        >
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <div class="row-action-dropdown" role="menu">
                                            <button
                                                type="button"
                                                class="row-action-item open-branch-modal"
                                                data-row-menu-item
                                                data-url="{{ route('admin.branches.edit', ['branch' => $branch, 'return_to' => request()->fullUrl()]) }}"
                                            >
                                                <i class="bi bi-pencil-square"></i>
                                                <span>Edit branch</span>
                                            </button>
                                            <form method="POST" action="{{ route('admin.branches.toggleStatus', $branch) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="row-action-item" type="submit" data-row-menu-item>
                                                    <i class="bi bi-toggle-{{ $branch->is_active ? 'off' : 'on' }}"></i>
                                                    <span>{{ $branch->is_active ? 'Disable branch' : 'Enable branch' }}</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-system-empty">No branches found.</td>
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
<div id="branchCreateModalOverlay" class="fixed inset-0 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-200 font-ui-body" style="z-index: 1300;">
    <div id="branchCreateModalSheet" class="relative w-[92vw] max-w-3xl max-h-[92vh] bg-white rounded-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200 font-ui-body">
        <div class="overflow-y-auto max-h-[84vh] bg-slate-50">
            <form id="branchCreateForm" method="POST" action="{{ route('admin.branches.store') }}" class="max-w-3xl w-full mx-auto">
                @csrf
                <input type="hidden" name="return_to" value="{{ old('return_to', request()->fullUrl()) }}">
                <input type="hidden" name="form_context" value="branch_create_modal">

                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-200">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-[1.65rem] leading-tight text-slate-900 font-ui-heading">Branch details</h2>
                                <p class="text-base text-slate-500">Enter the branch name, address, and operating status.</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-xl border border-slate-300 bg-slate-50 px-3 py-1 text-sm font-semibold tracking-wide text-slate-700">
                                    {{ $nextCode }}
                                </span>
                                <button id="branchCreateModalClose" type="button" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-300 bg-white text-slate-400 hover:text-slate-700 focus:outline-none">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="rounded-xl border px-4 py-4">
                            <label class="label-section">Branch Code</label>
                            <input type="text" value="{{ $nextCode }}" class="form-input bg-slate-100 text-slate-700 font-semibold" readonly>
                            <div class="text-sm text-slate-500 mt-2">Branch code is auto-assigned and cannot be changed.</div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="label-section">Branch Name <span class="text-rose-500">*</span></label>
                                <input
                                    type="text"
                                    id="branch_create_name"
                                    name="branch_name"
                                    value="{{ old('branch_name') }}"
                                    class="form-input"
                                    placeholder="Caguioa Sabangan Funeral Home"
                                    autocomplete="off"
                                    inputmode="text"
                                    required
                                >
                                @error('branch_name') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="form-error hidden" data-field-error="branch_name"></div>
                            </div>

                            <div>
                                <label class="label-section">Address <span class="text-rose-500">*</span></label>
                                <input type="text" name="address" value="{{ old('address') }}" class="form-input" placeholder="Street, City, Province" required>
                                @error('address') <div class="form-error">{{ $message }}</div> @enderror
                                <div class="form-error hidden" data-field-error="address"></div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="text-[1.1rem] leading-tight font-semibold text-slate-900">Operating status</div>
                                <p class="text-sm text-slate-500">Active branches can process new cases and payments</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span id="branch-create-status-pill" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ old('is_active', 1) ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                    {{ old('is_active', 1) ? 'Active' : 'Inactive' }}
                                </span>
                                <input type="hidden" name="is_active" value="0">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input id="branch_create_is_active" type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="sr-only peer">
                                    <div class="w-12 h-7 bg-slate-300 rounded-full peer peer-checked:bg-emerald-600 transition-colors"></div>
                                    <div class="absolute left-[3px] top-[3px] h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        <div class="text-sm text-slate-500">Review the branch details before saving.</div>
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
</div>

{{-- Branch edit modal --}}
<div id="branchModalOverlay" class="fixed inset-0 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm transition-opacity duration-200 font-ui-body" style="z-index: 1300;">
    <div id="branchModalSheet" class="relative w-[92vw] max-w-3xl max-h-[92vh] bg-white rounded-2xl overflow-hidden transform transition-all duration-200 scale-95 opacity-0 border border-slate-200 font-ui-body">
        <div id="branchModalContent" class="overflow-y-auto max-h-[84vh] bg-slate-50">
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                <span class="text-sm text-slate-400">Loading...</span>
            </div>
        </div>
    </div>
</div>

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

    const createOverlay   = document.getElementById('branchCreateModalOverlay');
    const createSheet     = document.getElementById('branchCreateModalSheet');
    const createOpenBtns  = [...document.querySelectorAll('#openBranchCreateModal, #openBranchCreateModalEmpty')];
    const createCloseBtn  = document.getElementById('branchCreateModalClose');
    const createCancelBtn = document.getElementById('branchCreateModalCancel');
    const createStatusToggle = document.getElementById('branch_create_is_active');
    const createStatusPill   = document.getElementById('branch-create-status-pill');
    const branchCards = [...document.querySelectorAll('[data-branch-card-href]')];
    const shouldOpenCreateModal = @json(old('form_context') === 'branch_create_modal');
    const branchNamePattern = /^[\p{L}\p{M}][\p{L}\p{M}\s'.&-]*$/u;
    const invalidClass = ['border-rose-300', 'bg-rose-50', 'focus:border-rose-500', 'focus:ring-rose-500'];

    [createOverlay, editOverlay].forEach((overlay) => {
        if (overlay && overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }
    });

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
    };

    const showModal = (overlay, sheet) => {
        if (!overlay || !sheet) return;
        overlay.classList.remove('hidden');
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
            overlay.classList.add('hidden');
            syncPageScrollLock();
            if (content) {
                content.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-16 gap-3">
                        <div class="w-7 h-7 rounded-full border-2 border-slate-200 border-t-slate-500 animate-spin"></div>
                        <span class="text-sm text-slate-400">Loading...</span>
                    </div>`;
            }
        }, 180);
    };

    const loadEditForm = async (url) => {
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
                const cancelLink = editContent.querySelector('.branch-modal-cancel');
                if (cancelLink) {
                    cancelLink.addEventListener('click', (evt) => {
                        evt.preventDefault();
                        hideModal(editOverlay, editSheet, editContent);
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

    bindBranchNameValidation(document.getElementById('branch_create_name'));
    bindBranchFormValidation(document.getElementById('branchCreateForm'));

    if (createStatusToggle && createStatusPill) {
        const syncCreateStatus = () => {
            const active = !!createStatusToggle.checked;
            createStatusPill.textContent = active ? 'Active' : 'Inactive';
            createStatusPill.className = `inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ${active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`;
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
            showModal(editOverlay, editSheet);
            loadEditForm(link.dataset.url);
        });
    });

    branchCards.forEach((card) => {
        const openBranchCases = () => {
            if (card.dataset.branchCardHref) {
                window.location.href = card.dataset.branchCardHref;
            }
        };

        card.addEventListener('click', (event) => {
            if (event.target.closest('a, button, form, [data-row-menu]')) {
                return;
            }

            openBranchCases();
        });

        card.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            if (event.target.closest('a, button, form, [data-row-menu]')) {
                return;
            }

            event.preventDefault();
            openBranchCases();
        });
    });

    if (editCloseBtn) editCloseBtn.addEventListener('click', () => hideModal(editOverlay, editSheet, editContent));
    if (editOverlay)  editOverlay.addEventListener('click',  (e) => { if (e.target === editOverlay) hideModal(editOverlay, editSheet, editContent); });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        if (createOverlay && !createOverlay.classList.contains('hidden')) { hideModal(createOverlay, createSheet); return; }
        if (editOverlay   && !editOverlay.classList.contains('hidden'))   { hideModal(editOverlay, editSheet, editContent); }
    });

    if (shouldOpenCreateModal) showModal(createOverlay, createSheet);
    syncPageScrollLock();
})();
</script>
@endsection
