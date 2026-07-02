<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'restaurant' => config('genz.restaurant'),
            'currency' => config('genz.currency'),
            'delivery_fee' => (float) config('genz.delivery_fee'),
        ]);
    }
}
