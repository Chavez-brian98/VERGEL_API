<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:150'],
            'trade_name' => ['nullable', 'string', 'max:150'],
            'tax_id' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('customers', 'tax_id')->withoutTrashed(),
            ],
            'nrc' => ['nullable', 'string', 'max:20'],
            'economic_activity' => ['nullable', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:60'],
            'municipality' => ['required', 'string', 'max:60'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'status' => ['sometimes', 'in:active,inactive'],
            'visit_frequency_value' => [
                'nullable',
                'integer',
                'min:1',
                'required_with:visit_frequency_unit',
            ],
            'visit_frequency_unit' => [
                'nullable',
                'in:days,weeks,months',
                'required_with:visit_frequency_value',
            ],
            'current_plan_id' => [
                'nullable',
                'integer',
                Rule::exists('subscription_plans', 'id')->withoutTrashed(),
            ],
        ];
    }
}
