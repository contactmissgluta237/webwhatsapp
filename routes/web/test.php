<?php

use Illuminate\Support\Facades\Route;

Route::get('/test/notification', App\Http\Controllers\TestNotificationController::class)->middleware('auth');
