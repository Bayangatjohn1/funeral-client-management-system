@extends('layouts.panel')

@section('page_title', 'Package Management')
@section('page_desc', 'Manage funeral service packages, pricing, inclusions, freebies, promos, and availability.')
@section('hide_layout_topbar', '1')

@section('content')
@php
    $currentUser = auth()->user();
    $isBranchAdmin = $currentUser?->isBranchAdmin() ?? false;
    $isMainAdmin = $currentUser?->isMainAdmin() ?? false;
    $packageDisplay = function ($package) {
        $structured = $package->packageInclusions ?? collect();
        $casketRow = $structured->firstWhere('service_type', \App\Models\Package::SERVICE_CASKET);
        $includedCasket = $casketRow?->casket_type ?: $package->coffin_type;
        $inclusionItems = $package->inclusionNames();

        if ($includedCasket) {
            $needle = mb_strtolower(trim((string) $includedCasket));
            $inclusionItems = array_values(array_filter($inclusionItems, function ($item) use ($needle) {
                $normalized = mb_strtolower(trim((string) $item));
                if ($normalized === '') {
                    return false;
                }

                return ! str_contains($normalized, 'casket')
                    && ! str_contains($normalized, 'coffin')
                    && ($needle === '' || ! str_contains($normalized, $needle));
            }));
        }

        return [
            'casket' => $includedCasket,
            'inclusions' => $inclusionItems,
            'freebies' => $package->freebieNames(),
        ];
    };
@endphp
<style>[x-cloak] { display: none !important; }</style>
<div class="admin-table-page admin-catalog-page service-management-page package-management-page" x-data="pkgCatalog()">
<div class="mx-auto w-full max-w-[1440px] px-4 sm:px-6 lg:px-8 py-6">
<div class="space-y-6">

<div class="management-toast no-print" role="status" aria-live="polite" data-page-context-toast>
    <i class="bi bi-box-seam" aria-hidden="true"></i>
    <span>You are viewing service package records.</span>
</div>

@if(session('success'))
    <div class="flash-success">{{ session('success') }}</div>
@endif

<style>
.pkg-stat-card {
    display: flex;
    flex-direction: column;
    background: #FAFAF7;
    border: 1.5px solid #C9C5BB;
    border-radius: 10px;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
}
.pkg-stat-card:hover {
    background: #F3F0E8;
    border-color: #3E4A3D;
    box-shadow: 0 2px 6px rgba(62,74,61,0.09);
}
.pkg-stat-card--active {
    background: rgba(139,154,139,0.15);
    border-color: #3E4A3D;
}
.pkg-stat-card--active:hover {
    background: rgba(139,154,139,0.22);
}
.pkg-stat-card__inner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.7rem 1rem;
    flex: 1;
}
.pkg-stat-card__icon {
    width: 2rem;
    height: 2rem;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.82rem;
    flex-shrink: 0;
}
.pkg-stat-card__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.08rem;
}
.pkg-stat-card__label {
    font-size: 0.65rem;
    font-weight: 700;
    color: #5F685F;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
    line-height: 1.3;
}
.pkg-stat-card__value {
    font-size: 1.15rem;
    font-weight: 800;
    line-height: 1.15;
    font-variant-numeric: tabular-nums;
}
.pkg-stat-card__desc {
    font-size: 0.63rem;
    color: #7A8577;
    font-weight: 500;
    line-height: 1.3;
    margin-top: 0.1rem;
}
.pkg-stat-card__action {
    font-size: 0.62rem;
    font-weight: 700;
    color: #5F685F;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    white-space: nowrap;
    flex-shrink: 0;
    opacity: 0;
    transition: opacity 0.15s ease;
}
.pkg-stat-card:hover .pkg-stat-card__action,
.pkg-stat-card--active .pkg-stat-card__action {
    opacity: 1;
}
.pkg-stat-card--active .pkg-stat-card__action {
    color: #3E4A3D;
}
.package-management-page .directory-card-view > .grid {
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr));
    gap: 18px;
}
.pkg-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-height: 100%;
    border: 1px solid #D7D1C6;
    border-radius: 10px;
    background: #FFFFFF;
    color: #2F302C;
    box-shadow: 0 1px 3px rgba(62,74,61,.08);
    transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
}
.pkg-card::after {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 10px;
    background: rgba(47, 48, 44, .10);
    opacity: 0;
    pointer-events: none;
    transition: opacity .16s ease;
}
.pkg-card:hover::after,
.pkg-card:focus-within::after {
    opacity: 1;
}
.pkg-card:hover,
.pkg-card:focus-within {
    border-color: #9BA695;
    box-shadow: 0 10px 26px rgba(62,74,61,.13);
    transform: translateY(-1px);
}
.pkg-card-head,
.pkg-card-price,
.pkg-card-promo,
.pkg-card-lists,
.pkg-card-footer {
    padding-left: 18px;
    padding-right: 18px;
}
.pkg-card-head { padding-top: 18px; padding-bottom: 13px; }
.pkg-card-title {
    color: #2F302C;
    font-size: 1rem;
    line-height: 1.3;
    font-weight: 950;
}
.pkg-card-casket {
    margin-top: 7px;
    display: inline-flex;
    max-width: 100%;
    align-items: center;
    gap: 6px;
    color: #5F6B5C;
    font-size: .78rem;
    line-height: 1.35;
    font-weight: 800;
}
.pkg-card-price {
    padding-top: 15px;
    padding-bottom: 15px;
    border-top: 1px solid #E7E1D7;
}
.pkg-card-kicker {
    color: #697565;
    font-size: .66rem;
    line-height: 1.25;
    font-weight: 950;
    text-transform: uppercase;
    letter-spacing: .07em;
}
.pkg-card-amount {
    margin-top: 3px;
    color: #2F302C;
    font-size: 1.55rem;
    line-height: 1.1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}
.pkg-card-promo {
    min-height: 42px;
    padding-bottom: 14px;
}
.pkg-muted-text {
    color: #7A8275;
    font-size: .78rem;
    font-weight: 750;
}
.pkg-card-lists {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    padding-top: 15px;
    padding-bottom: 15px;
    border-top: 1px solid #E7E1D7;
}
.pkg-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    min-height: 54px;
    padding-top: 10px;
    padding-bottom: 10px;
    border-top: 1px solid #E7E1D7;
}
.pkg-card-edit,
.table-row-action-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 34px;
    border: 1px solid #C9C5BB;
    border-radius: 8px;
    background: #FAFAF7;
    color: #3E4A3D;
    padding: 7px 10px;
    font-size: .78rem;
    line-height: 1.2;
    font-weight: 950;
    text-decoration: none;
    cursor: pointer;
    transition: opacity .15s ease, transform .15s ease, background .15s ease, border-color .15s ease;
}
.pkg-card-edit {
    position: absolute;
    left: 50%;
    top: 50%;
    z-index: 2;
    opacity: 0;
    transform: translate(-50%, -44%);
    box-shadow: 0 12px 28px rgba(47,48,44,.18);
}
.pkg-card:hover .pkg-card-edit,
.pkg-card:focus-within .pkg-card-edit {
    opacity: 1;
    transform: translate(-50%, -50%);
}
.pkg-card-edit:hover,
.table-row-action-link:hover {
    background: #F1EEE7;
    border-color: #8C9786;
    color: #2F392E;
}
.pkg-detail-view {
    color: #2F302C;
}
.pkg-detail-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #B5C4AD;
    border-radius: .85rem;
    background: #D3DEC9;
}
.pkg-detail-head h2 {
    margin: .2rem 0 0;
    color: #2F302C;
    font-size: 1.35rem;
    line-height: 1.2;
    font-weight: 780;
}
.pkg-detail-head p {
    margin-top: .35rem;
    color: #5F6B5C;
    font-size: .86rem;
    line-height: 1.5;
    font-weight: 650;
}
.pkg-detail-price {
    display: grid;
    gap: .15rem;
    min-width: 11rem;
    border: 1px solid #B5C4AD;
    border-radius: .75rem;
    background: #E1E7D9;
    padding: .75rem .9rem;
}
.pkg-detail-price span {
    color: #566653;
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.pkg-detail-price strong {
    color: #2F302C;
    font-size: 1.22rem;
    line-height: 1.2;
    white-space: nowrap;
}
.pkg-detail-badges {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: .45rem;
    margin-top: .7rem;
}
.pkg-detail-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    border: 1px solid #B5C4AD;
    border-radius: 999px;
    background: #E1E7D9;
    color: #566653;
    padding: .28rem .6rem;
    font-size: .72rem;
    font-weight: 700;
    line-height: 1.2;
}
.pkg-detail-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .85rem;
    padding-top: .9rem;
}
.pkg-detail-grid section {
    border: 1px solid #B5C4AD;
    border-radius: .85rem;
    background: #E1E7D9;
    padding: .95rem;
}
.pkg-detail-grid h3 {
    display: flex;
    align-items: center;
    gap: .45rem;
    color: #2F302C;
    font-size: .78rem;
    line-height: 1.3;
    font-weight: 700;
    letter-spacing: .06em;
    margin-bottom: .65rem;
    text-transform: uppercase;
}
.pkg-detail-grid p,
.pkg-detail-grid li,
.pkg-detail-grid small {
    color: #5F6B5C;
    font-size: .84rem;
    line-height: 1.45;
    font-weight: 650;
}
.pkg-detail-grid ul {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: .55rem;
}
.pkg-detail-grid li {
    display: grid;
    gap: .15rem;
    border-top: 1px solid #C7D5BE;
    padding-top: .55rem;
}
.pkg-detail-grid li:first-child {
    border-top: 0;
    padding-top: 0;
}
.pkg-detail-grid li span {
    color: #2F302C;
    font-weight: 720;
}
.pkg-detail-actions {
    display: flex;
    justify-content: flex-end;
    gap: .6rem;
    padding-top: .9rem;
    margin-top: .9rem;
    border-top: 1px solid #B5C4AD;
}
.pkg-detail-close {
    min-height: 2.65rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    border: 1px solid #B5C4AD;
    border-radius: .75rem;
    background: #E1E7D9;
    color: #3E4A3D;
    padding: 0 .95rem;
    font-size: .84rem;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    box-shadow: none !important;
    transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.pkg-detail-close:hover,
.pkg-detail-close:focus-visible {
    background: #C7D5BE;
    border-color: #8EA083;
    color: #263325;
    outline: none;
}
#packageModalOverlay {
    background: rgba(35, 43, 34, .46) !important;
    backdrop-filter: none !important;
}
#packageModalSheet {
    background: #D3DEC9 !important;
    border: 1px solid #8EA083 !important;
    border-radius: .9rem !important;
    box-shadow: none !important;
}
html[data-theme='dark'] .pkg-detail-view,
html[data-theme='dark'] .pkg-detail-head h2,
html[data-theme='dark'] .pkg-detail-head strong,
html[data-theme='dark'] .pkg-detail-grid h3,
html[data-theme='dark'] .pkg-detail-grid li span {
    color: #e2ecf9;
}
html[data-theme='dark'] .pkg-detail-head,
html[data-theme='dark'] .pkg-detail-actions {
    border-color: #243954;
}
html[data-theme='dark'] .pkg-detail-head p,
html[data-theme='dark'] .pkg-detail-grid p,
html[data-theme='dark'] .pkg-detail-grid li,
html[data-theme='dark'] .pkg-detail-grid small {
    color: #9fb6d2;
}
html[data-theme='dark'] .pkg-detail-grid section {
    background: #152035;
    border-color: #2a3f5f;
}
@media (max-width: 760px) {
    .pkg-detail-head,
    .pkg-detail-actions {
        display: grid;
    }
    .pkg-detail-price {
        min-width: 0;
    }
    .pkg-detail-grid {
        grid-template-columns: 1fr;
    }
}
html[data-theme='dark'] .pkg-stat-card,
html[data-theme='dark'] .pkg-card {
    background: #111e33;
    border-color: #243954;
    color: #e2ecf9;
}
html[data-theme='dark'] .pkg-stat-card:hover,
html[data-theme='dark'] .pkg-stat-card--active,
html[data-theme='dark'] .pkg-card:hover,
html[data-theme='dark'] .pkg-card:focus-within {
    background: #13263d;
    border-color: #3f5b7f;
    box-shadow: 0 10px 28px rgba(0,0,0,.24);
}
html[data-theme='dark'] .pkg-stat-card__label,
html[data-theme='dark'] .pkg-stat-card__desc,
html[data-theme='dark'] .pkg-stat-card__action,
html[data-theme='dark'] .pkg-card-kicker,
html[data-theme='dark'] .pkg-card-casket,
html[data-theme='dark'] .pkg-muted-text {
    color: #9fb6d2 !important;
}
html[data-theme='dark'] .pkg-stat-card__value,
html[data-theme='dark'] .pkg-card-title,
html[data-theme='dark'] .pkg-card-amount {
    color: #e2ecf9 !important;
}
html[data-theme='dark'] .pkg-card-price,
html[data-theme='dark'] .pkg-card-lists,
html[data-theme='dark'] .pkg-card-footer {
    border-color: #243954;
}
html[data-theme='dark'] .pkg-card-edit,
html[data-theme='dark'] .table-row-action-link {
    background: #152035;
    border-color: #2a3f5f;
    color: #d8ecff;
}
html[data-theme='dark'] .pkg-card-edit:hover,
html[data-theme='dark'] .table-row-action-link:hover {
    background: #1a2b41;
    border-color: #4a82c0;
    color: #ffffff;
}
@media (max-width: 640px) {
    .pkg-card-lists {
        grid-template-columns: 1fr;
    }
    .pkg-card-edit {
        position: static;
        opacity: 1;
        transform: none;
        box-shadow: none;
    }
}
</style>

<style>
.package-management-page .grid.grid-cols-1.sm\:grid-cols-3 {
    gap: .85rem !important;
}
.package-management-page .pkg-stat-card {
    background: #D3DEC9 !important;
    border: 1px solid var(--border) !important;
    border-radius: .75rem !important;
    box-shadow: none !important;
    color: var(--ink);
    transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.package-management-page .pkg-stat-card:hover,
.package-management-page .pkg-stat-card--active {
    background: #C7D5BE !important;
    border-color: #8EA083 !important;
    box-shadow: none !important;
}
.package-management-page .pkg-stat-card__inner {
    min-height: 4.6rem;
    padding: .65rem .9rem;
}
.package-management-page .pkg-stat-card__icon {
    background: transparent !important;
    color: #566653 !important;
    width: 2rem;
    height: 2rem;
    font-size: 1.05rem;
}
.package-management-page .pkg-stat-card__value {
    font-size: 1.05rem;
    line-height: 1.1;
}
.package-management-page .pkg-stat-card__desc {
    font-size: .62rem;
}
.package-management-page .pkg-stat-card__label,
.package-management-page .pkg-stat-card__desc,
.package-management-page .pkg-stat-card__action {
    color: #566653;
    font-weight: 650;
}
.package-management-page .pkg-stat-card__value {
    color: var(--ink) !important;
    font-weight: 750;
}
.package-management-page .admin-table-card {
    background: transparent !important;
    border: 0 !important;
    border-radius: 0 !important;
    box-shadow: none !important;
    overflow: visible !important;
}
.package-management-page .table-system-toolbar,
.package-management-page .table-system-head {
    background: transparent !important;
    border: 0 !important;
    padding-left: 1rem !important;
    padding-right: 1rem !important;
}
.package-management-page .table-system-toolbar {
    background: #D3DEC9 !important;
    border: 1px solid #B5C4AD !important;
    border-radius: .85rem !important;
    padding: .85rem !important;
    margin-bottom: .8rem !important;
}
.package-management-page .table-system-head {
    padding-top: .85rem !important;
    padding-bottom: .85rem !important;
}
.package-management-page .table-toolbar {
    background: transparent !important;
}
.package-management-page .directory-card-view {
    order: 3;
    background: transparent !important;
    border-top: 0 !important;
    padding: .75rem 1rem 0 !important;
    overflow: visible !important;
}
.package-management-page .package-table-view {
    order: 3;
}
.package-management-page .package-table-view .table-system-list {
    background: transparent;
    padding: 0;
}
.package-management-page .package-table-view .table-system-wrap {
    margin: 0;
}
.package-management-page .package-table-view .table-system-table {
    min-width: 980px;
}
.package-management-page .package-table-view .table-system-table tbody tr:hover {
    background: #E6EEDF !important;
}
.package-management-page .package-table-view .table-system-table th,
.package-management-page .package-table-view .table-system-table td {
    padding-top: .75rem !important;
    padding-bottom: .75rem !important;
}
.package-management-page .pkg-card {
    background: #E1E7D9 !important;
    border: 1px solid var(--border) !important;
    border-radius: .75rem !important;
    box-shadow: none !important;
    transform: none !important;
    color: var(--ink);
    transition: background-color .16s ease, border-color .16s ease, color .16s ease;
}
.package-management-page .pkg-card::after {
    display: none !important;
}
.package-management-page .pkg-card:hover,
.package-management-page .pkg-card:focus-within {
    background: #C7D5BE !important;
    border-color: #8EA083 !important;
    box-shadow: none !important;
    transform: none !important;
}
.package-management-page .pkg-card-head,
.package-management-page .pkg-card-price,
.package-management-page .pkg-card-promo,
.package-management-page .pkg-card-lists,
.package-management-page .pkg-card-footer {
    border-color: #B5C4AD !important;
    padding-left: .9rem !important;
    padding-right: .9rem !important;
}
.package-management-page .pkg-card-head {
    padding-top: .85rem !important;
    padding-bottom: .65rem !important;
}
.package-management-page .pkg-card-price {
    padding-top: .7rem !important;
    padding-bottom: .7rem !important;
}
.package-management-page .pkg-card-promo {
    min-height: 2rem !important;
    padding-bottom: .65rem !important;
}
.package-management-page .pkg-card-lists {
    gap: .75rem !important;
    padding-top: .75rem !important;
    padding-bottom: .75rem !important;
}
.package-management-page .pkg-card-footer {
    min-height: 2.75rem !important;
    padding-top: .55rem !important;
    padding-bottom: .55rem !important;
}
.package-management-page .pkg-card-amount {
    font-size: 1.25rem !important;
}
.package-management-page .pkg-card-title {
    font-size: .95rem !important;
}
.package-management-page .pkg-card-casket,
.package-management-page .pkg-muted-text,
.package-management-page .pkg-card-lists li,
.package-management-page .pkg-card-lists span {
    font-size: .74rem !important;
    line-height: 1.35 !important;
}
.package-management-page .pkg-card-title,
.package-management-page .pkg-card-amount {
    color: var(--ink) !important;
    font-weight: 750;
}
.package-management-page .pkg-card-kicker,
.package-management-page .pkg-card-casket {
    color: #566653 !important;
    font-weight: 650;
}
.package-management-page .pkg-card-edit {
    position: static !important;
    left: auto !important;
    top: auto !important;
    opacity: 1 !important;
    transform: none !important;
    background: #344333 !important;
    border: 1px solid #344333 !important;
    color: #fff !important;
    box-shadow: none !important;
}
.package-management-page .pkg-card:hover .pkg-card-edit,
.package-management-page .pkg-card:focus-within .pkg-card-edit {
    opacity: 1 !important;
    transform: none !important;
}
.package-management-page .pkg-card-edit:hover {
    background: #2F3A2E !important;
    border-color: #2F3A2E !important;
    color: #fff !important;
}
.package-management-page .admin-table-head-actions > .inline-flex {
    background: #E1E7D9 !important;
    border: 1px solid var(--border) !important;
    border-radius: .65rem !important;
    box-shadow: none !important;
    min-height: 2.7rem;
}
.package-management-page .admin-table-head-row,
.package-management-page .admin-table-head-actions {
    justify-content: space-between !important;
}
.package-management-page .admin-table-head-actions {
    width: 100%;
}
.package-management-page .admin-table-head-actions > .inline-flex button {
    min-height: 2.7rem;
    color: var(--ink);
    font-weight: 650;
    background: transparent !important;
    cursor: pointer;
}
.package-management-page .admin-table-head-actions > .inline-flex button:hover {
    background: #8EA083 !important;
    color: #F7FAF3 !important;
}
.package-management-page .admin-table-head-actions > .inline-flex .bg-slate-900 {
    background: #344333 !important;
    color: #fff !important;
}
.package-management-page .admin-table-head-actions > .inline-flex .bg-slate-900:hover {
    background: #2F3A2E !important;
    color: #fff !important;
}
.package-management-page .admin-table-head-actions .btn-primary-custom {
    background: #344333 !important;
    border-color: #344333 !important;
    color: #fff !important;
    min-width: 8.75rem;
    min-height: 2.7rem;
    font-weight: 650;
}
.package-management-page .admin-table-head-actions .btn-primary-custom:hover {
    background: #2F3A2E !important;
    border-color: #2F3A2E !important;
    color: #fff !important;
}
.package-management-page .table-system-wrap {
    margin-top: 0;
}
.package-management-page .table-toolbar-reset-wrap {
    display: none !important;
}
@media (max-width: 900px) {
    .package-management-page .table-toolbar {
        grid-template-columns: 1fr !important;
    }
}
</style>

{{-- Main card --}}
<section class="table-system-card admin-table-card">

    {{-- Header --}}
    <div class="table-system-head">
        <div class="admin-table-head-row">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="table-system-title">Service Packages</h2>
                    @if($isBranchAdmin)
                        <span class="inline-flex items-center gap-1 rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-700">
                            <i class="bi bi-eye"></i>
                            Read-only access
                        </span>
                    @endif
                </div>
                <p class="admin-table-head-copy">
                    @if($isBranchAdmin)
                        Branch admins can view packages but cannot modify pricing or package details.
                    @else
                        Manage pricing, inclusions, freebies, promos, and availability.
                    @endif
                </p>
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
                @if($isMainAdmin)
                    <a
                        href="{{ route('admin.packages.create') }}"
                        class="btn btn-primary-custom btn-sm"
                    >
                        <i class="bi bi-plus-circle"></i>
                        <span>Add Package</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="table-system-toolbar">
        <form
            method="GET"
            action="{{ route('admin.packages.index') }}"
            class="table-toolbar"
            data-table-toolbar
            data-live-search-suggestions
            data-live-search-commit-only
            data-search-debounce="400"
            style="grid-template-columns:minmax(260px, 1.8fr) minmax(170px, .75fr) minmax(170px, .75fr) auto;"
        >
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Search</label>
                <div class="table-toolbar-input-wrap">
                    <i class="bi bi-search table-toolbar-leading-icon" aria-hidden="true"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search packages..."
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
                <label class="table-toolbar-label">Promo</label>
                <div class="table-toolbar-select-wrap">
                    <i class="bi bi-tag table-toolbar-leading-icon" aria-hidden="true"></i>
                    <select name="promo" class="form-select table-toolbar-select" data-table-auto-submit>
                        <option value="">All Promos</option>
                        <option value="with_promo" {{ request('promo') === 'with_promo' ? 'selected' : '' }}>With Promo</option>
                        <option value="no_promo" {{ request('promo') === 'no_promo' ? 'selected' : '' }}>No Promo</option>
                    </select>
                    <i class="bi bi-chevron-down table-toolbar-select-icon" aria-hidden="true"></i>
                </div>
            </div>
            <div class="table-toolbar-field">
                <label class="table-toolbar-label">Sort</label>
                <div class="table-toolbar-select-wrap">
                    <i class="bi bi-sort-alpha-down table-toolbar-leading-icon" aria-hidden="true"></i>
                    <select name="sort" class="form-select table-toolbar-sort" data-table-sort>
                        <option value="name_asc" {{ request('sort', 'name_asc') === 'name_asc' ? 'selected' : '' }}>A-Z</option>
                        <option value="updated_desc" {{ request('sort') === 'updated_desc' ? 'selected' : '' }}>Newest</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Highest Price</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Lowest Price</option>
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
    <div x-show="view === 'card'" x-cloak class="directory-card-view">

        @if($packages->isEmpty())
            {{-- Empty state --}}
            <div class="flex flex-col items-center justify-center py-16 gap-3 text-center">
                <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                    <i class="bi bi-box-seam text-2xl text-slate-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800 text-sm">No packages found</h3>
                    <p class="text-xs text-slate-500 mt-1">Try changing the search or filters, or add a new package.</p>
                </div>
                @if($isMainAdmin)
                    <a
                        href="{{ route('admin.packages.create') }}"
                        class="btn btn-primary-custom btn-sm bg-[var(--brand-mid)] border-[var(--brand-mid)] text-white inline-flex items-center gap-1.5 mt-1"
                    >
                        <i class="bi bi-plus-circle"></i>
                        Add Package
                    </a>
                @endif
            </div>

        @else
            {{-- Package cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($packages as $package)
                @php
                    $display = $packageDisplay($package);
                    $includedCasket = $display['casket'];
                    $inclusionItems = $display['inclusions'];
                    $freebieItems = $display['freebies'];
                    $visibleInclusions = array_slice($inclusionItems, 0, 3);
                    $visibleFreebies = array_slice($freebieItems, 0, 3);
                @endphp
                <div
                    class="pkg-card"
                    data-live-search-row
                    data-live-search-title="{{ $package->name }}"
                    data-live-search-meta="{{ ($includedCasket ?? 'No casket') . ' / ₱' . number_format((float) $package->price, 2) }}"
                    data-live-search-text="{{ $package->name }} {{ $includedCasket }} {{ implode(' ', $inclusionItems) }} {{ implode(' ', $freebieItems) }}"
                >
                    {{-- Card header --}}
                    <div class="pkg-card-head">
                        <div class="flex-1 min-w-0">
                            <h3 class="pkg-card-title">{{ $package->name }}</h3>
                            @if($includedCasket)
                                <p class="pkg-card-casket"><i class="bi bi-box2-heart"></i> {{ $includedCasket }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="pkg-card-price">
                        <div>
                            <p class="pkg-card-kicker">Base Price</p>
                            <p class="pkg-card-amount">&#8369;{{ number_format((float) $package->price, 2) }}</p>
                        </div>
                    </div>

                    {{-- Promo --}}
                    <div class="pkg-card-promo">
                        @if($package->promo_is_active && $package->promo_value_type && $package->promo_value)
                            @if($package->is_promo_expired)
                                {{-- Toggle is on but end date has passed --}}
                                <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-500 border border-slate-200 rounded-full px-2.5 py-1 text-xs font-semibold">
                                    <i class="bi bi-tag text-[10px]"></i>
                                    {{ $package->promo_label ?: 'Promo' }} — Expired
                                </span>
                                <p class="text-[11px] text-red-400 mt-1.5 ml-0.5">
                                    <i class="bi bi-clock-history"></i>
                                    Ended {{ $package->promo_ends_at->format('M d, Y') }}
                                </p>
                            @elseif($package->is_promo_scheduled)
                                {{-- Toggle is on but start date hasn't arrived yet --}}
                                <span class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-600 border border-blue-200 rounded-full px-2.5 py-1 text-xs font-semibold">
                                    <i class="bi bi-tag text-[10px]"></i>
                                    {{ $package->promo_label ?: 'Promo' }} — Scheduled
                                </span>
                                <p class="text-[11px] text-slate-400 mt-1.5 ml-0.5">
                                    <i class="bi bi-calendar-event"></i>
                                    Starts {{ $package->promo_starts_at->format('M d, Y') }}
                                </p>
                            @else
                                {{-- Promo is live --}}
                                <span class="inline-flex items-center gap-1.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full px-2.5 py-1 text-xs font-semibold">
                                    <i class="bi bi-tag-fill text-[10px]"></i>
                                    {{ $package->promo_label ?: 'Promo Active' }}
                                    &mdash;
                                    @if($package->promo_value_type === 'PERCENT')
                                        {{ number_format((float) $package->promo_value, 2) }}% off
                                    @else
                                        &#8369;{{ number_format((float) $package->promo_value, 2) }} off
                                    @endif
                                </span>
                                @if($package->promo_starts_at || $package->promo_ends_at)
                                    <p class="text-[11px] text-slate-400 mt-1.5 ml-0.5">
                                        {{ $package->promo_starts_at?->format('M d, Y') ?? '—' }}
                                        &ndash;
                                        {{ $package->promo_ends_at?->format('M d, Y') ?? 'Ongoing' }}
                                    </p>
                                @endif
                            @endif
                        @else
                            <span class="pkg-muted-text">No active promo</span>
                        @endif
                    </div>

                    {{-- Inclusions & Freebies --}}
                    <div class="pkg-card-lists">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5 flex items-center gap-1">
                                <i class="bi bi-check2-all"></i> Inclusions
                            </p>
                            @if($inclusionItems)
                                <ul class="space-y-1 text-xs text-slate-600 leading-relaxed">
                                    @foreach($visibleInclusions as $item)
                                        <li class="flex gap-1.5"><span class="text-emerald-600">&bull;</span><span>{{ $item }}</span></li>
                                    @endforeach
                                </ul>
                                @if(count($inclusionItems) > 3)
                                    <p class="mt-1 text-[11px] font-semibold text-slate-400">+{{ count($inclusionItems) - 3 }} more</p>
                                @endif
                            @else
                                <p class="text-xs text-slate-400 italic">None listed</p>
                            @endif
                        </div>
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 mb-1.5 flex items-center gap-1">
                                <i class="bi bi-gift"></i> Freebies
                            </p>
                            @if($freebieItems)
                                <ul class="space-y-1 text-xs text-slate-600 leading-relaxed">
                                    @foreach($visibleFreebies as $item)
                                        <li class="flex gap-1.5"><span class="text-amber-600">&bull;</span><span>{{ $item }}</span></li>
                                    @endforeach
                                </ul>
                                @if(count($freebieItems) > 3)
                                    <p class="mt-1 text-[11px] font-semibold text-slate-400">+{{ count($freebieItems) - 3 }} more</p>
                                @endif
                            @else
                                <p class="text-xs text-slate-400 italic">None listed</p>
                            @endif
                        </div>
                    </div>

                    {{-- Card footer --}}
                    <div class="pkg-card-footer">
                        <span class="text-[11px] text-slate-400">
                            <i class="bi bi-clock text-[10px] mr-0.5"></i>
                            {{ $package->updated_at?->diffForHumans() ?? '—' }}
                        </span>
                        @if($isMainAdmin)
                            <button
                                type="button"
                                data-package-id="{{ $package->id }}"
                                data-edit-url="{{ route('admin.packages.edit', $package) }}"
                                data-package-view-trigger
                                class="pkg-card-edit"
                                aria-label="View {{ $package->name }}"
                            >
                                <i class="bi bi-eye"></i>
                                <span>View Package</span>
                            </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Card view pagination --}}
            <div class="table-system-pagination directory-card-pagination">
                @if($packages->hasPages()){{ $packages->links() }}@endif
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════
         TABLE VIEW
    ═══════════════════════════════════════════ --}}
    <div x-show="view === 'table'" x-cloak class="package-table-view">
        <div class="table-system-list">
            <div class="table-wrapper table-system-wrap">
                <table class="table-base table-system-table">
                    <thead>
                        <tr>
                            <th class="text-left">Name</th>
                            <th class="text-left">Included Casket</th>
                            <th class="text-left">Price</th>
                            <th class="text-left">Inclusions</th>
                            <th class="text-left">Freebies</th>
                            <th class="text-left">Promo</th>
                            @if($isMainAdmin)
                                <th class="table-col-actions">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($packages as $package)
                        @php
                            $display = $packageDisplay($package);
                            $includedCasket = $display['casket'];
                            $inclusionItems = $display['inclusions'];
                            $freebieItems = $display['freebies'];
                        @endphp
                        <tr
                            data-live-search-row
                            data-live-search-title="{{ $package->name }}"
                            data-live-search-meta="{{ ($includedCasket ?? 'No casket') . ' / ₱' . number_format((float) $package->price, 2) }}"
                            data-live-search-text="{{ $package->name }} {{ $includedCasket }} {{ implode(' ', $inclusionItems) }} {{ implode(' ', $freebieItems) }}"
                        >
                            <td class="table-primary">{{ $package->name }}</td>
                            <td>{{ $includedCasket ?? '—' }}</td>
                            <td>
                                <span class="font-semibold text-slate-900">&#8369;{{ number_format((float) $package->price, 2) }}</span>
                            </td>
                            <td class="table-secondary">
                                @if($inclusionItems)
                                    <ul class="space-y-1">
                                        @foreach(array_slice($inclusionItems, 0, 3) as $item)
                                            <li>&bull; {{ $item }}</li>
                                        @endforeach
                                    </ul>
                                    @if(count($inclusionItems) > 3)
                                        <span class="text-[11px] font-semibold text-slate-400">+{{ count($inclusionItems) - 3 }} more</span>
                                    @endif
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="table-secondary">
                                @if($freebieItems)
                                    <ul class="space-y-1">
                                        @foreach(array_slice($freebieItems, 0, 3) as $item)
                                            <li>&bull; {{ $item }}</li>
                                        @endforeach
                                    </ul>
                                    @if(count($freebieItems) > 3)
                                        <span class="text-[11px] font-semibold text-slate-400">+{{ count($freebieItems) - 3 }} more</span>
                                    @endif
                                @else
                                    None listed
                                @endif
                            </td>
                            <td>
                                @if($package->promo_is_active && $package->promo_value_type && $package->promo_value)
                                    @if($package->is_promo_expired)
                                        <div class="text-xs font-semibold text-slate-400">
                                            {{ $package->promo_label ?: 'Promo' }}
                                            <span class="text-red-400 ml-1">— Expired</span>
                                        </div>
                                        <div class="text-[11px] text-red-400 mt-0.5">
                                            <i class="bi bi-clock-history"></i>
                                            Ended {{ $package->promo_ends_at->format('M d, Y') }}
                                        </div>
                                    @elseif($package->is_promo_scheduled)
                                        <div class="text-xs font-semibold text-blue-600">
                                            {{ $package->promo_label ?: 'Promo' }}
                                            <span class="ml-1">— Scheduled</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            <i class="bi bi-calendar-event"></i>
                                            Starts {{ $package->promo_starts_at->format('M d, Y') }}
                                        </div>
                                    @else
                                        <div class="text-xs font-semibold text-emerald-700">{{ $package->promo_label ?: 'Promo' }}</div>
                                        <div class="table-secondary">
                                            {{ $package->promo_value_type === 'PERCENT'
                                                ? number_format((float) $package->promo_value, 2) . '%'
                                                : number_format((float) $package->promo_value, 2) }}
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            {{ $package->promo_starts_at?->format('M d, Y') ?? 'No start' }}
                                            &ndash;
                                            {{ $package->promo_ends_at?->format('M d, Y') ?? 'Ongoing' }}
                                        </div>
                                    @endif
                                @else
                                    <span class="table-secondary">No active promo</span>
                                @endif
                            </td>
                            @if($isMainAdmin)
                                <td class="table-col-actions">
                                    <button
                                        type="button"
                                        data-package-id="{{ $package->id }}"
                                        data-edit-url="{{ route('admin.packages.edit', $package) }}"
                                        data-package-view-trigger
                                        class="table-row-action-link"
                                        aria-label="View {{ $package->name }}"
                                    >
                                        <i class="bi bi-eye"></i>
                                        <span>View</span>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isMainAdmin ? 7 : 6 }}" class="table-system-empty">No packages found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="table-system-pagination">
                @if($packages->hasPages()){{ $packages->links() }}@endif
            </div>
        </div>
    </div>

</section>
</div>{{-- end space-y-6 --}}
</div>{{-- end centered container --}}
</div>{{-- end admin-table-page --}}

@foreach($packages as $package)
    @php
        $display = $packageDisplay($package);
        $casketRow = $package->packageInclusions->firstWhere('service_type', \App\Models\Package::SERVICE_CASKET);
        $customRows = $package->packageInclusions->filter(fn ($item) => $item->is_custom || $item->service_type === \App\Models\Package::SERVICE_CUSTOM);
        $serviceRows = $package->packageInclusions->reject(fn ($item) => $item->is_custom || $item->service_type === \App\Models\Package::SERVICE_CUSTOM || $item->service_type === \App\Models\Package::SERVICE_CASKET);
    @endphp
    <template id="packageDetailTemplate{{ $package->id }}">
        <div class="pkg-detail-view" data-package-detail-view>
            <div class="pkg-detail-head">
                <div>
                    <p class="pkg-card-kicker">Package Details</p>
                    <h2>{{ $package->name }}</h2>
                    <div class="pkg-detail-badges">
                        <span class="pkg-detail-badge"><i class="bi bi-box2-heart"></i>{{ $display['casket'] ?: 'No casket selected' }}</span>
                        <span class="pkg-detail-badge"><i class="bi bi-gift"></i>{{ $package->packageFreebies->count() }} freebies</span>
                        @if($package->promo_is_active && $package->promo_value_type && $package->promo_value)
                            <span class="pkg-detail-badge"><i class="bi bi-tag"></i>{{ $package->promo_status }}</span>
                        @else
                            <span class="pkg-detail-badge"><i class="bi bi-tag"></i>No active promo</span>
                        @endif
                    </div>
                    @if($package->short_description)
                        <p>{{ $package->short_description }}</p>
                    @endif
                </div>
                <div class="pkg-detail-price">
                    <span>Base Price</span>
                    <strong>&#8369;{{ number_format((float) $package->price, 2) }}</strong>
                </div>
            </div>

            <div class="pkg-detail-grid">
                <section>
                    <h3><i class="bi bi-box2-heart"></i> Included Casket / Coffin</h3>
                    <p>{{ $display['casket'] ?: 'No casket selected.' }}</p>
                </section>
                <section>
                    <h3><i class="bi bi-tag"></i> Promo</h3>
                    @if($package->promo_is_active && $package->promo_value_type && $package->promo_value)
                        <p>{{ $package->promo_label ?: 'Promo' }} - {{ $package->promo_status }}</p>
                        <small>
                            {{ $package->promo_value_type === 'PERCENT' ? number_format((float) $package->promo_value, 2) . '% off' : 'PHP ' . number_format((float) $package->promo_value, 2) . ' off' }}
                            · {{ $package->promo_starts_at?->format('M d, Y') ?? 'No start' }} to {{ $package->promo_ends_at?->format('M d, Y') ?? 'Ongoing' }}
                        </small>
                    @else
                        <p>No active promo for this package.</p>
                    @endif
                </section>
                <section>
                    <h3><i class="bi bi-check2-square"></i> Included Services</h3>
                    @if($serviceRows->isNotEmpty())
                        <ul>
                            @foreach($serviceRows as $row)
                                <li>
                                    <span>{{ \App\Models\Package::serviceTypeOptions()[$row->service_type] ?? $row->inclusion_name }}</span>
                                    @if($row->included_kilometers !== null)
                                        <small>{{ $row->included_kilometers }} km included · ₱{{ number_format((float) $row->price_per_excess_kilometer, 2) }}/excess km</small>
                                    @elseif($row->included_days !== null)
                                        <small>{{ $row->included_days }} day(s) included · ₱{{ number_format((float) $row->price_per_extended_day, 2) }}/extended day</small>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p>No structured service limits listed.</p>
                    @endif
                </section>
                <section>
                    <h3><i class="bi bi-list-check"></i> Other Package Inclusions</h3>
                    @if($customRows->isNotEmpty())
                        <ul>
                            @foreach($customRows as $row)
                                <li>{{ $row->inclusion_name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p>No other inclusions listed.</p>
                    @endif
                </section>
                <section>
                    <h3><i class="bi bi-gift"></i> Freebies</h3>
                    @if($package->packageFreebies->isNotEmpty())
                        <ul>
                            @foreach($package->packageFreebies as $freebie)
                                <li>{{ $freebie->quantity ?: 1 }} {{ $freebie->unit ?: 'item' }} - {{ $freebie->freebie_name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p>No freebies listed.</p>
                    @endif
                </section>
            </div>

            <div class="pkg-detail-actions">
                @if($isMainAdmin)
                    <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-primary-custom">
                        <i class="bi bi-pencil-square"></i> Edit Package
                    </a>
                @endif
                <button type="button" class="pkg-detail-close" data-package-detail-close>
                    <i class="bi bi-x-lg"></i>
                    <span>Close</span>
                </button>
            </div>
        </div>
    </template>
@endforeach

{{-- Package modal --}}
<div id="packageModalOverlay" class="fixed inset-0 hidden flex items-center justify-center transition-opacity duration-200 font-ui-body" style="z-index: 1300;">
    <div id="packageModalSheet" class="relative w-[92vw] max-w-4xl max-h-[92vh] overflow-hidden transform transition-all duration-200 scale-95 opacity-0 font-ui-body">
        <div id="packageModalContent" class="overflow-y-auto max-h-[84vh]" style="padding:1.25rem 1.5rem;">
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-6 h-6 rounded-full animate-spin" style="border:2px solid #e2e8f0;border-top-color:#475569"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function pkgCatalog() {
        return {
            view: localStorage.getItem('pkg-view') || 'card',
            setView(v) {
                this.view = v;
                localStorage.setItem('pkg-view', v);
            },
        };
    }

    (function () {
        const overlay  = document.getElementById('packageModalOverlay');
        const sheet    = document.getElementById('packageModalSheet');
        const content  = document.getElementById('packageModalContent');
        let activePackageId = null;
        if (!overlay || !sheet || !content) return;

        function openModal() {
            overlay.classList.remove('hidden');
            requestAnimationFrame(() => requestAnimationFrame(() => {
                overlay.style.opacity = '1';
                sheet.classList.remove('scale-95', 'opacity-0');
                sheet.classList.add('scale-100', 'opacity-100');
            }));
        }

        function closeModal() {
            sheet.classList.add('scale-95', 'opacity-0');
            sheet.classList.remove('scale-100', 'opacity-100');
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.classList.add('hidden');
                content.innerHTML = '';
                activePackageId = null;
            }, 200);
        }

        function showPackageView(packageId) {
            const template = document.getElementById('packageDetailTemplate' + packageId);
            if (!template) return;
            activePackageId = packageId;
            openModal();
            content.innerHTML = '';
            content.appendChild(template.content.cloneNode(true));
        }

        document.addEventListener('click', function (e) {
            const viewTrigger = e.target.closest('[data-package-view-trigger]');
            if (viewTrigger) {
                e.preventDefault();
                showPackageView(viewTrigger.dataset.packageId);
                return;
            }

            if (e.target.closest('[data-package-detail-close]')) {
                e.preventDefault();
                closeModal();
                return;
            }

        });

        document.addEventListener('keydown', function (e) {
            if ((e.key === 'Enter' || e.key === ' ') && e.target.closest('[data-package-view-trigger]')) {
                e.preventDefault();
                const trigger = e.target.closest('[data-package-view-trigger]');
                showPackageView(trigger.dataset.packageId);
            }
            if (e.key === 'Escape' && !overlay.classList.contains('hidden')) closeModal();
        });

        overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    })();
</script>
@endsection
