<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionMaintenanceConsumption extends Model
{
    protected $fillable = [
        'subscription_id',
        'appointment_id',
        'employee_id',
        'maintenance_cost',
        'wallet_transaction_id',
        'reversed_at',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_cost' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }
}
