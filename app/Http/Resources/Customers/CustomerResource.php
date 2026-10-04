<?php

namespace App\Http\Resources\Customers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'legalName' => $this->legal_name,
            'tradeName' => $this->trade_name,
            'taxId' => $this->tax_id,
            'nrc' => $this->nrc,
            'economicActivity' => $this->economic_activity,
            'address' => $this->address,
            'municipality' => $this->municipality,
            'department' => $this->department,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'visitFrequency' => $this->visit_frequency_value ? [
                'value' => $this->visit_frequency_value,
                'unit' => $this->visit_frequency_unit,
            ] : null,
            'currentPlan' => $this->whenLoaded('currentPlan', function () {
                return $this->currentPlan ? [
                    'id' => $this->currentPlan->id,
                    'planCode' => $this->currentPlan->plan_code ?? null,
                    'planName' => $this->currentPlan->plan_name ?? null,
                ] : null;
            }),
            'wallet' => $this->whenLoaded('wallet', function () {
                return $this->wallet ? [
                    'cashBalance' => (float) $this->wallet->cash_balance,
                    'reservedSubscriptionBalance' => (float) $this->wallet->reserved_subscription_balance,
                ] : null;
            }),
            'loyaltyPoints' => $this->whenLoaded('loyaltyPoint', function () {
                return $this->loyaltyPoint ? [
                    'pointsBalance' => $this->loyaltyPoint->points_balance,
                ] : null;
            }),
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}