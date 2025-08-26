@extends('emails.layouts.master')

@section('header-title', '⚠️ Quota WhatsApp bientôt épuisé')

@section('content')
    <div class="greeting">
        Bonjour,
    </div>

    <div class="content">
        <p>Votre quota de messages WhatsApp est bientôt épuisé.</p>
        
        <p><strong>Messages restants :</strong> {{ $remainingMessages }} sur {{ $totalMessages }}</p>
        
        <p>Vous avez atteint le seuil d'alerte de {{ $alertThreshold }}%.</p>
    </div>

    <div class="security-notice">
        <h3>💡 Actions recommandées</h3>
        <p>Pour continuer à utiliser WhatsApp sans interruption :</p>
        <ul>
            <li>Rechargez votre compte pour éviter les frais de dépassement</li>
            <li>Ou souscrivez un nouveau package</li>
        </ul>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $rechargeUrl }}" class="button">Recharger mon compte</a>
    </div>

    <div class="divider"></div>

    <div style="font-size: 14px; color: #718096;">
        <p><strong>Application :</strong> {{ $appName }}</p>
        <p><strong>Date :</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>
@endsection