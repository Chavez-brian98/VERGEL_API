<?php

namespace App\Http\Requests\Quote;

use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;

class IndexQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Quote::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:pending,paid'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'applied_plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'sort_by' => ['nullable', 'string', 'in:id,quote_number,status,total,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc,ASC,DESC'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
