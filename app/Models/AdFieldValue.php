<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdFieldValue extends Model
{
    protected $fillable = [
        'ad_id',
        'category_field_id',
        'value',
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function categoryField(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class);
    }
}
