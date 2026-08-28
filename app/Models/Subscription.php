<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'customer_id',
        'plan_id',
        'payment_date',
        'amount_paid',
        'reserved_balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount_paid' => 'decimal:2',
            'reserved_balance' => 'decimal:2',
        ];
    }
}
