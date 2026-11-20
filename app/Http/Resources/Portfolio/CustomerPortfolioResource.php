<?php

namespace App\Http\Resources\Portfolio;

use App\Models\CustomerPlanHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPortfolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cashBalance = $this->wallet ? (float) $this->wallet->cash_balance : 0.0;
        $reservedBalance = $this->wallet ? (float) $this->wallet->reserved_subscription_balance : 0.0;
        $pointsBalance = $this->loyaltyPoint ? (int) $this->loyaltyPoint->points_balance : 0;
        $totalAvailable = round($cashBalance + $reservedBalance, 2);

        $currentPlanHistory = $this->currentPlan
            ? $this->planHistories->first(
                fn (CustomerPlanHistory $history) => (int) $history->plan_id === (int) $this->current_plan_id
                    && $history->end_date === null
            )
            : null;

        $nextAppointment = $this->portfolio_next_appointment ?? null;
        $currentSubscription = $this->portfolio_current_subscription ?? null;

        return [
            'customerId' => $this->id,
            'legalName' => $this->legal_name,
            'tradeName' => $this->trade_name,
            'taxId' => $this->tax_id,
            'nrc' => $this->nrc,
            'economicActivity' => $this->economic_activity,
            'address' => $this->address,
            'department' => $this->department,
            'municipality' => $this->municipality,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'visitFrequency' => $this->visit_frequency_value ? [
                'value' => $this->visit_frequency_value,
                'unit' => $this->visit_frequency_unit,
            ] : null,
            'memberSince' => $this->created_at?->toIso8601String(),
            'hasPlan' => $this->currentPlan !== null,
            'currentPlan' => $this->currentPlan ? [
                'id' => $this->currentPlan->id,
                'planCode' => $this->currentPlan->plan_code,
                'planName' => $this->currentPlan->plan_name,
                'frequencyValue' => $this->currentPlan->frequency_value,
                'frequencyUnit' => $this->currentPlan->frequency_unit,
                'visitCount' => $this->currentPlan->visit_count,
                'maintenanceType' => $this->currentPlan->maintenance_type,
                'discountPercentage' => $this->currentPlan->discount_percentage !== null
                    ? (float) $this->currentPlan->discount_percentage
                    : null,
                'loyaltyPointsMultiplier' => $this->currentPlan->loyalty_points_multiplier !== null
                    ? (float) $this->currentPlan->loyalty_points_multiplier
                    : null,
                'active' => (bool) $this->currentPlan->active,
                'startDate' => $currentPlanHistory?->start_date?->format('Y-m-d'),
                'endDate' => $currentPlanHistory?->end_date?->format('Y-m-d'),
            ] : null,
            'planHistory' => $this->planHistories
                ->map(fn (CustomerPlanHistory $history) => [
                    'id' => $history->id,
                    'planId' => $history->plan_id,
                    'planCode' => $history->plan?->plan_code,
                    'planName' => $history->plan?->plan_name,
                    'startDate' => $history->start_date?->format('Y-m-d'),
                    'endDate' => $history->end_date?->format('Y-m-d'),
                ])
                ->values()
                ->all(),
            'wallet' => [
                'cashBalance' => round($cashBalance, 2),
                'reservedSubscriptionBalance' => round($reservedBalance, 2),
                'totalAvailable' => $totalAvailable,
            ],
            'loyalty' => [
                'pointsBalance' => $pointsBalance,
            ],
            'totalBalance' => $totalAvailable,
            'totalPoints' => $pointsBalance,
            'debt' => [
                'pendingQuotesCount' => (int) ($this->portfolio_pending_quotes_count ?? 0),
                'totalPendingAmount' => round((float) ($this->portfolio_total_pending_amount ?? 0), 2),
                'quotes' => collect($this->portfolio_pending_quotes ?? [])
                    ->map(function ($quote) {
                        $total = (float) $quote->total;
                        $amountPaid = (float) ($quote->paid_amount ?? 0);

                        return [
                            'id' => $quote->id,
                            'quoteNumber' => $quote->quote_number,
                            'total' => round($total, 2),
                            'amountPaid' => round($amountPaid, 2),
                            'pendingAmount' => round(max(0, $total - $amountPaid), 2),
                            'createdAt' => $quote->created_at?->toIso8601String(),
                        ];
                    })
                    ->values()
                    ->all(),
            ],
            'subscriptions' => [
                'activeCount' => (int) ($this->portfolio_subscription_active_count ?? 0),
                'current' => $currentSubscription ? [
                    'id' => $currentSubscription->id,
                    'planId' => $currentSubscription->plan_id,
                    'planCode' => $currentSubscription->plan?->plan_code,
                    'planName' => $currentSubscription->plan?->plan_name,
                    'paymentDate' => $currentSubscription->payment_date?->format('Y-m-d'),
                    'amountPaid' => (float) $currentSubscription->amount_paid,
                    'reservedBalance' => (float) $currentSubscription->reserved_balance,
                    'status' => $currentSubscription->status,
                ] : null,
            ],
            'appointments' => [
                'upcomingCount' => (int) ($this->portfolio_upcoming_appointments_count ?? 0),
                'next' => $nextAppointment ? [
                    'id' => $nextAppointment->id,
                    'appointmentDate' => $nextAppointment->appointment_date?->format('Y-m-d'),
                    'appointmentTime' => is_string($nextAppointment->appointment_time)
                        ? substr($nextAppointment->appointment_time, 0, 5)
                        : $nextAppointment->appointment_time,
                    'status' => $nextAppointment->status,
                    'origin' => $nextAppointment->origin,
                    'serviceId' => $nextAppointment->service_id,
                    'service' => $nextAppointment->service ? [
                        'id' => $nextAppointment->service->id,
                        'name' => $nextAppointment->service->name,
                        'price' => (float) $nextAppointment->service->price,
                    ] : null,
                    'employeeId' => $nextAppointment->employee_id,
                    'employee' => $nextAppointment->employee ? [
                        'id' => $nextAppointment->employee->id,
                        'fullName' => $nextAppointment->employee->full_name,
                    ] : null,
                ] : null,
                'lastCompletedDate' => $this->portfolio_last_completed_appointment_date,
            ],
            'recentQuotes' => collect($this->portfolio_recent_quotes ?? [])
                ->map(fn ($quote) => [
                    'id' => $quote->id,
                    'quoteNumber' => $quote->quote_number,
                    'total' => (float) $quote->total,
                    'status' => $quote->status,
                    'loyaltyPointsUsed' => (int) $quote->loyalty_points_used,
                    'loyaltyPointsEarned' => (int) $quote->loyalty_points_earned,
                    'createdAt' => $quote->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'recentPayments' => collect($this->portfolio_recent_payments ?? [])
                ->map(fn ($payment) => [
                    'id' => $payment->id,
                    'quoteId' => $payment->quote_id,
                    'quoteNumber' => $payment->quote?->quote_number,
                    'amountPaid' => (float) $payment->amount_paid,
                    'paymentMethod' => $payment->payment_method,
                    'paymentDate' => $payment->payment_date?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }
}
