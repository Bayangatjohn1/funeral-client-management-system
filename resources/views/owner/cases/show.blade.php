@extends('layouts.panel')

@section('page_title', 'Case Details')
@section('page_desc', 'Full case information, financials, and transaction history.')

@section('content')
<div class="owner-page-shell">

    @if(session('success'))
        <div class="flash-success">{{ session('success') }}</div>
    @endif

    @include('partials.case_view_content')

    <div class="flex gap-2 mt-4">
        <a href="{{ route('owner.history') }}" class="btn-outline">
            <i class="bi bi-arrow-left mr-1"></i>Back to Case History
        </a>
    </div>

</div>
@endsection
