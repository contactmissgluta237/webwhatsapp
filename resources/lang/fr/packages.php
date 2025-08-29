<?php

return [
    // Validation messages
    'coupon_code_required' => 'Veuillez entrer un code promo.',
    'coupon_code_invalid_format' => 'Le format du code promo est invalide.',
    'coupon_code_too_long' => 'Le code promo est trop long.',

    // Success messages
    'coupon_applied_successfully' => 'Code promo appliqué ! Vous économisez :savings XAF.',
    'subscription_success' => 'Félicitations ! Vous êtes maintenant abonné au package :package_name.',

    // Error messages
    'errors' => [
        'already_has_active_subscription' => 'Vous avez déjà un abonnement actif.',
        'package_not_available' => 'Ce package n\'est plus disponible.',
        'trial_already_used' => 'Vous avez déjà utilisé votre période d\'essai gratuite.',
        'insufficient_balance' => 'Solde insuffisant. Il vous manque :missing_amount :currency.',
    ],

    // Payment descriptions
    'payment_description' => 'Souscription au package :package_name',
    'promotion_applied' => '(promotion -:percentage%)',
    'coupon_applied' => '(coupon -:discount XAF)',
];
