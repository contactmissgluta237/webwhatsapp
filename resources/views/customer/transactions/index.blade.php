@extends('modern.layouts.master')

@section('title', 'Transactions externes')

@section('breadcrumb')
<div class="content-header-left col-md-8 col-12 mb-2">
    <div class="row breadcrumbs-top">
        <div class="col-12">
            <h2 class="content-header-title float-left mb-0">Transactions externes</h2>
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Transactions externes</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12 text-end mb-3">
        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('customer.transactions.recharge') }}" class="btn btn-success">
                <i class="ti ti-plus fs-4"></i>
                Nouvelle Recharge
            </a>
            <a href="{{ route('customer.transactions.withdrawal') }}" class="btn btn-warning">
                <i class="ti ti-minus fs-4"></i>
                Demander un Retrait
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Solde Actuel du Portefeuille : <span class="text-primary">{{ number_format($walletBalance, 0, ',', ' ') }} FCFA</span></h5>
                @livewire('customer.external-transaction-data-table')
            </div>
        </div>
    </div>
</div>
@endsection
