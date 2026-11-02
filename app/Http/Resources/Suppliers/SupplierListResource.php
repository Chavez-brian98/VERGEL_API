<?php

namespace App\Http\Resources\Suppliers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierListResource extends JsonResource
{
    /**
     * Transform the resource into an array for summary listings.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tradeName' => $this->trade_name,
            'taxId' => $this->tax_id,
            'nrc' => $this->nrc,
            'economicActivity' => $this->economic_activity,
            'phone' => $this->phone,
            'email' => $this->email,
            'status' => $this->status,
            'contactName' => $this->contact_name,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
