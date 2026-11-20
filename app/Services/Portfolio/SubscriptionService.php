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
    public function forCustomerId(int $customerId): Collection
    {
        $customer = Customer::findOrFail($customerId);

        return $this->forCustomer($customer);
    }

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

    public function currentForCustomerId(int $customerId): ?Subscription
    {
        $customer = Customer::findOrFail($customerId);

        return $this->currentForCustomer($customer);
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

    public function getById(int $subscriptionId): Subscription
    {
        return Subscription::with(['plan', 'payments', 'consumptions'])->findOrFail($subscriptionId);
    }
}
