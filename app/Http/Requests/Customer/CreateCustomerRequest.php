<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_name' => 'required|string|max:150',
            'trade_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:20|unique:customers,tax_id,NULL,id,deleted_at,NULL',
            'nrc' => 'nullable|string|max:20|unique:customers,nrc,NULL,id,deleted_at,NULL',
            'economic_activity' => 'nullable|string|max:150',
            'address' => 'required|string|max:255',
            'department' => 'nullable|string|max:60',
            'municipality' => 'required|string|max:60',
            'phone' => 'required|string|max:20|unique:customers,phone,NULL,id,deleted_at,NULL',
            'email' => 'nullable|email|max:120',
            'visit_frequency_value' => 'nullable|integer|min:1',
            'visit_frequency_unit' => 'nullable|in:days,weeks,months',
            'current_plan_id' => 'nullable|exists:subscription_plans,id,deleted_at,NULL',
        ];
    }
}
