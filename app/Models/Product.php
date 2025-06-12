<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Product",
 *     required={"name", "description", "category_id", "price", "status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Smartphone XYZ"),
 *     @OA\Property(property="description", type="string", example="Latest smartphone with advanced features"),
 *     @OA\Property(property="images", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="category_id", type="integer", example=1),
 *     @OA\Property(property="price", type="number", format="float", example=999.99),
 *     @OA\Property(property="discount_percentage", type="number", format="float", example=10.5),
 *     @OA\Property(property="status", type="string", enum={"deactive", "in_stock", "out_of_stock"}, example="in_stock"),
 *     @OA\Property(property="discounted_price", type="number", format="float", example=899.99, description="Calculated price after applying discount"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
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
        'category_id',
        'character_count',
        'price',
        'discount_percentage',
        'status',
        'images'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'discount_percentage' => 'float',
        'images' => 'array',
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