<?php

namespace App\Http\Requests\Finance;

class ShowQuotePaymentsRequest extends FinanceRequest
{
    /**
     * No existen parámetros adicionales: la autorización se resuelve en la base.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
