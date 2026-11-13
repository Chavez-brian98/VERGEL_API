<?php

namespace App\Http\Requests\Inspection;

use App\Models\Inspection;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInspectionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', Inspection::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:draft,submitted,reviewed'],
        ];
    }
}
