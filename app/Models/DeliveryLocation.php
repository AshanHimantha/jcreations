<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryLocation extends Model

/**
 * @OA\Schema(
 *     schema="DeliveryLocation",
 *     title="Delivery Location",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="city", type="string"),
 *     @OA\Property(property="shipping_charge", type="number", format="float"),
 *     @OA\Property(property="is_active", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'city',
        'shipping_charge',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'shipping_charge' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}