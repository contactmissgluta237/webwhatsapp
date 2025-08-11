@extends('modern.layouts.master')

@section('title', __('Customer details'))

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ __('Customer details') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="la la-dashboard"></i>{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Customer details') }}</li>
            </ol>
        </div>
    </div>
    <div class="col-4 p-0">
        <div class="d-flex justify-content-end">
            <a href="{{ route('admin.users.index') }}" class="btn btn-info"><i class="la la-reply"></i>{{ __('Back to Users') }}</a>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

@livewire('admin.customer-details.customer-details', ['customer' => $customer])
@endsection