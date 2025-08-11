@component('mail::message')
    # 🧪 Test Email Notification

    Félicitations ! Votre système d'email fonctionne parfaitement.

    **Application :** {{ $appName }}
    **Timestamp :** {{ $timestamp }}
    **URL :** {{ $appUrl }}

    ## ✅ Test Réussi

    Cette notification de test confirme que :
    - ✅ Mailpit intercepte correctement les emails
    - ✅ Laravel peut envoyer des emails via SMTP
    - ✅ La configuration email est fonctionnelle
    - ✅ Les templates Markdown fonctionnent

    @component('mail::button', ['url' => $appUrl])
        Accéder à l'Application
    @endcomponent

    Merci d'utiliser {{ $appName }} !

    @component('mail::subcopy')
        Ceci est un email de test automatique généré le {{ $timestamp }}.
    @endcomponent
@endcomponent
