<?php

namespace App\Http\Controllers;

use App\Http\Services\CartService;
use App\Models\Cart;

use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Cart",
 *     description="API endpoints for shopping cart management"
 * )
 */
class CartController extends Controller
{
    protected $cartService;
    
    /**
     * CartController constructor.
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }
    
    /**
     * Display the user's cart.
     * 
     * @OA\Get(
     *     path="/api/cart",
     *     summary="Get cart contents",
     *     description="Returns the current cart with all items. Requires cart_id for guest users.",
     *     tags={"Cart"},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="cart_id", type="integer", nullable=true, example=1, 
     *                          description="Required for guest users to identify their cart")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart contents",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="firebase_uid", type="string", nullable=true, example="user123"),
     *             @OA\Property(property="total", type="number", example=129.95),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/CartItem")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $result = $this->cartService->getOrCreateCart($request);
        $cart = $result['cart'];
        
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
     *     description="Removes all items from the cart. Requires cart_id for guest users.",
     *     tags={"Cart"},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="cart_id", type="integer", nullable=true, example=1,
     *                          description="Required for guest users to identify their cart")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cart cleared successfully"),
     *             @OA\Property(property="cart_id", type="integer", example=1,
     *                          description="Store this ID for future cart operations")
     *         )
     *     )
     * )
     */
    public function clear(Request $request)
    {
        $result = $this->cartService->getOrCreateCart($request);
        $cart = $result['cart'];
        
        // Delete all cart items
        $cart->items()->delete();
        
        return response()->json([
            'message' => 'Cart cleared successfully',
            'cart_id' => $cart->id
        ]);
    }
}