<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'plan_code',
        'plan_name',
        'frequency_value',
        'frequency_unit',
        'visit_count',
        'maintenance_type',
        'discount_percentage',
        'loyalty_points_multiplier',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'frequency_value' => 'integer',
            'visit_count' => 'integer',
            'discount_percentage' => 'decimal:2',
            'loyalty_points_multiplier' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'current_plan_id');
    }

    public function planHistories(): HasMany
    {
        return $this->hasMany(CustomerPlanHistory::class, 'plan_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class, 'applied_plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
