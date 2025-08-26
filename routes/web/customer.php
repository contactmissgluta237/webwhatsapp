<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:customer'])
    ->prefix('customer')
    ->name('customer.')
    ->group(function () {
        Route::get('/dashboard', App\Http\Controllers\Customer\Dashboard\IndexController::class)->name('dashboard');

        Route::get('/profile', App\Http\Controllers\Customer\Profile\ShowController::class)->name('profile.show');

        Route::prefix('referrals')->name('referrals.')->group(function () {
            Route::get('/', App\Http\Controllers\Customer\Referrals\IndexController::class)->name('index');
        });

        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', App\Http\Controllers\Customer\Transactions\GetExternalTransactionsController::class)->name('index');
            Route::get('/recharge', App\Http\Controllers\Customer\Transactions\CreateRechargeController::class)->name('recharge');
            Route::get('/withdrawal', App\Http\Controllers\Customer\Transactions\CreateWithdrawalController::class)->name('withdrawal');
            Route::get('/internal', App\Http\Controllers\Customer\Transactions\GetInternalTransactionsController::class)->name('internal');
        });

        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', App\Http\Controllers\Customer\Ticket\IndexController::class)->name('index');
            Route::get('/create', App\Http\Controllers\Customer\Ticket\CreateController::class)->name('create');
            Route::get('/{ticket}', App\Http\Controllers\Customer\Ticket\ShowController::class)->name('show');
        });

        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', App\Http\Controllers\Customer\Products\IndexController::class)->name('index');
            Route::get('/create', App\Http\Controllers\Customer\Products\CreateController::class)->name('create');
            Route::get('/{product}/edit', App\Http\Controllers\Customer\Products\EditController::class)->name('edit');
            Route::post('/{product}/toggle-status', App\Http\Controllers\Customer\Products\ToggleStatusController::class)->name('toggle-status');
            Route::delete('/{product}', App\Http\Controllers\Customer\Products\DeleteController::class)->name('delete');
        });

        Route::prefix('packages')->name('packages.')->group(function () {
            Route::get('/', App\Http\Controllers\Customer\Packages\IndexController::class)->name('index');
            Route::post('/{package}/subscribe', App\Http\Controllers\Customer\Packages\SubscribeController::class)->name('subscribe');
        });

        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            Route::get('/', App\Http\Controllers\Customer\WhatsApp\Account\IndexController::class)->name('index');
            Route::get('/create', App\Http\Controllers\Customer\WhatsApp\Account\CreateController::class)->name('create');
            Route::get('/{account}/conversations', App\Http\Controllers\Customer\WhatsApp\Conversations\IndexController::class)->name('conversations.index');
            Route::get('/{account}/conversations/{conversation}', App\Http\Controllers\Customer\WhatsApp\Conversations\ShowController::class)->name('conversations.show');
        });
    });
