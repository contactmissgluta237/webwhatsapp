<?php

use Illuminate\Support\Facades\Route;

Route::get('/test/notification', App\Http\Controllers\TestNotificationController::class)->middleware('auth');

Route::prefix('test/payment')->name('test.payment.')->group(function () {
    Route::get('{action}/{amount}', [App\Http\Controllers\Test\PaymentTestController::class, 'test'])
        ->where(['action' => 'recharge|withdraw', 'amount' => '[0-9]+'])
        ->name('execute');
    Route::get('balance', [App\Http\Controllers\Test\PaymentTestController::class, 'checkBalance'])
        ->name('balance');
    Route::get('status/{transactionId}', [App\Http\Controllers\Test\PaymentTestController::class, 'checkStatus'])
        ->name('status');
});
