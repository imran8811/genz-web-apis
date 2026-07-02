<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\FoodItemResource;
use App\Models\Category;
use App\Models\FoodItem;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuController extends Controller
{
    /**
     * Full menu: active categories with available items and variants.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['foodItems' => function ($q) {
                $q->where('is_available', true)->orderBy('sort_order')
                    ->with(['variants' => fn ($v) => $v->where('is_available', true)->orderBy('sort_order')]);
            }])
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(FoodItem $foodItem): FoodItemResource
    {
        $foodItem->load(['variants', 'category']);

        return new FoodItemResource($foodItem);
    }
}
