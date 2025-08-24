<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register Blade directive for theme colors
        Blade::directive('themeColor', function ($expression) {
            return "<?php echo config('theme.{$expression}'); ?>";
        });

        // Register helper for theme styles
        Blade::directive('authStyles', function () {
            return "<?php echo App\Helpers\ThemeHelper::getAuthStyles(); ?>";
        });

        // Share theme colors globally with all views
        view()->share('themeColors', [
            'primary' => config('theme.primary'),
            'secondary' => config('theme.secondary'),
            'auth' => config('theme.auth'),
            'status' => config('theme.status'),
            'text' => config('theme.text'),
        ]);
    }
}
