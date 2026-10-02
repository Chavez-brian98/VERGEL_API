<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category_id',
        'price',
        'unit_of_measure',
        'loyalty_points_multiplier',
        'description',
        'country_config_id',
        'active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function countryConfig(): BelongsTo
    {
        return $this->belongsTo(CountryConfig::class, 'country_config_id');
    }

    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'service_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }
}
