@extends('modern.layouts.master')

@section('title', __('Nouveau Agent WhatsApp'))

@section('page-style')
<style>
.qr-container {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.qr-code-display {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.instructions-panel {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 2rem;
    border-left: 5px solid #28a745;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.step-number {
    background: #28a745;
    color: white;
    width: 25px;
    height: 25px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 10px;
    font-size: 0.9rem;
}

.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px;
    padding: 3rem 2rem;
    margin-bottom: 2rem;
    text-align: center;
}

.form-section {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
</style>
@endsection

@section('content')
<!-- Breadcrumb start -->
<div class="row mx-0 mt-1 mb-1">
    <div class="col-8 p-0">
        <h2 class="content-header-title mb-0">{{ __('Nouveau Agent WhatsApp') }}</h2>
        <div class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}"><i class="la la-dashboard"></i>{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('whatsapp.index') }}">{{ __('WhatsApp') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Nouveau') }}</li>
            </ol>
        </div>
    </div>
    <div class="col-4 p-0 text-right">
        <a href="{{ route('whatsapp.index') }}" class="btn btn-outline-primary">
            <i class="la la-arrow-left"></i> {{ __('Retour à la liste') }}
        </a>
    </div>
</div>
<!-- Breadcrumb end -->
        {{-- Section Hero --}}
        <div class="hero-section">
            <p class="lead mb-0">{{ __('Connectez votre téléphone WhatsApp pour automatiser vos conversations avec l\'intelligence artificielle') }}</p>
        </div>

        {{-- Information sur le processus --}}
        <div class="alert alert-info mb-4">
            <h6><i class="la la-info-circle"></i> Comment ça marche ?</h6>
            <ol class="mb-0 pl-3">
                <li><strong>Donnez un nom</strong> à votre agent WhatsApp</li>
                <li><strong>Générez le QR code</strong> (peut prendre jusqu'à 2 minutes)</li>
                <li><strong>Scannez le QR</strong> avec WhatsApp sur votre téléphone</li>
                <li><strong>Confirmez</strong> - nous vérifierons que la connexion fonctionne</li>
                <li><strong>C'est prêt !</strong> Votre agent WhatsApp est opérationnel</li>
            </ol>
        </div>

        <div class="row">
            {{-- Formulaire de création --}}
            <div class="col-lg-12">
                <div class="form-section">
                    <div class="d-flex align-items-center mb-4">
                        <div class="step-number">1</div>
                        <h4 class="mb-0">{{ __('Configuration de votre agent') }}</h4>
                    </div>
                    
                    @livewire('whats-app.components.session-name-input')
                </div>
            </div>
        </div>

        {{-- Section QR Code (affiché quand généré) --}}
        @livewire('whats-app.create-session')
@endsection

@section('page-script')
@endsection
