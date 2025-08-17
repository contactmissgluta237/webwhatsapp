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
        });
    });
