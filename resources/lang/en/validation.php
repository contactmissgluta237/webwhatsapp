<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute field must be a valid email address.',
    'unique' => 'The :attribute has already been taken.',
    'exists' => 'The selected :attribute is invalid.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
    ],
    'confirmed' => 'The :attribute confirmation does not match.',
    'accepted' => 'The :attribute must be accepted.',
    'in' => 'The selected :attribute is invalid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'first_name' => [
            'required' => 'The first name is required.',
        ],
        'last_name' => [
            'required' => 'The last name is required.',
        ],
        'email' => [
            'required' => 'The email is required.',
            'email' => 'The email must be valid.',
            'unique' => 'This email is already in use.',
        ],
        'phone_number' => [
            'unique' => 'This phone number is already in use.',
        ],
        'country_id' => [
            'required' => 'Please select a country.',
            'exists' => 'Please select a valid country.',
        ],
        'phone_number_only' => [
            'required' => 'The phone number is required.',
            'min' => 'The number must contain at least 8 digits.',
            'max' => 'The number must not exceed 15 digits.',
        ],
        'password' => [
            'required' => 'The password is required.',
            'confirmed' => 'The password confirmation does not match.',
        ],
        'terms' => [
            'accepted' => 'You must accept the terms of service.',
        ],
        'referral_code' => [
            'exists' => 'This referral code does not exist.',
        ],
        'locale' => [
            'required' => 'The language is required.',
            'in' => 'Please select a valid language.',
        ],
        'agent_name' => [
            'required' => 'The agent name is required.',
            'max' => 'The agent name cannot exceed 100 characters.',
        ],
        'ai_model_id' => [
            'exists' => 'The selected model is invalid.',
        ],
        'agent_prompt' => [
            'max' => 'The prompt cannot exceed 2000 characters.',
        ],
        'trigger_words' => [
            'max' => 'The trigger words cannot exceed 500 characters.',
        ],
        'contextual_information' => [
            'max' => 'The contextual information cannot exceed 5000 characters.',
        ],
        'ignore_words' => [
            'max' => 'The words to ignore cannot exceed 500 characters.',
        ],
        'response_time' => [
            'in' => 'The selected response time is invalid.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'first_name' => 'first name',
        'last_name' => 'last name',
        'email' => 'email',
        'phone_number' => 'phone number',
        'country_id' => 'country',
        'phone_number_only' => 'phone number',
        'password' => 'password',
        'terms' => 'terms of service',
        'referral_code' => 'referral code',
        'locale' => 'language',
        'agent_name' => 'agent name',
        'ai_model_id' => 'AI model',
        'agent_prompt' => 'agent prompt',
        'trigger_words' => 'trigger words',
        'contextual_information' => 'contextual information',
        'ignore_words' => 'words to ignore',
        'response_time' => 'response time',
    ],
];
