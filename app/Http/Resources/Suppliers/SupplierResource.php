<?php

namespace App\Http\Resources\Suppliers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    /**
     * Transform the resource into an array.
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
            'address' => $this->address,
            'contactName' => $this->contact_name,
            'contactPhone' => $this->contact_phone,
            'status' => $this->status,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
