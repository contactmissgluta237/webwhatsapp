<?php

use App\Http\Controllers\WhatsApp\Webhook\IncomingMessageController;
use App\Http\Controllers\WhatsApp\Webhook\SessionConnectedController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WhatsApp API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('webhook')->group(function () {
    Route::post('/incoming-message', IncomingMessageController::class);
    Route::post('/session-connected', SessionConnectedController::class);
});
