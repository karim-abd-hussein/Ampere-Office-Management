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
        'fixed_kwh_price',
        'code_id'   // سعر الكيلو الثابت
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


        protected static function booted()
        {
            static::creating(function ($subscriber) {
                if (empty($subscriber->code_id)) {

                    // 🟢 Get the generator related to this subscriber
                    $generator = \App\Models\Generator::find($subscriber->generator_id);

                    if ($generator) {
                        $gen_code = trim($generator->code); // Example: "md_"

                        // 🟡 Get the last subscriber for this generator
                     $lastSubscriber = static::where('generator_id', $subscriber->generator_id)
                    ->whereNotNull('code_id')
                    ->orderByDesc(\DB::raw('CAST(SUBSTRING(code_id, 4) AS UNSIGNED)'))
                    ->first();
                            


                        // 🔢 Extract last numeric part if exists, otherwise start from 0
                        $lastNumber = 0;
                        if ($lastSubscriber && preg_match('/(\d+)$/', $lastSubscriber->code_id, $matches)) {
                            $lastNumber = (int)$matches[1];
                        }
                        
                        // ➕ Increment and create new code
                        $subscriber->code_id = $gen_code . ($lastNumber + 1);
                       
                    }
                }
            });
        }

}
