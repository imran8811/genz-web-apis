<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Mail\OrderConfirmationMail;
use App\Models\Deal;
use App\Models\ItemVariant;
use App\Models\Order;
use App\Services\RmsOrderForwarder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['array'],
            'items.*.variant_id' => ['required', 'integer', 'exists:item_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'deals' => ['array'],
            'deals.*.deal_id' => ['required', 'integer', 'exists:deals,id'],
            'deals.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'deals.*.selections' => ['array'],
            'deals.*.selections.*' => ['string', 'max:255'],
            'delivery.recipient_name' => ['required', 'string', 'max:255'],
            'delivery.phone' => ['required', 'string', 'max:30'],
            'delivery.address_line_1' => ['required', 'string', 'max:255'],
            'delivery.area' => ['nullable', 'string', 'max:255'],
            'delivery.city' => ['required', 'string', 'max:255'],
            'delivery.landmark' => ['nullable', 'string', 'max:255'],
            'delivery.notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:cod,online'],
        ]);

        $items = $data['items'] ?? [];
        $deals = $data['deals'] ?? [];

        if (count($items) === 0 && count($deals) === 0) {
            throw ValidationException::withMessages(['items' => ['Your cart is empty.']]);
        }

        $order = DB::transaction(function () use ($data, $items, $deals, $request) {
            $lines = [];
            $subtotal = 0.0;

            foreach ($items as $row) {
                $variant = ItemVariant::with('foodItem')->findOrFail($row['variant_id']);
                $qty = (int) $row['quantity'];
                $lineTotal = (float) $variant->price * $qty;
                $subtotal += $lineTotal;
                $lines[] = [
                    'item_variant_id' => $variant->id,
                    'deal_id' => null,
                    'name' => $variant->foodItem->name,
                    'variant_label' => $variant->label,
                    'selections' => null,
                    'quantity' => $qty,
                    'unit_price' => $variant->price,
                    'line_total' => $lineTotal,
                ];
            }

            foreach ($deals as $row) {
                $deal = Deal::findOrFail($row['deal_id']);
                $qty = (int) $row['quantity'];
                $lineTotal = (float) $deal->price * $qty;
                $subtotal += $lineTotal;
                $lines[] = [
                    'item_variant_id' => null,
                    'deal_id' => $deal->id,
                    'name' => $deal->name,
                    'variant_label' => $deal->selection_size,
                    'selections' => $row['selections'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $deal->price,
                    'line_total' => $lineTotal,
                ];
            }

            $deliveryFee = (float) config('genz.delivery_fee', 0);
            $d = $data['delivery'];

            $order = Order::create([
                'user_id' => $request->user()->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $subtotal + $deliveryFee,
                'shipping_name' => $d['recipient_name'],
                'shipping_phone' => $d['phone'],
                'shipping_address_line_1' => $d['address_line_1'],
                'shipping_area' => $d['area'] ?? null,
                'shipping_city' => $d['city'],
                'shipping_landmark' => $d['landmark'] ?? null,
                'notes' => $d['notes'] ?? null,
                'placed_at' => now(),
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        $order->load(['items', 'user']);
        $this->dispatchNotifications($order);

        return response()->json([
            'message' => 'Order placed successfully!',
            'order' => new OrderResource($order),
        ], 201);
    }

    /**
     * Fire post-order notifications. Both are best-effort — a mail/RMS failure
     * must never fail the order that was already committed.
     */
    private function dispatchNotifications(Order $order): void
    {
        try {
            if ($order->user?->email) {
                Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
            }
        } catch (\Throwable $e) {
            Log::warning('Order confirmation email failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            app(RmsOrderForwarder::class)->forward($order);
        } catch (\Throwable $e) {
            Log::warning('Forwarding order to RMS failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'GENZ-'.now()->format('Ymd').'-'.mt_rand(1000, 9999);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
