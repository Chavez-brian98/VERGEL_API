<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPlanHistory extends Model
{
    protected $table = 'customer_plan_history';

    protected $fillable = [
        'customer_id',
        'plan_id',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }
}
