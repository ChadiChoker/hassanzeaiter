<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryFieldOption extends Model
{
    protected $fillable = [
        'category_field_id',
        'external_id',
        'option_key',
        'option_label',
        'option_value',
        'order',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'order' => 'integer',
        'metadata' => 'array',
    ];

    public function categoryField(): BelongsTo
    {
        return $this->belongsTo(CategoryField::class);
    }
}
