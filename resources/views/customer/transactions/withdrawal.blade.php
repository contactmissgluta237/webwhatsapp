@extends('modern.layouts.master')

@section('title', __('Faire un retrait'))

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">{{ __('Faire un retrait') }}</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer.transactions.index') }}">Transactions</a></li>
                        <li class="breadcrumb-item active">{{ __('Retrait') }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('customer.transactions.index') }}" class="btn btn-outline-whatsapp">
                <i class="la la-arrow-left mr-1"></i> {{ __('Retour') }}
            </a>
        </div>
    </div>

    <div class="content-body">

<!-- Section du solde actuel -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card border-gray-light shadow-none bg-gradient-whatsapp text-white">
            <div class="card-body text-center py-4">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <i class="ti ti-wallet fs-1 me-3"></i>
                    <div>
                        <h6 class="text-white-50 mb-1">{{ __('Solde disponible') }}</h6>
                        <h2 class="text-white mb-0 fw-bold">
                            <x-user-currency :amount="auth()->user()->wallet?->balance ?? 0" />
                        </h2>
                    </div>
                </div>
                <small class="text-white-50">
                    <i class="ti ti-shield-check me-1"></i>
                    {{ __('Retraits sécurisés') }}
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-gray-light shadow-none h-100 d-flex">
            <div class="card-body d-flex flex-column justify-content-center text-center">
                <i class="ti ti-cash fs-1 text-whatsapp mb-2"></i>
                <h6 class="text-whatsapp mb-2 fw-bold">{{ __('Retrait rapide') }}</h6>
                <p class="text-muted small mb-0">
                    {{ __('Retirez vos fonds directement sur Mobile Money ou Orange Money') }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de retrait -->
<div class="row">
    <div class="col-12">
        <div class="card border-gray-light shadow-none">
            <div class="card-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center">
                    <div class="bg-whatsapp rounded-circle p-2 me-3">
                        <i class="ti ti-cash text-white"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 text-whatsapp">{{ __('Nouveau retrait') }}</h5>
                        <small class="text-muted">{{ __('Choisissez le montant et la méthode de réception') }}</small>
                    </div>
                </div>
            </div>
            <div class="card-body pt-4">
                <livewire:customer.create-customer-withdrawal-form />
            </div>
        </div>
    </div>
</div>

<!-- Section des avantages -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="text-center p-3">
            <div class="bg-info d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                <i class="ti ti-clock text-white fs-3"></i>
            </div>
            <h6 class="fw-bold">{{ __('Traitement rapide') }}</h6>
            <p class="text-muted small mb-0">
                {{ __('Les retraits sont traités dans les plus brefs délais') }}
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="text-center p-3">
            <div class="bg-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                <i class="ti ti-shield-check text-white fs-3"></i>
            </div>
            <h6 class="fw-bold">{{ __('100% Sécurisé') }}</h6>
            <p class="text-muted small mb-0">
                {{ __('Tous les retraits sont vérifiés et sécurisés') }}
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="text-center p-3">
            <div class="bg-success d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                <i class="ti ti-check text-white fs-3"></i>
            </div>
            <h6 class="fw-bold">{{ __('Frais transparents') }}</h6>
            <p class="text-muted small mb-0">
                {{ __('Aucun frais caché, calcul transparent') }}
            </p>
        </div>
    </div>
</div>

    </div> <!-- End content-body -->
@endsection
