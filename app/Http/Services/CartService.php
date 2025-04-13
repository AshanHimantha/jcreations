<?php

namespace App\Http\Services;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class CartService
{
    const COOKIE_NAME = 'cart_session_id';
    const COOKIE_EXPIRY = 43200; // 30 days in minutes
    
    /**
     * Get or create a cart for the current user/session.
     *
     * @param Request $request
     * @return array Contains the cart model and a cookie if one was created
     */
    public function getOrCreateCart(Request $request)
    {
        $firebaseUid = null;
        $sessionId = $request->cookie(self::COOKIE_NAME);
        $newCookie = null;
        
        // Check if user is authenticated with Firebase
        if ($request->user()) {
            $firebaseUid = $request->user()->firebase_uid;
            
            // Look for existing cart with this Firebase UID
            $cart = Cart::where('firebase_uid', $firebaseUid)->first();
            
            // If user has a session cart, migrate it to their account
            if (!$cart && $sessionId) {
                $sessionCart = Cart::where('session_id', $sessionId)->first();
                if ($sessionCart) {
                    $sessionCart->firebase_uid = $firebaseUid;
                    $sessionCart->session_id = null;
                    $sessionCart->save();
                    return ['cart' => $sessionCart, 'cookie' => null];
                }
            }
            
            // Create new cart if none exists
            if (!$cart) {
                $cart = Cart::create(['firebase_uid' => $firebaseUid]);
            }
            
            return ['cart' => $cart, 'cookie' => null];
        }
        
        // For guest users, use session ID
        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
            $newCookie = Cookie::make(self::COOKIE_NAME, $sessionId, self::COOKIE_EXPIRY);
        }
        
        $cart = Cart::where('session_id', $sessionId)->first();
        
        if (!$cart) {
            $cart = Cart::create(['session_id' => $sessionId]);
        }
        
        return ['cart' => $cart, 'cookie' => $newCookie];
    }

    /**
     * Sync session cart with user cart when a user logs in
     * 
     * @param string $firebaseUid The Firebase UID of the logged-in user
     * @param string $sessionId The session ID of the anonymous user
     * @return Cart The synced user cart
     */
    public function syncSessionCartWithUserCart(string $firebaseUid, string $sessionId)
    {
        // Find the session cart
        $sessionCart = Cart::where('session_id', $sessionId)->first();
        
        // Find or create the user cart
        $userCart = Cart::firstOrCreate(
            ['firebase_uid' => $firebaseUid],
            ['session_id' => null]
        );
        
        // If there's a session cart with items, merge them into the user cart
        if ($sessionCart && $sessionCart->items()->count() > 0) {
            // Get all items from session cart
            $sessionItems = $sessionCart->items()->get();
            
            foreach ($sessionItems as $item) {
                // Check if this product already exists in user cart
                $existingItem = $userCart->items()
                    ->where('product_id', $item->product_id)
                    ->first();
                
                if ($existingItem) {
                    // Update quantity if product already in cart
                    $existingItem->quantity += $item->quantity;
                    $existingItem->save();
                } else {
                    // Create new cart item if product not in cart
                    $userCart->items()->create([
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity
                    ]);
                }
            }
            
            // Delete the session cart after merging
            $sessionCart->items()->delete();
            $sessionCart->delete();
        }
        
        return $userCart;
    }
}