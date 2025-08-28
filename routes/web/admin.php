<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', App\Http\Controllers\Admin\Dashboard\IndexController::class)->name('dashboard');

        // Profile routes
        Route::get('/profile', App\Http\Controllers\Admin\Profile\ShowController::class)->name('profile.show');

        // Users management routes
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', App\Http\Controllers\Admin\Users\IndexUserController::class)->name('index');
            Route::get('/create', App\Http\Controllers\Admin\Users\CreateUserController::class)->name('create');
            Route::get('/{user}', App\Http\Controllers\Admin\Users\ShowUserController::class)->name('show');
            Route::get('/{user}/edit', App\Http\Controllers\Admin\Users\EditUserController::class)->name('edit');
        });

        // Customers management routes
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/{customer}', App\Http\Controllers\Admin\Customers\ShowCustomerController::class)->name('show');
        });

        // Referrals management routes
        Route::prefix('referrals')->name('referrals.')->group(function () {
            Route::get('/', App\Http\Controllers\Admin\Referrals\IndexController::class)->name('index');
        });

        // Transactions management routes
        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', App\Http\Controllers\Admin\Transactions\GetExternalTransactionsController::class)->name('index');
            Route::get('/internal', App\Http\Controllers\Admin\Transactions\GetInternalTransactionsController::class)->name('internal');
            Route::get('/recharge', App\Http\Controllers\Admin\Transactions\CreateRechargeController::class)->name('recharge');
            Route::get('/withdrawal', App\Http\Controllers\Admin\Transactions\CreateWithdrawalController::class)->name('withdrawal');
            Route::post('/externals/{externalTransaction}/approve', App\Http\Controllers\Admin\Transactions\ApproveWithdrawalTransactionController::class)->name('externals.approve');
        });

        // System Accounts management routes
        Route::prefix('system-accounts')->name('system-accounts.')->group(function () {
            Route::get('/', App\Http\Controllers\Admin\SystemAccounts\GetTransactionsController::class)->name('index');
            Route::get('/recharge', App\Http\Controllers\Admin\SystemAccounts\RechargeController::class)->name('recharge');
            Route::get('/withdrawal', App\Http\Controllers\Admin\SystemAccounts\WithdrawalController::class)->name('withdrawal');
        });

        // Settings routes
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', App\Http\Controllers\Admin\Settings\IndexController::class)->name('index');
        });

        // Subscriptions management routes
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', App\Http\Controllers\Admin\Subscriptions\IndexController::class)->name('index');
        });

        // Packages management routes
        Route::prefix('packages')->name('packages.')->group(function () {
            Route::get('/', App\Http\Controllers\Admin\Packages\IndexController::class)->name('index');
            Route::get('/create', App\Http\Controllers\Admin\Packages\CreateController::class)->name('create');
            Route::get('/{package}', App\Http\Controllers\Admin\Packages\ShowController::class)->name('show');
            Route::get('/{package}/edit', App\Http\Controllers\Admin\Packages\EditController::class)->name('edit');
            Route::post('/{package}/toggle-status', App\Http\Controllers\Admin\Packages\ToggleStatusController::class)->name('toggle-status');
            Route::delete('/{package}', App\Http\Controllers\Admin\Packages\DeleteController::class)->name('delete');
        });

        // Coupons management routes
        Route::prefix('coupons')->name('coupons.')->group(function () {
            Route::get('/', function () {
                return view('admin.coupons.index');
            })->name('index');
        });

        // Ticket management routes
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', App\Http\Controllers\Admin\Ticket\IndexController::class)->name('index');
            Route::get('/{ticket}', App\Http\Controllers\Admin\Ticket\ShowController::class)->name('show');
            Route::get('/{ticket}/reply', App\Http\Controllers\Admin\Ticket\ReplyController::class)->name('reply');
        });

        // WhatsApp management routes
        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            // Dashboard
            Route::get('/dashboard', [App\Http\Controllers\Admin\WhatsApp\WhatsAppDashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/statistics', [App\Http\Controllers\Admin\WhatsApp\WhatsAppDashboardController::class, 'statistics'])->name('dashboard.statistics');

            // Accounts management
            Route::prefix('accounts')->name('accounts.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\WhatsApp\WhatsAppAccountController::class, 'index'])->name('index');
                Route::get('/{account}', [App\Http\Controllers\Admin\WhatsApp\WhatsAppAccountController::class, 'show'])->name('show');
                Route::get('/{account}/statistics', [App\Http\Controllers\Admin\WhatsApp\WhatsAppAccountController::class, 'statistics'])->name('statistics');
                Route::post('/{account}/toggle-ai', [App\Http\Controllers\Admin\WhatsApp\WhatsAppAccountController::class, 'toggleAi'])->name('toggle-ai');
                Route::delete('/{account}', [App\Http\Controllers\Admin\WhatsApp\WhatsAppAccountController::class, 'destroy'])->name('destroy');
            });

            // Conversations management
            Route::prefix('conversations')->name('conversations.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\WhatsApp\WhatsAppConversationController::class, 'index'])->name('index');
                Route::get('/{conversation}', [App\Http\Controllers\Admin\WhatsApp\WhatsAppConversationController::class, 'show'])->name('show');
                Route::get('/{conversation}/statistics', [App\Http\Controllers\Admin\WhatsApp\WhatsAppConversationController::class, 'statistics'])->name('statistics');
                Route::get('/{conversation}/export', [App\Http\Controllers\Admin\WhatsApp\WhatsAppConversationController::class, 'export'])->name('export');
                Route::post('/{conversation}/toggle-ai', [App\Http\Controllers\Admin\WhatsApp\WhatsAppConversationController::class, 'toggleAi'])->name('toggle-ai');
                Route::delete('/{conversation}', [App\Http\Controllers\Admin\WhatsApp\WhatsAppConversationController::class, 'destroy'])->name('destroy');
            });
        });
    });
