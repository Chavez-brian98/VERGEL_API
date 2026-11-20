<?php

namespace App\Http\Requests\Portfolio;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalletTransactionRequest extends FormRequest
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
        $isAdjustment = $this->input('transaction_type') === 'adjustment';

        return [
            'transaction_type' => [
                'required',
                Rule::in([
                    'top_up',
                    'annual_fee',
                    'maintenance_deduction',
                    'adjustment',
                    'reserve_funds',
                    'spend_reserved_funds',
                ]),
            ],
            'amount' => array_merge(
                ['required', 'numeric'],
                $isAdjustment ? ['not_in:0'] : ['min:0.01']
            ),
            'payment_method' => ['nullable', 'string', 'max:40'],
            'is_annual_payment' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_type.required' => 'El tipo de transacción es obligatorio.',
            'transaction_type.in' => 'El tipo de transacción no es válido.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor que cero.',
            'amount.not_in' => 'El monto del ajuste no puede ser cero.',
            'payment_method.max' => 'El método de pago no puede superar los 40 caracteres.',
            'is_annual_payment.boolean' => 'El indicador de pago anual debe ser verdadero o falso.',
        ];
    }
}
