<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'group' => $this->group,
            'description' => $this->description,
            'price' => (float) $this->price,
            'tag' => $this->tag,
            'image_url' => $this->image_url,
            'requires_selection' => (bool) $this->requires_selection,
            'selection_size' => $this->selection_size,
            'selection_count' => $this->selection_count,
            'extras' => $this->whenLoaded('extras', fn () => $this->extras->pluck('label')),
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
            ])->values()),
        ];
    }
}
