<?php

use Illuminate\Support\Facades\Route;

Route::prefix('my-coolpay')->name('my-coolpay.')->group(function () {
    Route::get('success', App\Http\Controllers\Payment\Callback\SuccessController::class)->name('success');
    Route::get('error', App\Http\Controllers\Payment\Callback\ErrorController::class)->name('error');
    Route::get('cancel', App\Http\Controllers\Payment\Callback\CancelController::class)->name('cancel');
});
