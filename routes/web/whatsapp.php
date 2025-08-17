<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('whatsapp')
    ->name('whatsapp.')
    ->group(function () {

        Route::get('/', App\Http\Controllers\WhatsApp\Account\IndexController::class)->name('index');
        Route::get('/create', App\Http\Controllers\WhatsApp\Account\CreateController::class)->name('create');

        // actions
        Route::get('/configure-ai/{account}', App\Http\Controllers\WhatsApp\Account\ConfigureAiController::class)->name('configure-ai');
        Route::post('/{account}/toggle-ai', App\Http\Controllers\WhatsApp\Account\ToggleAiController::class)->name('toggle-ai');
        Route::delete('/{account}', App\Http\Controllers\WhatsApp\Account\DestroyController::class)->name('destroy');
    });
