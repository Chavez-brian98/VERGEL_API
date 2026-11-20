<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de las solicitudes de solo lectura del módulo de Reportes.
 */
abstract class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewReports');
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
     * @return array<string, array<int, string>>
     */
    protected function dateRangeRules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function paginationRules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
