@extends('layouts.panel')

@section('page_title', 'Add Package')
@section('page_desc', 'Create a structured service package with included services, freebies, and promo settings.')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.packages._form')
@endsection
