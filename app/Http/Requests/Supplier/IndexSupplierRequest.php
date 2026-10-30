<?php

namespace App\Http\Requests\Supplier;

use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

class IndexSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Supplier::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:active,inactive'],
            'economic_activity' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'sort_by' => ['nullable', 'string', 'in:id,name,trade_name,status,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
