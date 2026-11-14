<?php

namespace App\Http\Requests\Portfolio;

use Illuminate\Foundation\Http\FormRequest;

class ShowPortfolioRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para consultar la cartera.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewPortfolio');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
