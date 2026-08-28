<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    // Indicar que la tabla no maneja updated_at
    public $timestamps = false;

    protected $fillable = [
        'entry_type',
        'invoice_id',
        'purchase_id',
        'expense_id',
        'amount',
        'vat_amount',
        'total',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(DteInvoice::class, 'invoice_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(ExpenseTicket::class, 'expense_id');
    }
}