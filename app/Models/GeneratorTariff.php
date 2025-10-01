<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneratorTariff extends Model
{
    protected $table = 'generator_tariffs';

    public $timestamps = false;

    protected $fillable = [
        'generator_id', 'from_kwh', 'to_kwh', 'price_per_kwh',
    ];

    public function generator()
    {
        return $this->belongsTo(Generator::class);
    }
}
