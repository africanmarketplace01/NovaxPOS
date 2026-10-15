<?php

namespace App\View\Composers;

use App\Helpers\ThemeContrast;
use App\Models\landlord\SaasSettings;
use Illuminate\View\View;

/**
 * Shares the platform's branding with every public (landlord) page.
 *
 * Each public page used to rebuild the same values — site name, logo URL,
 * theme colours, font — in its own @php block. Doing it once here keeps the
 * pages consistent and lets the shared layout and partials rely on them.
 */
class PublicSiteComposer
{
    public function compose(View $view): void
    {
        $th = SaasSettings::allCached();

        $logo = $th['site_logo'] ?? null;
        $logoUrl = $logo
            ? (file_exists(public_path('landlord/images/logo/' . $logo))
                ? asset('landlord/images/logo/' . $logo)
                : route('landlord.logo', $logo))
            : null;

        $site = [
            'name'          => $th['site_name'] ?? 'NovaxPOS',
            'tagline'       => $th['site_tagline'] ?? 'The Modern Cloud Point of Sale Platform',
            'font'          => $th['site_font'] ?? 'Inter',
            'logo_url'      => $logoUrl,
            'primary'       => $th['theme_primary'] ?? '#0066CC',
            'primary_light' => $th['theme_primary_light'] ?? '#00A3FF',
            'accent'        => $th['theme_accent'] ?? '#66FF33',
            'dark'          => $th['theme_dark'] ?? '#003366',
        ];
        // Readable text on the accent, and the accent as text on the dark brand.
        $site['accent_ink'] = ThemeContrast::ink($site['accent']);
        $site['accent_on_dark'] = ThemeContrast::onDark($site['accent']);

        $view->with([
            'th'          => $th,
            'site'        => $site,
            // Names the existing page code already uses.
            'siteN'       => $site['name'],
            'siteLogoUrl' => $logoUrl,
        ]);
    }
}
