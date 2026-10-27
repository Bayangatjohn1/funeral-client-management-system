@extends('layouts.panel')

@section('page_title', ($privilegedAccount ?? false) ? 'Create System Admin Account' : (($accountRole ?? 'staff') === 'branch_admin' ? 'Create Branch Admin Account' : 'Create Staff Account'))
@section('page_desc', ($privilegedAccount ?? false) ? 'Create a personal administrative login with required MFA.' : 'Create an account for the selected role and assigned branch.')
@section('hide_layout_topbar', '1')
@section('content')
@include('partials.user_management_visual_styles')
@include('admin.users._create_form')
@endsection
