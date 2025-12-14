<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoryField extends Model
{
    protected $fillable = [
        'category_id',
        'external_id',
        'field_key',
        'field_label',
        'field_type',
        'is_required',
        'is_searchable',
        'validation_rules',
        'placeholder',
        'help_text',
        'order',
        'metadata',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_searchable' => 'boolean',
        'order' => 'integer',
        'metadata' => 'array',
    ];

 
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }


    public function options(): HasMany
    {
        return $this->hasMany(CategoryFieldOption::class)->orderBy('order');
    }

    public function adFieldValues(): HasMany
    {
        return $this->hasMany(AdFieldValue::class);
    }
}
