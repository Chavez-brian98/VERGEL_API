<?php

namespace App\Http\Resources\Transactions;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transforma el recurso de transacción histórica a un array representativo.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isCancelled = $this->trashed();
        $isPaid = ! $isCancelled && $this->status === 'paid';

        return [
            'id' => $this->id,
            'quoteNumber' => $this->quote_number,
            'transactionReference' => $this->quote_number,
            'customerId' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? [
                    'id' => $this->customer->id,
                    'legalName' => $this->customer->legal_name,
                    'tradeName' => $this->customer->trade_name,
                    'taxId' => $this->customer->tax_id,
                    'phone' => $this->customer->phone,
                    'email' => $this->customer->email,
                    'municipality' => $this->customer->municipality,
                    'department' => $this->customer->department,
                    'address' => $this->customer->address,
                ] : null;
            }),
            'employeeId' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', function () {
                return $this->employee ? [
                    'id' => $this->employee->id,
                    'fullName' => $this->employee->full_name,
                ] : null;
            }),
            'plan' => $this->whenLoaded('plan', function () {
                return $this->plan ? [
                    'id' => $this->plan->id,
                    'name' => $this->plan->name,
                    'code' => $this->plan->code,
                ] : null;
            }),
            'amounts' => [
                'basePrice' => (float) $this->base_price,
                'discountedSubtotal' => (float) $this->discounted_subtotal,
                'includesVat' => (bool) $this->includes_vat,
                'total' => (float) $this->total,
            ],
            'loyaltyPoints' => [
                'used' => (int) $this->loyalty_points_used,
                'earned' => (int) $this->loyalty_points_earned,
            ],
            'status' => [
                'raw' => $isCancelled ? 'cancelled' : $this->status,
                'state' => $isCancelled ? 'declined' : ($isPaid ? 'performed' : 'pending'),
                'label' => $isCancelled ? 'Trabajo Declinado / Cancelado' : ($isPaid ? 'Trabajo Efectuado (Pagado)' : 'Trabajo en Curso'),
                'isCancelled' => $isCancelled,
                'isPaid' => $isPaid,
                'badge' => $isCancelled ? 'danger' : ($isPaid ? 'success' : 'warning'),
            ],
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'itemType' => $item->item_type,
                        'quantity' => (float) $item->quantity,
                        'unitPrice' => (float) $item->unit_price,
                        'total' => (float) $item->total,
                        'service' => $item->relationLoaded('service') && $item->service ? [
                            'id' => $item->service->id,
                            'name' => $item->service->name,
                        ] : null,
                        'plant' => $item->relationLoaded('plant') && $item->plant ? [
                            'id' => $item->plant->id,
                            'name' => $item->plant->name,
                        ] : null,
                    ];
                });
            }),
            'payments' => $this->whenLoaded('payments', function () {
                return $this->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amountPaid' => (float) $payment->amount_paid,
                        'paymentMethod' => $payment->payment_method,
                        'paymentDate' => $payment->payment_date?->toIso8601String() ?? (string) $payment->payment_date,
                    ];
                });
            }),
            'createdAt' => $this->created_at?->toIso8601String(),
            'declinedAt' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
