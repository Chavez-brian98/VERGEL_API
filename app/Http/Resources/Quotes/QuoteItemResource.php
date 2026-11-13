<?php

namespace App\Http\Resources\Quotes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'itemType' => $this->item_type,
            'serviceId' => $this->service_id,
            'plantId' => $this->plant_id,
            'quantity' => (float) $this->quantity,
            'unitPrice' => (float) $this->unit_price,
            'total' => (float) $this->total,
        ];
    }
}
