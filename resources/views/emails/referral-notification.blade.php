@extends('emails.layouts.master')

@section('title', __('emails.referral.notification_title'))

@section('header-title', __('emails.referral.notification_header'))

@section('content')
    <div class="email-container">
        <h1>Nouveau filleul inscrit ! 🎉</h1>

        <p>Bonjour {{ $referrer->first_name }},</p>

        <p>Excellente nouvelle ! Une nouvelle personne s'est inscrite en utilisant votre code de parrainage.</p>

        <div class="highlight-box">
            <h3>Détails du nouveau filleul :</h3>
            <ul>
                <li><strong>Nom :</strong> {{ $newCustomer->full_name }}</li>
                <li><strong>Email :</strong> {{ $newCustomer->email }}</li>
                @if ($newCustomer->phone_number)
                    <li><strong>Téléphone :</strong> {{ $newCustomer->phone_number }}</li>
                @endif
                <li><strong>Date d'inscription :</strong> {{ $newCustomer->created_at->format('d/m/Y à H:i') }}</li>
            </ul>
        </div>

        <p>Votre réseau de parrainage continue de grandir ! Continuez à partager votre code de parrainage pour inviter plus
            de personnes à rejoindre notre plateforme.</p>

        <div class="cta-section">
            <a href="{{ url('/dashboard') }}" class="cta-button">Voir mon tableau de bord</a>
        </div>

        <p>Merci de faire partie de notre communauté !</p>

        <p>L'équipe {{ config('app.name') }}</p>
    </div>
@endsection
