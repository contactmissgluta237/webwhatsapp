<?php

return [
    'conversation' => 'conversation|conversations',
    'AI Information Request' => 'Demande d\'Information IA',
    'Your AI assistant needs help!' => 'Votre assistant IA a besoin d\'aide !',
    'Customer' => 'Client',
    'Question' => 'Question',
    'AI Response' => 'Réponse IA',
    'The AI couldn\'t provide a complete answer. Please review and update your knowledge base.' => 'L\'IA n\'a pas pu fournir une réponse complète. Veuillez réviser et mettre à jour votre base de connaissances.',
    'Customer from :phone asked a question your AI couldn\'t answer' => 'Le client :phone a posé une question à laquelle votre IA n\'a pas pu répondre',
    'View Conversation' => 'Voir la Conversation',

    // Validation messages - Auth
    'validation.auth.email.required' => 'L\'adresse email est requise.',
    'validation.auth.email.email' => 'L\'adresse email doit être valide.',
    'validation.auth.phone_number.required' => 'Le numéro de téléphone complet est requis.',
    'validation.auth.phone_number_only.required' => 'Veuillez saisir votre numéro de téléphone.',
    'validation.auth.phone_number_only.min' => 'Le numéro de téléphone doit contenir au moins 8 chiffres.',
    'validation.auth.phone_number_only.max' => 'Le numéro de téléphone ne doit pas dépasser 15 chiffres.',
    'validation.auth.password.required' => 'Le mot de passe est requis.',
    'validation.auth.country_id.required' => 'Veuillez sélectionner l\'indicateur de votre pays.',
    'validation.auth.country_id.exists' => 'L\'indicateur pays sélectionné n\'existe pas.',

    // Validation messages - Profile
    'validation.profile.first_name.required' => 'Le prénom est requis.',
    'validation.profile.first_name.string' => 'Le prénom doit être une chaîne de caractères.',
    'validation.profile.first_name.max' => 'Le prénom ne doit pas dépasser 255 caractères.',
    'validation.profile.last_name.required' => 'Le nom de famille est requis.',
    'validation.profile.last_name.string' => 'Le nom de famille doit être une chaîne de caractères.',
    'validation.profile.last_name.max' => 'Le nom de famille ne doit pas dépasser 255 caractères.',
    'validation.profile.email.required' => 'L\'email est requis.',
    'validation.profile.email.email' => 'L\'email doit être valide.',
    'validation.profile.email.unique' => 'Cet email est déjà utilisé.',
    'validation.profile.phone_number.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
    'validation.profile.phone_number.unique' => 'Ce numéro de téléphone est déjà utilisé.',

    // Validation messages - Product
    'validation.product.title.required' => 'Le titre est obligatoire.',
    'validation.product.title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
    'validation.product.description.required' => 'La description est obligatoire.',
    'validation.product.description.max' => 'La description ne peut pas dépasser 1000 caractères.',
    'validation.product.price.required' => 'Le prix est obligatoire.',
    'validation.product.price.numeric' => 'Le prix doit être un nombre.',
    'validation.product.price.min' => 'Le prix doit être supérieur à 0.',
    'validation.product.mediaFiles.file' => 'Le fichier uploadé n\'est pas valide.',
    'validation.product.mediaFiles.mimes' => 'Les fichiers autorisés sont : jpeg, jpg, png, gif, pdf, doc, docx.',
    'validation.product.mediaFiles.max' => 'La taille maximale d\'un fichier est de 10 Mo.',

    // Validation messages - Admin
    'validation.admin.selectedRoles.required' => 'Veuillez sélectionner au moins un rôle.',
    'validation.admin.selectedRoles.min' => 'Veuillez sélectionner au moins un rôle.',
    'validation.admin.selectedRoles.exists' => 'Le rôle sélectionné n\'est pas valide.',

    // Email subjects
    'emails.account_activation.subject' => 'Activation de votre compte',
    'emails.recharge_notification.subject' => 'Recharge de compte effectuée - :amount FCFA',
    'emails.withdrawal_requested.subject' => 'Nouvelle demande de retrait',

    // Profile Avatar validation
    'validation.profile.avatar.required' => 'Veuillez sélectionner une image.',
    'validation.profile.avatar.image' => 'Le fichier doit être une image.',
    'validation.profile.avatar.max' => 'L\'image ne doit pas dépasser 2MB.',

    // Profile Password validation
    'validation.profile.current_password.required' => 'Le mot de passe actuel est requis.',
    'validation.profile.current_password.string' => 'Le mot de passe actuel doit être une chaîne de caractères.',
    'validation.profile.password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
    'validation.profile.password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',

    // OTP validation
    'validation.auth.otpCode.required' => 'Le code de vérification est obligatoire.',
    'validation.auth.otpCode.min' => 'Le code doit contenir au moins 4 caractères.',
    'validation.auth.otpCode.max' => 'Le code ne doit pas dépasser 6 caractères.',

    // Auth validation additional
    'validation.auth.email.exists' => 'Cette adresse email n\'existe pas dans nos enregistrements.',
    'validation.auth.phoneNumber.required' => 'Le numéro de téléphone est obligatoire.',
    'validation.auth.phoneNumber.exists' => 'Ce numéro de téléphone n\'existe pas dans nos enregistrements.',

    // Reset Password validation
    'validation.auth.resetMethod.required' => 'La méthode de réinitialisation est requise.',
    'validation.auth.resetMethod.in' => 'Veuillez sélectionner une méthode de réinitialisation valide.',
    'validation.auth.password_confirmation.required' => 'La confirmation du mot de passe est requise.',
    'validation.auth.password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',

    'validation.auth.identifier.required' => 'L\'identifiant est requis.',
    'validation.auth.identifier.email' => 'L\'email doit être valide.',
    'validation.auth.identifier.exists' => 'Aucun compte n\'est associé à cet identifiant.',
    'validation.auth.identifier.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
    'validation.auth.token.required' => 'Le code de réinitialisation est requis.',
    'validation.auth.token.string' => 'Le code de réinitialisation doit être une chaîne de caractères.',
];
