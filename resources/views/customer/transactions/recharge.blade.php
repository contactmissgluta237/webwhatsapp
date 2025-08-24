@extends('modern.layouts.master')

@section('title', __('Recharger mon compte'))

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">{{ __('Recharger mon compte') }}</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('customer.transactions.index') }}">Transactions</a></li>
                        <li class="breadcrumb-item active">{{ __('Recharger') }}</li>
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
                        <h6 class="text-white-50 mb-1">{{ __('Solde actuel') }}</h6>
                        <h2 class="text-white mb-0 fw-bold">
                            <x-user-currency :amount="auth()->user()->wallet?->balance ?? 0" />
                        </h2>
                    </div>
                </div>
                <small class="text-white-50">
                    <i class="ti ti-shield-check me-1"></i>
                    {{ __('Compte sécurisé') }}
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-gray-light shadow-none h-100 d-flex">
            <div class="card-body d-flex flex-column justify-content-center text-center">
                <i class="ti ti-zap fs-1 text-whatsapp mb-2"></i>
                <h6 class="text-whatsapp mb-2 fw-bold">{{ __('Recharge rapide') }}</h6>
                <p class="text-muted small mb-0">
                    {{ __('Rechargez votre compte en quelques clics avec Mobile Money ou Orange Money') }}
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de recharge -->
<div class="row">
    <div class="col-12">
        <div class="card border-gray-light shadow-none">
            <div class="card-header border-bottom-0 pb-0">
                <div class="d-flex align-items-center">
                    <div class="bg-whatsapp rounded-circle p-2 me-3">
                        <i class="ti ti-credit-card text-white"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 text-whatsapp">{{ __('Nouvelle recharge') }}</h5>
                        <small class="text-muted">{{ __('Choisissez le montant et la méthode de paiement') }}</small>
                    </div>
                </div>
            </div>
            <div class="card-body pt-4">
                <livewire:customer.create-customer-recharge-form />
            </div>
        </div>
    </div>
</div>

<!-- Section des avantages -->
<div class="row mt-4">
    <div class="col-md-4">
        <div class="text-center p-3">
            <div class="bg-success d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                <i class="ti ti-bolt text-white fs-3"></i>
            </div>
            <h6 class="fw-bold">{{ __('Recharge instantanée') }}</h6>
            <p class="text-muted small mb-0">
                {{ __('Votre compte est crédité immédiatement après confirmation') }}
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
                {{ __('Toutes les transactions sont cryptées et sécurisées') }}
            </p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="text-center p-3">
            <div class="bg-warning d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                <i class="ti ti-headphones text-white fs-3"></i>
            </div>
            <h6 class="fw-bold">{{ __('Support 24/7') }}</h6>
            <p class="text-muted small mb-0">
                {{ __('Notre équipe est disponible pour vous aider') }}
            </p>
        </div>
    </div>
</div>

    </div> <!-- End content-body -->
@endsection
