@extends('layouts.panel')

@section('page_title', !empty($entryMode) && $entryMode === 'other' ? 'Other Branch Intake Form' : 'Main Branch Intake Form')

@section('content')
@if(session('success'))
    <div class="mb-4 bg-green-50 border p-3 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif
@if(session('warning'))
    <div class="mb-4 bg-amber-50 border p-3 text-amber-700 rounded">
        {{ session('warning') }}
    </div>
@endif

@if(session('summary'))
    <div class="mb-4 bg-white border rounded p-4 text-sm">
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
    <div class="mb-4 bg-red-50 border p-3 text-red-700 rounded">
        {{ $errors->first() }}
    </div>
@endif

@php
    $intakeErrorField = $errors->any() ? array_key_first($errors->toArray()) : null;
    $intakeErrorStep = match ($intakeErrorField) {
        'service_requested_at', 'branch_id', 'client_name', 'client_relationship', 'client_contact_number', 'client_email',
        'client_valid_id_type', 'client_valid_id_number', 'client_address', 'reporter_name', 'reporter_contact', 'reported_at' => 1,
        'deceased_name', 'deceased_address', 'born', 'died', 'gender', 'civil_status', 'senior_citizen_status',
        'senior_citizen_id_number', 'pwd_status', 'pwd_id_number', 'deceased_photo' => 2,
        'wake_location', 'funeral_service_at', 'interment_at', 'wake_days', 'place_of_cemetery', 'case_status' => 3,
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

