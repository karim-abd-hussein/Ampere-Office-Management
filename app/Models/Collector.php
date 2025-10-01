<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collector extends Model
{
    protected $fillable = ['name', 'phone'];

    // ✅ المولدات المرتبطة بهذا الجابي (many-to-many عبر collector_generator)
    public function generators(): BelongsToMany
    {
        return $this->belongsToMany(Generator::class, 'collector_generator', 'collector_id', 'generator_id');
        // لو ما عندك عمود assigned_at في الـ pivot، لا تحط withPivot('assigned_at')
    }
}
