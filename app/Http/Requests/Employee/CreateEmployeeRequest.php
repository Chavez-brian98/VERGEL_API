<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class CreateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:150',
            'role_id' => 'required|exists:roles,id',
            'dui' => 'nullable|string|max:15|unique:employees,dui,NULL,id,deleted_at,NULL',
            'address' => 'nullable|string|max:255',
            'municipality' => 'nullable|string|max:60',
            'department' => 'nullable|string|max:60',
            'birth_date' => 'nullable|date',
            'hire_date' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'active' => 'sometimes|boolean',
        ];
    }
}
