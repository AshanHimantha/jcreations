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

     /**
      * 
      
 * @OA\Schema(
 *     schema="Cart",
 *     title="Cart",
 *     description="Shopping cart model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="firebase_uid", type="string", nullable=true, example="user123"),
 *     @OA\Property(property="total", type="number", format="float", example=129.95),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/CartItem")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
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