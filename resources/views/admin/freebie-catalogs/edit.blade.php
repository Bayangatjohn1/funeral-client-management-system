@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Edit Freebie')
@section('page_desc', 'Update freebie details, category, and availability.')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.freebie-catalogs._form')
@endsection
