@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Add Freebie')
@section('page_desc', 'Create a reusable freebie for service package setup.')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.freebie-catalogs._form')
@endsection
