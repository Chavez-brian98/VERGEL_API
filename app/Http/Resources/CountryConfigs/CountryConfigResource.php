<?php

namespace App\Http\Resources\CountryConfigs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country' => $this->country,
            'currency' => $this->currency,
            'taxPercentage' => $this->tax_percentage !== null ? (float) $this->tax_percentage : null,
            'cardFeePercentage' => $this->card_fee_percentage !== null ? (float) $this->card_fee_percentage : null,
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
