<?php

namespace App\Http\Requests\Quote;

use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', Quote::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,paid'],
        ];
    }
}
