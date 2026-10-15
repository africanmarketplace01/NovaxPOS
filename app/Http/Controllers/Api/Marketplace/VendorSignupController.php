<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\landlord\Package;
use App\Models\landlord\Tenant;
use App\Traits\TenantInfo;
use Illuminate\Http\Request;

/**
 * Vendor self-registration from the mobile app (central / landlord domain).
 * Mirrors the web signup (PublicController::registerSubmit) for the trial
 * paths that don't require an online payment gateway.
 */
class VendorSignupController extends Controller
{
    use TenantInfo;

    /** Public plans the vendor can sign up for, with online-store support flag. */
    public function packages()
    {
        $packages = Package::public()->get()->map(function ($p) {
            $features = is_array($p->features) ? $p->features : (json_decode($p->features, true) ?: []);
            return [
                'id'               => $p->id,
                'name'             => $p->name,
                'description'      => $p->description,
                'price_monthly'    => $p->price_monthly,
                'price_yearly'     => $p->price_yearly,
                'price_onetime'    => $p->price_onetime,
                'is_free_trial'    => (bool) $p->is_free_trial,
                'allow_paid_trial' => (bool) $p->allow_paid_trial,
                'trial_days'       => $p->trial_days ?? 14,
                'badge'            => $p->badge,
                'online_store'     => in_array('ecommerce', $features),
                'features'         => $features,
                'marketplace_seller' => false,
            ];
        });

        // Free commission-only option: register with subscription_type=marketplace.
        if ($seller = Package::marketplaceSeller()) {
            [$percent, $flat] = \App\Services\Marketplace\FeeSplit::sellerRate('');
            $packages->push([
                'id'                 => $seller->id,
                'name'               => $seller->name,
                'description'        => $seller->description,
                'price_monthly'      => 0,
                'price_yearly'       => 0,
                'price_onetime'      => null,
                'is_free_trial'      => false,
                'allow_paid_trial'   => false,
                'trial_days'         => 0,
                'badge'              => 'Free',
                'online_store'       => true,
                'features'           => $seller->features,
                'marketplace_seller' => true,
                'commission_percent' => $percent,
                'commission_flat'    => $flat,
            ]);
        }

        return response()->json(['data' => $packages]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'tenant'            => 'required|alpha_num|min:3|max:30|unique:saleprosaas_landlord.tenants,id',
            'name'              => 'required|string|max:100',
            'email'             => 'required|email',
            'company_name'      => 'nullable|string|max:150',
            'phone_number'      => 'nullable|string|max:30',
            'password'          => 'required|min:6',
            'package_id'        => 'required_unless:subscription_type,marketplace|nullable|exists:saleprosaas_landlord.packages,id',
            'subscription_type' => 'required|in:free_trial,paid_trial,marketplace',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Marketplace-only sellers always go on the free seller plan, whatever package_id says.
        $isSeller = $data['subscription_type'] === Tenant::MARKETPLACE_SUBSCRIPTION;
        if ($isSeller) {
            $package = Package::marketplaceSeller();
            if (!$package) {
                return response()->json(['message' => 'Marketplace selling is not available yet.'], 422);
            }
            $data['package_id'] = $package->id;
            $request->merge(['package_id' => $package->id]);
        } else {
            $package = Package::findOrFail($data['package_id']);
        }
        if ($data['subscription_type'] === 'free_trial' && !$package->is_free_trial) {
            return response()->json(['message' => 'This plan does not offer a free trial. Please sign up on the web to pay.'], 422);
        }
        if ($data['subscription_type'] === 'paid_trial' && !$package->allow_paid_trial) {
            return response()->json(['message' => 'This plan does not support a trial period.'], 422);
        }

        // Upload logo (threaded into createTenant via logo_filename)
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $file = $request->file('logo');
            $logo = date('YmdHis') . '_' . $data['tenant'] . '.' . $file->getClientOriginalExtension();
            $file->move(storage_path('app/landlord-logos'), $logo);
            $request->merge(['logo_filename' => $logo]);
        }

        // createTenant() always returns a RedirectResponse (web flow), so we use
        // the actual tenant record as the source of truth for success/failure.
        // A late-stage exception (after the tenant DB is migrated/seeded) must NOT
        // fail the whole signup — the backfill below completes the profile.
        $result = null; $createError = null;
        try {
            $result = $this->createTenant($request);
        } catch (\Throwable $e) {
            $createError = $e;
            \Illuminate\Support\Facades\Log::error('App vendor signup createTenant error for ' . $data['tenant'] . ': ' . $e->getMessage());
        }

        try { if (function_exists('tenancy') && tenancy()->initialized) tenancy()->end(); } catch (\Throwable $e) {}

        $created = Tenant::find($data['tenant']);
        if (!$created) {
            $msg = 'Registration failed. Please try again or contact support.';
            if ($createError) {
                $msg = 'Registration failed: ' . $createError->getMessage();
            } elseif ($result instanceof \Illuminate\Http\RedirectResponse && $result->getSession()) {
                $msg = $result->getSession()->get('not_permitted') ?: $msg;
            }
            return response()->json(['message' => $msg], 422);
        }

        $root      = env('CENTRAL_DOMAIN', 'novasuitespos.com');
        $features  = is_array($package->features) ? $package->features : (json_decode($package->features, true) ?: []);
        $hasStore  = in_array('ecommerce', $features);

        // ── Defensive profile write ──────────────────────────────────────────
        // createTenant()'s final profile update can be lost if tenancy context
        // leaked or a late step threw. Explicitly persist the vendor's profile to
        // the landlord tenants row from validated data (tenancy already ended above).
        try {
            $companyName = $data['company_name'] ?? $data['name'];

            // Expiry is derived from the plan/subscription — an inactive or expired
            // tenant is redirected away from its subdomain (the 302 seen at login).
            //  - free_trial / paid_trial : registration date + the plan's trial days
            //  - monthly / yearly        : 30 / 365 days
            //  - one_time (lifetime)     : NULL — never expires, never blocked
            $sub = $data['subscription_type'];
            $expiry = null; // lifetime by default
            if ($sub === 'free_trial' || $sub === 'paid_trial') {
                $days = (int) ($package->trial_days ?? 14);
                $expiry = now()->addDays($days > 0 ? $days : 14)->toDateString();
            } elseif ($sub === 'monthly') {
                $expiry = now()->addDays(30)->toDateString();
            } elseif ($sub === 'yearly') {
                $expiry = now()->addDays(365)->toDateString();
            } // one_time => $expiry stays null (lifetime)

            $created->update(array_filter([
                'company_name'      => $companyName,
                'email'             => $data['email'],
                'phone_number'      => $data['phone_number'] ?? null,
                'logo'              => $request->logo_filename ?? null,
                'package_id'        => $data['package_id'],
                'subscription_type' => $data['subscription_type'],
                'ecommerce_enabled' => $hasStore ? 1 : 0,
                'status'            => 1,          // active
                'expiry_date'       => $expiry,    // null (stripped) for lifetime plans
            ], fn ($v) => $v !== null && $v !== ''));

            // Backfill the tenant's own general_settings + guarantee an owner login.
            $logo = $request->logo_filename ?? null;
            $created->run(function () use ($companyName, $data, $logo) {
                try {
                    \Illuminate\Support\Facades\DB::table('general_settings')->update(array_filter([
                        'site_title' => $companyName,
                        'site_logo'  => $logo,
                        'email'      => $data['email'],
                    ], fn ($v) => !empty($v)));
                } catch (\Throwable $e) {}

                // Ensure the owner/admin account exists so the vendor can sign in,
                // even if the seeder didn't create it.
                try {
                    $exists = \Illuminate\Support\Facades\DB::table('users')
                        ->where('email', $data['email'])->orWhere('name', $data['name'])->exists();
                    if (!$exists) {
                        \Illuminate\Support\Facades\DB::table('users')->insert([
                            'name'       => $data['name'],
                            'email'      => $data['email'],
                            'password'   => bcrypt($data['password']),
                            'phone'      => $data['phone_number'] ?? null,
                            'company_name' => $companyName,
                            'role_id'    => 1,     // owner / super admin
                            'is_active'  => 1,
                            'is_deleted' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Vendor signup owner-user backfill failed: ' . $e->getMessage());
                }
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Vendor signup profile backfill failed for ' . $data['tenant'] . ': ' . $e->getMessage());
        }

        // Seller extras: never expires, storefront on, listing switched on.
        if ($isSeller) {
            \App\Http\Controllers\landlord\SellerSignupController::finishSellerSetup($created->fresh());
            $hasStore = true;
        }

        return response()->json([
            'ok'           => true,
            'tenant'       => $data['tenant'],
            'store_url'    => 'https://' . $data['tenant'] . '.' . $root,
            'online_store' => $hasStore,
            'marketplace_seller' => $isSeller,
            'message'      => 'Your store is ready! Sign in with your store name, email and password.',
        ], 201);
    }
}
