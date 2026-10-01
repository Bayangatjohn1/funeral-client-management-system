@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Edit Add-on')
@section('page_desc', 'Update add-on details, pricing, category, and availability.')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.add-on-catalogs._form')
@endsection
