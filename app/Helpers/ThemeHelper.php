<?php

namespace App\Helpers;

class ThemeHelper
{
    /**
     * Get authentication CSS styles based on configuration
     */
    public static function getAuthStyles(): string
    {
        $primaryMain = config('theme.primary.main');
        $primaryHover = config('theme.primary.hover');
        $primaryLight = config('theme.primary.light');
        $gradientStart = config('theme.auth.background_gradient_start');
        $gradientEnd = config('theme.auth.background_gradient_end');
        $buttonGradientStart = config('theme.auth.button_gradient_start');
        $buttonGradientEnd = config('theme.auth.button_gradient_end');
        $textColor = config('theme.auth.text_color');
        $linkColor = config('theme.auth.link_color');
        $focusColor = config('theme.auth.focus_color');

        return "
        :root {
            --auth-primary: {$primaryMain};
            --auth-primary-hover: {$primaryHover};
            --auth-primary-light: {$primaryLight};
            --auth-gradient-start: {$gradientStart};
            --auth-gradient-end: {$gradientEnd};
            --auth-button-gradient-start: {$buttonGradientStart};
            --auth-button-gradient-end: {$buttonGradientEnd};
            --auth-text: {$textColor};
            --auth-link: {$linkColor};
            --auth-focus: {$focusColor};
        }

        .auth-wrapper {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--auth-gradient-start) 0%, var(--auth-gradient-end) 100%);
            padding: 20px 0;
        }

        .auth-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            max-width: 450px;
            width: 100%;
        }

        .auth-header {
            background: linear-gradient(45deg, var(--auth-gradient-start), var(--auth-gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0;
        }

        .auth-tabs {
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .auth-tabs .btn-check:checked+.btn {
            background: linear-gradient(45deg, var(--auth-button-gradient-start), var(--auth-button-gradient-end));
            border-color: transparent;
            color: white !important;
        }

        .auth-tabs .btn:not(.btn-check:checked + .btn) {
            border-radius: 0;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #000000 !important;
        }

        .auth-tabs .btn:not(.btn-check:checked + .btn):hover {
            background-color: var(--auth-primary-light);
            color: #000000 !important;
        }

        .form-control:focus {
            border-color: var(--auth-focus);
            box-shadow: 0 0 0 0.2rem rgba(".self::hexToRgb($focusColor).', 0.25);
        }

        .btn-auth {
            background: linear-gradient(45deg, var(--auth-button-gradient-start), var(--auth-button-gradient-end));
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba('.self::hexToRgb($primaryMain).', 0.3);
        }

        .auth-link {
            color: var(--auth-link);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-link:hover {
            color: var(--auth-primary-hover);
        }

        .password-toggle {
            border-left: none;
            background: transparent;
            color: #6c757d;
        }

        .password-toggle:hover {
            background-color: var(--auth-primary-light);
            color: var(--auth-primary);
        }

        .alert-success {
            background-color: var(--auth-primary-light);
            border-color: var(--auth-primary);
            color: var(--auth-text);
        }

        .alert-danger {
            background-color: #FEE4E6;
            border-color: #D31130;
            color: #D31130;
        }

        @media (max-width: 576px) {
            .auth-wrapper {
                padding: 10px;
            }

            .auth-card {
                margin: 0 auto;
            }

            .auth-tabs .btn {
                font-size: 0.9rem;
                padding: 0.5rem 0.75rem;
            }
        }
        ';
    }

    /**
     * Convert hex color to RGB values
     */
    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }

    /**
     * Get theme colors for JavaScript
     */
    public static function getThemeColorsForJs(): array
    {
        return [
            'primary' => config('theme.primary.main'),
            'primaryHover' => config('theme.primary.hover'),
            'primaryLight' => config('theme.primary.light'),
            'secondary' => config('theme.secondary.main'),
            'success' => config('theme.status.success'),
            'error' => config('theme.status.error'),
            'warning' => config('theme.status.warning'),
            'info' => config('theme.status.info'),
        ];
    }
}
