<?php

namespace App\Http\Requests\Finance;

class ShowPlantSalesRequest extends FinanceRequest
{
    /**
     * Reglas de validación para el historial de ventas de una planta.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }
}
