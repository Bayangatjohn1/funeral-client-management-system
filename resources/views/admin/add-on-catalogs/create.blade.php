@extends('layouts.panel')

@section('page_title', 'Add Add-on')
@section('hide_layout_topbar', '1')

@section('content')
    @include('admin.add-on-catalogs._form')
@endsection
