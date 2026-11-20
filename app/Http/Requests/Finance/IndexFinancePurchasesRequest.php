<?php

namespace App\Http\Requests\Finance;

class IndexFinancePurchasesRequest extends FinanceRequest
{
    /**
     * Reglas de validación para el listado de compras.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'supplier' => ['nullable', 'string', 'max:150'],
            'supplier_tax_id' => ['nullable', 'string', 'max:20'],
            'document_number' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }
}
