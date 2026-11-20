<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de las solicitudes de solo lectura del módulo de Finanzas.
 *
 * Centraliza la autorización (Admin / Administrador) y la normalización
 * de los alias de rango de fechas usados en otros módulos.
 */
abstract class FinanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewFinance');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('from') && $this->hasAny(['start_date', 'desde'])) {
            $this->merge(['from' => $this->input('start_date') ?? $this->input('desde')]);
        }

        if (! $this->has('to') && $this->hasAny(['end_date', 'hasta'])) {
            $this->merge(['to' => $this->input('end_date') ?? $this->input('hasta')]);
        }
    }

    /**
     * Reglas comunes de rango de fechas (AAAA-MM-DD).
     *
     * @return array<string, array<int, string>>
     */
    protected function dateRangeRules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }
}
