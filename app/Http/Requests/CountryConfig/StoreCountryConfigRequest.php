<?php

namespace App\Http\Requests\CountryConfig;

use Illuminate\Foundation\Http\FormRequest;

class StoreCountryConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country' => 'required|string|max:60|unique:country_configs,country',
            'currency' => 'required|string|max:10',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'card_fee_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
