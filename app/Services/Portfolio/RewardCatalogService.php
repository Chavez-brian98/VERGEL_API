<?php

namespace App\Services\Portfolio;

use App\Models\LoyaltyReward;
use Illuminate\Support\Collection;

/**
 * Consultas del catálogo de recompensas canjeables con EcoPuntos.
 */
class RewardCatalogService
{
    /**
     * @return Collection<int, LoyaltyReward>
     */
    public function available(): Collection
    {
        $today = now()->toDateString();

        return LoyaltyReward::query()
            ->with('service')
            ->where('active', true)
            ->where(function ($query) use ($today) {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', $today);
            })
            ->orderBy('points_cost')
            ->orderBy('reward_name')
            ->get();
    }

    /**
     * @return Collection<int, LoyaltyReward>
     */
    public function all(): Collection
    {
        return LoyaltyReward::query()
            ->with('service')
            ->orderBy('points_cost')
            ->orderBy('reward_name')
            ->get();
    }
}
