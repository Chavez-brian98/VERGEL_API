<?php

namespace App\Http\Requests\Quote;

use App\Models\Quote;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Quote::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'applied_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'includes_vat' => ['sometimes', 'boolean'],
            'loyalty_points_used' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:pending,paid'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:service,plant'],
            'items.*.service_id' => ['nullable', 'integer', 'exists:services,id'],
            'items.*.plant_id' => ['nullable', 'integer', 'exists:plants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Validaciones cruzadas entre los ítems y los puntos de lealtad.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $items = (array) $this->input('items', []);
                $hasServiceItem = false;

                foreach ($items as $index => $item) {
                    $type = $item['item_type'] ?? null;

                    if ($type === 'service') {
                        $hasServiceItem = true;
                        if (empty($item['service_id'])) {
                            $validator->errors()->add(
                                "items.{$index}.service_id",
                                'El servicio es obligatorio cuando el tipo de ítem es service.'
                            );
                        }
                    }

                    if ($type === 'plant' && empty($item['plant_id'])) {
                        $validator->errors()->add(
                            "items.{$index}.plant_id",
                            'La planta es obligatoria cuando el tipo de ítem es plant.'
                        );
                    }
                }

                $pointsUsed = (int) $this->input('loyalty_points_used', 0);

                if ($pointsUsed > 0 && ! $hasServiceItem) {
                    $validator->errors()->add(
                        'loyalty_points_used',
                        'Los puntos de lealtad solo pueden usarse para cubrir un servicio completo.'
                    );
                }
            },
        ];
    }
}
