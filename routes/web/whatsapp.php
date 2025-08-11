<?php

use App\Http\Controllers\WhatsApp\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('whatsapp')
    ->name('whatsapp.')
    ->group(function () {

        Route::get('/', [WhatsAppController::class, 'index'])->name('index');
        Route::get('/create', [WhatsAppController::class, 'create'])->name('create');
        Route::post('/store', [WhatsAppController::class, 'store'])->name('store');

    });
