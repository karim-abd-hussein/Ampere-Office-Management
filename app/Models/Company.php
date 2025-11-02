<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'ampere',
        'generator_id',
        'price_per_amp',
        'fixed_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'ampere'        => 'decimal:2',
        'price_per_amp' => 'decimal:2',
        'fixed_amount'  => 'decimal:2',
    ];


     public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }
}
