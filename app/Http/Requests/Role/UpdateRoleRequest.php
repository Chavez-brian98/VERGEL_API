<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role') ?? $this->route('id');

        return [
            'role_name' => 'sometimes|required|string|max:60|unique:roles,role_name,'.$roleId.',id',
            'description' => 'nullable|string|max:255',
        ];
    }
}
