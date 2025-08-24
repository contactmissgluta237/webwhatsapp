<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WhatsApp API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('webhook')->group(function () {
    Route::post('/incoming-message', App\Http\Controllers\Customer\WhatsApp\Webhook\IncomingMessageController::class);
    Route::post('/session', App\Http\Controllers\Api\WhatsApp\WhatsAppSessionStatusWebhookController::class);
});
