<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Marketplace\AuthApiController;
use App\Http\Controllers\Api\Marketplace\CatalogApiController;
use App\Http\Controllers\Api\Marketplace\OrderApiController;
use App\Http\Controllers\Api\Marketplace\SourcingApiController;

/*
|--------------------------------------------------------------------------
| Marketplace Mobile API (v1)
|--------------------------------------------------------------------------
| Base URL:  https://<central-domain>/api/marketplace
| Auth:      Bearer token (see POST /auth/login) via `marketplace.api`.
| Consumed by the Flutter marketplace app.
*/

Route::prefix('v1')->group(function () {

    // ── Public ──────────────────────────────────────────────────────────────
    Route::post('auth/register', [AuthApiController::class, 'register']);
    Route::post('auth/login',    [AuthApiController::class, 'login']);

    Route::get('theme',             [CatalogApiController::class, 'theme']);
    Route::get('categories',        [CatalogApiController::class, 'categories']);
    Route::get('brands',            [CatalogApiController::class, 'brands']);
    Route::get('products',          [CatalogApiController::class, 'products']);
    Route::get('products/{id}',     [CatalogApiController::class, 'product']);
    Route::get('shops',             [CatalogApiController::class, 'shops']);
    Route::get('shops/{tenant}',    [CatalogApiController::class, 'shopProducts']);

    Route::post('orders/track',     [OrderApiController::class, 'track']); // guest tracking
    Route::get('delivery/options',  [OrderApiController::class, 'deliveryOptions']); // agencies + zones + pricing

    // Vendor self-registration (trial plans)
    Route::get('vendor-signup/packages', [\App\Http\Controllers\Api\Marketplace\VendorSignupController::class, 'packages']);
    Route::post('vendor-signup/register', [\App\Http\Controllers\Api\Marketplace\VendorSignupController::class, 'register']);

    // ── Delivery-agency portal ──────────────────────────────────────────────
    Route::post('agency/login', [\App\Http\Controllers\Api\Marketplace\DeliveryAgencyApiController::class, 'login']);
    Route::prefix('agency')->middleware('agency.api')->controller(\App\Http\Controllers\Api\Marketplace\DeliveryAgencyApiController::class)->group(function () {
        Route::get('me',                       'me');
        Route::post('logout',                  'logout');
        Route::get('orders',                   'orders');
        Route::post('orders/{id}/assign',      'assignAgent');
        Route::post('orders/{id}/status',      'updateOrderStatus');
        Route::get('agents',                   'agents');
        Route::post('agents',                  'storeAgent');
        Route::delete('agents/{id}',           'deleteAgent');
        Route::get('bikes',                    'bikes');
        Route::post('bikes',                   'storeBike');
        Route::delete('bikes/{id}',            'deleteBike');
    });

    // ── Authenticated (customer) ────────────────────────────────────────────
    Route::middleware('marketplace.api')->group(function () {
        Route::get('auth/me',        [AuthApiController::class, 'me']);
        Route::put('auth/profile',   [AuthApiController::class, 'updateProfile']);
        Route::post('auth/logout',   [AuthApiController::class, 'logout']);
        Route::post('push/register', [AuthApiController::class, 'pushRegister']);

        // Orders
        Route::get('orders',         [OrderApiController::class, 'index']);
        Route::post('orders',        [OrderApiController::class, 'place']);
        Route::get('orders/{id}',    [OrderApiController::class, 'show']);

        // Sourcing requests + chat
        Route::get('requests',                       [SourcingApiController::class, 'index']);
        Route::post('requests',                      [SourcingApiController::class, 'store']);
        Route::get('requests/{id}',                  [SourcingApiController::class, 'show']);
        Route::get('threads/{response}/messages',    [SourcingApiController::class, 'messages']);
        Route::post('threads/{response}/messages',   [SourcingApiController::class, 'send']);
    });
});
