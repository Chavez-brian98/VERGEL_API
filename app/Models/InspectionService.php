<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class InspectionService extends Pivot
{
    protected $table = 'inspection_services';

    protected $fillable = [
        'inspection_id',
        'service_id',
        'suggested_quantity',
        'notes',
    ];

    protected $casts = [
        'suggested_quantity' => 'integer',
    ];
}
