<?php

use App\Http\Controllers\WhatsApp\Account\ConfigureAiController;
use App\Http\Controllers\WhatsApp\Account\CreateController;
use App\Http\Controllers\WhatsApp\Account\DestroyController;
use App\Http\Controllers\WhatsApp\Account\IndexController;
use App\Http\Controllers\WhatsApp\Account\ToggleAiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('whatsapp')
    ->name('whatsapp.')
    ->group(function () {

        Route::get('/', IndexController::class)->name('index');
        Route::get('/create', CreateController::class)->name('create');
        Route::get('/configure-ai/{account}', ConfigureAiController::class)->name('configure-ai');
        Route::post('/{account}/toggle-ai', ToggleAiController::class)->name('toggle-ai');
        Route::delete('/{account}', DestroyController::class)->name('destroy');
    });