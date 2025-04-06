<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;


Route::prefix('admin')->group(function () {


    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        // Authenticated routes
        Route::get('/logout', [AuthController::class, 'logout']);
        Route::get('/verify', [AuthController::class, 'user']);
        Route::put('/user/profile', [AuthController::class, 'updateProfile']);


        // User management routes
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);


     
    
      
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
