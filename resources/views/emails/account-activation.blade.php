<x-mail::message>
    # Activation de votre compte

    Bienvenue ! Votre compte a été créé avec succès.

    Pour activer votre compte {{ $maskedIdentifier }}, utilisez le code ci-dessous :

    <div
        style="text-align: center; font-size: 24px; font-weight: bold; padding: 20px; border: 2px solid #10b981; border-radius: 8px; margin: 20px 0; background-color: #ecfdf5; color: #047857;">
        {{ $otp }}
    </div>

    Ce code est valide pendant 10 minutes.

    Vous pouvez également cliquer sur le bouton ci-dessous pour activer directement votre compte :

    <x-mail::button :url="$activationUrl" color="success">
        Activer mon compte
    </x-mail::button>

    🎉 **Une fois votre compte activé, vous pourrez :**
    - Vous connecter à votre espace personnel
    - Accéder à toutes les fonctionnalités
    - Profiter de nos services

    Si vous n'avez pas créé de compte, veuillez ignorer cet email.

    Merci de nous avoir rejoint !

    {{ config('app.name') }}
</x-mail::message>
