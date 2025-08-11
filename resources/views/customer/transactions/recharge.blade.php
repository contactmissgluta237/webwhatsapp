@extends('modern.layouts.master')

@section('title', 'Recharger mon compte')

@section('breadcrumb')
<div class="content-header-left col-md-8 col-12 mb-2">
    <div class="row breadcrumbs-top">
        <div class="col-12">
            <h2 class="content-header-title float-left mb-0">Recharger mon compte</h2>
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer.transactions.index') }}">Transactions</a></li>
                    <li class="breadcrumb-item active">Recharger</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-12 text-end mb-3">
        <a href="{{ route('customer.transactions.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left fs-4"></i>
            Retour
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <i class="ti ti-credit-card me-2"></i>
                    Recharger mon compte
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h6 class="text-muted mb-2">Solde actuel</h6>
                        <h3 class="text-primary mb-0">
                            {{ number_format(auth()->user()->wallet?->balance ?? 0, 0, ',', ' ') }} FCFA
                        </h3>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-label-success">Compte actif</span>
                    </div>
                </div>

                <hr class="my-4">

                <livewire:customer.create-customer-recharge-form />
            </div>
        </div>
    </div>
</div>
@endsection
