<?php

return [
    // Validation messages
    'coupon_code_required' => 'Please enter a coupon code.',
    'coupon_code_invalid_format' => 'The coupon code format is invalid.',
    'coupon_code_too_long' => 'The coupon code is too long.',

    // Success messages
    'coupon_applied_successfully' => 'Coupon applied! You save :savings XAF.',
    'subscription_success' => 'Congratulations! You are now subscribed to the :package_name package.',

    // Error messages
    'errors' => [
        'already_has_active_subscription' => 'You already have an active subscription.',
        'package_not_available' => 'This package is no longer available.',
        'trial_already_used' => 'You have already used your free trial period.',
        'insufficient_balance' => 'Insufficient balance. You need :missing_amount :currency more.',
    ],

    // Payment descriptions
    'payment_description' => 'Subscription to :package_name package',
    'promotion_applied' => '(promotion -:percentage%)',
    'coupon_applied' => '(coupon -:discount XAF)',
];
