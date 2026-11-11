@extends($intakeModal ? 'layouts.intake-frame' : 'layouts.panel')

@section('page_title', ($entryMode ?? null) === 'other' ? 'Case Intake - Other Branch' : 'Case Intake - Main Branch')
@section('page_desc', ($entryMode ?? null) === 'other' ? 'Record a completed case reported by another branch.' : 'Record client, deceased, service, and payment details for a new case.')
@section('hide_layout_topbar', '1')
@section('suppress_layout_page_header', '1')

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
    .topbar-heading { display: none !important; }
</style>
@endpush

@section('content')
@if(session('intake_case_saved_toast'))
    @include('partials.centered-success-toast', ['toastId' => 'intake-case-saved-toast', 'title' => 'Case saved successfully', 'message' => 'Your new case was saved. You can continue with another intake.'])
@elseif(session('success'))
    <div class="flash-success mb-4">
        {{ session('success') }}
    </div>
@endif
@if(session('warning'))
    <div class="flash-warning mb-4">
        {{ session('warning') }}
    </div>
@endif


@if($errors->any())
    <div class="flash-error mb-4">
        {{ $errors->first() }}
    </div>
@endif

@php
    $intakeErrorField = $errors->any() ? array_key_first($errors->toArray()) : null;
    $intakeErrorFieldRoot = $intakeErrorField ? explode('.', $intakeErrorField, 2)[0] : null;
    $intakeErrorStep = match ($intakeErrorFieldRoot) {
        'branch_id', 'client_first_name', 'client_last_name', 'client_middle_name', 'client_suffix',
        'existing_client_id', 'client_relationship', 'client_contact_number', 'client_email',
        'client_valid_id_type', 'client_valid_id_number', 'client_address', 'reporter_name', 'reporter_contact', 'reported_at' => 1,
        'deceased_first_name', 'deceased_last_name', 'deceased_middle_name', 'deceased_suffix',
        'deceased_address', 'born', 'died', 'gender', 'civil_status', 'senior_citizen_status',
        'senior_citizen_id_number', 'pwd_status', 'pwd_id_number', 'deceased_photo' => 2,
        'package_id', 'custom_package_name', 'custom_package_price', 'custom_package_inclusions',
        'custom_package_freebies', 'custom_service_types', 'custom_service_coverage',
        'custom_casket_catalog_id', 'hearse_catalog_id', 'custom_freebie_catalog_ids', 'custom_other_inclusions',
        'selected_add_ons', 'replacement_casket_catalog_id',
        'apply_retrieval_excess', 'retrieval_excess_kilometers', 'apply_hearse_excess',
        'hearse_excess_kilometers', 'actual_retrieval_kilometers', 'actual_hearse_kilometers',
        'service_requested_at', 'viewing_type', 'wake_location', 'chapel_address', 'chapel_add_on_id', 'wake_start_date', 'wake_start_time', 'wake_end_date', 'wake_end_time', 'funeral_service_at',
        'funeral_service_time', 'funeral_ceremony_location', 'funeral_ceremony_location_mode', 'interment_at', 'interment_time', 'wake_days', 'place_of_cemetery', 'case_status',
        'transport_option', 'transport_notes', 'coffin_length_cm', 'coffin_size',
        'embalming_required', 'embalming_status', 'embalming_at', 'embalming_notes' => 3,
        'additional_services', 'additional_service_amount', 'additional_service_items',
        'mark_as_paid', 'payment_type', 'paid_at', 'amount_paid', 'payment', 'payment_method',
        'payment_proof_id', 'payment_proof_form_id', 'receipt_or_no', 'reference_number', 'cashless_type', 'bank_name', 'other_bank_name', 'account_name', 'mobile_number',
        'payment_notes', 'approval_code', 'card_type', 'terminal_provider', 'wallet_provider',
        'payment_channel', 'bank_reference' => 4,
        'confirm_review' => 5,
        default => 1,
    };
@endphp

@php($backUrl = !empty($entryMode) && $entryMode === 'other'
    ? route('funeral-cases.other-reports')
    : route('funeral-cases.index', ['record_scope' => 'main']))
@php($cancelUrl = $returnTo ?? $backUrl)
@php($initialStep = $intakeErrorStep ?? 1)
@if($intakeModal)
    @php($formAction = ($historicalMode ?? false)
        ? route('historical-intake.store', ['intake_modal' => 1])
        : route('intake.main.store', ['intake_modal' => 1]))
@endif
@include('staff.intake._form', ['showIntakePageHeading' => !$intakeModal])
@endsection
