@extends('emails.layouts.master')

@section('title', 'Nouvelle demande de retrait (Admin)')
@section('header-title', 'Nouvelle demande de retrait')

@section('content')
    <div class="greeting">
        Bonjour Administrateur,
    </div>

    <div class="content">
        <p>Une nouvelle demande de retrait a été initiée dans le système.</p>
        <p>Voici les détails de la demande :</p>
    </div>

    <div class="highlight-box"
        style="background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 25px; margin: 25px 0;">
        <h3 style="color: #2d3748; margin-bottom: 15px; font-size: 18px;">📋 Détails du retrait</h3>
        <div style="background-color: white; padding: 20px; border-radius: 8px; margin-bottom: 15px;">
            <p style="margin: 8px 0;"><strong>Client :</strong> {{ $customer->full_name }}
                ({{ $customer->email ?: $customer->phone_number }})</p>
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
        <p>Veuillez vous connecter à l'interface d'administration pour examiner et traiter cette demande.</p>
    </div>

    <div class="security-notice">
        <h3>🔒 Informations importantes</h3>
        <p>• Vérifiez l'authenticité de la demande avant de procéder au traitement.</p>
        <p>• Assurez-vous que le solde du client est suffisant.</p>
    </div>

    <div class="content">
        <p>Cordialement,</p>
        <p><strong>L'équipe {{ config('app.name') }}</strong></p>
    </div>
@endsection
