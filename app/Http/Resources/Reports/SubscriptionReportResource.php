<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'plan' => [
                'id' => (int) $this->resource->plan_id,
                'code' => (string) $this->resource->plan_code,
                'name' => (string) $this->resource->plan_name,
            ],
            'subscriptionsCount' => (int) $this->resource->subscriptions_count,
            'activeCount' => (int) $this->resource->active_count,
            'pendingPaymentCount' => (int) $this->resource->pending_payment_count,
            'completedCount' => (int) $this->resource->completed_count,
            'cancelledCount' => (int) $this->resource->cancelled_count,
            'totalPaid' => round((float) $this->resource->total_paid, 2),
            'reservedBalance' => round((float) $this->resource->reserved_balance, 2),
        ];
    }
}
