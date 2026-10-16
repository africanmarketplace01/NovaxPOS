<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Package;
use App\Models\landlord\PlanPromotion;
use App\Models\landlord\SaasSettings;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\Tenant;
use App\Models\landlord\Testimonial;
use App\Traits\TenantInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    use TenantInfo;

    public function landing()
    {
        $packages = Package::public()->get();

        // Live tenants for the "Trusted by" section — active, with a company name
        $trustedTenants = Tenant::active()
            ->whereNotNull('company_name')
            ->where('company_name', '!=', '')
            ->orderBy('created_at')
            ->get(['id', 'company_name', 'logo', 'ecommerce_enabled']);

        // Testimonials from DB; fall back to empty collection if table doesn't exist yet
        try {
            $testimonials = Testimonial::active()->orderBy('sort_order')->get();
        } catch (\Throwable $e) {
            $testimonials = collect();
        }

        $promotion = PlanPromotion::current();

        return view('landlord.landing', compact('packages', 'trustedTenants', 'testimonials', 'promotion'));
    }

    public function pricing()
    {
        $packages = Package::public()->get();

        // Same live promotion as the landing page, so both show the same prices.
        $promotion = PlanPromotion::current();

        return view('landlord.pricing', compact('packages', 'promotion'));
    }

    public function contactForRenewal(Request $request)
    {
        $request->validate([
            'id' => ['nullable', 'alpha_dash', 'max:100'],
        ]);

        $tenant = $request->filled('id')
            ? Tenant::query()->find($request->string('id')->toString())
            : null;

        return view('landlord.contact-for-renewal', [
            'tenantId'     => $request->string('id')->toString(),
            'tenant'       => $tenant,
            'supportEmail' => SaasSettings::get('support_email') ?: config('mail.from.address'),
            'supportPhone' => SaasSettings::get('support_phone'),
            'siteName'     => SaasSettings::get('site_name', config('app.name', 'NovaxPOS')),
        ]);
    }

    public function register(Request $request)
    {
        $packages = Package::public()->get();
        $selected = $request->package_id ? Package::find($request->package_id) : $packages->first();
        $type     = $request->type ?? 'monthly';

        return view('landlord.register', compact('packages', 'selected', 'type'));
    }

    public function registerSubmit(Request $request)
    {
        $request->validate([
            'tenant'            => 'required|alpha_num|min:3|max:30|unique:saleprosaas_landlord.tenants,id',
            'name'              => 'required|string|max:100',
            'email'             => 'required|email',
            'company_name'      => 'nullable|string|max:150',
            'phone_number'      => 'nullable|string|max:30',
            'password'          => 'required|min:6|confirmed',
            'package_id'        => 'required|exists:saleprosaas_landlord.packages,id',
            'subscription_type' => 'required|in:free_trial,monthly,yearly,one_time,paid_trial',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        // Upload logo immediately so it can be threaded through all payment paths
        $logoFilename = null;
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $file = $request->file('logo');
            $ext  = $file->getClientOriginalExtension();
            $logoFilename = date('YmdHis') . '_' . $request->tenant . '.' . $ext;
            $file->move(storage_path('app/landlord-logos'), $logoFilename);
        }
        // Make filename available to createTenant() via request merge
        if ($logoFilename) {
            $request->merge(['logo_filename' => $logoFilename]);
        }

        $package = Package::findOrFail($request->package_id);

        // Free trial path — create tenant immediately (no payment)
        if ($request->subscription_type === 'free_trial') {
            if (!$package->is_free_trial) {
                return back()->with('error', 'This package does not have a free trial.');
            }
            $result = $this->createTenant($request);
            if ($result instanceof \Illuminate\Http\RedirectResponse) return $result;
            return redirect('http://' . $request->tenant . '.' . env('CENTRAL_DOMAIN'))
                ->with('success', 'Your account is ready. Enjoy your free trial!');
        }

        // Paid trial path — create tenant immediately (active during trial, pay later)
        if ($request->subscription_type === 'paid_trial') {
            if (!$package->allow_paid_trial) {
                return back()->with('error', 'This package does not support a paid trial period.');
            }
            $result = $this->createTenant($request);
            if ($result instanceof \Illuminate\Http\RedirectResponse) return $result;
            return redirect('http://' . $request->tenant . '.' . env('CENTRAL_DOMAIN'))
                ->with('success', 'Your ' . ($package->trial_days ?? 14) . '-day trial has started! You can pay to continue once it ends.');
        }

        // Paid plan (monthly, yearly, one_time) — store in session, redirect to checkout.
        // The payment controllers read this back from the session, never from the form,
        // and only the password hash is kept so the plaintext never leaves this request.
        session(['pending_checkout' => [
            'token'             => Str::random(40),
            'tenant'            => $request->tenant,
            'name'              => $request->name,
            'email'             => $request->email,
            'company_name'      => $request->company_name,
            'phone_number'      => $request->phone_number,
            'password_hash'     => Hash::make($request->password),
            'package_id'        => $request->package_id,
            'subscription_type' => $request->subscription_type,
            'logo_filename'     => $logoFilename,   // thread through to payment callbacks
        ]]);

        return redirect()->route('saas.checkout');
    }

    public function businesses(Request $request)
    {
        $query = Tenant::active()
            ->whereNotNull('company_name')
            ->where('company_name', '!=', '');

        if ($search = $request->search) {
            $query->where('company_name', 'like', "%{$search}%");
        }

        $businesses = $query->orderBy('company_name')
            ->paginate(24, ['id', 'company_name', 'logo', 'ecommerce_enabled'])
            ->withQueryString();

        $tenantIds = $businesses->getCollection()->pluck('id');
        $listingInfo = $tenantIds->isEmpty()
            ? collect()
            : MarketplaceListing::visible()
                ->whereIn('tenant_id', $tenantIds)
                ->selectRaw('tenant_id, MAX(tenant_store_name) as store_name, COUNT(*) as product_count')
                ->groupBy('tenant_id')
                ->get()
                ->keyBy('tenant_id');
        $categoryInfo = $tenantIds->isEmpty()
            ? collect()
            : MarketplaceListing::visible()
                ->whereIn('tenant_id', $tenantIds)
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select('tenant_id', 'category')
                ->distinct()
                ->get()
                ->groupBy('tenant_id')
                ->map(fn ($rows) => $rows->pluck('category')->values());

        $businesses->getCollection()->each(function ($tenant) use ($listingInfo, $categoryInfo) {
            $info = $listingInfo->get($tenant->id);
            $tenant->setAttribute('marketplace_store_name', $info?->store_name);
            $tenant->setAttribute('marketplace_product_count', (int) ($info?->product_count ?? 0));
            $tenant->setAttribute('marketplace_categories', $categoryInfo->get($tenant->id, collect()));
        });

        return view('landlord.businesses', compact('businesses'));
    }

    public function tutorials()
    {
        $tutorials = \App\Models\landlord\VideoTutorial::published()
            ->ordered()
            ->get()
            ->groupBy('category');

        return view('landlord.tutorials', compact('tutorials'));
    }

    public function registrationSuccess()
    {
        $data = session('registration_success');

        // Guard: if accessed directly without flash data, send back to register
        if (empty($data['login_url'])) {
            return redirect()->route('register');
        }

        return view('landlord.registration-success', [
            'loginUrl' => $data['login_url'],
            'email'    => $data['email'] ?? '',
            // Marketplace-only sellers get a store, not a POS (SellerSignupController).
            'isSeller' => !empty($data['seller']),
        ]);
    }
}
