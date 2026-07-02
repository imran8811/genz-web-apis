<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'sizes' => $this->sizes,
            'image_url' => $this->image_url,
            'items' => FoodItemResource::collection($this->whenLoaded('foodItems')),
        ];
    }
}
