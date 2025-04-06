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
        
        $response = response()->json($cart);
        
        // If there's a cookie to set, add it to the response
        if ($result['cookie']) {
            $response->cookie($result['cookie']);
        }
        
        return $response;
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
        $result = $this->cartService->getOrCreateCart($request);
        $cart = $result['cart'];
        
        // Delete all cart items
        $cart->items()->delete();
        
        $response = response()->json([
            'message' => 'Cart cleared successfully'
        ]);
        
        // If there's a cookie to set, add it to the response
        if ($result['cookie']) {
            $response->cookie($result['cookie']);
        }
        
        return $response;
    }
}