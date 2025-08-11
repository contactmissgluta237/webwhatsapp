@extends('modern.layouts.master')

@section('title', 'Mon profil')

@section('breadcrumb')
<div class="content-header-left col-md-8 col-12 mb-2">
    <div class="row breadcrumbs-top">
        <div class="col-12">
            <h2 class="content-header-title float-left mb-0">Mon profil</h2>
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">{{ __('profile.my_profile') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-body">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Mon profil</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        @livewire('shared.profile-form', ['userType' => 'admin'])
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <x-push-notification-profile :user="auth()->user()" />
    </div>
</div>
@endsection
