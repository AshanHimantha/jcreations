<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'limit_amount',
        'is_active'
    ];

    protected $casts = [
        'limit_amount' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public static function getCurrentLimit()
    {
        return self::where('is_active', true)->first();
    }
}