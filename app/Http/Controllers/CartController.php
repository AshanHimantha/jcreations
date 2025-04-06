<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Cart",
 *     description="API endpoints for shopping cart management"
 * )
 */
class CartController extends Controller
{
    /**
     * Get or create a cart for the current user/session.
     */
    private function getOrCreateCart(Request $request)
    {
        $firebaseUid = null;
        $sessionId = $request->cookie('cart_session_id');
        
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
                    return $sessionCart;
                }
            }
            
            // Create new cart if none exists
            if (!$cart) {
                $cart = Cart::create(['firebase_uid' => $firebaseUid]);
            }
            
            return $cart;
        }
        
        // For guest users, use session ID
        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
            
            // Set cookie that expires in 30 days
            cookie('cart_session_id', $sessionId, 43200);
        }
        
        $cart = Cart::where('session_id', $sessionId)->first();
        
        if (!$cart) {
            $cart = Cart::create(['session_id' => $sessionId]);
        }
        
        return $cart;
    }

    /**
     * Display the user's cart.
     * 
     * @OA\Get(
     *     path="/api/cart",
     *     summary="Get cart contents",
     *     description="Returns the current cart with all items",
     *     tags={"Cart"},
     *     @OA\Response(
     *         response=200,
     *         description="Cart contents",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="firebase_uid", type="string", nullable=true, example="user123"),
     *             @OA\Property(property="session_id", type="string", nullable=true),
     *             @OA\Property(property="total", type="number", example=129.95),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="subtotal", type="number", example=89.98),
     *                     @OA\Property(
     *                         property="product",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Chocolate Birthday Cake"),
     *                         @OA\Property(property="price", type="number", example=45.99),
     *                         @OA\Property(property="discount_percentage", type="number", example=0),
     *                         @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        
        // Load cart items with their products
        $cart->load('items.product');
        
        return response()->json($cart);
    }

    /**
     * Clear all items from the cart.
     * 
     * @OA\Delete(
     *     path="/api/cart",
     *     summary="Clear cart",
     *     description="Removes all items from the cart",
     *     tags={"Cart"},
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cart cleared successfully")
     *         )
     *     )
     * )
     */
    public function clear(Request $request)
    {
        $cart = $this->getOrCreateCart($request);
        
        // Delete all cart items
        $cart->items()->delete();
        
        return response()->json([
            'message' => 'Cart cleared successfully'
        ]);
    }
}