<?php

namespace App\Http\Resources\Movements;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovementResource extends JsonResource
{
    /**
     * Transforma el recurso de movimiento contable a un array estandarizado.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $typeLabel = match ($this->movement_type) {
            'sale' => 'Venta',
            'purchase' => 'Compra',
            'expense' => 'Gasto / Ticket',
            default => ucfirst((string) $this->movement_type),
        };

        $entityRole = match ($this->movement_type) {
            'sale' => 'customer',
            'purchase' => 'supplier',
            'expense' => 'expense_category',
            default => 'other',
        };

        $isCancelled = $this->status === 'cancelled' || $this->trashed();

        return [
            'id' => $this->id,
            'type' => $this->movement_type,
            'typeLabel' => $typeLabel,
            'documentType' => $this->document_type,
            'documentNumber' => $this->document_number,
            'entity' => [
                'name' => $this->entity_name,
                'taxId' => $this->entity_tax_id,
                'role' => $entityRole,
            ],
            'date' => $this->movement_date instanceof \DateTimeInterface
                ? $this->movement_date->format('Y-m-d')
                : (string) $this->movement_date,
            'amounts' => [
                'amount' => (float) $this->amount,
                'vatAmount' => (float) $this->vat_amount,
                'total' => (float) $this->total,
            ],
            'status' => [
                'raw' => $this->status,
                'label' => $isCancelled ? 'Cancelado / Anulado' : 'Completado',
                'isCancelled' => $isCancelled,
                'badge' => $isCancelled ? 'danger' : 'success',
            ],
            'notes' => $this->notes,
            'employee' => $this->whenLoaded('employee', function () {
                return $this->employee ? [
                    'id' => $this->employee->id,
                    'fullName' => $this->employee->full_name,
                ] : null;
            }),
            'origin' => [
                'type' => $this->movementable_type ? class_basename($this->movementable_type) : null,
                'id' => $this->movementable_id,
            ],
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
