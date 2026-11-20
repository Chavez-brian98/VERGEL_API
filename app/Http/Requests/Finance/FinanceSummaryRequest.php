<?php

namespace App\Http\Requests\Finance;

class FinanceSummaryRequest extends FinanceRequest
{
    /**
     * Reglas de validación para el rango de fechas de los indicadores.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return $this->dateRangeRules();
    }
}
