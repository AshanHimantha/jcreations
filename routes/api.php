<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['firebase.auth'])->group(function () {

    Route::get('/user', function (Request $request) {
        return response()->json([
            'message' => 'Authenticated!',
            'user' => $request->attributes->get('firebaseUser'),
        ]);
    });
    
});
