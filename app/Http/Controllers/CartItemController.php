<?php

namespace App\Http\Controllers;

use App\Http\Services\CartService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Cart Items",
 *     description="API endpoints for managing items in the shopping cart"
 * )
 */
class CartItemController extends Controller
{
    protected $cartService;
    
    /**
     * CartItemController constructor.
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Add an item to the cart.
     * 
     * @OA\Post(
     *     path="/api/cart/items",
     *     summary="Add item to cart",
     *     description="Adds a product to the shopping cart. Requires cart_id for guest users.",
     *     tags={"Cart Items"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id", "quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=2),
     *             @OA\Property(property="wish", type="string", nullable=true, example="Happy Birthday Ashan",
     *                          description="Custom message for cake decoration"),
     *             @OA\Property(property="cart_id", type="integer", nullable=true, example=1,
     *                          description="Required for guest users to identify their cart")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item added to cart",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Item added to cart"),
     *             @OA\Property(property="item", ref="#/components/schemas/CartItemWithProduct"),
     *             @OA\Property(property="cart_id", type="integer", example=1,
     *                          description="Store this ID for future cart operations")
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
                'quantity' => 'required|integer|min:1',
                'wish' => 'nullable|string|max:255'
            ]);
            
            $result = $this->cartService->getOrCreateCart($request);
            $cart = $result['cart'];
            
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
                // Update quantity and wish if already in cart
                $cartItem->quantity += $validated['quantity'];
                if (isset($validated['wish'])) {
                    $cartItem->wish = $validated['wish'];
                }
                $cartItem->save();
            } else {
                // Add new item to cart
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $validated['product_id'],
                    'quantity' => $validated['quantity'],
                    'wish' => $validated['wish'] ?? null
                ]);
            }
            
            // Load the product relationship
            $cartItem->load('product');
            
            return response()->json([
                'message' => 'Item added to cart',
                'item' => $cartItem,
                'cart_id' => $cart->id
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
     *     description="Updates the quantity and/or wish message of an item in the cart. Requires cart_id for guest users.",
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
     *             @OA\Property(property="quantity", type="integer", example=3, 
     *                          description="Item quantity. Optional - only needed if updating quantity"),
     *             @OA\Property(property="wish", type="string", nullable=true, example="Happy Birthday Ashan",
     *                          description="Custom message for cake decoration"),
     *             @OA\Property(property="cart_id", type="integer", nullable=true, example=1,
     *                          description="Required for guest users to identify their cart")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cart item updated"),
     *             @OA\Property(property="item", ref="#/components/schemas/CartItem"),
     *             @OA\Property(property="cart_id", type="integer", example=1,
     *                          description="Store this ID for future cart operations")
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
                'quantity' => 'sometimes|integer|min:1',
                'wish' => 'nullable|string|max:255'
            ]);
            
            $result = $this->cartService->getOrCreateCart($request);
            $cart = $result['cart'];
            
            // Find the cart item
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->firstOrFail();
                
            // Update quantity if provided
            if (isset($validated['quantity'])) {
                $cartItem->quantity = $validated['quantity'];
            }
            
            // Update wish if provided
            if (isset($validated['wish'])) {
                $cartItem->wish = $validated['wish'];
            }
            
            $cartItem->save();
            
            return response()->json([
                'message' => 'Cart item updated',
                'item' => $cartItem,
                'cart_id' => $cart->id
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
     *     description="Removes an item from the shopping cart. Requires cart_id for guest users.",
     *     tags={"Cart Items"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Cart Item ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="cart_id", type="integer", nullable=true, example=1,
     *                          description="Required for guest users to identify their cart")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Item removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Item removed from cart"),
     *             @OA\Property(property="cart_id", type="integer", example=1,
     *                          description="Store this ID for future cart operations")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Cart item not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $result = $this->cartService->getOrCreateCart($request);
            $cart = $result['cart'];
            
            // Find and delete the cart item
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->firstOrFail();
                
            $cartItem->delete();
            
            return response()->json([
                'message' => 'Item removed from cart',
                'cart_id' => $cart->id
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