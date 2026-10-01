@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Add Add-on')
@section('page_desc', 'Create a reusable add-on for service packages and case intake.')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.add-on-catalogs._form')
@endsection
