@extends('layouts.panel')
@section('page_title', 'Owner Dashboard')
@section('page_desc', 'Business performance and current collection exposure.')
@section('hide_layout_topbar', '1')
@section('suppress_layout_page_header', '1')
@section('content')
@include('owner.partials.executive-dashboard')
@endsection