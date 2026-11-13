<?php

namespace App\Services\Portfolio;

use App\Models\Customer;
use App\Models\Subscription;
use Illuminate\Support\Collection;

/**
 * Consultas de suscripciones de clientes.
 */
class SubscriptionService
{
    /**
     * @return Collection<int, Subscription>
     */
    public function forCustomer(Customer $customer): Collection
    {
        return $customer->subscriptions()
            ->with(['plan', 'payments'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();
    }

    public function currentForCustomer(Customer $customer): ?Subscription
    {
        return $customer->subscriptions()
            ->with('plan')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->first();
    }
}
