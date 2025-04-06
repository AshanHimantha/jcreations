<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
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
     *             @OA\Property(property="item", ref="#/components/schemas/CartItemWithProduct")
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
            
            $response = response()->json([
                'message' => 'Item added to cart',
                'item' => $cartItem
            ]);
            
            // If there's a cookie to set, add it to the response
            if ($result['cookie']) {
                $response->cookie($result['cookie']);
            }
            
            return $response;
            
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
     *             @OA\Property(property="item", ref="#/components/schemas/CartItem")
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
            
            $result = $this->cartService->getOrCreateCart($request);
            $cart = $result['cart'];
            
            // Find the cart item
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->firstOrFail();
                
            // Update quantity
            $cartItem->quantity = $validated['quantity'];
            $cartItem->save();
            
            $response = response()->json([
                'message' => 'Cart item updated',
                'item' => $cartItem
            ]);
            
            // If there's a cookie to set, add it to the response
            if ($result['cookie']) {
                $response->cookie($result['cookie']);
            }
            
            return $response;
            
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
            $result = $this->cartService->getOrCreateCart($request);
            $cart = $result['cart'];
            
            // Find and delete the cart item
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('id', $id)
                ->firstOrFail();
                
            $cartItem->delete();
            
            $response = response()->json([
                'message' => 'Item removed from cart'
            ]);
            
            // If there's a cookie to set, add it to the response
            if ($result['cookie']) {
                $response->cookie($result['cookie']);
            }
            
            return $response;
            
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