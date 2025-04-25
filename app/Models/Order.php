<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'contact_number',
        'city',
        'address',
        'status',
        'req_datetime',
        'payment_type',
        'total_amount',
        'order_datetime',
        'firebase_uid', // Added firebase_uid to fillable attributes
    ];

    protected $casts = [
        'order_datetime' => 'datetime',
        'req_datetime' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the order items for this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}