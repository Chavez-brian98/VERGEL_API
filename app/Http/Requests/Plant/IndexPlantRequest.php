<?php

namespace App\Http\Requests\Plant;

use App\Models\Plant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexPlantRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', Plant::class);
    }

    /**
     * Prepara y normaliza los datos de la solicitud antes de aplicar las reglas de validación.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('active')) {
            $value = filter_var($this->active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $this->merge(['active' => $value]);
            }
        }
    }

    /**
     * Obtiene las reglas de validación aplicables a la solicitud.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'category_type' => ['nullable', 'string', 'max:80'],
            'active' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:id,name,price,category_type,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }

    /**
     * Obtiene los mensajes personalizados para los errores de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.string' => 'El término de búsqueda debe ser una cadena de texto.',
            'search.max' => 'El término de búsqueda no puede superar los 150 caracteres.',
            'category_type.string' => 'La categoría debe ser una cadena de texto.',
            'category_type.max' => 'La categoría no puede superar los 80 caracteres.',
            'active.boolean' => 'El filtro de estado activo debe ser un valor booleano.',
            'min_price.numeric' => 'El precio mínimo debe ser un número válido.',
            'min_price.min' => 'El precio mínimo no puede ser negativo.',
            'max_price.numeric' => 'El precio máximo debe ser un número válido.',
            'max_price.min' => 'El precio máximo no puede ser negativo.',
            'max_price.gte' => 'El precio máximo debe ser mayor o igual al precio mínimo.',
            'per_page.integer' => 'La cantidad de elementos por página debe ser un número entero.',
            'per_page.min' => 'La cantidad mínima por página es 1.',
            'per_page.max' => 'La cantidad máxima por página es 100.',
            'limit.integer' => 'El límite de elementos por página debe ser un número entero.',
            'limit.min' => 'El límite mínimo por página es 1.',
            'limit.max' => 'El límite máximo por página es 100.',
            'sort_by.in' => 'El campo de ordenamiento no es válido.',
            'sort_direction.in' => 'La dirección de ordenamiento debe ser asc o desc.',
        ];
    }
}
