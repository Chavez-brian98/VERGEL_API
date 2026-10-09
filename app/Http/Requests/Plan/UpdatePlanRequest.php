<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $planId = $this->route('plan') ?? $this->route('subscription_plan') ?? $this->route('id');

        return [
            'plan_code' => 'sometimes|required|string|max:20|unique:subscription_plans,plan_code,'.$planId.',id',
            'plan_name' => 'sometimes|required|string|max:100',
            'frequency_value' => 'sometimes|required|integer|min:1',
            'frequency_unit' => 'sometimes|required|in:days,weeks,months',
            'visit_count' => 'sometimes|required|integer|min:1',
            'maintenance_type' => 'nullable|string|max:100',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'loyalty_points_multiplier' => 'nullable|numeric|min:1',
            'active' => 'sometimes|boolean',
        ];
    }
}
