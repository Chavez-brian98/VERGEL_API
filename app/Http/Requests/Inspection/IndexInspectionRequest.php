<?php

namespace App\Http\Requests\Inspection;

use App\Models\Inspection;
use Illuminate\Foundation\Http\FormRequest;

class IndexInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Inspection::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'employee_id' => ['sometimes', 'integer', 'exists:employees,id'],
            'status' => ['sometimes', 'in:draft,submitted,reviewed'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'in:id,inspection_date,created_at,status,total_area'],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
