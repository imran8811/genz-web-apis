<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()
            ->with('items')
            ->latest()
            ->get();

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        $this->authorizeOwner($request, $order);

        return new OrderResource($order->load('items'));
    }

    /** Fetch one of the current user's orders by its order number (for the "View order" page). */
    public function track(Request $request, string $orderNumber): OrderResource
    {
        $order = $request->user()->orders()
            ->where('order_number', $orderNumber)
            ->with(['items', 'user'])
            ->firstOrFail();

        return new OrderResource($order);
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        if ($order->user_id !== $request->user()->id) {
            throw new AccessDeniedHttpException('This order does not belong to you.');
        }
    }
}
