<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DteInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'quote_id',
        'customer_id',
        'employee_id',
        'document_type',
        'environment',
        'subtotal',
        'vat_amount',
        'card_fee',
        'total',
        'control_number',
        'receipt_stamp',
        'issue_date',
        'transaction_status',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'card_fee' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
