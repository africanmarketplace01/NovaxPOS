<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StoreSettings;
use App\Services\MarketplaceSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tenant-side controller: manages which products are listed on the global marketplace.
 *
 * Products are "pushed" to the landlord marketplace_listings table via a direct
 * DB connection using the landlord connection alias.
 */
class MarketplaceListingController extends Controller
{
    // ── Listing management page ───────────────────────────────────────────────

    public function index(Request $request)
    {
        $store = StoreSettings::instance();

        if (!$store->marketplace_listed) {
            return view('backend.store.marketplace-not-enabled', compact('store'));
        }

        $products = Product::where('is_active', true)
            ->where('type', 'standard')
            ->with(['category', 'brand'])
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        // Fetch which product_ids are currently listed in the marketplace
        $tenantId = tenancy()->tenant?->id ?? config('app.tenant_id');
        $listed   = DB::connection('saleprosaas_landlord')
            ->table('marketplace_listings')
            ->where('tenant_id', $tenantId)
            ->pluck('is_active', 'product_id')
            ->toArray();

        return view('backend.store.marketplace-listings', compact('store', 'products', 'listed'));
    }

    // ── Toggle a single product on/off the marketplace ─────────────────────────

    public function toggle(Request $request, int $productId)
    {
        $product  = Product::where('is_active', true)->findOrFail($productId);
        $store    = StoreSettings::instance();
        $tenantId = tenancy()->tenant?->id ?? config('app.tenant_id');

        if (!$store->marketplace_listed) {
            return response()->json(['error' => 'Marketplace listing is not enabled for your store.'], 422);
        }

        $existing = DB::connection('saleprosaas_landlord')
            ->table('marketplace_listings')
            ->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            // Toggle is_active
            $newState = !$existing->is_active;
            DB::connection('saleprosaas_landlord')
                ->table('marketplace_listings')
                ->where('id', $existing->id)
                ->update(['is_active' => $newState, 'updated_at' => now()]);

            return response()->json(['listed' => $newState]);
        }

        // New listing
        $this->upsertListing($tenantId, $product, $store);
        return response()->json(['listed' => true]);
    }

    // ── Sync all listed products (price/stock refresh) ─────────────────────────

    public function syncAll(Request $request)
    {
        $tenantId = tenancy()->tenant?->id ?? config('app.tenant_id');
        $store    = StoreSettings::instance();

        if (!$store->marketplace_listed) {
            return back()->with('error', 'Enable marketplace listing first.');
        }

        // Only sync products that are already in the listing table
        $listedIds = DB::connection('saleprosaas_landlord')
            ->table('marketplace_listings')
            ->where('tenant_id', $tenantId)
            ->pluck('product_id')
            ->toArray();

        if (empty($listedIds)) {
            return back()->with('warning', 'No products are listed yet. Enable individual products first.');
        }

        $products = Product::whereIn('id', $listedIds)->where('is_active', true)->get();
        $count    = 0;

        foreach ($products as $product) {
            $this->upsertListing($tenantId, $product, $store);
            $count++;
        }

        return back()->with('message', "{$count} product(s) synced to the marketplace.");
    }

    // ── Publish ALL active products to the marketplace ─────────────────────────

    public function publishAll(Request $request)
    {
        $store    = StoreSettings::instance();
        $tenantId = tenancy()->tenant?->id ?? config('app.tenant_id');

        if (!$store->marketplace_listed) {
            return back()->with('error', 'Enable marketplace listing first.');
        }

        $products = Product::where('is_active', true)
            ->where('type', 'standard')
            ->get();

        $count = 0;
        foreach ($products as $product) {
            $this->upsertListing($tenantId, $product, $store);
            $count++;
        }

        return back()->with('message', "{$count} product(s) published to the marketplace.");
    }

    // ── Remove all listings for this tenant ───────────────────────────────────

    public function removeAll()
    {
        $tenantId = tenancy()->tenant?->id ?? config('app.tenant_id');

        DB::connection('saleprosaas_landlord')
            ->table('marketplace_listings')
            ->where('tenant_id', $tenantId)
            ->delete();

        StoreSettings::instance()->update(['marketplace_listed' => false, 'marketplace_listed_at' => null]);

        return back()->with('message', 'All marketplace listings removed.');
    }

    // ── Helper: upsert a listing row in the landlord DB ──────────────────────

    private function upsertListing(string $tenantId, Product $product, StoreSettings $store): void
    {
        app(MarketplaceSyncService::class)->upsertListing($tenantId, $product, $store);
    }
}
