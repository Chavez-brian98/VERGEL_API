<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country_config extends Model
{
    protected $table = 'country_config';

    protected $fillable = [
        'country',
        'currency',
        'tax_percentage',
        'card_fee_percentage',
    ];

    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'country_config_id');
    }

}
