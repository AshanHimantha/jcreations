<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="CartItem",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="cart_id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="CartItemWithProduct",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/CartItem"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="product",
 *                 type="object",
 *                 ref="#/components/schemas/Product"
 *             )
 *         )
 *     }
 * )
 */
class CartItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
    ];

    /**
     * The cart this item belongs to.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * The product this cart item refers to.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the subtotal for this item.
     */
    public function getSubtotalAttribute()
    {
        $discountMultiplier = (100 - $this->product->discount_percentage) / 100;
        return $this->quantity * $this->product->price * $discountMultiplier;
    }
}