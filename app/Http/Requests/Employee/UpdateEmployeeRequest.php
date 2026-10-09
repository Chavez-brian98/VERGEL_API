<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee') ?? $this->route('id');

        return [
            'full_name' => 'sometimes|required|string|max:150',
            'role_id' => 'sometimes|required|exists:roles,id',
            'dui' => 'nullable|string|max:15|unique:employees,dui,'.$employeeId.',id,deleted_at,NULL',
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
