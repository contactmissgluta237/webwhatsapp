<?php

use App\Http\Controllers\Payment\Callback\CancelController;
use App\Http\Controllers\Payment\Callback\ErrorController;
use App\Http\Controllers\Payment\Callback\SuccessController;
use Illuminate\Support\Facades\Route;

Route::prefix('my-coolpay')->name('my-coolpay.')->group(function () {
    Route::get('success', SuccessController::class)->name('success');
    Route::get('error', ErrorController::class)->name('error');
    Route::get('cancel', CancelController::class)->name('cancel');
});
