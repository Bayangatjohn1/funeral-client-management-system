@extends('layouts.panel')
@section('page_title','Collection Records')
@section('page_desc','Valid receipts by payment date. Open a case to review full information.')
@section('hide_layout_topbar','1')
@section('content')
<div class="owner-page-shell owner-collections-page">
    <div class="flex justify-end"><a class="btn-outline" href="{{ $dashboardUrl }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Dashboard</a></div>
    <form method="GET" action="{{ route('owner.collections') }}" class="filter-panel owner-collections-filters">
        <input type="hidden" name="range" value="CUSTOM">
        @foreach(request()->only(['dashboard_branch','dashboard_range','dashboard_from','dashboard_to']) as $name=>$value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
        <label class="flex-1">Search<input name="q" value="{{ $search }}" maxlength="100" class="input-custom w-full" placeholder="Payment record, case, or client"></label>
        <label>Branch<select name="branch_id" class="input-custom"><option value="">All Branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($branchId===$branch->id)>{{ $branch->branch_code }} — {{ $branch->branch_name }}</option>@endforeach</select></label>
        <label>Payment date from<input name="date_from" type="date" value="{{ $dateFrom }}" required></label><label>Payment date to<input name="date_to" type="date" value="{{ $dateTo }}" min="{{ $dateFrom }}" required></label>
        <button class="btn-primary" type="submit">Apply</button><a class="btn-outline" href="{{ request()->fullUrlWithoutQuery(['q','page']) }}">Clear Search</a>
    </form>
    @if($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif
    <div class="oe oe-records-page oe-card">
        @include('owner.partials.executive-details')
        <div>{{ $records->links() }}</div>
    </div>
</div>
@include('owner.partials.executive-styles')
@endsection
