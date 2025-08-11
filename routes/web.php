<?php

use App\Http\Controllers\AuthCheckController;
use App\Http\Controllers\Notifications\ReadAndRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', AuthCheckController::class);

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
    Route::get('/notifications/{notificationId}/read', ReadAndRedirectController::class)->name('notifications.read');
    require __DIR__.'/web/admin.php';
    require __DIR__.'/web/customer.php';
    require __DIR__.'/web/user.php';
    require __DIR__.'/web/whatsapp.php';
});

Route::get('/test/notification', App\Http\Controllers\TestNotificationController::class)->middleware('auth');

// Test routes (synchrone simple)
Route::prefix('test/payment')->name('test.payment.')->group(function () {

    // Test principal : /test/payment/recharge/1000
    Route::get('{action}/{amount}', [App\Http\Controllers\Test\PaymentTestController::class, 'test'])
        ->where(['action' => 'recharge|withdraw', 'amount' => '[0-9]+'])
        ->name('execute');

    // Test balance : /test/payment/balance
    Route::get('balance', [App\Http\Controllers\Test\PaymentTestController::class, 'checkBalance'])
        ->name('balance');

    // Test status : /test/payment/status/trans_123
    Route::get('status/{transactionId}', [App\Http\Controllers\Test\PaymentTestController::class, 'checkStatus'])
        ->name('status');
});
