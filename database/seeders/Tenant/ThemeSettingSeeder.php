<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ThemeSettingSeeder extends Seeder
{
    /**
     * Seed the tenant theme_settings table.
     *
     * In SaaS mode, copies the superadmin's active theme from the landlord DB
     * so every tenant automatically inherits the platform theme.
     * Falls back to a single hardcoded default if the landlord DB does not yet
     * have the theme_settings table or has no active theme configured.
     *
     * Tenants cannot modify theme settings — they are locked by the controller.
     */
    public function run(): void
    {
        if (DB::table('theme_settings')->count()) {
            return; // already seeded, nothing to do
        }

        $row = null;

        // ── Try to pull the active theme from the landlord (SaaS) DB ─────────
        $landlordConnection = config('tenancy.database.central_connection');

        if ($landlordConnection && $landlordConnection !== config('database.default')) {
            try {
                if (Schema::connection($landlordConnection)->hasTable('theme_settings')) {
                    $row = DB::connection($landlordConnection)
                        ->table('theme_settings')
                        ->where('is_deleted', false)
                        ->where('is_active', true)
                        ->orderBy('id')
                        ->first();

                    if ($row) {
                        // Strip the primary key; tenant gets its own auto-incremented id
                        $row = collect($row)->except('id')->toArray();
                    }
                }
            } catch (\Throwable $e) {
                // Landlord DB not reachable or schema mismatch — fall through to default
                $row = null;
            }
        }

        // ── Fallback: one clean default theme ─────────────────────────────────
        if (!$row) {
            $row = [
                'name'                          => 'Default',
                'theme_appearance'              => 'system_both',
                'theme_color'                   => '#6366F1',
                'font_family'                   => 'Jost',
                'icon_pack'                     => 'solar',
                'item_size'                     => 16,
                'input_design'                  => 'filled',
                'button_style'                  => 'filled',
                'button_colors'                 => json_encode(['#6366F1']),
                'button_dark_colors'            => json_encode(['#818CF8']),
                'button_preferred_width'        => 'fit',
                'button_height'                 => 52,
                'border_radius'                 => 'rounded-lg',
                'sidebar_style'                 => 'normal',
                'sidebar_corner'                => 'rounded',
                'sidebar_item_inactive_color'   => '#818CF8',
                'sidebar_item_active_color'     => '#6366F1',
                'sidebar_item_inactive_dark_color' => '#A5B4FC',
                'sidebar_item_active_dark_color'   => '#818CF8',
                'sidebar_subitem_inactive_color'   => '#818CF8',
                'sidebar_subitem_active_color'     => '#6366F1',
                'sidebar_subitem_inactive_dark_color' => '#A5B4FC',
                'sidebar_subitem_active_dark_color'   => '#818CF8',
                'auth_background_type'          => 'themed_gradient',
                'auth_themed_color'             => '#EEF2FF',
                'auth_themed_dark_color'        => '#0F172A',
                'auth_themed_gradient'          => json_encode(['#EEF2FF', '#DBEAFE']),
                'auth_themed_dark_gradient'     => json_encode(['#0F172A', '#312E81']),
                'auth_themed_deg'               => 120,
                'auth_themed_dark_deg'          => 120,
                'auth_background_opacity'       => '0.8',
                'auth_dark_background_opacity'  => '0.8',
                'site_background'               => 'themed',
                'site_themed_color'             => '#EEF2FF',
                'site_themed_dark_color'        => '#0F172A',
                'site_gradient'                 => json_encode(['#F8FAFC', '#EEF2FF']),
                'site_dark_gradient'            => json_encode(['#0F172A', '#020617']),
                'dash_background'               => 'themed',
                'dash_themed_color'             => '#EEF2FF',
                'dash_themed_dark_color'        => '#0F172A',
                'chart_colors'                  => json_encode(['#6366F1','#3B82F6','#10B981','#F97316','#F43F5E','#8B5CF6']),
                'app_platform'                  => 'both',
                'light_logo'                    => null,
                'dark_logo'                     => null,
                'is_active'                     => true,
                'active_for'                    => json_encode(['app']),
                'is_deleted'                    => false,
            ];
        }

        DB::table('theme_settings')->insert($row);
    }
}
