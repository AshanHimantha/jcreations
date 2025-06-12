<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * @OA\Schema(
 *     schema="Banner",
 *     title="Banner",
 *     description="Banner model",
 *     @OA\Property(property="id", type="integer", format="int64", description="Banner ID"),
 *     @OA\Property(property="image_path", type="string", description="Path to the banner image"),
 *     @OA\Property(property="title", type="string", nullable=true, description="Banner title"),
 *     @OA\Property(property="subtitle", type="string", nullable=true, description="Banner subtitle"),
 *     @OA\Property(property="link", type="string", nullable=true, description="Banner link URL"),
 *     @OA\Property(property="is_active", type="boolean", description="Whether the banner is active"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Creation date"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Last update date")
 * )
 */
class Banner extends Model
{
    use HasFactory;

   protected $fillable = [
    'image_path',
    'type',
    'title',
    'subtitle',
    'link',
    'is_active'
];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}