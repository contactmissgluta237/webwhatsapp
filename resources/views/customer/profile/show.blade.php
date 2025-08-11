@extends('modern.layouts.master')

@section('title', 'Mon profil')

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-12 p-0">
        <h2 class="content-header-title mb-0">{{ __('profile.my_profile') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}"><i class="la la-home"></i>{{__('Home') }}</a></li>
                <li class="breadcrumb-item active">{{ __('profile.my_profile') }}</li>
            </ol>
        </div>
    </div>
</div>
<!-- Breadcrumb end -->

<div class="row">
    <div class="col-12 p-0">
        @livewire('shared.profile-form', ['userType' => 'customer'])
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <x-push-notification-profile :user="auth()->user()" />
    </div>
</div>
@endsection
