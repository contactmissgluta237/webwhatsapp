@extends('modern.layouts.master')

@section('title', __('Customer details'))

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="content-header-left col-md-6 col-12 mb-2">
        <h3 class="content-header-title text-whatsapp">{{ __('Détails du Client') }}</h3>
        <div class="row breadcrumbs-top">
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Utilisateurs</a></li>
                    <li class="breadcrumb-item active">Détails</li>
                </ol>
            </div>
        </div>
    </div>
    <div class="content-header-right col-md-6 col-12 text-right">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-whatsapp rounded btn-glow">
            <i class="la la-arrow-left mr-1"></i> {{ __('Retour aux Utilisateurs') }}
        </a>
    </div>
</div>
<!-- Breadcrumb end -->

@livewire('admin.customer-details.customer-details', ['customer' => $customer])
@endsection