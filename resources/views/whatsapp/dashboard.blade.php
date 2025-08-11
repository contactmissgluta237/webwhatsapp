@extends('modern.layouts.master')

@section('title', __('WhatsApp Dashboard'))

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ __('WhatsApp Automation') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}"><i class="la la-dashboard"></i>{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('WhatsApp') }}</li>
            </ol>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

@livewire('whats-app.dashboard')
@endsection
