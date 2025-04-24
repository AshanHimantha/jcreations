<?php

namespace App\Http\Services;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartService
{
    /**
     * Get or create a cart based on provided data.
     *
     * @param Request $request
     * @return array Contains the cart model
     */
    public function getOrCreateCart(Request $request)
    {
        // For authenticated users, use firebase_uid
        if ($request->user()) {
            $firebaseUid = $request->user()->firebase_uid;
            
            // Look for existing cart with this Firebase UID
            $cart = Cart::where('firebase_uid', $firebaseUid)->first();
            
            // If user has a cart_id in request, try to merge it
            $cartId = $request->json('cart_id');
            if (!$cart && $cartId) {
                $anonymousCart = Cart::find($cartId);
                if ($anonymousCart) {
                    $anonymousCart->firebase_uid = $firebaseUid;
                    $anonymousCart->save();
                    return ['cart' => $anonymousCart];
                }
            }
            
            // Create new cart if none exists
            if (!$cart) {
                $cart = Cart::create(['firebase_uid' => $firebaseUid]);
            }
            
            return ['cart' => $cart];
        }
        
        // For guests, use cart_id from request
        $cartId = $request->json('cart_id');
        
        // Try to find the cart
        if ($cartId) {
            $cart = Cart::find($cartId);
            if ($cart) {
                return ['cart' => $cart];
            }
        }
        
        // Create a new cart if none found
        $cart = Cart::create();
        
        return ['cart' => $cart];
    }

    /**
     * Sync anonymous cart with user cart when a user logs in
     * 
     * @param string $firebaseUid The Firebase UID of the logged-in user
     * @param int $cartId The ID of the anonymous cart
     * @return Cart The synced user cart
     */
    public function syncCartWithUserCart(string $firebaseUid, int $cartId)
    {
        // Find the anonymous cart
        $anonymousCart = Cart::find($cartId);
        
        // Find or create the user cart
        $userCart = Cart::firstOrCreate(
            ['firebase_uid' => $firebaseUid]
        );
        
        // If there's an anonymous cart with items, merge them into the user cart
        if ($anonymousCart && $anonymousCart->items()->count() > 0) {
            // Get all items from anonymous cart
            $anonymousItems = $anonymousCart->items()->get();
            
            foreach ($anonymousItems as $item) {
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
            
            // Delete the anonymous cart after merging
            $anonymousCart->items()->delete();
            $anonymousCart->delete();
        }
        
        return $userCart;
    }
}