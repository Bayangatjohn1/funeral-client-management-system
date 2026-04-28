@extends('layouts.panel')

@section('page_title','Edit Client')
@section('page_desc', 'Update client details and contact information.')

@section('content')
@include('staff.clients.partials.edit-form')
@endsection
