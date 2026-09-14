@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Edit Casket')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.casket-catalogs._form')
@endsection
