<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'ampere',
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
}
