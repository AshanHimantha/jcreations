<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;


Route::prefix('admin')->group(function () {

    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);


    Route::middleware('auth:sanctum')->group(function () {

        // Authenticated routes
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::get('/verify', [AuthController::class, 'user']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);


        // User management routes
        Route::apiResource('users', UserController::class);

        // Role management routes
        Route::get('/roles', [UserController::class, 'getAvailableRoles']);
        Route::post('/users/{user}/roles', [UserController::class, 'assignRoles']);
        Route::delete('/users/{user}/roles', [UserController::class, 'removeRoles']);

        // Role verification routes
        Route::get('/users/{user}/is-admin', [UserController::class, 'isAdmin']);
        Route::get('/users/{user}/is-staff', [UserController::class, 'isStaff']);
        Route::get('/users/{user}/is-cashier', [UserController::class, 'isCashier']);
    });
});


// Firebase authenticated routes
Route::middleware(['firebase.auth'])->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'message' => 'Authenticated!',
            'user' => $request->attributes->get('firebaseUser'),
        ]);
    });
});
