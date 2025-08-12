@extends('modern.layouts.master')

@section('title', __('Transactions externes'))

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">{{ __('Transactions externes') }}</h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">{{ __('Transactions externes') }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('customer.transactions.recharge') }}" class="btn btn-whatsapp">
                    <i class="la la-plus mr-1"></i> {{ __('Nouvelle Recharge') }}
                </a>
                <a href="{{ route('customer.transactions.withdrawal') }}" class="btn btn-outline-whatsapp">
                    <i class="la la-minus mr-1"></i> {{ __('Demander un Retrait') }}
                </a>
            </div>
        </div>
    </div>

    <div class="content-body">

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">{{ __('Solde Actuel du Portefeuille') }} : <span class="text-whatsapp">{{ number_format($walletBalance, 0, ',', ' ') }} FCFA</span></h5>
                @livewire('customer.external-transaction-data-table')
            </div>
        </div>
    </div>
</div>
@endsection
