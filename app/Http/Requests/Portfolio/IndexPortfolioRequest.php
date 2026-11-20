<?php

namespace App\Http\Requests\Portfolio;

use Illuminate\Foundation\Http\FormRequest;

class IndexPortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewPortfolio');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'limit.integer' => 'El límite debe ser un número entero.',
            'limit.min' => 'El límite mínimo por página es 1.',
            'limit.max' => 'El límite máximo por página es 100.',
        ];
    }
}
