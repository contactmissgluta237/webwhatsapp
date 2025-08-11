@extends('emails.layouts.master')

@section('content')
    <p>Bonjour {{ $customer->first_name }},</p>

    <p>Nous vous informons qu'un retrait de <strong>{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</strong> a
        été initié sur votre compte par un administrateur.</p>

    <p>Détails de la transaction :</p>
    <ul>
        <li><strong>ID de transaction externe :</strong> {{ $transaction->external_transaction_id }}</li>
        <li><strong>Montant :</strong> {{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</li>
        <li><strong>Méthode de paiement :</strong> {{ $transaction->payment_method->label }}</li>
        <li><strong>Compte de l'expéditeur :</strong> {{ $transaction->sender_account }}</li>
        <li><strong>Compte du destinataire :</strong> {{ $transaction->receiver_account }}</li>
        <li><strong>Date :</strong> {{ $transaction->created_at->format('d/m/Y H:i') }}</li>
    </ul>

    <p>Votre nouveau solde est de <strong>{{ number_format($customer->wallet->balance, 0, ',', ' ') }} FCFA</strong>.</p>

    <p>Si vous avez des questions ou si vous n'êtes pas à l'origine de cette opération, veuillez nous contacter
        immédiatement.</p>

    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
@endsection
