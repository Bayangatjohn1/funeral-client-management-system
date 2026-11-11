@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Catalog Photo')

@section('content')
    @include('admin.service-management.partials.quick-photo-form')
@endsection
