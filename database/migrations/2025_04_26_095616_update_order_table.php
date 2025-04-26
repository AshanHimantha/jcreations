<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Order;
use App\Models\DeliveryLocation;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First add the new column as nullable
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_location_id')->nullable();
        });

        // Now populate the delivery_location_id based on city
        $orders = Order::all();
        foreach ($orders as $order) {
            $deliveryLocation = DeliveryLocation::where('city', $order->city)
                ->where('is_active', true)
                ->first();
            
            if ($deliveryLocation) {
                $order->delivery_location_id = $deliveryLocation->id;
                $order->save();
            }
        }

        // Now make it non-nullable and add the constraint
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_location_id')->nullable(false)->change();
            $table->foreign('delivery_location_id')->references('id')->on('delivery_locations');
            $table->dropColumn('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_location_id']);
            $table->dropColumn('delivery_location_id');
            $table->string('city')->nullable();
        });
    }
};