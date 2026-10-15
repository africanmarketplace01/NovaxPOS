<?php

namespace App\View\Composers;

use App\Helpers\ThemeContrast;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\SaasSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Storefront-wide values for every marketplace and customer-account view.
 *
 * Shared through a composer (not the layout's @php block) because Blade
 * renders a page's sections before its layout, so pages could not otherwise
 * see the shop name, the signed-in customer or the enabled payment methods.
 */
class MarketplaceComposer
{
    public function compose(View $view): void
    {
        try {
            $th = SaasSettings::allCached();
        } catch (\Throwable $e) {
            $th = [];
        }

        // The shop's own name (admin → Settings → Marketplace Name).
        $name = trim($th['marketplace_name'] ?? '') ?: 'NovaxSuite POS & Marketplace';
        [$main, $sub] = str_contains($name, ' & ')
            ? array_map('trim', explode(' & ', $name, 2))
            : [$name, ''];

        $logo = $th['site_logo'] ?? null;
        $logoUrl = $logo
            ? (file_exists(public_path('landlord/images/logo/' . $logo))
                ? asset('landlord/images/logo/' . $logo)
                : route('landlord.logo', $logo))
            : null;

        // Header categories, busiest first; cached briefly.
        try {
            $categories = Cache::remember('mk_header_categories', 600, fn () => MarketplaceListing::visible()
                ->whereNotNull('category')
                ->selectRaw('category, COUNT(*) as total')->groupBy('category')
                ->orderByDesc('total')->limit(10)->pluck('category')->all());
        } catch (\Throwable $e) {
            $categories = [];
        }

        // Ways to pay, as actually enabled in the admin settings.
        $pay = array_filter([
            'Cash on delivery' => true,
            'MTN MoMo' => ($th['mtn_enabled'] ?? '0') === '1',
            'Orange Money' => ($th['orange_enabled'] ?? '0') === '1',
            'Bank transfer' => ($th['bank_transfer_enabled'] ?? '0') === '1',
            'Card' => ! empty($th['stripe_key']) || ! empty($th['paystack_public_key']),
        ]);

        // Cart lives in session key `mkt_cart` (see MarketplaceCheckoutController).
        $cart = session('mkt_cart', []);

        // Storefront colours follow the admin theme settings: the accent drives
        // buttons, cart and deals, the primary drives links and active states,
        // the dark colour the strip and footer.
        $cta = $th['theme_accent'] ?? '#D71920';
        $mkTheme = [
            'font' => $th['site_font'] ?? 'Inter',
            'cta' => $cta,
            'cta_ink' => ThemeContrast::ink($cta),
            'cta_on_dark' => ThemeContrast::onDark($cta),
            'link' => $th['theme_primary'] ?? '#164EA6',
            'dark' => $th['theme_dark'] ?? '#0B2E63',
        ];

        $view->with([
            'th' => $th,
            'mkTheme' => $mkTheme,
            'mkName' => $name,
            'mkNameMain' => $main,
            'mkNameSub' => $sub,
            'siteLogoUrl' => $logoUrl,
            'mkCategories' => $categories,
            'mkPay' => $pay,
            'mpCustomer' => Auth::guard('customer')->user(),
            'mpCartCount' => array_sum(array_map(fn ($i) => (int) ($i['qty'] ?? 0), $cart)),
            'supportEmail' => $th['support_email'] ?? null,
            'supportPhone' => $th['support_phone'] ?? null,
        ]);
    }
}
