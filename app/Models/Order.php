<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'contact_number',
        'delivery_location_id', // Replace 'city' with this
        'address',
        'status',
        'req_datetime',
        'payment_type',
        'payment_status', 
        'total_amount',
        'shipping_charge',
        'order_datetime',
        'firebase_uid',
        'cart_id',
    ];

    protected $casts = [
        'order_datetime' => 'datetime',
        'req_datetime' => 'datetime',
        'total_amount' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
    ];

    /**
     * Get the order items for this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the delivery location for this order.
     */
    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class, 'delivery_location_id');
    }
}