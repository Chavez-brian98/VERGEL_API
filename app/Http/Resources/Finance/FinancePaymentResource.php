<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancePaymentResource extends JsonResource
{
    /**
     * Transforma un abono registrado a su representación camelCase.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quoteId' => $this->quote_id,
            'amountPaid' => (float) $this->amount_paid,
            'paymentMethod' => $this->payment_method,
            'paymentDate' => $this->payment_date?->toIso8601String() ?? (string) $this->payment_date,
            'employee' => $this->whenLoaded('employee', function () {
                return $this->employee ? [
                    'id' => $this->employee->id,
                    'fullName' => $this->employee->full_name,
                ] : null;
            }),
            'createdAt' => (string) $this->created_at,
        ];
    }
}
