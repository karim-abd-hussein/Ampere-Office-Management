<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscriber_name',
        'subscriber_phone',
        'subscriber_meter_number',
        'subscriber_box_number',
        'subscriber_use_fixed_price',
        'subscriber_id',
        'subscriber_status',
        'generator_id',
        'collector_id',
        'cycle_id',
        'old_reading',
        'new_reading',
        'consumption',
        'unit_price_used',
        'calculated_total',
        'final_amount',
        'issued_at',
        'subscriber_code_id'
    ];

    protected $casts = [
        'issued_at'        => 'datetime',
        'old_reading'      => 'integer',
        'new_reading'      => 'integer',
        'consumption'      => 'integer',
        'unit_price_used'  => 'decimal:2',
        'calculated_total' => 'decimal:2',
        'final_amount'     => 'decimal:2',
        'subscriber_use_fixed_price' => 'boolean',
    ];

    public function subscriber() { return $this->belongsTo(Subscriber::class, 'subscriber_id'); }
    public function generator()  { return $this->belongsTo(Generator::class); }
    public function collector()  { return $this->belongsTo(Collector::class); }
    public function cycle()      { return $this->belongsTo(Cycle::class); }

    // ✅ كانت موجودة لديك — نتركها كما هي حتى لا ينكسر أي كود قديم
    public function receipt()    { return $this->hasOne(Receipt::class); }

    // ✅ الجديدة: أكثر من وصل لنفس الفاتورة
    public function receipts()   { return $this->hasMany(Receipt::class); }

    protected static bool $skipChain = false;

    protected static function booted(): void
    {

    //      static::creating(function ($invoice) {
    //     if ($invoice->subscriber &&  $invoice->subscriber_status==null) {
    //         $invoice->subscriber_status = $invoice->subscriber->status;
    //     }
    //    });


        // static::saving(function (Invoice $invoice) {
        //     if (!$invoice->generator_id && $invoice->subscriber) {
        //         $invoice->generator_id = $invoice->subscriber->generator_id;
        //     }

        //     if (is_null($invoice->unit_price_used) && $invoice->subscriber && $invoice->subscriber->generator) {
        //         $invoice->unit_price_used = $invoice->subscriber->generator->price_per_kwh ?? 0;
        //     }

        //     $old = max(0, (int) $invoice->old_reading);
        //     $new = max(0, (int) $invoice->new_reading);

        //     $invoice->consumption      = max(0, $new - $old);
        //     $price                     = (float) ($invoice->unit_price_used ?? 0);
        //     $invoice->calculated_total = round($invoice->consumption * $price, 2);

        //     if ($invoice->isDirty(['new_reading','old_reading','unit_price_used','consumption']) || is_null($invoice->final_amount)) {
        //         $invoice->final_amount = $invoice->calculated_total;
        //     }
        // });

        static::saved(function (Invoice $invoice) {
            if (self::$skipChain) return;
            if (! $invoice->wasChanged(['new_reading', 'old_reading'])) return;

            $invoice->loadMissing('cycle:id,start_date');
            self::recalculateForwardFrom($invoice);
        });
    }

    public static function recalculateForwardFrom(Invoice $start): void
    {
        $rows = static::query()
            ->where('subscriber_id', $start->subscriber_id)
            ->join('cycles', 'cycles.id', '=', 'invoices.cycle_id')
            ->orderBy('cycles.start_date')
            ->orderBy('invoices.id')
            ->select('invoices.*')
            ->get();

        if ($rows->isEmpty()) return;

        $index = $rows->search(fn ($r) => (int) $r->id === (int) $start->id);
        if ($index === false) return;

        $prev = $rows[$index];


        $rows->load(['subscriber.generator']);

        for ($i = $index + 1, $n = $rows->count(); $i < $n; $i++) {
            /** @var \App\Models\Invoice $curr */
            $curr = $rows[$i];

            $curr->old_reading      = max(0, (int) $prev->new_reading);
            $curr->consumption      = max(0, (int) $curr->new_reading - (int) $curr->old_reading);
            if($curr->subscriber_use_fixed_price){

                $curr->calculated_total = round($curr->consumption * (float)$curr->unit_price_used, 2);
            }else{


                 $generator = $curr->subscriber->generator;
                    if ($generator) {
                        $price = $generator->priceForConsumption($curr->consumption);
                        $curr->calculated_total = round($curr->consumption * (float)$price, 2);
                        $curr->unit_price_used = $price;
                    }


            }
            
            $curr->final_amount     = $curr->calculated_total;

            self::$skipChain = true;
            $curr->saveQuietly();
            self::$skipChain = false;

            $prev = $curr;
        }
    }
}
