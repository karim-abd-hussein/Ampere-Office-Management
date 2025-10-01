<?php
// app/Models/Area.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = ['name'];

    /** المولّدات ضمن هذه المنطقة */
    public function generators(): HasMany
    {
        return $this->hasMany(Generator::class);
    }
}
