<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WhatsApp API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('webhook')->group(function () {
    Route::post('/incoming-message', App\Http\Controllers\WhatsApp\Webhook\IncomingMessageController::class);
    Route::post('/session-connected', App\Http\Controllers\WhatsApp\Webhook\SessionConnectedController::class);
});
