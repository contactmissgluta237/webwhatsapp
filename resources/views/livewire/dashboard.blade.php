@extends('modern.layouts.master')
@section('title', 'Dashboard Client')

@push('styles')
    <!-- slick css -->
    <link rel="stylesheet" href="{{ asset('assets/vendor/slick/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/slick/slick-theme.css') }}">

    <!-- Data Table css-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/datatable/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/datatable/datatable2/buttons.dataTables.min.css') }}">
@endpush

@section('content')
    <div class="content-header-left col-md-8 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-left mb-0">Dashboard Client</h2>
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Tableau de bord</a></li>
                        <li class="breadcrumb-item active">Accueil</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Push Notifications Settings -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">🔔 Notifications Push</h4>
                    <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <p class="card-text">Activez les notifications push pour recevoir des alertes en temps réel sur
                            votre appareil.</p>
                        @include('components.push-notification-button')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Dashboard Metrics Component -->
    <div class="row">
        <div class="col-12">
            @livewire('customer.customer-dashboard-metrics')
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Actions Rapides</h4>
                    <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6 col-12">
                                <a href="{{ route('customer.transactions.recharge') }}"
                                    class="btn btn-success btn-block btn-round btn-glow mb-2">
                                    <i class="la la-plus-circle mr-1"></i>
                                    Nouvelle Recharge
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 col-12">
                                <a href="{{ route('customer.transactions.withdrawal') }}"
                                    class="btn btn-warning btn-block btn-round btn-glow mb-2">
                                    <i class="la la-minus-circle mr-1"></i>
                                    Demander un Retrait
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 col-12">
                                <a href="{{ route('customer.transactions.index') }}"
                                    class="btn btn-info btn-block btn-round btn-glow mb-2">
                                    <i class="la la-list mr-1"></i>
                                    Mes Transactions
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 col-12">
                                <a href="{{ route('customer.referrals.index') }}"
                                    class="btn btn-primary btn-block btn-round btn-glow mb-2">
                                    <i class="la la-users mr-1"></i>
                                    Mes Filleuls
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/vendor/slick/slick.min.js') }}"></script>
    <script src="{{ asset('assets/js/ticket.js') }}"></script>
@endpush
