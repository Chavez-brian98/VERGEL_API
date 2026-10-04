<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyPoint extends Model
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyPointTransaction::class, 'loyalty_points_id');
    }
}
