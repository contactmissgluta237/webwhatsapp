@extends('modern.layouts.master')

@section('title', __('Payment Status'))

@section('breadcrumb')
    <div class="content-header-left col-md-8 col-12 mb-2">
        <div class="row breadcrumbs-top">
            <div class="col-12">
                <h2 class="content-header-title float-left mb-0">@yield('title')</h2>
                <div class="breadcrumb-wrapper col-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active">@yield('title')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container text-center mt-5">
        @php
            $iconClass = '';
            $textColorClass = '';
            $title = '';

            switch ($statusType) {
                case 'success':
                    $iconClass = 'ti ti-circle-check';
                    $textColorClass = 'text-success';
                    $title = 'Paiement Réussi';
                    break;
                case 'error':
                    $iconClass = 'ti ti-circle-x';
                    $textColorClass = 'text-danger';
                    $title = 'Erreur de Paiement';
                    break;
                case 'cancel':
                    $iconClass = 'ti ti-circle-minus';
                    $textColorClass = 'text-warning';
                    $title = 'Paiement Annulé';
                    break;
                default:
                    $iconClass = 'ti ti-info-circle';
                    $textColorClass = 'text-info';
                    $title = 'Statut du Paiement';
                    break;
            }
        @endphp

        <div class="card p-4 shadow-sm">
            <div class="card-body">
                <i class="{{ $iconClass }} display-1 {{ $textColorClass }} mb-3"></i>
                <h1 class="{{ $textColorClass }} mb-3">{{ $title }}</h1>
                <p class="lead">{{ $message }}</p>
                <hr>
                <a href="{{ $returnUrl }}" class="btn btn-primary mt-3">
                    <i class="ti ti-arrow-back-up me-2"></i>Retourner à la liste des transactions
                </a>
            </div>
        </div>
    </div>
@endsection
