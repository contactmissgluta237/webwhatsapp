@extends('emails.layouts.master')

@section('content')
    <p>Bonjour Administrateur,</p>

    <p>Une nouvelle transaction a été enregistrée sur un compte système.</p>

    <p>Détails de la transaction :</p>
    <ul>
        <li><strong>Type de transaction :</strong> {{ $transaction->type->label }}</li>
        <li><strong>Compte système :</strong> {{ $transaction->systemAccount->type->label }}</li>
        <li><strong>Montant :</strong> {{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</li>
        <li><strong>Ancien solde :</strong> {{ number_format($transaction->old_balance, 0, ',', ' ') }} FCFA</li>
        <li><strong>Nouveau solde :</strong> {{ number_format($transaction->new_balance, 0, ',', ' ') }} FCFA</li>
        <li><strong>Description :</strong> {{ $transaction->description }}</li>
        <li><strong>Date :</strong> {{ $transaction->created_at->format('d/m/Y H:i') }}</li>
    </ul>

    <p>Cordialement,</p>
    <p>L'équipe {{ config('app.name') }}</p>
@endsection
