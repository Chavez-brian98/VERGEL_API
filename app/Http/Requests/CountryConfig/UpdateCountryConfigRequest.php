<?php

namespace App\Http\Requests\CountryConfig;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCountryConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $countryConfigId = $this->route('country_config') ?? $this->route('country_config_id') ?? $this->route('id');

        return [
            'country' => 'sometimes|required|string|max:60|unique:country_configs,country,'.$countryConfigId.',id',
            'currency' => 'sometimes|required|string|max:10',
            'tax_percentage' => 'sometimes|required|numeric|min:0|max:100',
            'card_fee_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
