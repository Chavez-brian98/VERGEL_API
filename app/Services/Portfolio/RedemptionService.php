<?php

namespace App\Services\Portfolio;

use App\Models\Customer;
use App\Models\LoyaltyRedemption;
use Illuminate\Support\Collection;

/**
 * Consultas de canjes de EcoPuntos de clientes.
 */
class RedemptionService
{
    /**
     * @return Collection<int, LoyaltyRedemption>
     */
    public function forCustomer(Customer $customer): Collection
    {
        return $customer->loyaltyRedemptions()
            ->with('reward')
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->get();
    }
}
