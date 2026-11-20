<?php

namespace App\Http\Requests\Finance;

use App\Http\Requests\Movement\IndexMovementRequest;

class IndexFinanceMovementsRequest extends IndexMovementRequest
{
    /**
     * El libro financiero de movimientos es de uso exclusivo de Admin / Administrador.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewFinance');
    }

    /**
     * Acepta los alias de fecha y tipo usados en el resto del módulo Finanzas.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $mergeData = [];

        foreach (['from' => 'start_date', 'desde' => 'start_date'] as $alias => $target) {
            if ($this->has($alias) && ! $this->has($target)) {
                $mergeData[$target] = $this->input($alias);
            }
        }

        foreach (['to' => 'end_date', 'hasta' => 'end_date'] as $alias => $target) {
            if ($this->has($alias) && ! $this->has($target)) {
                $mergeData[$target] = $this->input($alias);
            }
        }

        $spanishTypes = [
            'todos' => 'all',
            'venta' => 'sale',
            'compra' => 'purchase',
            'gasto' => 'expense',
        ];

        foreach (['movement_type', 'type'] as $key) {
            if (! $this->has($key)) {
                continue;
            }

            $value = strtolower((string) $this->input($key));
            if (isset($spanishTypes[$value])) {
                $mergeData[$key] = $spanishTypes[$value];
            }
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }
}
