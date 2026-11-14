<?php

namespace App\Http\Resources\Loyalty;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyRewardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rewardCode' => $this->reward_code,
            'rewardName' => $this->reward_name,
            'description' => $this->description,
            'serviceId' => $this->service_id,
            'serviceName' => $this->service?->name,
            'pointsCost' => (int) $this->points_cost,
            'active' => (bool) $this->active,
            'available' => $this->isAvailable(),
            'validFrom' => $this->valid_from?->format('Y-m-d'),
            'validUntil' => $this->valid_until?->format('Y-m-d'),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
