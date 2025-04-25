<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="API endpoints for cash on delivery order management"
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders/cod",
     *     summary="Create a new cash on delivery order",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cart_id", "customer_name", "contact_number", "city", "address"},
     *             @OA\Property(property="cart_id", type="integer", example=1),
     *             @OA\Property(property="customer_name", type="string", example="John Doe"),
     *             @OA\Property(property="contact_number", type="string", example="1234567890"),
     *             @OA\Property(property="city", type="string", example="New York"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="firebase_uid", type="string", example="abc123xyz", nullable=true),
     *             @OA\Property(property="req_datetime", type="string", format="date-time", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation errors")
     * )
     */
    public function createCodOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|exists:carts,id',
            'customer_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'firebase_uid' => 'nullable|string|max:128',
            'req_datetime' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Fetch cart with items and their products
        $cart = Cart::with('items.product')->find($request->cart_id);
        
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Cart not found or empty'], 404);
        }

        // Create order
        $order = Order::create([
            'customer_name' => $request->customer_name,
            'contact_number' => $request->contact_number,
            'city' => $request->city,
            'address' => $request->address,
            'firebase_uid' => $request->firebase_uid, // Store firebase_uid when provided
            'status' => 'pending',
            'req_datetime' => $request->req_datetime ?? now(),
            'payment_type' => 'cash_on_delivery', // Changed from 'cod' to 'cash_on_delivery'
            'total_amount' => $cart->total,
            'order_datetime' => now(),
        ]);

        // Create order items from cart items
        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->product->price * ((100 - $item->product->discount_percentage) / 100),
                'total_price' => $item->subtotal,
            ]);
        }

        // Clear the cart after order is created
        $cart->items()->delete();

        return response()->json([
            'message' => 'Cash on delivery order created successfully',
            'order_id' => $order->id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Get order details",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Order details"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function getOrder($id): JsonResponse
    {
        $order = Order::with('orderItems')->find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
        
        return response()->json($order, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/orders/{id}/status",
     *     summary="Update order status",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "processing", "shipped", "delivered", "cancelled"},
     *                 example="processing"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status updated successfully"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function updateOrderStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->status = $request->status;
        $order->save();
        
        return response()->json([
            'message' => 'Order status updated successfully',
            'order_id' => $order->id,
            'status' => $order->status,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/user/{firebase_uid}/orders",
     *     summary="Get all orders for a specific user",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="firebase_uid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         @OA\Schema(type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled"})
     *     ),
     *     @OA\Response(response=200, description="List of user's orders")
     * )
     */
    public function getUserOrders($firebaseUid, Request $request): JsonResponse
    {
        $query = Order::query()
            ->with('orderItems')
            ->where('firebase_uid', $firebaseUid);
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);
        
        return response()->json($orders, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     summary="Get all orders with optional filtering",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         @OA\Schema(type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="payment_type",
     *         in="query",
     *         @OA\Schema(type="string", enum={"cash_on_delivery", "card_payment"})
     *     ),
     *     @OA\Parameter(
     *         name="firebase_uid",
     *         in="query",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of orders")
     * )
     */
    public function getAllOrders(Request $request): JsonResponse
    {
        $query = Order::query()->with('orderItems');
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by payment type
        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }
        
        // Filter by firebase_uid
        if ($request->has('firebase_uid')) {
            $query->where('firebase_uid', $request->firebase_uid);
        }

        // Paginate results
        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);
        
        return response()->json($orders, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/orders/{id}/cancel",
     *     summary="Cancel an order",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Order cancelled successfully"),
     *     @OA\Response(response=400, description="Cannot cancel order in current status"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function cancelOrder($id): JsonResponse
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
        
        // Check if order can be cancelled
        if (in_array($order->status, ['delivered', 'shipped'])) {
            return response()->json([
                'error' => 'Cannot cancel order in ' . $order->status . ' status'
            ], 400);
        }
        
        $order->status = 'cancelled';
        $order->save();
        
        return response()->json([
            'message' => 'Order cancelled successfully',
            'order_id' => $order->id,
        ], 200);
    }
}