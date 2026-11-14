<?php

namespace App\Http\Requests\Portfolio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPortfolioMovementRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para consultar movimientos.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewPortfolioMovements');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(['all', 'wallet', 'loyalty'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de movimiento debe ser all, wallet o loyalty.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página mínima es 1.',
            'limit.integer' => 'El límite debe ser un número entero.',
            'limit.min' => 'El límite mínimo por página es 1.',
            'limit.max' => 'El límite máximo por página es 100.',
        ];
    }
}
