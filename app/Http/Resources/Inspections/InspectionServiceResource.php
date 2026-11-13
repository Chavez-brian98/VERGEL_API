<?php

namespace App\Http\Resources\Inspections;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionServiceResource extends JsonResource
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
            'price' => (float) $this->price,
            'unitOfMeasure' => $this->unit_of_measure,
            'suggestedQuantity' => $this->pivot ? (int) $this->pivot->suggested_quantity : 1,
            'notes' => $this->pivot ? $this->pivot->notes : null,
        ];
    }
}
