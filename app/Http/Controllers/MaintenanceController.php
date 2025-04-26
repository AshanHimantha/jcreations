<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaintenanceController extends Controller
{
    /**
     * Clean up old carts and pending orders
     *
     * @return JsonResponse
     */
    public function cleanupOldData(): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $cartCount = $this->removeOldCarts();
            $orderCount = $this->removeOldPendingOrders();
            
            DB::commit();
            
            Log::info("Maintenance cleanup completed: {$cartCount} carts and {$orderCount} pending orders removed");
            
            return response()->json([
                'message' => 'Cleanup completed successfully',
                'carts_removed' => $cartCount,
                'orders_removed' => $orderCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error during maintenance cleanup: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Error during cleanup',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove carts older than 2 weeks
     *
     * @return int Number of carts removed
     */
    private function removeOldCarts(): int
    {
        $twoWeeksAgo = Carbon::now()->subWeeks(2);
        
        // Find old carts
        $oldCarts = Cart::where('updated_at', '<', $twoWeeksAgo)->get();
        $cartCount = $oldCarts->count();
        
        if ($cartCount > 0) {
            foreach ($oldCarts as $cart) {
                // Delete associated items first
                $cart->items()->delete();
                // Then delete the cart itself
                $cart->delete();
            }
            
            Log::info("Removed {$cartCount} carts older than " . $twoWeeksAgo->format('Y-m-d H:i:s'));
        }
        
        return $cartCount;
    }

    /**
     * Remove pending card payment orders older than 2 weeks
     *
     * @return int Number of orders removed
     */
    private function removeOldPendingOrders(): int
    {
        $twoWeeksAgo = Carbon::now()->subWeeks(2);
        
        // Find old pending card payment orders
        $oldOrders = Order::where('payment_type', 'card_payment')
                          ->where('payment_status', 'pending')
                          ->where('order_datetime', '<', $twoWeeksAgo)
                          ->get();
        
        $orderCount = $oldOrders->count();
        
        if ($orderCount > 0) {
            foreach ($oldOrders as $order) {
                // Delete associated order items first
                $order->orderItems()->delete();
                // Then delete the order itself
                $order->delete();
            }
            
            Log::info("Removed {$orderCount} pending card payment orders older than " . $twoWeeksAgo->format('Y-m-d H:i:s'));
        }
        
        return $orderCount;
    }
}