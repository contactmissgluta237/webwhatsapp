@extends('modern.layouts.master')

@section('title', 'Notifications - ' . $account->session_name)

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="col-8 p-0">
            <h2 class="content-header-title mb-0">
                <i class="la la-bell"></i> {{ __('Configuration des Notifications') }}
            </h2>
            <div class="breadcrumb-wrapper">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('customer.dashboard') }}">
                            <i class="la la-dashboard"></i>{{ __('Dashboard') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('whatsapp.index') }}">{{ __('WhatsApp') }}</a>
                    </li>
                    <li class="breadcrumb-item active">
                        {{ __('Notifications') }} - {{ $account->session_name }}
                    </li>
                </ol>
            </div>
        </div>
        <div class="col-4 p-0 text-right">
            <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-whatsapp">
                <i class="la la-arrow-left"></i> {{ __('Retour aux comptes') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-gray-light shadow-none">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="la la-bell text-warning"></i>
                        {{ __('Configuration des Notifications') }}
                    </h4>
                    <p class="text-muted mb-0">
                        {{ __('Configurez les canaux de notification pour votre agent WhatsApp.') }}
                    </p>
                </div>
                <div class="card-content">
                    <div class="card-body">
                        @livewire('customer.whats-app.notification-config', ['account' => $account])
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    // Configuration Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000"
    };

    // Écouter les événements Livewire pour les notifications
    window.addEventListener('notification-updated', () => {
        toastr.success('Configuration des notifications mise à jour avec succès!');
    });
</script>
@endpush