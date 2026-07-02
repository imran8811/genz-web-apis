<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DealResource;
use App\Models\Deal;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DealController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $deals = Deal::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->with(['extras', 'options'])
            ->get();

        return DealResource::collection($deals);
    }

    public function show(Deal $deal): DealResource
    {
        $deal->load(['extras', 'options.variants']);

        return new DealResource($deal);
    }
}
