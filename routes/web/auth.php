<?php

use App\Http\Controllers\Auth\ActivateAccountViewController;
use App\Http\Controllers\Auth\ForgotPasswordViewController;
use App\Http\Controllers\Auth\LoginViewController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterViewController;
use App\Http\Controllers\Auth\ResetPasswordViewController;
use App\Http\Controllers\Auth\VerifyOtpViewController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', LoginViewController::class)->name('login');
    Route::get('/register', RegisterViewController::class)->name('register');
    Route::get('/activate-account', ActivateAccountViewController::class)->name('account.activate');
    Route::get('/forgot-password', ForgotPasswordViewController::class)->name('password.request');
    Route::get('/verify-otp', VerifyOtpViewController::class)->name('password.verify.otp');
    Route::get('/reset-password/{token?}', ResetPasswordViewController::class)->name('password.reset');
    Route::get('/reset-password/{token}/{identifier}/{resetType}', ResetPasswordViewController::class)->name('password.reset.phone');
});

Route::middleware('auth')->group(function () {
    Route::get('profile', function () {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.profile.show');
        }

        if ($user->hasRole('customer')) {
            return redirect()->route('customer.profile.show');
        }

        return redirect()->route('login');
    })->name('profile');

    Route::post('logout', LogoutController::class)->name('logout');
});
