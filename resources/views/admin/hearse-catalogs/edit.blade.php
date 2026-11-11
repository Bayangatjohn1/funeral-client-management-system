@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')
@section('title', 'Edit Hearse')
@section('content')
    @include('admin.hearse-catalogs._form')
@endsection
