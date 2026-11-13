<?php

namespace App\Http\Requests\Inspection;

use App\Models\Inspection;
use Illuminate\Foundation\Http\FormRequest;

class UploadPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', Inspection::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'photos' => ['required_without:photos_data', 'array'],
            'photos.*' => ['file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'photos_data' => ['required_without:photos', 'array'],
            'photos_data.*.file' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'photos_data.*.caption' => ['nullable', 'string', 'max:255'],
        ];
    }
}
