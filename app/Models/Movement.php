<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'movement_type',
        'movementable_type',
        'movementable_id',
        'document_type',
        'document_number',
        'entity_name',
        'entity_tax_id',
        'movement_date',
        'amount',
        'vat_amount',
        'total',
        'status',
        'notes',
        'employee_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Relación polimórfica con la entidad origen (Quote, Purchase, ExpenseTicket).
     */
    public function movementable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Colaborador responsable de la gestión o registro del movimiento.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
