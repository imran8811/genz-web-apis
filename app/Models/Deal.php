<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    protected $fillable = [
        'name', 'slug', 'group', 'description', 'price', 'tag', 'image_url',
        'requires_selection', 'selection_size', 'selection_count',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'requires_selection' => 'boolean',
        'selection_count' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function extras(): HasMany
    {
        return $this->hasMany(DealExtra::class)->orderBy('sort_order');
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(FoodItem::class, 'deal_options');
    }
}
