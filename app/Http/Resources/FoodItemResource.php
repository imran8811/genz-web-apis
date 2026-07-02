<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $variants = $this->whenLoaded('variants');
        $prices = $this->relationLoaded('variants')
            ? $this->variants->pluck('price')->map(fn ($p) => (float) $p)
            : collect();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'is_special' => (bool) $this->is_special,
            'is_signature' => (bool) $this->is_signature,
            'is_available' => (bool) $this->is_available,
            'category_slug' => $this->whenLoaded('category', fn () => $this->category->slug),
            'price_from' => $prices->isNotEmpty() ? $prices->min() : null,
            'variants' => ItemVariantResource::collection($variants),
        ];
    }
}
