<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        '_metadata' => [
            'success' => true,
            'message' => 'API is running',
        ],
        'data' => [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
        ],
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Coupon validation API
Route::middleware('auth')->post('/coupons/validate', function (Request $request) {
    $couponService = app(\App\Services\CouponService::class);

    $request->validate([
        'coupon_code' => 'required|string',
        'package_price' => 'required|numeric|min:0',
    ]);

    $validation = $couponService->validateCoupon(
        $request->coupon_code,
        $request->user(),
        $request->package_price
    );

    return response()->json($validation);
});

// WhatsApp API Routes
Route::prefix('whatsapp')->group(__DIR__.'/api/whatsapp.php');

// MyCoolPay Webhook
Route::post('payment/mycoolpay/webhook', App\Http\Controllers\Api\MyCoolPayWebhookController::class);
