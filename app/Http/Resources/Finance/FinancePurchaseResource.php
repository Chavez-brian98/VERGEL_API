<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancePurchaseResource extends JsonResource
{
    /**
     * Transforma una compra registrada a su representación camelCase.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtotal = round(
            (float) $this->taxed_amount + (float) $this->exempt_amount + (float) $this->non_taxable_amount,
            2
        );
        $vatAmount = (float) $this->vat_amount;

        return [
            'id' => $this->id,
            'issueDate' => $this->issue_date?->toDateString() ?? (string) $this->issue_date,
            'documentType' => $this->document_type,
            'documentNumber' => $this->document_number,
            'supplier' => [
                'name' => $this->supplier_name,
                'taxId' => $this->supplier_tax_id,
                'nrc' => $this->supplier_nrc,
            ],
            'amounts' => [
                'taxed' => (float) $this->taxed_amount,
                'exempt' => (float) $this->exempt_amount,
                'nonTaxable' => (float) $this->non_taxable_amount,
                'vat' => $vatAmount,
                'subtotal' => $subtotal,
                'total' => round($subtotal + $vatAmount, 2),
            ],
            'notes' => $this->notes,
            'employee' => $this->whenLoaded('employee', function () {
                return $this->employee ? [
                    'id' => $this->employee->id,
                    'fullName' => $this->employee->full_name,
                ] : null;
            }),
            'createdAt' => (string) $this->created_at,
        ];
    }
}
