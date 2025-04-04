<?php

namespace App\Http\Middleware;

use Closure;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirebaseAuthMiddleware
{
    protected $auth;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
        $this->auth = $factory->createAuth();
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $verifiedIdToken = $this->auth->verifyIdToken($token);
            $request->attributes->add(['firebaseUser' => $verifiedIdToken->claims()]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid Token'], 401);
        }

        return $next($request);
    }
}
