<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quote_number',
        'customer_id',
        'employee_id',
        'applied_plan_id',
        'base_price',
        'discounted_subtotal',
        'includes_vat',
        'loyalty_points_used',
        'loyalty_points_earned',
        'total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'discounted_subtotal' => 'decimal:2',
            'includes_vat' => 'boolean',
            'loyalty_points_used' => 'integer',
            'loyalty_points_earned' => 'integer',
            'total' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
