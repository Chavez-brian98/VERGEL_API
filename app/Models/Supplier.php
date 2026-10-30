<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'trade_name',
        'tax_id',
        'nrc',
        'economic_activity',
        'phone',
        'email',
        'address',
        'contact_name',
        'contact_phone',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Compras asociadas al proveedor (por tax_id / nrc / nombre)
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_tax_id', 'tax_id');
    }
}
