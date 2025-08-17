@extends('emails.layouts.master')

@section('content')
<div class="content">
    <h2 style="color: #e74c3c; margin-bottom: 20px;">
        <i class="fa fa-exclamation-triangle"></i>
        Déconnexion de votre compte WhatsApp
    </h2>
    
    <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
        Bonjour <strong>{{ $account->user->first_name ?? $account->user->name }}</strong>,
    </p>
    
    <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
        Nous vous informons que votre compte WhatsApp <strong>"{{ $account->session_name }}"</strong> 
        s'est déconnecté le {{ $account->last_disconnected_at->format('d/m/Y à H:i') }}.
    </p>
    
    @if($account->phone_number)
    <div class="info-box" style="background-color: #f8f9fa; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0;">
        <p style="margin: 0; font-size: 14px;">
            <strong>Numéro de téléphone :</strong> {{ $account->phone_number }}
        </p>
    </div>
    @endif
    
    <p style="font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
        Pour rétablir la connexion de votre compte WhatsApp, veuillez vous connecter à votre tableau de bord 
        et utiliser la fonction de reconnexion.
    </p>
    
    <div class="text-center" style="margin: 30px 0;">
        <a href="{{ url('/whatsapp') }}" 
           class="btn btn-primary" 
           style="background-color: #25d366; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
            Reconnecter mon compte WhatsApp
        </a>
    </div>
    
    <div class="alert" style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;">
        <p style="margin: 0; font-size: 14px; color: #856404;">
            <strong>Note importante :</strong> Si vous n'avez pas initialisé cette déconnexion, 
            cela peut être dû à une perte de connexion internet ou à une déconnexion depuis l'application WhatsApp.
        </p>
    </div>
    
    <p style="font-size: 14px; color: #6c757d; margin-top: 30px;">
        Pour toute question ou assistance, n'hésitez pas à nous contacter.
    </p>
</div>
@endsection