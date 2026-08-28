<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointTransaction extends Model
{
    // Indicar que la tabla no maneja updated_at
    public $timestamps = false;

    protected $fillable = [
        'loyalty_points_id',
        'quote_id',
        'transaction_type',
        'points',
        'description',
        'created_at',
    ];

    public function loyaltyPoints(): BelongsTo
    {
        return $this->belongsTo(LoyaltyPoint::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}