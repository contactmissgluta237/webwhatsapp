<?php

use App\Http\Controllers\PushSubscription\DestroyPushSubscriptionController;
use App\Http\Controllers\PushSubscription\StorePushSubscriptionController;
use App\Http\Controllers\UserPresence\HeartbeatController;
use App\Http\Controllers\UserPresence\MarkUserOfflineController;
use App\Http\Controllers\UserPresence\UserStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // User Presence
    Route::post('/user/heartbeat', HeartbeatController::class)->name('user.heartbeat');
    Route::post('/user/offline', MarkUserOfflineController::class)->name('user.offline');
    Route::get('/user/status', UserStatusController::class)->name('user.status');

    // Push subscriptions (routes principales)
    Route::post('/push/subscribe', StorePushSubscriptionController::class)
        ->name('push.subscribe');
    Route::delete('/push/unsubscribe', DestroyPushSubscriptionController::class)
        ->name('push.unsubscribe');

    // Push subscriptions (compatibilité avec le JavaScript existant)
    Route::post('/push-subscriptions', StorePushSubscriptionController::class);
    Route::delete('/push-subscriptions', DestroyPushSubscriptionController::class);
});
