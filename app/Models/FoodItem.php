<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodItem extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'image_url',
        'is_special', 'is_signature', 'is_available', 'sort_order',
    ];

    protected $casts = [
        'is_special' => 'boolean',
        'is_signature' => 'boolean',
        'is_available' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ItemVariant::class)->orderBy('sort_order');
    }
}
