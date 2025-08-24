<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Theme Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the theme configuration for the application.
    | You can easily modify colors and styles from here to change the
    | entire application appearance.
    |
    */

    // Main brand colors
    'primary' => [
        'main' => env('THEME_PRIMARY_MAIN', '#075E54'), // WhatsApp Dark Green
        'hover' => env('THEME_PRIMARY_HOVER', '#054A42'), // Darker WhatsApp Green
        'light' => env('THEME_PRIMARY_LIGHT', '#E6F5F3'), // Light WhatsApp Green
        'gradient_start' => env('THEME_PRIMARY_GRADIENT_START', '#075E54'),
        'gradient_end' => env('THEME_PRIMARY_GRADIENT_END', '#128C7E'),
    ],

    // Secondary colors
    'secondary' => [
        'main' => env('THEME_SECONDARY_MAIN', '#128C7E'), // WhatsApp Light Green
        'hover' => env('THEME_SECONDARY_HOVER', '#0F7A6C'),
        'light' => env('THEME_SECONDARY_LIGHT', '#DCF8C6'), // WhatsApp Message Bubble Green
    ],

    // Authentication pages specific colors
    'auth' => [
        'background_gradient_start' => env('THEME_AUTH_BG_START', '#075E54'),
        'background_gradient_end' => env('THEME_AUTH_BG_END', '#128C7E'),
        'card_background' => env('THEME_AUTH_CARD_BG', '#FFFFFF'),
        'button_gradient_start' => env('THEME_AUTH_BTN_START', '#075E54'),
        'button_gradient_end' => env('THEME_AUTH_BTN_END', '#128C7E'),
        'text_color' => env('THEME_AUTH_TEXT', '#075E54'),
        'link_color' => env('THEME_AUTH_LINK', '#075E54'),
        'focus_color' => env('THEME_AUTH_FOCUS', '#075E54'),
    ],

    // Status colors
    'status' => [
        'success' => env('THEME_SUCCESS', '#128C7E'),
        'error' => env('THEME_ERROR', '#D31130'),
        'warning' => env('THEME_WARNING', '#F39C12'),
        'info' => env('THEME_INFO', '#3498DB'),
    ],

    // Text colors
    'text' => [
        'primary' => env('THEME_TEXT_PRIMARY', '#0A1317'),
        'secondary' => env('THEME_TEXT_SECONDARY', '#5D6C7B'),
        'muted' => env('THEME_TEXT_MUTED', '#96A6B4'),
        'on_primary' => env('THEME_TEXT_ON_PRIMARY', '#FFFFFF'),
    ],
];
