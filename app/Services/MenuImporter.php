<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Deal;
use App\Models\FoodItem;
use App\Models\ItemVariant;
use Illuminate\Support\Facades\DB;

/**
 * Imports a canonical menu (menu.json shape) into the storefront tables.
 * Upserts by slug so item/variant/deal IDs stay stable across syncs
 * (preserving references from carts and historical orders). Records that
 * disappear from the feed are deactivated, not deleted.
 *
 * Used by both MenuSeeder (bootstrap from menu.json) and `menu:sync`
 * (live pull from the RMS public feed).
 */
class MenuImporter
{
    /**
     * @param  array{categories?: array<int,array<string,mixed>>}  $menu
     * @return array{categories:int, items:int, variants:int, deals:int}
     */
    public function import(array $menu): array
    {
        $categories = $menu['categories'] ?? [];

        return DB::transaction(function () use ($categories) {
            $seenCategorySlugs = [];
            $seenItemSlugs = [];
            $seenDealSlugs = [];
            $itemIdBySlug = [];
            $variantCount = 0;
            $catSort = 0;

            // ---- Pass 1: product categories + items + variants ----
            foreach ($categories as $cat) {
                if ($this->isDealGroup($cat['id'])) {
                    continue;
                }

                $category = Category::updateOrCreate(
                    ['slug' => $cat['id']],
                    [
                        'name' => $cat['name'],
                        'type' => $cat['type'],
                        'sizes' => $cat['sizes'] ?? null,
                        'image_url' => $cat['image'] ?? null,
                        'sort_order' => $catSort++,
                        'is_active' => true,
                    ],
                );
                $seenCategorySlugs[] = $cat['id'];

                $itemSort = 0;
                foreach (($cat['items'] ?? []) as $item) {
                    $food = FoodItem::updateOrCreate(
                        ['slug' => $item['id']],
                        [
                            'category_id' => $category->id,
                            'name' => $item['name'],
                            'description' => $item['description'] ?? null,
                            'image_url' => $item['image'] ?? null,
                            'is_special' => $item['special'] ?? false,
                            'is_signature' => $item['signature'] ?? false,
                            'is_available' => true,
                            'sort_order' => $itemSort++,
                        ],
                    );
                    $itemIdBySlug[$item['id']] = $food->id;
                    $seenItemSlugs[] = $item['id'];

                    $keepVariantIds = [];
                    if ($cat['type'] === 'sized') {
                        $vSort = 0;
                        foreach (($cat['sizes'] ?? []) as $size) {
                            $price = $item['prices'][$size] ?? null;
                            if ($price === null) {
                                continue;
                            }
                            $variant = ItemVariant::updateOrCreate(
                                ['food_item_id' => $food->id, 'label' => $size],
                                ['price' => $price, 'sort_order' => $vSort++, 'is_available' => true],
                            );
                            $keepVariantIds[] = $variant->id;
                            $variantCount++;
                        }
                    } else {
                        $variant = ItemVariant::updateOrCreate(
                            ['food_item_id' => $food->id, 'label' => null],
                            ['price' => $item['price'], 'sort_order' => 0, 'is_available' => true],
                        );
                        $keepVariantIds[] = $variant->id;
                        $variantCount++;
                    }
                    // Drop variants no longer offered for this item.
                    ItemVariant::where('food_item_id', $food->id)
                        ->whereNotIn('id', $keepVariantIds)
                        ->delete();
                }
            }

            // ---- Pass 2: deal groups ----
            $dealSort = 0;
            foreach ($categories as $cat) {
                if (! $this->isDealGroup($cat['id'])) {
                    continue;
                }

                foreach (($cat['items'] ?? []) as $item) {
                    $selection = $item['pizzaSelection'] ?? null;

                    $deal = Deal::updateOrCreate(
                        ['slug' => $item['id']],
                        [
                            'name' => $item['name'],
                            'group' => $cat['name'],
                            'description' => $item['description'] ?? null,
                            'image_url' => $item['image'] ?? null,
                            'price' => $item['price'],
                            'tag' => $item['tag'] ?? null,
                            'requires_selection' => $selection !== null,
                            'selection_size' => $selection['size'] ?? null,
                            'selection_count' => $selection['count'] ?? 1,
                            'is_active' => true,
                            'sort_order' => $dealSort++,
                        ],
                    );
                    $seenDealSlugs[] = $item['id'];

                    // Extras: replace wholesale (cheap, no stable identity).
                    $deal->extras()->delete();
                    foreach (($item['dealExtras'] ?? []) as $i => $label) {
                        $deal->extras()->create(['label' => $label, 'sort_order' => $i]);
                    }

                    $optionIds = $selection !== null
                        ? collect($selection['from'] ?? [])
                            ->map(fn ($slug) => $itemIdBySlug[$slug] ?? null)
                            ->filter()->values()->all()
                        : [];
                    $deal->options()->sync($optionIds);
                }
            }

            // ---- Deactivate anything no longer present in the feed ----
            Category::whereNotIn('slug', $seenCategorySlugs ?: ['__none__'])->update(['is_active' => false]);
            FoodItem::whereNotIn('slug', $seenItemSlugs ?: ['__none__'])->update(['is_available' => false]);
            Deal::whereNotIn('slug', $seenDealSlugs ?: ['__none__'])->update(['is_active' => false]);

            return [
                'categories' => count($seenCategorySlugs),
                'items' => count($seenItemSlugs),
                'variants' => $variantCount,
                'deals' => count($seenDealSlugs),
            ];
        });
    }

    private function isDealGroup(string $categoryId): bool
    {
        return str_ends_with($categoryId, 'deals');
    }
}
