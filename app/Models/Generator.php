<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Generator extends Model
{
    protected $fillable = [
        'name', 'code', 'area_id', 'user_id',
        'location', 'is_active', 'status', 'price_per_kwh',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** شرائح التسعير */
    public function tariffs(): HasMany
    {
        return $this->hasMany(GeneratorTariff::class)->orderBy('from_kwh');
    }

    /** ✅ الجباة المرتبطون بهذه المولدة (pivot: collector_generator) */
    public function collectors(): BelongsToMany
    {
        // لو جدول الـ pivot اسمه collector_generator (المعتاد)، خليه كذا صريح:
        return $this->belongsToMany(Collector::class, 'collector_generator', 'generator_id', 'collector_id');
        // ولو عندك أعمدة إضافية بالـ pivot، تقدر تضيف ->withPivot([...]) و ->withTimestamps()
    }

    /**
     * يرجّع سعر الكيلو المناسب لاستهلاك معيّن (ك.و.س).
     * إن لم يجد شريحة مطابقة يرجّع أقرب شريحة (الأولى أو الأخيرة)،
     * أو يرجّع price_per_kwh كـ fallback.
     */
    public function priceForConsumption(?float $consumption): float
    {
        $c = (float) ($consumption ?? 0);

        $tiers = $this->relationLoaded('tariffs')
            ? $this->tariffs
            : $this->tariffs()->get();

        if ($tiers->isEmpty()) {
            return (float) ($this->price_per_kwh ?? 0);
        }

        $tiers = $tiers->sortBy('from_kwh')->values();

        foreach ($tiers as $t) {
            $from = (float) $t->from_kwh;
            $to   = is_null($t->to_kwh) ? null : (float) $t->to_kwh;

            if ($c >= $from && ($to === null || $c <= $to)) {
                return (float) $t->price_per_kwh;
            }
        }

        // تحت أول شريحة → خذ الأولى، فوق آخر شريحة → خذ الأخيرة
        if ($c < (float) $tiers->first()->from_kwh) {
            return (float) $tiers->first()->price_per_kwh;
        }

        return (float) $tiers->last()->price_per_kwh;
    }
}
