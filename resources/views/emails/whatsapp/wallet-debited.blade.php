@extends('emails.layouts.master')

@section('header-title', '💳 Débit automatique - Quota épuisé')

@section('content')
    <div class="greeting">
        Bonjour,
    </div>

    <div class="content">
        <p>Votre quota de messages WhatsApp étant épuisé, nous avons automatiquement débité votre wallet pour continuer le service.</p>
    </div>

    <div class="otp-container" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
        <div style="color: white; font-size: 18px; font-weight: 600; margin-bottom: 15px;">📊 Détails du débit</div>
        <div style="color: white; font-size: 16px;">
            <p><strong>Montant débité :</strong> {{ number_format($debitedAmount, 0, ',', ' ') }} XAF</p>
            <p><strong>Nouveau solde :</strong> {{ number_format($newBalance, 0, ',', ' ') }} XAF</p>
            <p><strong>Date :</strong> {{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>

    <div class="content">
        <p>Ce débit vous permet de continuer à utiliser WhatsApp sans interruption.</p>
    </div>

    <div class="security-notice">
        <h3>💡 Pour éviter les frais futurs</h3>
        <p>Rechargez votre package ou consultez votre wallet pour gérer vos fonds.</p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $packagesUrl }}" class="button" style="margin-right: 10px;">Recharger mon package</a>
        <a href="{{ $walletUrl }}" class="button" style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);">Voir mon wallet</a>
    </div>

    <div class="divider"></div>

    <div style="font-size: 14px; color: #718096;">
        <p><strong>Application :</strong> {{ $appName }}</p>
    </div>
@endsection