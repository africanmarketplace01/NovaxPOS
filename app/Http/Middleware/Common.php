<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Auth;
use App\Models\Language;
use Illuminate\Support\Facades\Cache;

class Common
{
    use \App\Traits\TenantInfo;

    public function handle(Request $request, Closure $next)
    {
        // ── Per-tenant cache prefix (SaaS isolation) ──────────────────────
        $cachePrefix = '';
        if (config('database.connections.saleprosaas_landlord')) {
            try {
                $tenantId = tenant()?->getTenantKey();
                if ($tenantId) {
                    $cachePrefix = 'tenant_' . $tenantId . '_';
                }
            } catch (\Throwable $e) {
                // Not inside a tenancy context
            }
        }

        // Get general setting — never cache a null result so that a fresh tenant
        // whose general_settings table is still empty will retry on every request
        // until a row exists (seeded during onboarding or first settings save).
        $general_setting = Cache::get($cachePrefix . 'general_setting');
        if (!$general_setting) {
            $general_setting = DB::table('general_settings')->latest()->first();
            if ($general_setting) {
                Cache::put($cachePrefix . 'general_setting', $general_setting, 60*60*24*365);
            }
        }

        // ✅ Timezone setup
        $timezone = $general_setting->timezone ?? config('app.timezone');
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        if(config('database.connections.saleprosaas_landlord')) {
            $subdomain = $this->getTenantId();

            $tenant = \App\Models\landlord\Tenant::find($subdomain);
            $tenantStatus = 1;
            if ($tenant) {
                $tenantStatus = isset($tenant->status) ? $tenant->status : 1;
            }

            if ($tenantStatus == 0) {
                auth()->logout();
                if (!request()->is('login')) {
                    return redirect('/login')->with('not_permitted', __('db.account_inactive'));
                }
            } elseif ($tenant && $tenant->expiry_date && $tenant->expiry_date->isPast()) {
                // The landlord tenant record is the source of truth for billing.
                // general_settings.expiry_date is a tenant-local legacy copy and can
                // be stale after a package is renewed from the landlord dashboard.
                if (!session('superadmin_impersonating')) {
                    auth()->logout();
                    return redirect('https://'.env('CENTRAL_DOMAIN').'/contact-for-renewal?id='.$subdomain);
                }
            }
            View::share('subdomain', $subdomain);
        }
        //setting language
        View::composer(['backend.layout.0main_rtl', 'backend.layout.main'], function ($view) use ($cachePrefix) {
            $languages = Cache::rememberForever($cachePrefix . 'languages_list', function () {
                if (Schema::hasTable('languages')) {
                    return Language::select('id', 'name')->orderBy('name')->get();
                }
                else {
                    return collect();
                }
            });

            $view->with('languages', $languages);
        });

        //setting theme
        if(isset($_COOKIE['theme'])) {
            View::share('theme', $_COOKIE['theme']);
        }
        else {
            View::share('theme', 'light');
        }
        $currency = Cache::remember($cachePrefix . 'currency', 60*60*24*365, function () {
            $settingData = DB::table('general_settings')->select('currency')->latest()->first();
            return \App\Models\Currency::find($settingData->currency);
        });

        $currency_list = Cache::remember($cachePrefix . 'currency_list', 60*60*24*365, function () {
            return \App\Models\Currency::where('is_active', true)->get();
        });

        View::share('general_setting', $general_setting);
        View::share('currency', $currency);
        View::share('currency_list', $currency_list);
        config(['staff_access' => $general_setting->staff_access, 'is_packing_slip' => $general_setting->is_packing_slip, 'date_format' => $general_setting->date_format, 'currency' => $currency->symbol ?? $currency->code, 'currency_position' => $general_setting->currency_position ?? 'prefix', 'decimal' => $general_setting->decimal ?? 2, 'is_zatca' => $general_setting->is_zatca, 'company_name' => $general_setting->company_name, 'vat_registration_number' => $general_setting->vat_registration_number, 'without_stock' => $general_setting->without_stock, 'addons' => $general_setting->modules]);

        $alert_product = DB::table('products')->where('is_active', true)->whereColumn('alert_quantity', '>', 'qty')->count();
        $dso_alert_product = DB::table('dso_alerts')->select('number_of_products')->whereDate('created_at', date("Y-m-d"))->first();
        if($dso_alert_product)
            $dso_alert_product_no = $dso_alert_product->number_of_products;
        else
            $dso_alert_product_no = 0;

        // Dynamic days from settings
        $days = $general_setting->expiry_alert_days ?? 0;
        // dd($days);

        $expire_alert_products = DB::table('product_batches')
            ->join('products', 'products.id', '=', 'product_batches.product_id')
            ->where('products.is_active', true)
            ->where('product_batches.qty', '>', 0)
            ->whereDate('product_batches.expired_date', '<=', now()->addDays((int) $days)->format('Y-m-d'))
            ->count();

        // View share (exact same style)
        View::share([
            'alert_product'          => $alert_product,
            'dso_alert_product_no'   => $dso_alert_product_no,
            'expire_alert_products'  => $expire_alert_products,
        ]);


        $role = Cache::remember($cachePrefix . 'user_role_' . Auth::id(), 60*60*24*365, function () {
            return DB::table('roles')->find(Auth::user()->role_id);
        });
        View::share('role', $role);
        $permission_list = Cache::remember($cachePrefix . 'permissions', 60*60*24*365, function () {
            return DB::table('permissions')->get();
        });
        View::share('permission_list', $permission_list);
        $role_has_permissions = Cache::remember($cachePrefix . 'role_has_permissions_' . Auth::user()->role_id, 60*60*24*365, function () {
            return DB::table('role_has_permissions')->where('role_id', Auth::user()->role_id)->get();
        });
        View::share('role_has_permissions', $role_has_permissions);

        $role_has_permissions_list = Cache::remember($cachePrefix . 'role_has_permissions_list' . Auth::user()->role_id, 60*60*24*365, function () {
            return DB::table('permissions')->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')->where('role_id', Auth::user()->role_id)->select('permissions.name')->get();
        });
        View::share('role_has_permissions_list', $role_has_permissions_list);

        $categories_list = Cache::remember($cachePrefix . 'category_list', 60*60*24*365, function () {
            return DB::table('categories')->where('is_active', true)->get();
        });
        View::share('categories_list', $categories_list);

        return $next($request);
    }
}
