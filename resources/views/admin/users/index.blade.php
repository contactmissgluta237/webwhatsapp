@extends('modern.layouts.master')

@section('title', __('User Management'))

@section('content')

<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ __('User Management') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="la la-home"></i>{{ __('Home') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Users') }}</li>
            </ol>
        </div>
    </div>
    <div class="col-4 p-0">
        <div class="d-flex justify-content-end">
            <a href="{{ route('admin.users.create') }}" class="btn btn-info">{{ __('Create a new user') }}</a>
        </div>
    </div>
</div>

<!-- Ticket start -->
<div class="row ticket-app p-0">
    <div class="col-12 p-0">
        <div class="card card-border">
            <div class="card-header">
                <h4 class="card-title">
                    {{ __('Users List') }}
                </h4>
            </div>
            <div class="card-body">
                <div class="app-scroll app-datatable-default">
                    @livewire('admin.users.user-data-table')
                </div>
            </div>  
        </div>
    </div>
</div>
<!-- Ticket end -->
@endsection
