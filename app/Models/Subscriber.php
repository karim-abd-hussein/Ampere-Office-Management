<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'meter_number',
        'box_number',
        'generator_id',
        'status',
        'subscription_date',
        'import_ref',        // يُحفظ تلقائياً أثناء الاستيراد
        'use_fixed_price',   // تفعيل التسعير الثابت
        'fixed_kwh_price',   // سعر الكيلو الثابت
    ];

    protected $casts = [
        'subscription_date' => 'date',
        'use_fixed_price'   => 'boolean',
        'fixed_kwh_price'   => 'decimal:4',
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(Generator::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
