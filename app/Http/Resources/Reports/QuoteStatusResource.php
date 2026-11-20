<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteStatusResource extends JsonResource
{
    /**
     * @var array<string, array{label: string, badge: string}>
     */
    private const STATUSES = [
        'paid' => ['label' => 'Pagado', 'badge' => 'success'],
        'partial' => ['label' => 'Parcial', 'badge' => 'warning'],
        'pending' => ['label' => 'Pendiente', 'badge' => 'danger'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $key = (string) $this->resource->payment_status;
        $billed = round((float) $this->resource->total_billed, 2);
        $paid = round((float) $this->resource->total_paid, 2);

        return [
            'key' => $key,
            'label' => self::STATUSES[$key]['label'],
            'badge' => self::STATUSES[$key]['badge'],
            'quotesCount' => (int) $this->resource->quotes_count,
            'totalBilled' => $billed,
            'totalPaid' => $paid,
            'totalPending' => round($billed - $paid, 2),
        ];
    }
}
