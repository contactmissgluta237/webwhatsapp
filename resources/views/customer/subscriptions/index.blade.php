@extends('modern.layouts.master')

@section('title', 'Mes Souscriptions')

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="content-header-left col-md-6 col-12 mb-2">
            <h3 class="content-header-title">
                <i class="la la-gift text-primary mr-2"></i>
                Mes Souscriptions
            </h3>
            <div class="row breadcrumbs-top">
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                        <li class="breadcrumb-item active">Mes Souscriptions</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="content-header-right col-md-6 col-12 text-right">
            <a href="{{ route('customer.packages.index') }}" class="btn btn-whatsapp">
                <i class="la la-plus mr-1"></i> Choisir un package
            </a>
        </div>
    </div>

    <div class="content-body">
        @if(auth()->user()->activeSubscription)
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card bg-whatsapp">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3 text-white">
                                        <i class="la la-gift la-2x"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-1 text-white">Package Actuel</h3>
                                        <h3 class="mb-0 text-white" >{{ auth()->user()->activeSubscription->package->display_name }}</h3>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="mb-2">
                                        <span class="badge badge-lg bg-white text-whatsapp">
                                            {{ auth()->user()->activeSubscription->getRemainingDays() }} jour(s) restant(s)
                                        </span>
                                    </div>
                                    <a href="{{ route('customer.subscriptions.show', auth()->user()->activeSubscription->id) }}" 
                                       class="btn btn-sm btn-white text-whatsapp">
                                        <i class="la la-eye"></i> Voir les détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card shadow-none border-gray-light">
                    <div class="card-body">
                        @livewire('customer.subscriptions-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection