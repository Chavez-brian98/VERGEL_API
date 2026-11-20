<?php

namespace App\Http\Requests\Finance;

use Illuminate\Validation\Rule;

class IndexFinanceQuotesRequest extends FinanceRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $spanishStatuses = [
            'todos' => 'all',
            'pagado' => 'paid',
            'parcial' => 'partial',
            'pendiente' => 'pending',
        ];

        if ($this->has('payment_status')) {
            $status = strtolower((string) $this->input('payment_status'));
            $this->merge(['payment_status' => $spanishStatuses[$status] ?? $status]);
        }
    }

    /**
     * Reglas de validación para el listado financiero de cotizaciones.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->dateRangeRules(), [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'quote_number' => ['nullable', 'string', 'max:20'],
            'payment_status' => ['nullable', 'string', Rule::in(['all', 'paid', 'partial', 'pending'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }
}
