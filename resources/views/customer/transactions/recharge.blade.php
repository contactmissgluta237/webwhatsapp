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

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <i class="ti ti-credit-card me-2"></i>
                    {{ __('Recharger mon compte') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h6 class="text-muted mb-2">{{ __('Solde actuel') }}</h6>
                        <h3 class="text-primary mb-0">
                            {{ number_format(auth()->user()->wallet?->balance ?? 0, 0, ',', ' ') }} FCFA
                        </h3>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-label-success">{{ __('Compte actif') }}</span>
                    </div>
                </div>

                <hr class="my-4">

                <livewire:customer.create-customer-recharge-form />
            </div>
        </div>
    </div>
</div>
@endsection
