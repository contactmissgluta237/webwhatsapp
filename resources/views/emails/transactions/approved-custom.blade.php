@extends('emails.layouts.master')

@section('content')
# Approbation de votre demande de retrait

Bonjour {{ $transaction->wallet->user->first_name }},

Votre demande de retrait d'un montant de **{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA** a été approuvée avec succès.

Le montant a été déduit de votre portefeuille.

Merci d'utiliser nos services.

Cordialement,<br>
L'équipe {{ config('app.name') }}
@endsection
