<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', App\Http\Controllers\Auth\LoginViewController::class)->name('login');
    Route::get('/register', App\Http\Controllers\Auth\RegisterViewController::class)->name('register');
    Route::get('/activate-account', App\Http\Controllers\Auth\ActivateAccountViewController::class)->name('account.activate');
    Route::get('/forgot-password', App\Http\Controllers\Auth\ForgotPasswordViewController::class)->name('password.request');
    Route::get('/verify-otp', App\Http\Controllers\Auth\VerifyOtpViewController::class)->name('password.verify.otp');
    Route::get('/reset-password/{token?}', App\Http\Controllers\Auth\ResetPasswordViewController::class)->name('password.reset');
    Route::get('/reset-password/{token}/{identifier}/{resetType}', App\Http\Controllers\Auth\ResetPasswordViewController::class)->name('password.reset.phone');
});

Route::middleware('auth')->group(function () {
    Route::get('profile', App\Http\Controllers\Auth\ProfileRedirectController::class)->name('profile');

    Route::post('logout', App\Http\Controllers\Auth\LogoutController::class)->name('logout');
});
