<?php

namespace App\Http\Requests\Plant;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('plants', 'name')->withoutTrashed(),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'category_type' => [
                'nullable',
                'string',
                'max:80',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
            'image_url' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
            'active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la planta es obligatorio.',
            'name.string' => 'El nombre de la planta debe ser una cadena de texto.',
            'name.max' => 'El nombre de la planta no puede exceder los 150 caracteres.',
            'name.unique' => 'Ya existe una planta registrada con este nombre.',
            'price.required' => 'El precio de la planta es obligatorio.',
            'price.numeric' => 'El precio debe ser un número válido.',
            'price.min' => 'El precio no puede ser negativo.',
            'price.max' => 'El precio excede el límite máximo permitido.',
            'category_type.string' => 'La categoría debe ser una cadena de texto.',
            'category_type.max' => 'La categoría no puede exceder los 80 caracteres.',
            'image.image' => 'El archivo proporcionado debe ser una imagen válida.',
            'image.mimes' => 'La imagen debe ser de formato jpeg, png, jpg o webp.',
            'image.max' => 'El tamaño de la imagen no debe superar los 5 MB.',
            'image_url.image' => 'El archivo proporcionado debe ser una imagen válida.',
            'image_url.mimes' => 'La imagen debe ser de formato jpeg, png, jpg o webp.',
            'image_url.max' => 'El tamaño de la imagen no debe superar los 5 MB.',
            'active.boolean' => 'El campo activo debe ser verdadero o falso.',
        ];
    }
}
