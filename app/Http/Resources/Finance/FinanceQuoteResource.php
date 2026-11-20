<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceQuoteResource extends JsonResource
{
    /**
     * Transforma la cotización con su estado de pago calculado.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = (float) $this->total;
        $paid = (float) ($this->payments_sum_amount_paid ?? 0);

        $status = $total > 0 && $paid >= $total
            ? 'paid'
            : ($paid > 0 ? 'partial' : 'pending');

        $labels = [
            'paid' => ['Pagado', 'success'],
            'partial' => ['Parcial', 'warning'],
            'pending' => ['Pendiente', 'danger'],
        ];

        return [
            'id' => $this->id,
            'quoteNumber' => $this->quote_number,
            'customerId' => $this->customer_id,
            'customerName' => $this->whenLoaded('customer', fn () => $this->customer?->legal_name),
            'total' => $total,
            'paidAmount' => $paid,
            'pendingAmount' => round(max($total - $paid, 0), 2),
            'paymentStatus' => [
                'key' => $status,
                'label' => $labels[$status][0],
                'badge' => $labels[$status][1],
            ],
            'quoteStatus' => $this->status,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
