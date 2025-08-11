@extends('emails.layouts.master')

@section('content')
    <p>Bonjour {{ $customer->first_name }},</p>

    <p>Nous avons le plaisir de vous informer qu'une recharge de
        <strong>{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</strong> a été effectuée sur votre compte.
    </p>

    <p>Détails de la transaction :</p>
    <ul>
        <li><strong>ID de transaction externe :</strong> {{ $transaction->external_transaction_id }}</li>
        <li><strong>Montant :</strong> {{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</li>
        <li><strong>Méthode de paiement :</strong> {{ $transaction->payment_method->label }}</li>
        <li><strong>Compte de l'expéditeur :</strong> {{ $transaction->sender_account }}</li>
        <li><strong>Date :</strong> {{ $transaction->created_at->format('d/m/Y H:i') }}</li>
    </ul>

    <p>Votre nouveau solde est de <strong>{{ number_format($customer->wallet->balance, 0, ',', ' ') }} FCFA</strong>.</p>

    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>

    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
@endsection
