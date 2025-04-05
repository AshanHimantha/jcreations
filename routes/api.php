<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Test cookie route - place before authenticated routes
Route::get('/test-cookie', function (Request $request) {
    $minutes = 60;
    $response = response()->json([
        'message' => 'Test cookie created successfully!',
        'timestamp' => now()->toIso8601String()
    ]);
    
    return $response->cookie(
        'test_cookie', 
        json_encode(['test' => 'data', 'created_at' => now()->toIso8601String()]), 
        $minutes
    );
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
