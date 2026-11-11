<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inspection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'appointment_id',
        'employee_id',
        'inspection_date',
        'findings',
        'terrain_width',
        'terrain_length',
        'total_area',
        'area_unit',
        'terrain_notes',
        'status',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'terrain_width' => 'decimal:2',
        'terrain_length' => 'decimal:2',
        'total_area' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'inspection_services')
            ->using(InspectionService::class)
            ->withPivot(['suggested_quantity', 'notes'])
            ->withTimestamps();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(InspectionPhoto::class);
    }
}
