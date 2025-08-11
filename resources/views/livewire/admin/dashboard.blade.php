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
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ __('Home') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}"><i class="la la-dashboard"></i>{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item">{{ __('Home') }}</li>
            </ol>
        </div>
    </div>
    <!-- Filter -->
    <div class="col-4 p-0">
        <div class="d-flex justify-content-end">
            <button type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilter" role="button"
                aria-expanded="false" aria-controls="collapseFilter"
                class="btn btn-info">
                <i class="la la-filter"></i> {{ __('Filter') }}
            </button>
        </div>
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
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Soldes des Comptes Système</h4>
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
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Gestion des Comptes Système</h4>
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
