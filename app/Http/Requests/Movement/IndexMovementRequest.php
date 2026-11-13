<?php

namespace App\Http\Requests\Movement;

use App\Models\Movement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMovementRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para consultar el libro de movimientos.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', Movement::class);
    }

    /**
     * Prepara y normaliza los parámetros de filtrado antes de la validación.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // Homogeneizar alias type -> movement_type
        if ($this->has('type') && ! $this->has('movement_type')) {
            $mergeData['movement_type'] = $this->input('type');
        }

        // Homogeneizar alias ticket -> expense
        if ($this->has('movement_type') && $this->input('movement_type') === 'ticket') {
            $mergeData['movement_type'] = 'expense';
        }

        // Homogeneizar limit -> per_page
        if ($this->has('limit') && ! $this->has('per_page')) {
            $mergeData['per_page'] = $this->input('limit');
        }

        // Normalizar búsqueda
        if ($this->has('search')) {
            $search = trim((string) $this->input('search'));
            $mergeData['search'] = $search !== '' ? $search : null;
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Reglas de validación para filtrar los movimientos del libro general.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'movement_type' => ['nullable', 'string', Rule::in(['all', 'sale', 'purchase', 'expense', 'ticket'])],
            'type' => ['nullable', 'string', Rule::in(['all', 'sale', 'purchase', 'expense', 'ticket'])],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'search' => ['nullable', 'string', 'max:100'],
            'entity_name' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'string', Rule::in(['all', 'completed', 'cancelled'])],
            'sort_by' => ['nullable', 'string', Rule::in(['id', 'movement_date', 'total', 'amount', 'created_at', 'document_number'])],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Mensajes de error en español ante fallos de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'movement_type.in' => 'El tipo de movimiento debe ser all, sale, purchase o expense (ticket).',
            'type.in' => 'El tipo de movimiento debe ser all, sale, purchase o expense (ticket).',
            'start_date.date_format' => 'La fecha inicial debe tener el formato AAAA-MM-DD (ej. 2026-11-01).',
            'end_date.date_format' => 'La fecha final debe tener el formato AAAA-MM-DD (ej. 2026-11-30).',
            'end_date.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'search.max' => 'El término de búsqueda no debe superar los 100 caracteres.',
            'entity_name.max' => 'El nombre del cliente/proveedor no debe superar los 150 caracteres.',
            'status.in' => 'El estado debe ser all, completed o cancelled.',
            'sort_by.in' => 'El campo de ordenamiento especificado no es válido.',
            'sort_direction.in' => 'La dirección de ordenamiento debe ser asc o desc.',
            'per_page.min' => 'La cantidad mínima por página es 1.',
            'per_page.max' => 'La cantidad máxima por página es 100.',
            'limit.min' => 'El límite mínimo por página es 1.',
            'limit.max' => 'El límite máximo por página es 100.',
        ];
    }
}
