<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::post('/user/heartbeat', App\Http\Controllers\UserPresence\HeartbeatController::class)->name('user.heartbeat');
    Route::post('/user/offline', App\Http\Controllers\UserPresence\MarkUserOfflineController::class)->name('user.offline');
    Route::get('/user/status', App\Http\Controllers\UserPresence\UserStatusController::class)->name('user.status');

    Route::post('/push/subscribe', App\Http\Controllers\PushSubscription\StorePushSubscriptionController::class)
        ->name('push.subscribe');
    Route::delete('/push/unsubscribe', App\Http\Controllers\PushSubscription\DestroyPushSubscriptionController::class)
        ->name('push.unsubscribe');

    Route::post('/push-subscriptions', App\Http\Controllers\PushSubscription\StorePushSubscriptionController::class);
    Route::delete('/push-subscriptions', App\Http\Controllers\PushSubscription\DestroyPushSubscriptionController::class);
});