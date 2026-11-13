<?php

namespace App\Http\Requests\Portfolio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoyaltyTransactionRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para registrar movimientos.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('managePortfolio');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transaction_type' => [
                'required',
                Rule::in([
                    'earned_purchase',
                    'redeemed',
                    'referral',
                    'promotional',
                    'special_bonus',
                ]),
            ],
            'points' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
            'quote_id' => ['nullable', 'integer', 'exists:quotes,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_type.required' => 'El tipo de transacción de puntos es obligatorio.',
            'transaction_type.in' => 'El tipo de transacción de puntos no es válido.',
            'points.required' => 'La cantidad de puntos es obligatoria.',
            'points.integer' => 'La cantidad de puntos debe ser un número entero.',
            'points.min' => 'La cantidad de puntos debe ser mayor que cero.',
            'description.max' => 'La descripción no puede superar los 255 caracteres.',
            'quote_id.exists' => 'La cotización indicada no existe.',
        ];
    }
}
