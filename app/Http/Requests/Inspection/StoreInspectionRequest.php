<?php

namespace App\Http\Requests\Inspection;

use App\Models\Inspection;
use Illuminate\Foundation\Http\FormRequest;

class StoreInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Inspection::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'inspection_date' => ['required', 'date'],
            'findings' => ['nullable', 'string'],
            'terrain_width' => ['nullable', 'numeric', 'min:0'],
            'terrain_length' => ['nullable', 'numeric', 'min:0'],
            'total_area' => ['nullable', 'numeric', 'min:0'],
            'area_unit' => ['sometimes', 'string', 'max:20'],
            'terrain_notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:draft,submitted,reviewed'],
            'services' => ['sometimes', 'array'],
            'services.*.service_id' => ['required_with:services', 'integer', 'exists:services,id'],
            'services.*.suggested_quantity' => ['sometimes', 'integer', 'min:1'],
            'services.*.notes' => ['nullable', 'string'],
            'photos' => ['sometimes', 'array'],
            'photos.*' => ['file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'photos_data' => ['sometimes', 'array'],
            'photos_data.*.file' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'photos_data.*.caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
