<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyInvoice extends Model
{
    protected $fillable = [
        'company_id',
        'cycle_id',
        'ampere',
        'price_per_amp',
        'fixed_amount',
        'total_amount',
        'issued_at',
    ];

    protected $casts = [
        'issued_at'      => 'datetime',
        'ampere'         => 'float',
        'price_per_amp'  => 'float',
        'fixed_amount'   => 'float',
        'total_amount'   => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    // ✅ دائماً خليه يساوي المبلغ الثابت فقط
    protected static function booted(): void
    {
        static::saving(function (CompanyInvoice $m) {
            $m->total_amount = (float) ($m->fixed_amount ?? 0);
        });
    }
}
