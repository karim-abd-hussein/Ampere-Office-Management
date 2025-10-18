<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

class Cycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'generator_id',
        'start_date',
        'end_date',
        'unit_price_per_kwh',
        'is_archived',
    ];

    protected $casts = [
        'start_date'          => 'date',
        'end_date'            => 'date',
        'is_archived'         => 'boolean',
        'unit_price_per_kwh'  => 'decimal:2',
    ];

    // العلاقات
    public function generator()
    {
        return $this->belongsTo(Generator::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * قبل الحفظ: نضمن عدم سقوط قيمة unit_price_per_kwh
     * - لو فاضي: ناخذها من سعر المولّدة، وإذا ما في، نخليها 0
     * - نطبّع التواريخ كـ YYYY-MM-DD
     */
    protected static function booted(): void
    {
        static::saving(function (Cycle $cycle) {
            if ($cycle->start_date) {
                $cycle->start_date = Carbon::parse($cycle->start_date)->toDateString();
            }
            if ($cycle->end_date) {
                $cycle->end_date = Carbon::parse($cycle->end_date)->toDateString();
            }

            if ($cycle->unit_price_per_kwh === null || $cycle->unit_price_per_kwh === '') {
                $cycle->unit_price_per_kwh = (float) optional($cycle->generator)->price_per_kwh ?? 0;
            }
        });
    }

    /**
     * كود الدورة للعرض: "شهر 8 أسبوع 2" مبني على start_date
     * الأسابيع: 1–7 => 1 ، 8–14 => 2 ، 15–21 => 3 ، 22–28 => 4 ، >28 => 5
     */
    public function getCodeAttribute(): string
    {
        if (! $this->start_date) {
            return '';
        }

        $d = $this->start_date instanceof Carbon
            ? $this->start_date
            : Carbon::parse($this->start_date);

        $weekOfMonth = intdiv($d->day - 1, 7) + 1;


         // Check if generator is loaded, if not load it
                if (!$this->relationLoaded('generator') && $this->generator_id) {
                    $this->load('generator');
                }

          // Access generator name through relationship
         $generatorName = $this->generator ? $this->generator->name : 'غير محدد';
        return "{$generatorName} ( شهر {$d->month} أسبوع {$weekOfMonth})";
    }
}
