@extends('layouts.panel')

@section('page_title', ($entryMode ?? null) === 'other' ? 'Case Intake - Other Branch' : 'Case Intake - Main Branch')
@section('hide_layout_topbar', '1')

@push('styles')
<style>
    body, .panel-shell-body, .app-shell, .main-area, .page-content {
        background: transparent !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .main-area { padding: 0 !important; }
    .topbar { padding: 0 18px !important; margin: 0 !important; }
    @media (max-width: 1023px) {
        .topbar { padding: 0 16px !important; }
    }
    .page-content {
        overflow: visible !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        background: transparent !important;
    }
    .page-content > * { width: 100% !important; max-width: 100% !important; }
    .intake-root {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        background: transparent !important;
        min-height: calc(100vh - var(--topbar-h));
    }
    /* Keep the intake workspace clean by removing the layout page heading. */
    .topbar-heading,
    .panel-page-header { display: none !important; }
</style>
@endpush

@section('content')
@if(session('success'))
    <div class="flash-success mb-4">
        {{ session('success') }}
    </div>
@endif
@if(session('warning'))
    <div class="flash-warning mb-4">
        {{ session('warning') }}
    </div>
@endif

@if(session('summary'))
    <div class="mb-4 rounded border border-[var(--color-border)] bg-[var(--color-bg-surface)] p-4 text-sm text-[var(--color-text-secondary)]">
        <div class="font-semibold mb-2">Last Saved Summary</div>
        <div>Package: {{ session('summary.package') }}</div>
        <div>Subtotal: {{ number_format(session('summary.subtotal'), 2) }}</div>
        <div>Discount: {{ number_format(session('summary.discount'), 2) }}</div>
        <div>Discount Rule: {{ session('summary.discount_source', 'NONE') }}</div>
        <div>Total: {{ number_format(session('summary.total'), 2) }}</div>
        <div>Payment Status: {{ session('summary.payment_status') }}</div>
    </div>
@endif

@if($errors->any())
    <div class="flash-error mb-4">
        {{ $errors->first() }}
    </div>
@endif

@php
    $intakeErrorField = $errors->any() ? array_key_first($errors->toArray()) : null;
    $intakeErrorStep = match ($intakeErrorField) {
        'branch_id', 'client_name', 'client_relationship', 'client_contact_number', 'client_email',
        'client_valid_id_type', 'client_valid_id_number', 'client_address', 'reporter_name', 'reporter_contact', 'reported_at' => 1,
        'deceased_name', 'deceased_address', 'born', 'died', 'gender', 'civil_status', 'senior_citizen_status',
        'senior_citizen_id_number', 'pwd_status', 'pwd_id_number', 'deceased_photo' => 2,
        'service_requested_at', 'wake_location', 'wake_start_date', 'wake_start_time', 'funeral_service_at',
        'funeral_service_time', 'interment_at', 'interment_time', 'wake_days', 'place_of_cemetery', 'case_status' => 3,
        'package_id' => 4,
        'additional_services', 'additional_service_amount' => 5,
        'mark_as_paid', 'payment_type', 'paid_at', 'amount_paid', 'payment' => 6,
        default => 1,
    };
@endphp

@php($backUrl = !empty($entryMode) && $entryMode === 'other'
    ? route('funeral-cases.other-reports')
    : route('funeral-cases.index', ['record_scope' => 'main']))
@php($cancelUrl = $backUrl)
@php($initialStep = $intakeErrorStep ?? 1)
@include('staff.intake._form')
@endsection
