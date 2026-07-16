<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\ItemVariant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Server-side re-pricing for slug-based checkout.
 *
 * The storefront (genz-web / genz-app) now reads the menu directly from the
 * genz-admin public feed (slugs, no numeric ids), so checkout submits item/deal
 * *slugs*. We never trust client prices — this resolves each slug to a trusted
 * price. Source of truth is the admin feed (cached briefly); if the feed is
 * unreachable we fall back to the local synced mirror so checkout stays up.
 */
class AdminMenuPricing
{
    private const CACHE_KEY = 'admin_menu_pricing_maps';
    private const TTL_SECONDS = 300;

    /**
     * Price a menu item line by slug (+ size for sized items).
     *
     * @return array{name:string,label:?string,price:float}|null
     */
    public function priceItem(string $slug, ?string $size): ?array
    {
        $items = $this->feedMaps()['items'] ?? [];
        $row = $items[$slug] ?? null;

        if ($row !== null) {
            if ($row['prices'] !== null) {
                if ($size === null || ! array_key_exists($size, $row['prices'])) {
                    return null;
                }

                return ['name' => $row['name'], 'label' => $size, 'price' => $row['prices'][$size]];
            }

            return ['name' => $row['name'], 'label' => null, 'price' => (float) ($row['price'] ?? 0)];
        }

        // Feed miss / unavailable — fall back to the synced mirror.
        $query = ItemVariant::whereHas('foodItem', fn ($f) => $f->where('slug', $slug))->with('foodItem');
        $size !== null ? $query->where('label', $size) : $query->whereNull('label');
        $variant = $query->first();

        if (! $variant) {
            return null;
        }

        return ['name' => $variant->foodItem->name, 'label' => $variant->label, 'price' => (float) $variant->price];
    }

    /**
     * Price a deal line by slug.
     *
     * @return array{name:string,label:?string,price:float}|null
     */
    public function priceDeal(string $slug): ?array
    {
        $deals = $this->feedMaps()['deals'] ?? [];
        $row = $deals[$slug] ?? null;

        if ($row !== null) {
            return ['name' => $row['name'], 'label' => $row['selection_size'], 'price' => (float) $row['price']];
        }

        $deal = Deal::where('slug', $slug)->first();

        if (! $deal) {
            return null;
        }

        return ['name' => $deal->name, 'label' => $deal->selection_size, 'price' => (float) $deal->price];
    }

    /**
     * Build (and briefly cache) price lookups from the admin feed. Only
     * successful fetches are cached, so a transient outage retries next time.
     *
     * @return array{items:array<string,array>,deals:array<string,array>}
     */
    private function feedMaps(): array
    {
        try {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable) {
            // Cache misconfigured — fall through and fetch directly.
        }

        $built = $this->buildFromFeed();
        if ($built !== null) {
            try {
                Cache::put(self::CACHE_KEY, $built, self::TTL_SECONDS);
            } catch (\Throwable) {
                // Non-fatal: caching is an optimisation, not required.
            }

            return $built;
        }

        return ['items' => [], 'deals' => []];
    }

    /**
     * @return array{items:array<string,array>,deals:array<string,array>}|null
     */
    private function buildFromFeed(): ?array
    {
        $url = config('genz.admin_menu_url');
        if (! $url) {
            return null;
        }

        try {
            $response = Http::timeout(15)->acceptJson()->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $feed = $response->json();
        if (! is_array($feed) || ! isset($feed['categories']) || ! is_array($feed['categories'])) {
            return null;
        }

        $items = [];
        $deals = [];

        foreach ($feed['categories'] as $category) {
            $isDeals = str_ends_with((string) ($category['id'] ?? ''), 'deals');

            foreach (($category['items'] ?? []) as $item) {
                $slug = $item['id'] ?? null;
                if (! $slug) {
                    continue;
                }

                if ($isDeals) {
                    $deals[$slug] = [
                        'name' => $item['name'] ?? $slug,
                        'price' => (float) ($item['price'] ?? 0),
                        'selection_size' => $item['pizzaSelection']['size'] ?? null,
                    ];

                    continue;
                }

                $prices = null;
                if (isset($item['prices']) && is_array($item['prices'])) {
                    $prices = [];
                    foreach ($item['prices'] as $size => $price) {
                        $prices[$size] = (float) $price;
                    }
                }

                $items[$slug] = [
                    'name' => $item['name'] ?? $slug,
                    'prices' => $prices,
                    'price' => isset($item['price']) ? (float) $item['price'] : null,
                ];
            }
        }

        return ['items' => $items, 'deals' => $deals];
    }
}
