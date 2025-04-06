<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Cart Items",
 *     description="API endpoints for managing items in the shopping cart"
 * )
 */
class CartItemController extends Controller
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
     * Add an item to the cart.
     * 
     * @OA\Post(
     *     path="/api/cart/items",
     *     summary="Add item to cart",
     *     description="Adds a product to the shopping cart",
     *     tags={"Cart Items"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item added to cart",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Item added to cart"),
     *             @OA\Property(
     *                 property="item",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="cart_id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(
     *                     property="product",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Chocolate Birthday Cake")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Product not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);
            
            $cart = $this->getOrCreateCart($request);
            
            // Check if product exists and is in stock
            $product = Product::findOrFail($validated['product_id']);
            
            if ($product->status !== 'in_stock') {
                return response()->json([
                    'message' => 'Product is not available for purchase',
                    'status' => $product->status
                ], 422);
            }
            
            // Check if product is already in cart
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $validated['product_id'])
                ->first();
                
            if ($cartItem) {
                // Update quantity if already in cart
                $cartItem->quantity += $validated['quantity'];
                $cartItem->save();
            } else {
                // Add new item to cart
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity']
                ]);
            }
            
            // Load the product relationship
            $cartItem->load('product');
            
            return response()->json([
                'message' => 'Item added to cart',
                'item' => $cartItem
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error adding item to cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the quantity of a cart item.
     * 
     * @OA\Put(
     *     path="/api/cart/items/{id}",
     *     summary="Update cart item",
     *     description="Updates the quantity of an item in the cart",
     *     tags={"Cart Items"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Cart Item ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cart item updated"),
     *             @OA\Property(
     *                 property="item",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="quantity", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Cart item not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'quantity' => 'required|integer|min:1'
            ]);
            
            $cart = $this->getOrCreateCart($request);
            
            // Find the cart item
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->firstOrFail();
                
            // Update quantity
            $cartItem->quantity = $validated['quantity'];
            $cartItem->save();
            
            return response()->json([
                'message' => 'Cart item updated',
                'item' => $cartItem
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating cart item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove an item from the cart.
     * 
     * @OA\Delete(
     *     path="/api/cart/items/{id}",
     *     summary="Remove item from cart",
     *     description="Removes an item from the shopping cart",
     *     tags={"Cart Items"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Cart Item ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Item removed from cart")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Cart item not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $cart = $this->getOrCreateCart($request);
            
            // Find and delete the cart item
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->firstOrFail();
                
            $cartItem->delete();
            
            return response()->json([
                'message' => 'Item removed from cart'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error removing cart item',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}