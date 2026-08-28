<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyPoints extends Model
{
    protected $fillable = [
        'customer_id',
        'points_balance',
    ];

    protected function casts(): array
    {
        return [
            'points_balance' => 'integer',
        ];
    }
}
