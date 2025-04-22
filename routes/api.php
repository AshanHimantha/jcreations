<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\User;


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

Route::get('/test-cookie', function () {
    return response()
        ->json(['message' => 'Test cookie set successfully'])
        ->cookie('test_cookie', 'cookie_value', 60); // Cookie valid for 60 minutes
});

// Add this route for direct cookie debugging
Route::get('/debug-cookie', function () {
    $cookie = cookie('debug_cookie', 'test_value', 60);
    
    // Output cookie details for debugging
    return response()
        ->json([
            'message' => 'Cookie test',
            'cookie_details' => [
                'name' => $cookie->getName(),
                'value' => $cookie->getValue(),
                'minutes' => $cookie->getMaxAge() / 60,
                'path' => $cookie->getPath(),
                'domain' => $cookie->getDomain(),
                'secure' => $cookie->isSecure(),
                'httpOnly' => $cookie->isHttpOnly(),
                'sameSite' => $cookie->getSameSite()
            ]
        ])
        ->cookie($cookie);
});

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);


Route::get('/cart', [CartController::class, 'index']);
Route::delete('/cart', [CartController::class, 'clear']);
Route::post('/cart/items', [CartItemController::class, 'store']);
Route::put('/cart/items/{id}', [CartItemController::class, 'update']);
Route::delete('/cart/items/{id}', [CartItemController::class, 'destroy']);

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
            Route::get('/products', [ProductController::class, 'adminIndex']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::post('/products/{product}', [ProductController::class, 'update']);
            Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        });

        Route::middleware(['role:' . User::ROLE_ADMIN . ',' . User::ROLE_STAFF])->group(function () {
            // Staff routes here - might include product management
        });
        
        Route::middleware(['role:' . User::ROLE_ADMIN . ',' . User::ROLE_CASHIER])->group(function () {
            // Cashier routes here
        });
    });
});
