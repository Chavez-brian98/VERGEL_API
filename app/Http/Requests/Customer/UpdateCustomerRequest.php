<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer') ?? $this->route('id'); // depends on route parameter name

        return [
            'legal_name' => 'sometimes|required|string|max:150',
            'trade_name' => 'nullable|string|max:150',
            'tax_id' => 'nullable|string|max:20|unique:customers,tax_id,'.$customerId.',id,deleted_at,NULL',
            'nrc' => 'nullable|string|max:20|unique:customers,nrc,'.$customerId.',id,deleted_at,NULL',
            'economic_activity' => 'nullable|string|max:150',
            'address' => 'sometimes|required|string|max:255',
            'department' => 'nullable|string|max:60',
            'municipality' => 'sometimes|required|string|max:60',
            'phone' => 'sometimes|required|string|max:20|unique:customers,phone,'.$customerId.',id,deleted_at,NULL',
            'email' => 'nullable|email|max:120',
            'visit_frequency_value' => 'nullable|integer|min:1',
            'visit_frequency_unit' => 'nullable|in:days,weeks,months',
        ];
    }
}
