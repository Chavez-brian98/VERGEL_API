<?php

namespace App\Http\Requests\Transaction;

use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;

class ShowTransactionRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para consultar el detalle de una transacción.
     */
    public function authorize(): bool
    {
        $quoteParam = $this->route('transaction') ?? $this->route('id');
        $quote = $quoteParam instanceof Quote
            ? $quoteParam
            : Quote::withTrashed()->find($quoteParam);

        return (bool) $this->user()?->can('view', $quote ?? Quote::class);
    }

    /**
     * Reglas de validación aplicables a la solicitud de detalle.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
