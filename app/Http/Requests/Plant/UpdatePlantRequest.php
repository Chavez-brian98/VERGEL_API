<?php

namespace App\Http\Requests\Plant;

use App\Models\Plant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlantRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', Plant::class);
    }

    /**
     * Prepara y normaliza los datos de la solicitud antes de aplicar las reglas de validación.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        if ($this->has('active')) {
            $value = filter_var($this->active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $mergeData['active'] = $value;
            }
        }

        if ($this->has('remove_image')) {
            $value = filter_var($this->remove_image, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $mergeData['remove_image'] = $value;
            }
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Obtiene las reglas de validación aplicables para actualizar una planta.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $plantId = $this->route('plant') ?? $this->route('id');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                Rule::unique('plants', 'name')->ignore($plantId)->withoutTrashed(),
            ],
            'price' => [
                'sometimes',
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
            'remove_image' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Obtiene los mensajes personalizados para los errores de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la planta es obligatorio.',
            'name.string' => 'El nombre de la planta debe ser una cadena de texto.',
            'name.max' => 'El nombre de la planta no puede exceder los 150 caracteres.',
            'name.unique' => 'Ya existe otra planta registrada con este nombre.',
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
            'remove_image.boolean' => 'El indicador para remover la imagen debe ser verdadero o falso.',
        ];
    }
}
