@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Add Package')
@section('page_desc', 'Create a structured service package with included services, freebies, and promo settings.')
@section('hide_layout_topbar', '1')
@section('suppress_layout_page_header', '1')

@section('content')
    @include('admin.packages._form')
@endsection
