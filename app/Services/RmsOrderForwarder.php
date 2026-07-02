<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\ItemVariant;
use App\Models\Order;
use Illuminate\Support\Facades\Http;

/**
 * Forwards a placed online order into the RMS (genz-rms-apis) so it shows up in
 * the POS. Posts to the RMS integration endpoint with a shared secret header.
 * Each line carries the menu-item slug so the RMS can link it to its own
 * menu_items mirror by slug. Best-effort: the caller wraps this so a failure
 * never blocks the customer's checkout.
 */
class RmsOrderForwarder
{
    public function forward(Order $order): bool
    {
        $url = config('genz.rms.orders_url');
        $secret = config('genz.rms.secret');

        if (! $url || ! $secret) {
            return false; // integration not configured
        }

        $order->loadMissing('items');

        $payload = [
            'web_order_number' => $order->order_number,
            'order_type' => 'Delivery',
            'source' => 'web',
            'subtotal' => (int) round($order->subtotal),
            'delivery_charge' => (int) round($order->delivery_fee),
            'total' => (int) round($order->total_amount),
            'payment_method' => $order->payment_method,
            'customer' => [
                'name' => $order->shipping_name,
                'phone' => $order->shipping_phone,
            ],
            'address' => trim(implode(', ', array_filter([
                $order->shipping_address_line_1,
                $order->shipping_area,
                $order->shipping_city,
                $order->shipping_landmark,
            ]))),
            'notes' => $order->notes,
            'items' => $order->items->map(fn ($item) => [
                'slug' => $this->resolveSlug($item),
                'item_name' => $item->name,
                'size' => $item->variant_label,
                'unit_price' => (int) round($item->unit_price),
                'quantity' => (int) $item->quantity,
                'line_total' => (int) round($item->line_total),
                'deal_selections' => $item->selections ?: null,
            ])->values()->all(),
        ];

        $response = Http::timeout(10)
            ->withHeaders(['X-Integration-Secret' => $secret])
            ->acceptJson()
            ->post($url, $payload);

        return $response->successful();
    }

    /** Map a web order line back to the shared menu-item slug (or null). */
    private function resolveSlug($item): ?string
    {
        if ($item->item_variant_id) {
            return ItemVariant::with('foodItem')->find($item->item_variant_id)?->foodItem?->slug;
        }
        if ($item->deal_id) {
            return Deal::find($item->deal_id)?->slug;
        }

        return null;
    }
}
