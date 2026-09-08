<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'time' => now()->toISOString(),
    ]));

    // Public catalog
    Route::get('/site', [SiteController::class, 'show']);
    Route::get('/menu', [MenuController::class, 'index']);
    Route::get('/menu/items/{foodItem}', [MenuController::class, 'show']);
    Route::get('/deals', [DealController::class, 'index']);
    Route::get('/deals/{deal}', [DealController::class, 'show']);

    // Auth
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    // Authenticated
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::delete('/auth/account', [AuthController::class, 'deleteAccount']);

        Route::post('/checkout', [CheckoutController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/track/{orderNumber}', [OrderController::class, 'track']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
    });
});

/**
 * Run pending migrations on a host with no shell access.
 *
 * The token is hardcoded rather than read from env(), because a cached
 * production config makes env() return null and the guard would then silently
 * pass for everyone. Call:
 *   /api/run-migration?token=1vmhQu9Nt7KTJN5NN6aBwvCncpls3CmZRNrCKCt5
 *
 * DELETE THIS ROUTE once the migration has run. It runs `migrate --force`
 * against production with no confirmation, and the token lives in a public
 * repo — it stops a URL-guesser, nothing more.
 */
Route::get('/run-migration', function (Request $request) {
    $expected = '1vmhQu9Nt7KTJN5NN6aBwvCncpls3CmZRNrCKCt5';

    if (! hash_equals($expected, (string) $request->query('token'))) {
        return response()->json(['error' => 'Invalid or missing token.'], 403);
    }

    try {
        Artisan::call('migrate', ['--force' => true]);

        return response()->json(['ok' => true, 'output' => Artisan::output()]);
    } catch (Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
