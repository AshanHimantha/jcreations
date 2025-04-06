<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firebase_uid',
        'session_id',
    ];

    /**
     * Get the items in the cart.
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the total price of items in the cart.
     */
    public function getTotalAttribute()
    {
        return $this->items->sum(function ($item) {
            $discountMultiplier = (100 - $item->product->discount_percentage) / 100;
            return $item->quantity * $item->product->price * $discountMultiplier;
        });
    }
}