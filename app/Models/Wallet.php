<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'customer_id',
        'cash_balance',
        'reserved_subscription_balance',
    ];

    protected function casts(): array
    {
        return [
            'cash_balance' => 'decimal:2',
            'reserved_subscription_balance' => 'decimal:2',
        ];
    }
}
