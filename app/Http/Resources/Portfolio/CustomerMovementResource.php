<?php

namespace App\Http\Resources\Portfolio;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $movement = is_array($this->resource) ? $this->resource : (array) $this->resource;

        return [
            'id' => $movement['id'],
            'customerId' => $movement['customerId'] ?? null,
            'source' => $movement['source'],
            'transactionType' => $movement['transactionType'],
            'amount' => $movement['amount'],
            'points' => $movement['points'],
            'description' => $movement['description'],
            'paymentMethod' => $movement['paymentMethod'],
            'createdAt' => $movement['createdAt'],
        ];
    }
}
