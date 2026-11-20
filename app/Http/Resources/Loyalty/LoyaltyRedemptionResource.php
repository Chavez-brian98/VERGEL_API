<?php

namespace App\Http\Resources\Loyalty;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyRedemptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customer_id,
            'rewardId' => $this->reward_id,
            'rewardCode' => $this->reward?->reward_code,
            'rewardName' => $this->reward?->reward_name,
            'appointmentId' => $this->appointment_id,
            'pointsSpent' => (int) $this->points_spent,
            'status' => $this->status,
            'requestedAt' => $this->requested_at?->toIso8601String(),
            'approvedAt' => $this->approved_at?->toIso8601String(),
            'fulfilledAt' => $this->fulfilled_at?->toIso8601String(),
            'cancelledAt' => $this->cancelled_at?->toIso8601String(),
            'cancellationReason' => $this->cancellation_reason,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
