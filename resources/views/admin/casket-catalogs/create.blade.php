@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Add Casket')
@section('page_desc', 'Create a casket or coffin option for package setup.')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.casket-catalogs._form')
@endsection
