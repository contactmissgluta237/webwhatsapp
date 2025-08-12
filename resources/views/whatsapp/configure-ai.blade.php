@extends('modern.layouts.master')

@section('title', 'Configuration Agent IA - ' . $account->session_name)

@push('styles')

<link rel="stylesheet" href="{{ asset('assets/css/whatsapp-ai-config.css') }}">
<link rel="stylesheet" href="{{ asset('css/whatsapp-simulator.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush

@section('content')
    <div class="row mx-0 mt-1 mb-1">
        <div class="col-8 p-0">
            <h2 class="content-header-title mb-0">
                <i class="la la-robot"></i> {{ __('Configuration Agent IA') }}
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
                        {{ __('Configuration IA') }} - {{ $account->session_name }}
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

    <div class="ai-config-container">
        <div class="ai-config-main">
            <!-- Formulaire de configuration (70%) -->
            <div class="ai-config-form">
                @livewire('whats-app.ai-configuration-form', ['account' => $account])
            </div>

            <!-- Simulateur de conversation (30%) -->
            <div class="ai-config-simulator">
                @livewire('whats-app.conversation-simulator', ['account' => $account])
            </div>
        </div>
        <small class="text-muted mt-2 d-block text-center">
            <i class="la la-info-circle"></i>
            {{ __('Les modifications sont automatiquement prises en compte dans le simulateur. Sauvegardez pour les appliquer définitivement.') }}
        </small>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // Gestion des notifications via Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('configuration-saved', (event) => {
                const data = event[0];
                if (data.type === 'success') {
                    toastr.success(data.message);
                } else {
                    toastr.error(data.message);
                }
            });

            Livewire.on('ai-agent-configured', (event) => {
                const data = event[0];
                Swal.fire({
                    title: data.type === 'success' ? 'Succès!' : 'Erreur!',
                    text: data.message,
                    icon: data.type,
                    confirmButtonText: 'Ok'
                });
            });
        });
    </script>
@endpush
