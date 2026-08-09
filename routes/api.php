<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'message' => 'API is running']);
});

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/auth/login', [App\Http\Controllers\AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('/auth/logout', [App\Http\Controllers\AuthController::class, 'logout']);

        Route::apiResources([
            'fuel-types'      => App\Http\Controllers\FuelTypeController::class,
            'tanks'           => App\Http\Controllers\TankController::class,
            'nozzles'         => App\Http\Controllers\NozzleController::class,
            'products'        => App\Http\Controllers\ProductController::class,
            'accounts'        => App\Http\Controllers\AccountController::class,
            'purchase-orders' => App\Http\Controllers\PurchaseOrderController::class,
            'sales'           => App\Http\Controllers\SaleController::class,
        ]);

        Route::post('deliveries',        [App\Http\Controllers\DeliveryController::class, 'store']);
        Route::post('nozzle-readings',   [App\Http\Controllers\NozzleReadingController::class, 'store']);
        Route::post('deep-readings',     [App\Http\Controllers\DeepReadingController::class, 'store']);
        Route::post('stock-adjustments', [App\Http\Controllers\StockAdjustmentController::class, 'store']);
    });
});
