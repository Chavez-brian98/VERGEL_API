<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'issue_date',
        'document_type',
        'document_number',
        'supplier_tax_id',
        'supplier_nrc',
        'supplier_name',
        'employee_id',
        'taxed_amount',
        'exempt_amount',
        'non_taxable_amount',
        'vat_amount',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'taxed_amount' => 'decimal:2',
        'exempt_amount' => 'decimal:2',
        'non_taxable_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
