<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\MarketplaceReview;
use App\Models\landlord\SaasSettings;
use Illuminate\Http\Request;

class CatalogApiController extends Controller
{
    /**
     * Brand theme for the mobile apps — mirrors the SaaS theme colours so the
     * apps can render with the platform palette (primary, accent, dark, cyan…).
     */
    public function theme()
    {
        $s = [];
        try { $s = SaasSettings::allCached(); } catch (\Throwable $e) { $s = []; }

        return response()->json([
            'app_name' => $s['app_name'] ?? $s['site_title'] ?? 'Novax Marketplace',
            'logo_url' => $s['app_logo'] ?? $s['logo'] ?? null,
            'colors'   => [
                'primary'        => $s['theme_primary']       ?? '#0066CC',
                'primary_light'  => $s['theme_primary_light'] ?? '#00A3FF',
                'accent'         => $s['theme_accent']        ?? '#66FF33',
                'lime'           => $s['theme_lime']          ?? '#8DFF00',
                'dark'           => $s['theme_dark']          ?? '#003366',
                'cyan'           => $s['theme_cyan']          ?? '#00D4FF',
            ],
        ]);
    }

    private function listingCard(MarketplaceListing $l): array
    {
        return [
            'id'          => $l->id,
            'name'        => $l->name,
            'slug'        => $l->slug,
            'category'    => $l->category,
            'brand'       => $l->brand,
            'price'       => (float) $l->price,
            'promo_price' => $l->promo_price ? (float) $l->promo_price : null,
            'currency'    => $l->currency,
            'in_stock'    => (bool) $l->in_stock,
            'stock_qty'   => (int) $l->stock_qty,
            'image_url'   => $l->image_url,
            'tenant_id'   => $l->tenant_id,
            'shop'        => $l->tenant_store_name ?: $l->tenant_name,
        ];
    }

    public function products(Request $request)
    {
        $q = MarketplaceListing::visible();

        if ($request->filled('q')) {
            $term = $request->q;
            $q->where(fn ($sq) => $sq->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('tenant_store_name', 'like', "%{$term}%"));
        }
        if ($request->filled('category')) $q->where('category', $request->category);
        if ($request->filled('brand'))    $q->where('brand', $request->brand);
        if ($request->filled('tenant'))   $q->where('tenant_id', $request->tenant);
        if ($request->boolean('in_stock')) $q->inStock();

        match ($request->get('sort')) {
            'price_asc'  => $q->orderBy('price', 'asc'),
            'price_desc' => $q->orderBy('price', 'desc'),
            'name'       => $q->orderBy('name', 'asc'),
            // Random "discovery" mix. A client-supplied seed keeps pagination
            // stable across pages so items don't repeat while scrolling.
            'random'     => $q->orderByRaw('RAND(' . (int) $request->get('seed', 1) . ')'),
            default      => $q->latest(),
        };

        $page = $q->paginate(min((int) $request->get('per_page', 20), 50));

        return response()->json([
            'data' => collect($page->items())->map(fn ($l) => $this->listingCard($l)),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'total'        => $page->total(),
            ],
        ]);
    }

    public function product($id)
    {
        $l = MarketplaceListing::visible()->findOrFail($id);

        $reviews = collect();
        $avg = 0; $count = 0;
        try {
            $reviews = MarketplaceReview::approved()->forListing($l->id)->latest()->limit(50)->get();
            $count = $reviews->count();
            $avg = $count ? round($reviews->avg('rating'), 1) : 0;
        } catch (\Throwable $e) { /* no reviews table */ }

        return response()->json([
            'product' => array_merge($this->listingCard($l), [
                'description' => $l->description,
                'gallery'     => $l->galleryImages(),
                'rating'      => ['average' => $avg, 'count' => $count],
            ]),
            'reviews' => $reviews->map(fn ($r) => [
                'name'     => $r->reviewer_name,
                'rating'   => (int) $r->rating,
                'title'    => $r->title,
                'comment'  => $r->comment,
                'verified' => (bool) $r->is_verified,
                'date'     => optional($r->created_at)->format('M j, Y'),
            ]),
        ]);
    }

    public function categories()
    {
        $cats = MarketplaceListing::visible()->distinct()->pluck('category')->filter()->sort()->values();
        return response()->json(['data' => $cats]);
    }

    public function brands()
    {
        $brands = MarketplaceListing::visible()->distinct()->pluck('brand')->filter()->sort()->values();
        return response()->json(['data' => $brands]);
    }

    public function shops()
    {
        $rows = MarketplaceListing::visible()
            ->selectRaw('tenant_id, tenant_store_name, tenant_name, COUNT(*) as product_count, MAX(image_url) as sample_image, SUM(stock_qty) as total_stock')
            ->groupBy('tenant_id', 'tenant_store_name', 'tenant_name')
            ->orderByDesc('product_count')
            ->limit(50)->get();

        // Prefer the vendor's own store logo (from registration) over a product image.
        $logos = [];
        try {
            $logos = \App\Models\landlord\Tenant::whereIn('id', $rows->pluck('tenant_id'))->pluck('logo', 'id')->toArray();
        } catch (\Throwable $e) {}

        $shops = $rows->map(function ($s) use ($logos) {
            $logo = $logos[$s->tenant_id] ?? null;
            return [
                'tenant_id'     => $s->tenant_id,
                'name'          => $s->tenant_store_name ?: $s->tenant_name,
                'product_count' => (int) $s->product_count,
                'total_stock'   => (int) $s->total_stock,
                'image_url'     => $logo ? url('/landlord-logo/' . $logo) : $s->sample_image,
            ];
        });
        return response()->json(['data' => $shops]);
    }

    public function shopProducts(Request $request, string $tenantId)
    {
        $page = MarketplaceListing::visible()->where('tenant_id', $tenantId)
            ->latest()->paginate(min((int) $request->get('per_page', 20), 50));
        return response()->json([
            'data' => collect($page->items())->map(fn ($l) => $this->listingCard($l)),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }
}
