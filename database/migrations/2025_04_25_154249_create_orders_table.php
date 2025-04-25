<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('contact_number');
            $table->string('city');
            $table->text('address');
            $table->enum('status', ['pending', 'in_progress', 'delivered', 'returned'])->default('pending');
            $table->enum('payment_type', ['cash_on_delivery', 'card_payment']);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->timestamp('req_datetime')->nullable(); 
            $table->timestamp('order_datetime')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};