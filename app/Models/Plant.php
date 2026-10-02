<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price',
        'category_type',
        'active',
    ];

    public function quoteItems(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'plant_id');
    }
}
