<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id', 'item_variant_id', 'deal_id', 'name', 'variant_label',
        'selections', 'quantity', 'unit_price', 'line_total',
    ];

    protected $casts = [
        'selections' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'item_variant_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
