<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class ChangePlanRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('assignPlan', Customer::class);
    }

    public function rules(): array
    {
        return [
            'plan_id' => 'required|exists:subscription_plans,id,deleted_at,NULL',
        ];
    }
}
