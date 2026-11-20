<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancePlantSaleResource extends JsonResource
{
    /**
     * Transforma un ítem de planta vendida a su representación camelCase.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $quote = $this->whenLoaded('quote');
        $total = (float) ($quote?->total ?? 0);
        $paid = (float) ($quote?->payments_sum_amount_paid ?? 0);

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
            'quoteId' => $this->quote_id,
            'quoteNumber' => $this->whenLoaded('quote', fn () => $quote?->quote_number),
            'customerName' => $this->whenLoaded('quote', fn () => $quote?->customer?->legal_name),
            'saleDate' => $this->whenLoaded('quote', fn () => $quote?->created_at?->toIso8601String()),
            'quantity' => (float) $this->quantity,
            'unitPrice' => (float) $this->unit_price,
            'total' => (float) $this->total,
            'paidAmount' => $paid,
            'paymentStatus' => [
                'key' => $status,
                'label' => $labels[$status][0],
                'badge' => $labels[$status][1],
            ],
        ];
    }
}
