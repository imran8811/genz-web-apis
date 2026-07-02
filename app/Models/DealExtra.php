<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealExtra extends Model
{
    protected $fillable = ['deal_id', 'label', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }
}
