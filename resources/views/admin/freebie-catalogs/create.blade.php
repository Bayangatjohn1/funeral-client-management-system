@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Add Freebie')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.freebie-catalogs._form')
@endsection
