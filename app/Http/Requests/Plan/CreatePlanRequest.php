<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class CreatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_code' => 'required|string|max:20|unique:subscription_plans,plan_code,NULL,id,deleted_at,NULL',
            'plan_name' => 'required|string|max:100',
            'frequency_value' => 'required|integer|min:1',
            'frequency_unit' => 'required|in:days,weeks,months',
            'visit_count' => 'required|integer|min:1',
            'maintenance_type' => 'nullable|string|max:100',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'loyalty_points_multiplier' => 'nullable|numeric|min:1',
            'active' => 'sometimes|boolean',
        ];
    }
}
