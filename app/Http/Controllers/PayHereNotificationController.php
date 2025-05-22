<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PayHereNotificationController extends Controller
{
    public function handleNotification(Request $request)
    {
        // Log the request for debugging
        Log::info('PayHere notification received', $request->all());

        $merchant_id = $request->input('merchant_id');
        $order_id = $request->input('order_id');
        $payhere_amount = $request->input('payhere_amount');
        $payhere_currency = $request->input('payhere_currency');
        $status_code = $request->input('status_code');
        $md5sig = $request->input('md5sig');

        // Get merchant secret from config
        $merchant_secret = config('services.payment_gateway.merchant_secret');

        // Generate local MD5 signature for verification
        $local_md5sig = strtoupper(
            md5(
                $merchant_id . 
                $order_id . 
                $payhere_amount . 
                $payhere_currency . 
                $status_code . 
                strtoupper(md5($merchant_secret)) 
            ) 
        );

        // Verify signature and check if payment is successful
        if (($local_md5sig === $md5sig) && ($status_code == 2)) {
            // Find the order
            $order = Order::find($order_id);
            
            if ($order) {
                // Update payment status to success
                $order->payment_status = 'success';
                $order->save();
                
                // Delete cart if cart_id exists
                if ($order->cart_id) {
                    $cart = Cart::find($order->cart_id);
                    if ($cart) {
                        // Delete cart items first
                        $cart->items()->delete();
                        // Then delete the cart
                        $cart->delete();
                    }
                }
                
                // Send SMS notification
                try {
                    // Get recipient phone number from order
                    $recipient = $order->contact_number; // Update this field name based on your Order model
                    
                    $response = \Illuminate\Support\Facades\Http::post('https://app.text.lk/api/http/sms/send', [
                        'api_token' => env('TEXT_LK_API_TOKEN'),
                        'recipient' => $recipient,
                        'sender_id' => env('TEXT_LK_SENDER_ID'),
                        'type' => 'plain',
                        'message' => "Your order #{$order_id} has been successfully paid and confirmed. View your invoice: https://jcreations.lk/invoice/{$order_id}. Thank you for your purchase!"
                    ]);
                    
                    if ($response->successful()) {
                        Log::info("SMS notification sent for order #{$order_id}");
                    } else {
                        Log::error("Failed to send SMS notification for order #{$order_id}: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("SMS notification error for order #{$order_id}: " . $e->getMessage());
                }
                
                Log::info("Payment successful for order #{$order_id}. Cart deleted.");
                return response('Payment notification processed successfully', 200);
            } else {
                Log::error("Order #{$order_id} not found");
                return response('Order not found', 404);
            }
            } else {
                    // Log verification failure
                    if ($local_md5sig !== $md5sig) {
                        Log::warning("MD5 signature mismatch for order #{$order_id}");
                    }
                    if ($status_code != 2) {
                        Log::warning("Payment unsuccessful for order #{$order_id}, status code: {$status_code}");
                    }
                    
                    // Find and delete the order
                    $order = Order::find($order_id);
                    if ($order) {
                        $order->delete();
                        Log::info("Order #{$order_id} deleted due to payment verification failure");
                    } else {
                        Log::warning("Failed to delete order #{$order_id}: Order not found");
                    }
                    
                    return response('Invalid signature or payment not successful', 400);
                }
    }
}