<?php

namespace App\Http\Requests\Transaction;

use App\Models\Quote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTransactionRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para consultar el historial de transacciones.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', Quote::class);
    }

    /**
     * Prepara y normaliza los parámetros de búsqueda y filtrado antes de la validación.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // Homogeneizar alias client_id -> customer_id
        if ($this->has('client_id') && ! $this->has('customer_id')) {
            $mergeData['customer_id'] = $this->input('client_id');
        }

        // Homogeneizar limit -> per_page si viene en los parámetros
        if ($this->has('limit') && ! $this->has('per_page')) {
            $mergeData['per_page'] = $this->input('limit');
        }

        // Limpiar espacios en blanco en la cadena de búsqueda general
        if ($this->has('search')) {
            $search = trim((string) $this->input('search'));
            $mergeData['search'] = $search !== '' ? $search : null;
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Reglas de validación aplicables para listar y filtrar el historial de transacciones.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Buscador principal general (ID cliente, N° de cotización/referencia, o nombre del cliente)
            'search' => ['nullable', 'string', 'max:100'],

            // Filtro específico por cliente
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'client_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],

            // Filtro opcional por clasificación histórica
            'status_type' => ['nullable', 'string', Rule::in(['all', 'performed', 'declined'])],

            // Rango de fechas
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],

            // Ordenamiento y paginación
            'sort_by' => ['nullable', 'string', Rule::in(['id', 'quote_number', 'total', 'created_at'])],
            'sort_direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Mensajes descriptivos en español ante fallos de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'search.max' => 'El término de búsqueda no debe superar los 100 caracteres.',
            'customer_id.exists' => 'El cliente especificado no existe en el sistema.',
            'client_id.exists' => 'El cliente especificado no existe en el sistema.',
            'status_type.in' => 'El tipo de estado histórico debe ser all, performed o declined.',
            'start_date.date_format' => 'La fecha inicial debe tener el formato AAAA-MM-DD (ej. 2026-11-01).',
            'end_date.date_format' => 'La fecha final debe tener el formato AAAA-MM-DD (ej. 2026-11-30).',
            'end_date.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'sort_by.in' => 'El campo de ordenamiento no es válido.',
            'sort_direction.in' => 'La dirección de ordenamiento debe ser asc o desc.',
            'per_page.min' => 'La cantidad mínima por página es 1.',
            'per_page.max' => 'La cantidad máxima por página es 100.',
            'limit.min' => 'El límite mínimo por página es 1.',
            'limit.max' => 'El límite máximo por página es 100.',
        ];
    }
}
