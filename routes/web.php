<?php

use Illuminate\Support\Facades\Route;

Route::get('/', App\Http\Controllers\AuthCheckController::class);

Route::get('/push/diagnostic', App\Http\Controllers\PushNotificationDiagnosticController::class)
    ->middleware('auth')
    ->name('push.diagnostic');

/**
 * Include auth routes
 */
require __DIR__.'/web/auth.php';

/**
 * Payment callback routes
 */
Route::prefix('payment')->name('payment.')->group(function () {
    require __DIR__.'/web/payment.php';
});

/**
 * All authenticated routes
 */
Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/notifications/{notificationId}/read', App\Http\Controllers\Notifications\ReadAndRedirectController::class)->name('notifications.read');
    require __DIR__.'/web/admin.php';
    require __DIR__.'/web/customer.php';
    require __DIR__.'/web/user.php';
    require __DIR__.'/web/whatsapp.php';
});

/**
 * Tests routes
 */
require __DIR__.'/web/test.php';
