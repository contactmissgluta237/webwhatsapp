@extends('emails.layouts.master')

@section('title', 'Demande de retrait initialisée')
@section('header-title', 'Demande de retrait en cours')

@section('content')
    <div class="greeting">
        Bonjour {{ $customer->first_name }},
    </div>

    <div class="content">
        <p>Votre demande de retrait a été initialisée avec succès sur votre compte.</p>
        <p>Voici les détails de votre demande :</p>
    </div>

    <div class="highlight-box"
        style="background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 25px; margin: 25px 0;">
        <h3 style="color: #2d3748; margin-bottom: 15px; font-size: 18px;">📋 Détails du retrait</h3>
        <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
            <p style="margin: 8px 0;"><strong>Montant :</strong> {{ \App\Helpers\CurrencyHelper::formatUsd($transaction->amount) }}
            </p>
            <p style="margin: 8px 0;"><strong>Méthode de paiement :</strong> {{ $transaction->payment_method->label }}</p>
            <p style="margin: 8px 0;"><strong>Compte destinataire :</strong> {{ $transaction->receiver_account }}</p>
            <p style="margin: 8px 0;"><strong>Date de demande :</strong>
                {{ $transaction->created_at->format('d/m/Y à H:i') }}</p>
            <p style="margin: 8px 0;"><strong>Référence :</strong> {{ $transaction->external_transaction_id }}</p>
        </div>
    </div>

    <div class="content">
        <p><strong>🕐 Délai de traitement :</strong></p>
        <p>Votre demande sera traitée dans les <strong>24 heures</strong> suivant cette notification. Vous recevrez un email
            de confirmation une fois le retrait effectué.</p>
    </div>

    <div class="security-notice">
        <h3>🔒 Informations importantes</h3>
        <p>• Vérifiez que les informations du compte destinataire sont correctes</p>
        <p>• Cette demande ne peut plus être annulée</p>
        <p>• En cas de problème, contactez notre support client</p>
        <p>• Si vous n'avez pas effectué cette demande, contactez-nous immédiatement</p>
    </div>

    <div class="content">
        <p>Merci de votre confiance.</p>
        <p><strong>L'équipe {{ config('app.name') }}</strong></p>
    </div>
@endsection
