<?php

return [
    'required' => 'Le champ :attribute est requis.',
    'email' => 'Le champ :attribute doit être une adresse email valide.',
    'unique' => 'Le champ :attribute est déjà utilisé.',
    'exists' => 'Le champ :attribute sélectionné est invalide.',
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'max' => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'accepted' => 'Le champ :attribute doit être accepté.',
    'in' => 'Le champ :attribute sélectionné est invalide.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'first_name' => [
            'required' => 'Le prénom est requis.',
        ],
        'last_name' => [
            'required' => 'Le nom de famille est requis.',
        ],
        'email' => [
            'required' => 'L\'email est requis.',
            'email' => 'L\'email doit être valide.',
            'unique' => 'Cet email est déjà utilisé.',
        ],
        'phone_number' => [
            'unique' => 'Ce numéro de téléphone est déjà utilisé.',
        ],
        'country_id' => [
            'required' => 'Veuillez sélectionner un pays.',
            'exists' => 'Veuillez sélectionner un pays valide.',
        ],
        'phone_number_only' => [
            'required' => 'Le numéro de téléphone est requis.',
            'min' => 'Le numéro doit contenir au moins 8 chiffres.',
            'max' => 'Le numéro ne doit pas dépasser 15 chiffres.',
        ],
        'password' => [
            'required' => 'Le mot de passe est requis.',
            'confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ],
        'terms' => [
            'accepted' => 'Vous devez accepter les conditions d\'utilisation.',
        ],
        'referral_code' => [
            'exists' => 'Ce code de parrainage n\'existe pas.',
        ],
        'locale' => [
            'required' => 'La langue est requise.',
            'in' => 'Veuillez sélectionner une langue valide.',
        ],
        'agent_name' => [
            'required' => 'Le nom de l\'agent est obligatoire.',
            'max' => 'Le nom de l\'agent ne peut pas dépasser 100 caractères.',
        ],
        'ai_model_id' => [
            'exists' => 'Le modèle sélectionné est invalide.',
        ],
        'agent_prompt' => [
            'max' => 'Le prompt ne peut pas dépasser 2000 caractères.',
        ],
        'trigger_words' => [
            'max' => 'Les mots déclencheurs ne peuvent pas dépasser 500 caractères.',
        ],
        'contextual_information' => [
            'max' => 'Les informations contextuelles ne peuvent pas dépasser 5000 caractères.',
        ],
        'ignore_words' => [
            'max' => 'Les mots à ignorer ne peuvent pas dépasser 500 caractères.',
        ],
        'response_time' => [
            'in' => 'Le délai de réponse sélectionné est invalide.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'first_name' => 'prénom',
        'last_name' => 'nom de famille',
        'email' => 'email',
        'phone_number' => 'numéro de téléphone',
        'country_id' => 'pays',
        'phone_number_only' => 'numéro de téléphone',
        'password' => 'mot de passe',
        'terms' => 'conditions d\'utilisation',
        'referral_code' => 'code de parrainage',
        'locale' => 'langue',
        'agent_name' => 'nom de l\'agent',
        'ai_model_id' => 'modèle IA',
        'agent_prompt' => 'prompt de l\'agent',
        'trigger_words' => 'mots déclencheurs',
        'contextual_information' => 'informations contextuelles',
        'ignore_words' => 'mots à ignorer',
        'response_time' => 'délai de réponse',
    ],
];
