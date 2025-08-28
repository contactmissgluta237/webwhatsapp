@extends('modern.layouts.master')
@section('title', __('Admin Dashboard'))

@push('styles')
<!-- slick css -->
<link rel="stylesheet" href="{{ asset('assets/vendor/slick/slick.css') }}">
<link rel="stylesheet" href="{{ asset('assets/vendor/slick/slick-theme.css') }}">

<!-- Data Table css-->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/datatable/jquery.dataTables.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/vendor/datatable/datatable2/buttons.dataTables.min.css') }}">
@endpush

@section('content')

<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="content-header-left col-md-6 col-12 mb-2">
        <h3 class="content-header-title text-whatsapp">{{ __('Dashboard Admin') }}</h3>
        <div class="row breadcrumbs-top">
            <div class="breadcrumb-wrapper col-12">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
    <div class="content-header-right col-md-6 col-12 text-right">
        <button type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilter" role="button"
            aria-expanded="false" aria-controls="collapseFilter"
            class="btn btn-outline-whatsapp rounded btn-glow">
            <i class="la la-filter"></i> {{ __('Filter') }}
        </button>
    </div>
</div>
<!-- Breadcrumb end -->

<!-- Filter Options-->
@livewire('components.filter-date-start-to-end-component')
<!-- Filter Options End -->

<!-- Component 1: Dashboard Metrics -->
@livewire('admin.dashboard.dashboard-metrics')
<!-- Dashboard Metrics End -->

<!-- Component 2: System Account Balances -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-none border-gray-light">
            <div class="card-header bg-white border-bottom-0">
                <h4 class="card-title text-whatsapp">Soldes des Comptes Système</h4>
                <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
            </div>
            <div class="card-content">
                <div class="card-body">
                    @livewire('admin.system-accounts.management.system-account-balances')
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Component 3: System Account Management -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-none border-gray-light">
            <div class="card-header bg-white border-bottom-0">
                <h4 class="card-title text-whatsapp">Gestion des Comptes Système</h4>
                <a class="heading-elements-toggle"><i class="la la-ellipsis-v font-medium-3"></i></a>
                <div class="heading-elements">
                    <ul class="list-inline mb-0">
                        <li><a data-action="collapse"><i class="ft-minus"></i></a></li>
                        <li><a data-action="reload"><i class="ft-rotate-cw"></i></a></li>
                        <li><a data-action="expand"><i class="ft-maximize"></i></a></li>
                    </ul>
                </div>
            </div>
            <div class="card-content collapse show">
                <div class="card-body">
                    @livewire('admin.system-account-management')
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
