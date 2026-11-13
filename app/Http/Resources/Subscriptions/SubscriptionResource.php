<?php

namespace App\Http\Resources\Subscriptions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price = (float) ($this->plan?->price ?? 0);
        $amountPaid = (float) $this->amount_paid;

        return [
            'id' => $this->id,
            'customerId' => $this->customer_id,
            'planId' => $this->plan_id,
            'planCode' => $this->plan?->plan_code,
            'planName' => $this->plan?->plan_name,
            'planPrice' => $price,
            'paymentDate' => $this->payment_date?->format('Y-m-d'),
            'amountPaid' => $amountPaid,
            'pendingBalance' => round(max(0, $price - $amountPaid), 2),
            'reservedBalance' => (float) $this->reserved_balance,
            'status' => $this->status,
            'payments' => $this->whenLoaded('payments', fn () => $this->payments
                ->map(fn ($payment) => [
                    'id' => $payment->id,
                    'paymentDate' => $payment->payment_date?->toIso8601String(),
                    'amount' => (float) $payment->amount,
                    'paymentMethod' => $payment->payment_method,
                    'isAnnualPayment' => (bool) $payment->is_annual_payment,
                    'description' => $payment->description,
                ])
                ->values()
                ->all()),
            'consumptions' => $this->whenLoaded('consumptions', fn () => $this->consumptions
                ->map(fn ($consumption) => [
                    'id' => $consumption->id,
                    'appointmentId' => $consumption->appointment_id,
                    'maintenanceCost' => (float) $consumption->maintenance_cost,
                    'reversedAt' => $consumption->reversed_at?->toIso8601String(),
                    'createdAt' => $consumption->created_at?->toIso8601String(),
                ])
                ->values()
                ->all()),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
