@extends('modern.layouts.master')

@section('title', __('Rafraîchir la session WhatsApp'))

@section('page-style')
<style>
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 3rem 2rem;
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        pointer-events: none;
    }

    .hero-content {
        position: relative;
        z-index: 1;
    }

    .session-card {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        border: 1px solid #e9ecef;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .session-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #25D366 0%, #128C7E 100%);
    }

    .session-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .session-info-item {
        background: #f8f9fa;
        padding: 1.25rem;
        border-radius: 10px;
        border-left: 4px solid #25D366;
        transition: all 0.3s ease;
    }

    .session-info-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.15);
    }

    .session-info-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .session-info-value {
        font-size: 1rem;
        font-weight: 700;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .qr-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        position: relative;
        overflow: hidden;
    }

    .qr-container::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }

    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .qr-code-display {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        position: relative;
        z-index: 1;
    }

    .instructions-panel {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        height: fit-content;
    }

    .instruction-step {
        display: flex;
        align-items: flex-start;
        padding: 1rem;
        margin-bottom: 0.75rem;
        border-radius: 10px;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .instruction-step:hover {
        background: rgba(37, 211, 102, 0.05);
        border-color: rgba(37, 211, 102, 0.2);
        transform: translateX(5px);
    }

    .step-icon {
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 1rem;
        font-size: 0.875rem;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(37, 211, 102, 0.3);
    }

    .step-content {
        flex: 1;
        padding-top: 0.25rem;
    }

    .step-title {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.25rem;
    }

    .step-description {
        font-size: 0.875rem;
        color: #6c757d;
        line-height: 1.4;
    }

    .connection-status-card {
        background: white;
        border-radius: 15px;
        padding: 3rem 2rem;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        text-align: center;
    }

    .loading-animation {
        position: relative;
        margin-bottom: 2rem;
    }

    .loading-animation::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 60px;
        height: 60px;
        border: 3px solid rgba(37, 211, 102, 0.2);
        border-radius: 50%;
        border-top-color: #25D366;
        animation: spin 1s linear infinite;
        transform: translate(-50%, -50%);
    }

    @keyframes spin {
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }

    .progress {
        height: 8px;
        background-color: rgba(37, 211, 102, 0.1);
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        background: linear-gradient(90deg, #25D366 0%, #128C7E 100%);
        border-radius: 10px;
    }

    .alert-whatsapp {
        background: rgba(37, 211, 102, 0.1);
        color: #128C7E;
        border: 1px solid rgba(37, 211, 102, 0.3);
        border-radius: 10px;
        border-left: 4px solid #25D366;
    }

    .btn-whatsapp {
        background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
        border: none;
        color: white;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        transition: all 0.3s ease;
    }

    .btn-whatsapp:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(37, 211, 102, 0.4);
        color: white;
    }

    .btn-outline-whatsapp:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(37, 211, 102, 0.2);
    }

    .text-whatsapp {
        color: #25D366 !important;
    }

    .bg-whatsapp {
        background-color: #25D366 !important;
    }

    .card-modern {
        background: white;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        overflow: hidden;
    }

    .session-info-table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .session-info-table td {
        border: 1px solid #e9ecef;
        position: relative;
        vertical-align: middle !important;
        padding: 1.25rem !important;
    }

    .session-info-table tr:first-child td {
        border-top: none;
    }

    .session-info-table tr td:first-child {
        border-left: none;
    }

    .session-info-table tr td:last-child {
        border-right: none;
    }

    .session-info-table tr:last-child td {
        border-bottom: none;
    }

    .session-info-table td:hover {
        background-color: rgba(37, 211, 102, 0.05);
        transition: background-color 0.2s ease;
    }

    .session-info-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .session-info-value {
        font-size: 0.95rem;
        font-weight: 700;
        color: #495057;
        line-height: 1.3;
    }
</style>
@endsection

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ __('Rafraîchir la session WhatsApp') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}"><i class="la la-dashboard"></i>{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('whatsapp.index') }}">{{ __('WhatsApp') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Rafraîchir session') }}</li>
            </ol>
        </div>
    </div>
    <div class="col-4 p-0 text-right">
        <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-whatsapp">
            <i class="la la-arrow-left"></i> {{ __('Retour à la liste') }}
        </a>
    </div>
</div>
<!-- Breadcrumb end -->

{{-- Section Hero --}}
<div class="hero-section">
    <div class="hero-content">
        <p class="lead mb-0">
            {{ __('Générez un nouveau QR Code pour restaurer la connexion avec votre téléphone') }}
        </p>
    </div>
</div>

{{-- Information sur le processus --}}
<div class="alert alert-whatsapp mb-2">
    <h6><i class="la la-info-circle"></i> {{ __('Processus de reconnexion') }}</h6>
    <p class="mb-0">{{ __('Cette action va générer un nouveau QR code que vous devrez scanner avec votre téléphone pour restaurer la connexion WhatsApp.') }}</p>
</div>

<div class="content-body">
    <section id="whatsapp-refresh">


        {{-- Section informations de session --}}
        <div class="row">
            <div class="col-lg-12">
                <div class="card-modern">
                    <div class="card-body p-0">
                        <table class="table table-bordered session-info-table mb-0">
                            <tbody>
                                <tr>
                                    <td style="width: 50%;">
                                        <div class="d-flex align-items-center">
                                            <i class="la la-tag text-whatsapp mr-3 la-lg"></i>
                                            <div>
                                                <div class="session-info-label">{{ __('Nom de session') }}</div>
                                                <div class="session-info-value"><strong>{{ $account->session_name }}</strong></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="width: 50%;">
                                        <div class="d-flex align-items-center">
                                            <i class="la la-phone text-whatsapp mr-3 la-lg"></i>
                                            <div>
                                                <div class="session-info-label">{{ __('Numéro de téléphone') }}</div>
                                                <div class="session-info-value"><strong>{{ $account->phone_number ?? __('Non défini') }}</strong></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="la la-signal text-whatsapp mr-3 la-lg"></i>
                                            <div>
                                                <div class="session-info-label">{{ __('Statut actuel') }}</div>
                                                <div class="session-info-value">
                                                    <span class="badge {{ $account->status->getBadgeClass() }}">
                                                        {{ $account->status->label }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="la la-clock-o text-whatsapp mr-3 la-lg"></i>
                                            <div>
                                                <div class="session-info-label">{{ __('Dernière connexion') }}</div>
                                                <div class="session-info-value"><strong>{{ $account->last_seen_at ? $account->last_seen_at->diffForHumans() : __('Jamais') }}</strong></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Bouton de génération sur ligne complète --}}
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card-modern">
                    <div class="card-body text-center py-4">
                        <p class="text-muted mb-3">{{ __('Cliquez sur le bouton ci-dessous pour commencer le processus de reconnexion') }}</p>
                        @livewire('customer.whats-app.refresh-session', ['account' => $account], key('refresh-session-' . $account->id))
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection