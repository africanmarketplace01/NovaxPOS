<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Package;
use App\Models\landlord\Tenant;
use App\Services\Marketplace\FeeSplit;
use App\Traits\TenantInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * "Sell on the marketplace" — free signup for marketplace-only sellers.
 *
 * No package price and no expiry: the store is created on the hidden
 * Marketplace Seller plan (Products + Online store only) and the seller pays
 * a commission on each marketplace order instead (see FeeSplit / FeeHolds).
 * Upgrading to a full POS plan later keeps the same store and data.
 */
class SellerSignupController extends Controller
{
    use TenantInfo;

    public function show()
    {
        $package = Package::marketplaceSeller();

        return view('landlord.sell', [
            'package' => $package,
            'commission' => $this->commissionLabel(),
        ]);
    }

    public function store(Request $request)
    {
        $package = Package::marketplaceSeller();
        if (! $package) {
            return back()->withInput()->with('error', 'Marketplace selling is not available yet. Please try again later.');
        }

        $data = $request->validate([
            'tenant'       => 'required|alpha_num|min:3|max:30|unique:saleprosaas_landlord.tenants,id',
            'name'         => 'required|string|max:100',
            'email'        => 'required|email',
            'company_name' => 'required|string|max:150',
            'phone_number' => 'required|string|max:30',
            'password'     => 'required|min:6|confirmed',
            'logo'         => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'accept_terms' => 'accepted',
        ], [
            'accept_terms.accepted' => 'Please confirm you accept the per-order commission.',
        ]);
        $data['tenant'] = strtolower($data['tenant']);

        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $file = $request->file('logo');
            $logo = date('YmdHis') . '_' . $data['tenant'] . '.' . $file->getClientOriginalExtension();
            $file->move(storage_path('app/landlord-logos'), $logo);
            $request->merge(['logo_filename' => $logo]);
        }

        $request->merge([
            'tenant'            => $data['tenant'],
            'package_id'        => $package->id,
            'subscription_type' => Tenant::MARKETPLACE_SUBSCRIPTION,
        ]);

        // createTenant() answers with a redirect on success *and* failure, so
        // the tenant row is the only reliable success signal.
        $result = null;
        try {
            $result = $this->createTenant($request);
        } catch (\Throwable $e) {
            Log::error('Marketplace seller signup failed for ' . $data['tenant'] . ': ' . $e->getMessage());
        }
        try {
            if (function_exists('tenancy') && tenancy()->initialized) {
                tenancy()->end();
            }
        } catch (\Throwable $e) {
        }

        $tenant = Tenant::find($data['tenant']);
        if (! $tenant) {
            $message = 'We could not create your store. Please try again or contact support.';
            if ($result instanceof \Illuminate\Http\RedirectResponse && $result->getSession()) {
                $message = $result->getSession()->get('not_permitted') ?: $message;
            }

            return back()->withInput($request->except('password', 'password_confirmation'))->with('error', $message);
        }

        self::finishSellerSetup($tenant);

        // Mark the success page as a seller signup so it talks about a store, not a POS.
        session()->flash('registration_success', array_merge(
            session('registration_success', []) ?: [],
            [
                'login_url' => 'http://' . $tenant->id . '.' . env('CENTRAL_DOMAIN') . '/login',
                'email' => $data['email'],
                'seller' => true,
            ]
        ));

        return redirect()->route('registration.success');
    }

    /**
     * Seller-specific setup after createTenant(): never expires, storefront
     * on, and marketplace listing switched on inside the store so the seller
     * can publish products straight away. Shared with the vendor app signup.
     */
    public static function finishSellerSetup(Tenant $tenant): void
    {
        $tenant->update([
            'subscription_type' => Tenant::MARKETPLACE_SUBSCRIPTION,
            'expiry_date'       => null,
            'ecommerce_enabled' => true,
            'status'            => true,
        ]);

        try {
            $tenant->run(function () {
                // Tenant-local copy of the expiry; cleared so nothing reads a trial end date.
                DB::table('general_settings')->update(['expiry_date' => null, 'subscription_type' => Tenant::MARKETPLACE_SUBSCRIPTION]);

                if (\Illuminate\Support\Facades\Schema::hasTable('store_settings')) {
                    $store = \App\Models\StoreSettings::instance();
                    $store->marketplace_listed = true;
                    $store->save();
                }
            });
        } catch (\Throwable $e) {
            Log::warning('Marketplace seller setup incomplete for ' . $tenant->id . ': ' . $e->getMessage());
        }
    }

    /** "5% per order" / "5% + $0.25 per order" for the signup page. */
    private function commissionLabel(): string
    {
        [$percent, $flat] = FeeSplit::sellerRate('');
        $parts = [];
        if ($percent > 0) {
            $parts[] = rtrim(rtrim(number_format($percent, 2), '0'), '.') . '%';
        }
        if ($flat > 0) {
            $parts[] = '$' . number_format($flat, 2);
        }

        return ($parts ? implode(' + ', $parts) : 'No commission') . ' per order';
    }
}
