<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyPointTransaction extends Model
{
    protected $table = 'loyalty_points_transactions';

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
