@extends('layouts.panel')

@section('page_title', 'Case Details')
@section('page_desc', 'Full case information, financials, and transaction history.')

@section('content')
@php
    $defaultReturnUrl = route('owner.history');
    $requestedReturnUrl = request()->query('return_to');
    $previousUrl = url()->previous();
    $currentUrl = request()->fullUrl();
    $returnUrl = is_string($requestedReturnUrl) && $requestedReturnUrl !== ''
        ? $requestedReturnUrl
        : ($previousUrl !== $currentUrl ? $previousUrl : $defaultReturnUrl);

    if (
        !is_string($returnUrl)
        || $returnUrl === ''
        || !\Illuminate\Support\Str::startsWith($returnUrl, [url('/'), '/'])
    ) {
        $returnUrl = $defaultReturnUrl;
    }
@endphp

<div class="owner-page-shell owner-case-details-shell">

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    @include('partials.case_view_content')

    <div class="flex gap-2 mt-4">
        <a href="{{ $returnUrl }}" class="btn-outline">
            <i class="bi bi-arrow-left mr-1"></i>Back
        </a>
    </div>

</div>
@endsection
