<?php

namespace App\Http\Resources\Plans;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'planCode' => $this->plan_code,
            'planName' => $this->plan_name,
            'frequencyValue' => $this->frequency_value,
            'frequencyUnit' => $this->frequency_unit,
            'visitCount' => $this->visit_count,
            'maintenanceType' => $this->maintenance_type,
            'discountPercentage' => $this->discount_percentage !== null ? (float) $this->discount_percentage : null,
            'loyaltyPointsMultiplier' => $this->loyalty_points_multiplier !== null ? (float) $this->loyalty_points_multiplier : null,
            'active' => (bool) $this->active,
            'activeSubscribersCount' => (int) ($this->active_subscribers_count ?? 0),
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
