@extends(request()->boolean('modal') ? 'layouts.modal-frame' : 'layouts.panel')

@section('page_title', 'Edit Package')
@section('page_desc', 'Update structured package details, included services, freebies, and promo settings.')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.packages._form', ['package' => $package])
@endsection
