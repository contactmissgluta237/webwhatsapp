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
    <div class="content-header-left col-md-6 col-12 mb-2">
        <h3 class="content-header-title">Tableau de bord Client</h3>
        <div class="row breadcrumbs-top">
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Tableau de bord</li>
                </ol>
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
    <div class="row match-height">
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body text-center">
                        <a href="{{ route('customer.transactions.recharge') }}" class="d-block text-decoration-none">
                            <i class="la la-plus-circle font-large-2 mb-1 text-success"></i>
                            <h6 class="text-dark">Nouvelle Recharge</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body text-center">
                        <a href="{{ route('customer.transactions.withdrawal') }}" class="d-block text-decoration-none">
                            <i class="la la-minus-circle font-large-2 mb-1 text-warning"></i>
                            <h6 class="text-dark">Demander un Retrait</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body text-center">
                        <a href="{{ route('customer.transactions.index') }}" class="d-block text-decoration-none">
                            <i class="la la-list font-large-2 mb-1 text-info"></i>
                            <h6 class="text-dark">Mes Transactions</h6>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-12">
            <div class="card pull-up">
                <div class="card-content">
                    <div class="card-body text-center">
                        <a href="{{ route('customer.referrals.index') }}" class="d-block text-decoration-none">
                            <i class="la la-users font-large-2 mb-1 text-primary"></i>
                            <h6 class="text-dark">Mes Filleuls</h6>
                        </a>
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