<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Product",
 *     required={"name", "description", "category_id", "price", "status"},
 *     @OA\Property(property="id", type="integer", example=1, description="Product ID"),
 *     @OA\Property(property="name", type="string", maxLength=255, example="Smartphone XYZ", description="Product name"),
 *     @OA\Property(property="description", type="string", example="Latest smartphone with advanced features", description="Product description"),
 *     @OA\Property(property="character_count", type="integer", minimum=0, example=50, description="Character count of description"),
 *     @OA\Property(
 *         property="images", 
 *         type="array", 
 *         @OA\Items(type="string"), 
 *         example={"products/image1.jpg", "products/image2.jpg"},
 *         description="Array of product image paths"
 *     ),
 *     @OA\Property(property="category_id", type="integer", example=1, description="Category ID"),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         description="Product category (included when relationship is loaded)",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Electronics"),
 *         @OA\Property(property="description", type="string", example="Electronic devices and accessories"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     ),
 *     @OA\Property(property="price", type="number", format="float", minimum=0, example=999.99, description="Original price"),
 *     @OA\Property(property="discount_percentage", type="number", format="float", minimum=0, maximum=100, example=10.5, description="Discount percentage"),
 *     @OA\Property(
 *         property="status", 
 *         type="string", 
 *         enum={"deactive", "in_stock", "out_of_stock"}, 
 *         example="in_stock",
 *         description="Product status"
 *     ),
 *     @OA\Property(property="daily_deals", type="boolean", example=false, description="Whether product is part of daily deals"),
 *     @OA\Property(property="discounted_price", type="number", format="float", example=899.99, description="Calculated price after applying discount (computed attribute)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-14T10:30:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-14T10:30:00Z")
 * )
 */
class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description', 
        'character_count',
        'images',
        'category_id',
        'price',
        'discount_percentage',
        'status',
        'daily_deals'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'images' => 'array',
        'daily_deals' => 'boolean'
    ];

    /**
     * Get the category that owns the product
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the discounted price of the product
     */
    public function getDiscountedPriceAttribute()
    {
        if ($this->discount_percentage > 0) {
            return round($this->price * (1 - $this->discount_percentage / 100), 2);
        }
        return $this->price;
    }
}