<?php

use App\Http\Controllers\MobileNumberController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PayHereNotificationController;
use App\Models\User;
use App\Http\Controllers\DeliveryLocationController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\MaintenanceController;

Route::get('create-storage-link', function () {
    Artisan::call('storage:link');
    return response()->json(['message' => 'Storage link created successfully']);
});

Route::get('clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    return response()->json(['message' => 'All caches cleared successfully']);
});

Route::get('migrate', function () {
    Artisan::call('migrate');
    return response()->json(['message' => 'Migration completed successfully']);
});

Route::get('/maintenance/cleanup', [MaintenanceController::class, 'cleanupOldData']);


// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/products/{limit?}', [ProductController::class, 'index']);
Route::get('/product/single/{id}', [ProductController::class, 'show']);
Route::get('/products/search/{limit?}', [ProductController::class, 'search']);

// locations
Route::get('/locations', [DeliveryLocationController::class, 'index']);
Route::get('/locations/{location}', [DeliveryLocationController::class, 'show']);


Route::get('/cart', [CartController::class, 'index']);
Route::get('/cart/{id}', [CartController::class, 'show']);
Route::delete('/cart', [CartController::class, 'clear']);
Route::post('/cart/items', [CartItemController::class, 'store']);
Route::put('/cart/items/{id}', [CartItemController::class, 'update']);
Route::delete('/cart/items/{id}', [CartItemController::class, 'destroy']);


Route::post('/send-verification-code', [EmailVerificationController::class, 'sendVerificationCode']);

Route::get('/mobile-numbers', [MobileNumberController::class, 'index']);


// Cash on Delivery routes
Route::post('/orders/cod', [OrderController::class, 'createCodOrder']);
Route::get('/orders/{id}', [OrderController::class, 'getOrder']);

Route::get('/banner', [BannerController::class, 'show']);

// Add these routes to your existing routes
Route::post('/orders/online', [OrderController::class, 'createOnlineOrder']);

Route::get('/user/{firebase_uid}/orders/{limit}', [OrderController::class, 'getUserOrders']);

Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        // Authenticated routes
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::get('/verify', [AuthController::class, 'user']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);

        Route::middleware(['role:' . User::ROLE_ADMIN])->group(function () {
            // User management routes
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);

            // Protected category management routes (create, update, delete)
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::put('/categories/{category}', [CategoryController::class, 'update']);
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

            // Protected product management routes
            Route::get('/products/limit/{limit}', [ProductController::class, 'adminIndex']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::put('/products/{product}', [ProductController::class, 'update']);
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);


            // Create, update, delete operations
            Route::post('/locations', [DeliveryLocationController::class, 'store']);
            Route::put('/locations/{location}', [DeliveryLocationController::class, 'update']);
            Route::delete('/locations/{location}', [DeliveryLocationController::class, 'destroy']);


            Route::get('orders/search', [OrderController::class, 'searchOrders']);
            Route::put('/orders/{id}/status', [OrderController::class, 'updateOrderStatus']);
            Route::delete('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
            Route::get('/orders', [OrderController::class, 'getAllOrders']);
            Route::put('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus']);

            Route::post('/banner', [BannerController::class, 'store']);
            Route::delete('/banner', [BannerController::class, 'destroy']);

            Route::post('/mobile-numbers', [MobileNumberController::class, 'store']);
            Route::put('/mobile-numbers/{mobile_number}', [MobileNumberController::class, 'update']);
            Route::delete('/mobile-numbers/{mobile_number}', [MobileNumberController::class, 'destroy']);
        });

        Route::middleware(['role:' . User::ROLE_ADMIN . ',' . User::ROLE_STAFF])->group(function () {
            // Staff routes here - might include product management
        });

        Route::middleware(['role:' . User::ROLE_ADMIN . ',' . User::ROLE_CASHIER])->group(function () {
            // Cashier routes here
        });
    });
});

// PayHere notification endpoint
Route::post('/payhere/notify', [PayHereNotificationController::class, 'handleNotification'])->name('payhere.notify');
