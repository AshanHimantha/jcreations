<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\DeliveryLocation;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Orders",
 *     description="API endpoints for order management including cash on delivery and online payments"
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders/cod",
     *     summary="Create a new cash on delivery order",
     *     description="Creates a new order with payment type set to cash on delivery",
     *     operationId="createCodOrder",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Order creation details",
     *         @OA\JsonContent(
     *             required={"cart_id", "customer_name", "contact_number", "city", "address"},
     *             @OA\Property(property="cart_id", type="integer", example=1, description="ID of the cart containing items to order"),
     *             @OA\Property(property="customer_name", type="string", example="John Doe", description="Full name of the customer"),
     *             @OA\Property(property="contact_number", type="string", example="1234567890", description="Contact phone number for delivery"),
     *             @OA\Property(property="city", type="string", example="New York", description="City for delivery (must exist in delivery_locations table)"),
     *             @OA\Property(property="address", type="string", example="123 Main St", description="Detailed delivery address"),
     *             @OA\Property(property="firebase_uid", type="string", example="abc123xyz", nullable=true, description="Firebase user ID for authenticated users"),
     *             @OA\Property(property="req_datetime", type="string", format="date-time", example="2025-04-30T14:00:00Z", nullable=true, description="Requested delivery date and time"),
     *             @OA\Property(property="shipping_charge", type="number", format="float", example=10.00, nullable=true, description="Custom shipping charge (if not using location-based charge)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cash on delivery order created successfully"),
     *             @OA\Property(property="order_id", type="integer", example=123),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="total_amount", type="number", format="float", example=150.75)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", example={"cart_id": {"The cart id field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart not found or empty",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Cart not found or empty")
     *         )
     *     )
     * )
     */
    public function createCodOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|exists:carts,id',
            'customer_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'city' => 'required|exists:delivery_locations,city,is_active,1',
            'address' => 'required|string|max:255',
            'firebase_uid' => 'nullable|string|max:128',
            'req_datetime' => 'nullable|date',
            'shipping_charge' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Fetch cart with items and their products
        $cart = Cart::with('items.product')->find($request->cart_id);
        
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Cart not found or empty'], 404);
        }

        // Get shipping charge from delivery location
        $deliveryLocation = DeliveryLocation::where('city', $request->city)
            ->where('is_active', true)
            ->firstOrFail();
        $shippingCharge = $deliveryLocation->shipping_charge;

        // Create order
        $order = Order::create([
            'customer_name' => $request->customer_name,
            'contact_number' => $request->contact_number,
            'delivery_location_id' => $deliveryLocation->id,  // Use delivery_location_id instead of city
            'address' => $request->address,
            'firebase_uid' => $request->firebase_uid,
            'status' => 'pending',
            'req_datetime' => $request->req_datetime ?? now(),
            'payment_type' => 'cash_on_delivery',
            'total_amount' => $cart->total,
            'shipping_charge' => $shippingCharge,
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
        
        // Send SMS notification for COD order
        try {
            $recipient = $order->contact_number;
            
            $response = \Illuminate\Support\Facades\Http::post('https://app.text.lk/api/http/sms/send', [
                'api_token' => '490|p1hxF1oYz3QxBTHQjuODsDjHVFPjQ59Tx7QU2o5i9f850b6f',
                'recipient' => $recipient,
                'sender_id' => 'TextLKDemo',
                'type' => 'plain',
                'message' => "Your Cash on Delivery order #{$order->id} has been successfully placed. Thank you for your purchase!"
            ]);
            
            if ($response->successful()) {
                \Illuminate\Support\Facades\Log::info("SMS notification sent for COD order #{$order->id}");
            } else {
                \Illuminate\Support\Facades\Log::error("Failed to send SMS notification for COD order #{$order->id}: " . $response->body());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("SMS notification error for COD order #{$order->id}: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Cash on delivery order created successfully',
            'order_id' => $order->id,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/orders/online",
     *     summary="Create a new online order with card payment",
     *     description="Creates a new order with payment type set to card payment and returns payment gateway information",
     *     operationId="createOnlineOrder",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Order creation details",
     *         @OA\JsonContent(
     *             required={"cart_id", "customer_name", "contact_number", "city", "address"},
     *             @OA\Property(property="cart_id", type="integer", example=1, description="ID of the cart containing items to order"),
     *             @OA\Property(property="customer_name", type="string", example="John Doe", description="Full name of the customer"),
     *             @OA\Property(property="contact_number", type="string", example="1234567890", description="Contact phone number for delivery"),
     *             @OA\Property(property="city", type="string", example="New York", description="City for delivery (must exist in delivery_locations table)"),
     *             @OA\Property(property="address", type="string", example="123 Main St", description="Detailed delivery address"),
     *             @OA\Property(property="firebase_uid", type="string", example="abc123xyz", nullable=true, description="Firebase user ID for authenticated users"),
     *             @OA\Property(property="req_datetime", type="string", format="date-time", example="2025-04-30T14:00:00Z", nullable=true, description="Requested delivery date and time"),
     *             @OA\Property(property="shipping_charge", type="number", format="float", example=10.00, nullable=true, description="Custom shipping charge (if not using location-based charge)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Online order created successfully"),
     *             @OA\Property(property="order_id", type="integer", example=123),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="payment_status", type="string", example="pending"),
     *             @OA\Property(property="total_amount", type="string", example="150.75"),
     *             @OA\Property(property="shipping_charge", type="string", example="10.00"),
     *             @OA\Property(property="total_with_shipping", type="string", example="160.75"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="product_name", type="string", example="Product Name"),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="unit_price", type="string", example="75.38"),
     *                     @OA\Property(property="subtotal", type="string", example="150.75")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="payment_data",
     *                 type="object",
     *                 @OA\Property(property="merchant_id", type="string", example="1221046"),
     *                 @OA\Property(property="order_id", type="integer", example=123),
     *                 @OA\Property(property="amount", type="string", example="160.75"),
     *                 @OA\Property(property="currency", type="string", example="LKR"),
     *                 @OA\Property(property="hash", type="string", example="A1B2C3D4E5F6...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", example={"cart_id": {"The cart id field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart not found or empty",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Cart not found or empty")
     *         )
     *     )
     * )
     */
    public function createOnlineOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required|exists:carts,id',
            'customer_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'city' => 'required|exists:delivery_locations,city,is_active,1',
            'address' => 'required|string|max:255',
            'firebase_uid' => 'nullable|string|max:128',
            'req_datetime' => 'nullable|date',
            'shipping_charge' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Fetch cart with items and their products
        $cart = Cart::with('items.product')->find($request->cart_id);
        
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Cart not found or empty'], 404);
        }

        // Get shipping charge from delivery location
        $deliveryLocation = DeliveryLocation::where('city', $request->city)
            ->where('is_active', true)
            ->firstOrFail();
        $shippingCharge = $deliveryLocation->shipping_charge;

        // Calculate total amount including shipping
        $totalWithShipping = $cart->total + $shippingCharge;

        // Create order
        $order = Order::create([
            'customer_name' => $request->customer_name,
            'contact_number' => $request->contact_number,
            'delivery_location_id' => $deliveryLocation->id,  // Use delivery_location_id instead of city
            'address' => $request->address,
            'firebase_uid' => $request->firebase_uid,
            'status' => 'pending',
            'req_datetime' => $request->req_datetime ?? now(),
            'payment_type' => 'card_payment',
            'payment_status' => 'pending',  // Default payment status is pending
            'total_amount' => $cart->total,
            'shipping_charge' => $shippingCharge,
            'order_datetime' => now(),
            'cart_id' => $cart->id, // Store cart_id for later deletion
        ]);

        // Create order items from cart items
        $orderItems = [];
        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'unit_price' => $item->product->price * ((100 - $item->product->discount_percentage) / 100),
                'total_price' => $item->subtotal,
            ]);
            
            $orderItems[] = [
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                'unit_price' => number_format($item->product->price * ((100 - $item->product->discount_percentage) / 100), 2),
                'subtotal' => number_format($item->subtotal, 2),
            ];
        }

        // Generate payment gateway hash
        $merchant_id = '1221046';
        $merchant_secret = config('services.payment_gateway.merchant_secret', 'YOUR_DEFAULT_SECRET'); // Get from config
        $order_id = $order->id;
        $amount = $totalWithShipping;
        $currency = 'LKR';
        
        $hash = strtoupper(
            md5(
                $merchant_id . 
                $order_id . 
                number_format($amount, 2, '.', '') . 
                $currency .  
                strtoupper(md5($merchant_secret)) 
            ) 
        );

    

        return response()->json([
            'message' => 'Online order created successfully',
            'order_id' => $order->id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_amount' => number_format($order->total_amount, 2),
            'shipping_charge' => number_format($shippingCharge, 2),
            'total_with_shipping' => number_format($totalWithShipping, 2),
            'items' => $orderItems,
            'payment_data' => [
                'merchant_id' => $merchant_id,
                'order_id' => $order_id,
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => $currency,
                'hash' => $hash
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Get order details",
     *     description="Retrieves detailed information about a specific order including all order items",
     *     operationId="getOrder",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order details",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=123),
     *             @OA\Property(property="customer_name", type="string", example="John Doe"),
     *             @OA\Property(property="contact_number", type="string", example="1234567890"),
     *             @OA\Property(property="delivery_location_id", type="integer", example=5),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="firebase_uid", type="string", example="abc123xyz", nullable=true),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="payment_type", type="string", example="cash_on_delivery"),
     *             @OA\Property(property="payment_status", type="string", example="pending", nullable=true),
     *             @OA\Property(property="total_amount", type="number", format="float", example=150.75),
     *             @OA\Property(property="shipping_charge", type="number", format="float", example=10.00),
     *             @OA\Property(property="order_datetime", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
     *             @OA\Property(property="req_datetime", type="string", format="date-time", example="2025-04-30T14:00:00Z", nullable=true),
     *             @OA\Property(
     *                 property="orderItems",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=456),
     *                     @OA\Property(property="order_id", type="integer", example=123),
     *                     @OA\Property(property="product_name", type="string", example="Product Name"),
     *                     @OA\Property(property="quantity", type="integer", example=2),
     *                     @OA\Property(property="unit_price", type="number", format="float", example=75.38),
     *                     @OA\Property(property="total_price", type="number", format="float", example=150.75)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Order not found")
     *         )
     *     )
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
     *     path="/api/admin/orders/{id}/status",
     *     summary="Update order status",
     *     description="Updates the processing status of an existing order",
     *     operationId="updateOrderStatus",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="New order status",
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "processing", "shipped", "delivered", "cancelled"},
     *                 example="processing",
     *                 description="The new status for the order"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order status updated successfully"),
     *             @OA\Property(property="order_id", type="integer", example=123),
     *             @OA\Property(property="status", type="string", example="processing")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", example={"status": {"The status field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Order not found")
     *         )
     *     )
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
     * @OA\Put(
     *     path="/api/admin/orders/{id}/payment-status",
     *     summary="Update order payment status",
     *     description="Updates the payment status of an existing order",
     *     operationId="updatePaymentStatus",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="New payment status",
     *         @OA\JsonContent(
     *             required={"payment_status"},
     *             @OA\Property(
     *                 property="payment_status",
     *                 type="string",
     *                 enum={"pending", "success", "failed"},
     *                 example="success",
     *                 description="The new payment status for the order"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order payment status updated successfully"),
     *             @OA\Property(property="order_id", type="integer", example=123),
     *             @OA\Property(property="payment_status", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", example={"payment_status": {"The payment status field is required."}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Order not found")
     *         )
     *     )
     * )
     */
    public function updatePaymentStatus(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_status' => 'required|string|in:pending,success,failed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order = Order::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->payment_status = $request->payment_status;
        $order->save();
        
        return response()->json([
            'message' => 'Order payment status updated successfully',
            'order_id' => $order->id,
            'payment_status' => $order->payment_status,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/user/{firebase_uid}/orders",
     *     summary="Get all orders for a specific user",
     *     description="Retrieves a paginated list of all orders associated with a specific Firebase user ID",
     *     operationId="getUserOrders",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="firebase_uid",
     *         in="path",
     *         required=true,
     *         description="Firebase user ID",
     *         @OA\Schema(type="string", example="abc123xyz")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter orders by status",
     *         @OA\Schema(type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of records per page (default 15)",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of user's orders",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(
     *                 property="data", 
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="customer_name", type="string", example="John Doe"),
     *                     @OA\Property(property="contact_number", type="string", example="1234567890"),
     *                     @OA\Property(property="delivery_location_id", type="integer", example=5),
     *                     @OA\Property(property="address", type="string", example="123 Main St"),
     *                     @OA\Property(property="firebase_uid", type="string", example="abc123xyz", nullable=true),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="payment_type", type="string", example="cash_on_delivery"),
     *                     @OA\Property(property="payment_status", type="string", example="pending", nullable=true),
     *                     @OA\Property(property="total_amount", type="number", format="float", example=150.75),
     *                     @OA\Property(property="shipping_charge", type="number", format="float", example=10.00),
     *                     @OA\Property(property="order_datetime", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
     *                     @OA\Property(property="req_datetime", type="string", format="date-time", example="2025-04-30T14:00:00Z", nullable=true),
     *                     @OA\Property(
     *                         property="orderItems",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=456),
     *                             @OA\Property(property="product_name", type="string", example="Product Name"),
     *                             @OA\Property(property="quantity", type="integer", example=2),
     *                             @OA\Property(property="unit_price", type="number", format="float", example=75.38)
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="first_page_url", type="string", example="http://example.com/api/user/abc123xyz/orders?page=1"),
     *             @OA\Property(property="from", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=2),
     *             @OA\Property(property="last_page_url", type="string", example="http://example.com/api/user/abc123xyz/orders?page=2"),
     *             @OA\Property(property="next_page_url", type="string", example="http://example.com/api/user/abc123xyz/orders?page=2"),
     *             @OA\Property(property="path", type="string", example="http://example.com/api/user/abc123xyz/orders"),
     *             @OA\Property(property="per_page", type="integer", example=15),
     *             @OA\Property(property="prev_page_url", type="string", example=null),
     *             @OA\Property(property="to", type="integer", example=15),
     *             @OA\Property(property="total", type="integer", example=25)
     *         )
     *     )
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
     *     path="/api/admin/orders",
     *     summary="Get all orders with optional filtering",
     *     description="Retrieves a list of all orders with optional filters for status, payment type, and user",
     *     operationId="getAllOrders",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter orders by status",
     *         @OA\Schema(type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="payment_type",
     *         in="query",
     *         description="Filter orders by payment type",
     *         @OA\Schema(type="string", enum={"cash_on_delivery", "card_payment"})
     *     ),
     *     @OA\Parameter(
     *         name="firebase_uid",
     *         in="query",
     *         description="Filter orders by user's Firebase ID",
     *         @OA\Schema(type="string", example="abc123xyz")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records to return (default: all records)",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of orders",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="customer_name", type="string", example="John Doe"),
     *                 @OA\Property(property="contact_number", type="string", example="1234567890"),
     *                 @OA\Property(property="delivery_location_id", type="integer", example=5),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="firebase_uid", type="string", example="abc123xyz", nullable=true),
     *                 @OA\Property(property="status", type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled"}, example="pending"),
     *                 @OA\Property(property="payment_type", type="string", enum={"cash_on_delivery", "card_payment"}, example="cash_on_delivery"),
     *                 @OA\Property(property="payment_status", type="string", enum={"pending", "success", "failed"}, example="pending", nullable=true),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=150.75),
     *                 @OA\Property(property="shipping_charge", type="number", format="float", example=10.00),
     *                 @OA\Property(property="order_datetime", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
     *                 @OA\Property(property="req_datetime", type="string", format="date-time", example="2025-04-30T14:00:00Z", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
     *                 @OA\Property(
     *                     property="orderItems",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=456),
     *                         @OA\Property(property="order_id", type="integer", example=123),
     *                         @OA\Property(property="product_name", type="string", example="Product Name"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="unit_price", type="number", format="float", example=75.38),
     *                         @OA\Property(property="total_price", type="number", format="float", example=150.75)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllOrders(Request $request): JsonResponse
    {
        $query = Order::query();
        
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
    
        // Order by created_at desc to get latest orders first
        $query->orderBy('created_at', 'desc');
        
        // Limit results if specified, otherwise get all
        if ($request->has('limit') && is_numeric($request->limit) && $request->limit > 0) {
            $orders = $query->take($request->limit)->get();
        } else {
            $orders = $query->get();
        }
        
        return response()->json($orders, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/orders/search",
     *     summary="Search orders by various criteria",
     *     description="Performs a search across orders with filtering options for text search, status, payment type, and date range",
     *     operationId="searchOrders",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Search query (searches in customer name, order ID, contact number, and address)",
     *         @OA\Schema(type="string", minLength=2, example="john")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter orders by status",
     *         @OA\Schema(type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="payment_type",
     *         in="query",
     *         description="Filter orders by payment type",
     *         @OA\Schema(type="string", enum={"cash_on_delivery", "card_payment"})
     *     ),
     *     @OA\Parameter(
     *         name="from_date",
     *         in="query",
     *         description="Filter orders from this date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2025-04-01")
     *     ),
     *     @OA\Parameter(
     *         name="to_date",
     *         in="query",
     *         description="Filter orders until this date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date", example="2025-04-27")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records per page (default 20)",
     *         @OA\Schema(type="integer", default=20, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="customer_name", type="string", example="John Doe"),
     *                 @OA\Property(property="contact_number", type="string", example="1234567890"),
     *                 @OA\Property(property="delivery_location_id", type="integer", example=5),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="firebase_uid", type="string", example="abc123xyz", nullable=true),
     *                 @OA\Property(property="status", type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled"}, example="pending"),
     *                 @OA\Property(property="payment_type", type="string", enum={"cash_on_delivery", "card_payment"}, example="cash_on_delivery"),
     *                 @OA\Property(property="payment_status", type="string", enum={"pending", "success", "failed"}, example="pending", nullable=true),
     *                 @OA\Property(property="total_amount", type="number", format="float", example=150.75),
     *                 @OA\Property(property="shipping_charge", type="number", format="float", example=10.00),
     *                 @OA\Property(property="order_datetime", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
     *                 @OA\Property(property="req_datetime", type="string", format="date-time", example="2025-04-30T14:00:00Z", nullable=true),
     *                 @OA\Property(
     *                     property="orderItems",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=456),
     *                         @OA\Property(property="order_id", type="integer", example=123),
     *                         @OA\Property(property="product_name", type="string", example="Product Name"),
     *                         @OA\Property(property="quantity", type="integer", example=2),
     *                         @OA\Property(property="unit_price", type="number", format="float", example=75.38),
     *                         @OA\Property(property="total_price", type="number", format="float", example=150.75)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object", example={"query": {"The query field is required."}})
     *         )
     *     )
     * )
     */
    public function searchOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
            'status' => 'nullable|string|in:pending,processing,shipped,delivered,cancelled',
            'payment_type' => 'nullable|string|in:cash_on_delivery,card_payment',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d',
            'limit' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Order::query()->with('orderItems');
        $searchTerm = $request->query('query');

        // Search in multiple columns
        $query->where(function ($q) use ($searchTerm) {
            $q->where('customer_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('id', 'LIKE', "%{$searchTerm}%")
              ->orWhere('contact_number', 'LIKE', "%{$searchTerm}%")
              ->orWhere('address', 'LIKE', "%{$searchTerm}%");
        });

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Order by created_at desc to get latest orders first
        $query->orderBy('created_at', 'desc');
        
        // Limit results if specified, otherwise get all
        if ($request->has('limit') && is_numeric($request->limit) && $request->limit > 0) {
            $orders = $query->take($request->limit)->get();
        } else {
            $orders = $query->get();
        }

        return response()->json($orders, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/orders/{id}/cancel",
     *     summary="Cancel an order",
     *     description="Cancels an existing order if it's in a cancellable state (not delivered or shipped)",
     *     operationId="cancelOrder",
     *     tags={"Orders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully"),
     *             @OA\Property(property="order_id", type="integer", example=123)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot cancel order in current status",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Cannot cancel order in shipped status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Order not found")
     *         )
     *     )
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

    
   

/**
 * @OA\Schema(
 *     schema="Order",
 *     title="Order",
 *     description="Order model",
 *     @OA\Property(property="id", type="integer", example=123),
 *     @OA\Property(property="customer_name", type="string", example="John Doe"),
 *     @OA\Property(property="contact_number", type="string", example="1234567890"),
 *     @OA\Property(property="delivery_location_id", type="integer", example=5),
 *     @OA\Property(property="address", type="string", example="123 Main St"),
 *     @OA\Property(property="firebase_uid", type="string", example="abc123xyz", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"pending", "processing", "shipped", "delivered", "cancelled"}, example="pending"),
 *     @OA\Property(property="payment_type", type="string", enum={"cash_on_delivery", "card_payment"}, example="cash_on_delivery"),
 *     @OA\Property(property="payment_status", type="string", enum={"pending", "success", "failed"}, example="pending", nullable=true),
 *     @OA\Property(property="total_amount", type="number", format="float", example=150.75),
 *     @OA\Property(property="shipping_charge", type="number", format="float", example=10.00),
 *     @OA\Property(property="order_datetime", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
 *     @OA\Property(property="req_datetime", type="string", format="date-time", example="2025-04-30T14:00:00Z", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
 *     @OA\Property(
 *         property="orderItems",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/OrderItem")
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="OrderItem",
 *     title="OrderItem",
 *     description="Order item model",
 *     @OA\Property(property="id", type="integer", example=456),
 *     @OA\Property(property="order_id", type="integer", example=123),
 *     @OA\Property(property="product_name", type="string", example="Product Name"),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="unit_price", type="number", format="float", example=75.38),
 *     @OA\Property(property="total_price", type="number", format="float", example=150.75),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-04-27T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-04-27T10:30:00Z")
 * )
 */
}