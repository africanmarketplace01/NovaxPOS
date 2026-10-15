<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\MarketplaceReview;
use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\Tenant;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    // ── Marketplace homepage ───────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = MarketplaceListing::visible();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%")
                   ->orWhere('tenant_store_name', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('tenant')) {
            $query->where('tenant_id', $request->tenant);
        }

        if ($request->get('in_stock')) {
            $query->inStock();
        }

        $this->applyPriceFilter($query, $request);
        $this->applySort($query, $request->get('sort', 'newest'));

        $products   = $query->paginate(24)->withQueryString();
        $categories = MarketplaceListing::visible()->distinct()->pluck('category')->filter()->sort()->values();
        $shops      = MarketplaceListing::visible()
            ->selectRaw('tenant_id, tenant_store_name, tenant_name, COUNT(*) as product_count')
            ->groupBy('tenant_id', 'tenant_store_name', 'tenant_name')
            ->orderByDesc('product_count')
            ->limit(12)
            ->get();

        // Attach tenant logos to shop rows
        $tenantModels = Tenant::whereIn('id', $shops->pluck('tenant_id'))->get()->keyBy('id');
        $shops->each(function ($shop) use ($tenantModels) {
            $t = $tenantModels->get($shop->tenant_id);
            $shop->logo_url = $t ? $t->logo_url : null;
        });

        $featured = MarketplaceListing::visible()->inStock()->latest()->limit(8)->get();

        // ── Home-page shelves (only built when the shopper isn't searching) ──
        $deals = $newArrivals = $categoryTiles = $categoryRows = collect();
        if (! self::isBrowsing($request)) {
            // Biggest real discounts first.
            $deals = MarketplaceListing::visible()->inStock()
                ->whereNotNull('promo_price')->whereColumn('promo_price', '<', 'price')->where('price', '>', 0)
                ->orderByRaw('(price - promo_price) / price DESC')
                ->limit(12)->get();

            $newArrivals = MarketplaceListing::visible()->inStock()->latest()->limit(12)->get();

            // Category tiles use a real product photo from that category.
            $counts = MarketplaceListing::visible()->whereNotNull('category')
                ->selectRaw('category, COUNT(*) as total')->groupBy('category')
                ->orderByDesc('total')->limit(12)->pluck('total', 'category');
            $categoryTiles = $counts->map(function ($total, $category) {
                $photo = MarketplaceListing::visible()->where('category', $category)
                    ->whereNotNull('image_url')->where('image_url', '!=', '')->latest()->value('image_url');
                return (object) ['name' => $category, 'total' => $total, 'image_url' => $photo];
            })->values();

            // A shelf for each of the two busiest categories.
            $categoryRows = $counts->keys()->take(2)->map(fn ($category) => (object) [
                'name' => $category,
                'products' => MarketplaceListing::visible()->inStock()->where('category', $category)->latest()->limit(10)->get(),
            ])->filter(fn ($row) => $row->products->count() >= 4)->values();
        }

        return view('landlord.marketplace.index', compact(
            'products', 'categories', 'shops', 'featured', 'deals', 'newArrivals', 'categoryTiles', 'categoryRows'
        ));
    }

    /**
     * True when the shopper is searching, filtering or sorting: the page shows
     * a results grid instead of the home-page shelves.
     */
    public static function isBrowsing(Request $request): bool
    {
        return $request->hasAny(['q', 'category', 'tenant', 'price_min', 'price_max', 'sort', 'all', 'in_stock']);
    }

    /** ?price_min / ?price_max on the price the shopper actually pays. */
    private function applyPriceFilter($query, Request $request): void
    {
        $effective = 'COALESCE(promo_price, price)';
        if (is_numeric($request->price_min)) {
            $query->whereRaw("{$effective} >= ?", [(float) $request->price_min]);
        }
        if (is_numeric($request->price_max)) {
            $query->whereRaw("{$effective} <= ?", [(float) $request->price_max]);
        }
    }

    private function applySort($query, ?string $sort): void
    {
        match ($sort) {
            'price_asc'  => $query->orderByRaw('COALESCE(promo_price, price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(promo_price, price) desc'),
            'name'       => $query->orderBy('name', 'asc'),
            'discount'   => $query->orderByRaw('CASE WHEN promo_price IS NOT NULL AND price > 0 THEN (price - promo_price) / price ELSE 0 END DESC'),
            default      => $query->latest(),
        };
    }

    // ── Single shop (one tenant's products on marketplace) ────────────────────

    public function shop(Request $request, string $tenantId)
    {
        $shopInfo = MarketplaceListing::visible()
            ->where('tenant_id', $tenantId)
            ->selectRaw('tenant_id, tenant_store_name, tenant_name, COUNT(*) as product_count')
            ->groupBy('tenant_id', 'tenant_store_name', 'tenant_name')
            ->firstOrFail();

        // Attach tenant logo
        $shopTenant = Tenant::find($tenantId);
        $shopInfo->logo_url = $shopTenant ? $shopTenant->logo_url : null;

        $query = MarketplaceListing::visible()->forTenant($tenantId);

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $this->applyPriceFilter($query, $request);
        // The store page's sort menu was ignored before; sort like the main catalogue.
        $this->applySort($query, $request->get('sort', 'newest'));

        $products   = $query->paginate(20)->withQueryString();
        $categories = MarketplaceListing::visible()->forTenant($tenantId)->distinct()->pluck('category')->filter()->sort()->values();

        return view('landlord.marketplace.shop', compact('shopInfo', 'products', 'categories'));
    }

    // ── Product detail ────────────────────────────────────────────────────────

    public function product(int $id)
    {
        $product = MarketplaceListing::visible()->findOrFail($id);

        $related = MarketplaceListing::visible()
            ->where('id', '!=', $id)
            ->where(function ($q) use ($product) {
                $q->where('category', $product->category)
                  ->orWhere('tenant_id', $product->tenant_id);
            })
            ->inStock()
            ->limit(4)
            ->get();

        // Reviews + rating summary
        $reviews = collect();
        $ratingStats = ['count' => 0, 'average' => 0, 'breakdown' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]];
        try {
            $reviews = MarketplaceReview::approved()->forListing($id)->latest()->get();
            if ($reviews->count()) {
                $ratingStats['count']   = $reviews->count();
                $ratingStats['average'] = round($reviews->avg('rating'), 1);
                foreach ($reviews as $r) {
                    $star = max(1, min(5, (int) $r->rating));
                    $ratingStats['breakdown'][$star]++;
                }
            }
        } catch (\Throwable $e) {
            // reviews table not migrated yet — page still renders
        }

        return view('landlord.marketplace.product', compact('product', 'related', 'reviews', 'ratingStats'));
    }

    // ── Submit a product review ────────────────────────────────────────────────

    public function storeReview(Request $request, int $id)
    {
        $product = MarketplaceListing::visible()->findOrFail($id);

        $data = $request->validate([
            'reviewer_name'  => 'required|string|max:80',
            'reviewer_email' => 'nullable|email|max:120',
            'rating'         => 'required|integer|min:1|max:5',
            'title'          => 'nullable|string|max:120',
            'comment'        => 'required|string|max:2000',
        ]);

        MarketplaceReview::create(array_merge($data, [
            'listing_id'  => $product->id,
            'product_id'  => $product->product_id,
            'tenant_id'   => $product->tenant_id,
            'is_verified' => false,
            'is_approved' => true,
        ]));

        return redirect()
            ->route('marketplace.product', ['id' => $product->id, 'slug' => $product->slug])
            ->with('success', 'Thank you! Your review has been posted.')
            ->withFragment('reviews');
    }
}
