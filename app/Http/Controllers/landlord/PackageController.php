<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Package;
use App\Traits\TenantInfo;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    use TenantInfo;

    public function index()
    {
        $packages = Package::orderBy('sort_order')->get();
        $features = $this->features();
        return view('superadmin.packages.index', compact('packages', 'features'));
    }

    public function create()
    {
        $features = $this->features();
        return view('superadmin.packages.create', compact('features'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string',
            'price_monthly'    => 'required|numeric|min:0',
            'price_yearly'     => 'required|numeric|min:0',
            'price_onetime'    => 'nullable|numeric|min:0',
            'features'         => 'nullable|array',
            'product_limit'    => 'nullable|integer|min:1',
            'user_limit'       => 'nullable|integer|min:1',
            'warehouse_limit'  => 'nullable|integer|min:1',
            'is_free_trial'    => 'boolean',
            'trial_days'       => 'integer|min:1|max:365',
            'allow_paid_trial' => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer|min:0',
            'badge'            => 'nullable|string|max:50',
            'show_on_public'   => 'boolean',
        ]);

        $validated['features']         = $request->input('features', []);
        $validated['is_free_trial']    = $request->boolean('is_free_trial');
        $validated['allow_paid_trial'] = $request->boolean('allow_paid_trial');
        $validated['is_active']        = $request->boolean('is_active', true);
        $validated['show_on_public']   = $request->boolean('show_on_public');

        Package::create($validated);

        return redirect()->route('superadmin.packages.index')
            ->with('message', 'Package created successfully.');
    }

    public function edit(Package $package)
    {
        $features = $this->features();
        return view('superadmin.packages.edit', compact('package', 'features'));
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'description'      => 'nullable|string',
            'price_monthly'    => 'required|numeric|min:0',
            'price_yearly'     => 'required|numeric|min:0',
            'price_onetime'    => 'nullable|numeric|min:0',
            'features'         => 'nullable|array',
            'product_limit'    => 'nullable|integer|min:1',
            'user_limit'       => 'nullable|integer|min:1',
            'warehouse_limit'  => 'nullable|integer|min:1',
            'is_free_trial'    => 'boolean',
            'trial_days'       => 'integer|min:1|max:365',
            'allow_paid_trial' => 'boolean',
            'is_active'        => 'boolean',
            'sort_order'       => 'integer|min:0',
            'badge'            => 'nullable|string|max:50',
            'show_on_public'   => 'boolean',
        ]);

        $validated['features']         = $request->input('features', []);
        $validated['is_free_trial']    = $request->boolean('is_free_trial');
        $validated['allow_paid_trial'] = $request->boolean('allow_paid_trial');
        $validated['is_active']        = $request->boolean('is_active', true);
        $validated['show_on_public']   = $request->boolean('show_on_public');

        $package->update($validated);

        return redirect()->route('superadmin.packages.index')
            ->with('message', 'Package updated successfully.');
    }

    public function destroy(Package $package)
    {
        if ($package->tenants()->count()) {
            return back()->with('not_permitted', 'Cannot delete a package with active tenants.');
        }
        $package->delete();
        return redirect()->route('superadmin.packages.index')
            ->with('message', 'Package deleted.');
    }

    /** API endpoint used by tenant login page to fetch package data */
    public function fetchData(Package $package)
    {
        return response()->json([
            'features'          => $package->features,
            'product_limit'     => $package->product_limit,
            'user_limit'        => $package->user_limit,
            'warehouse_limit'   => $package->warehouse_limit,
            'price_monthly'     => $package->price_monthly,
            'price_yearly'      => $package->price_yearly,
            'price_onetime'     => $package->price_onetime,
            'allow_paid_trial'  => $package->allow_paid_trial,
            'trial_days'        => $package->trial_days,
            'is_free_trial'     => $package->is_free_trial,
        ]);
    }
}
