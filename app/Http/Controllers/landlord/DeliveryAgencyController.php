<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\SaasSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class DeliveryAgencyController extends Controller
{
    // ── Registration form ─────────────────────────────────────────────────────

    public function registerForm()
    {
        $settings = SaasSettings::allCached();
        return view('landlord.delivery.register', compact('settings'));
    }

    // ── Submit registration ───────────────────────────────────────────────────

    public function register(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:120',
            'contact_name'    => 'required|string|max:100',
            'email'           => 'required|email|max:150|unique:saleprosaas_landlord.delivery_agencies,email',
            'phone'           => 'required|string|max:30',
            'address'         => 'nullable|string|max:300',
            'description'     => 'nullable|string|max:1000',
            'coverage_areas'  => 'required|string|max:1000',
            'base_rate'       => 'required|numeric|min:0|max:9999',
            'per_km_rate'     => 'nullable|numeric|min:0|max:999',
            'currency'        => 'nullable|string|max:10',
            'estimated_days_min' => 'required|integer|min:1|max:60',
            'estimated_days_max' => 'required|integer|min:1|max:60|gte:estimated_days_min',
            'password'        => 'required|string|min:8|confirmed',
            'logo'            => 'nullable|image|mimes:png,jpg,jpeg,webp|max:5120',
        ]);

        $data = $request->only([
            'name', 'contact_name', 'email', 'phone', 'address',
            'description', 'coverage_areas', 'base_rate', 'per_km_rate',
            'currency', 'estimated_days_min', 'estimated_days_max',
        ]);

        $data['slug']     = DeliveryAgency::generateSlug($request->name);
        $data['password'] = Hash::make($request->password);
        $data['status']   = 'pending';
        $data['base_rate'] = $request->base_rate ?? 0;
        $data['per_km_rate'] = $request->per_km_rate ?? 0;
        $data['currency']  = $request->currency ?? 'USD';

        // Logo upload
        if ($request->hasFile('logo')) {
            $logoDir = public_path('landlord/images/agencies');
            if (!is_dir($logoDir)) mkdir($logoDir, 0775, true);
            $filename = date('YmdHis') . '_agency.' . $request->file('logo')->getClientOriginalExtension();
            $request->file('logo')->move($logoDir, $filename);
            $data['logo'] = $filename;
        }

        $agency = DeliveryAgency::create($data);

        // Notify superadmin
        $this->notifySuperadmin($agency);

        return redirect()->route('delivery.register.success')
            ->with('agency_name', $agency->name);
    }

    public function registerSuccess()
    {
        return view('landlord.delivery.register-success');
    }

    // ── Superadmin notification ───────────────────────────────────────────────

    private function notifySuperadmin(DeliveryAgency $agency): void
    {
        try {
            $adminEmail = SaasSettings::get('support_email');
            if (!$adminEmail) return;

            Mail::raw(
                "A new delivery agency has registered and is awaiting approval.\n\n" .
                "Name: {$agency->name}\n" .
                "Contact: {$agency->contact_name}\n" .
                "Email: {$agency->email}\n" .
                "Phone: {$agency->phone}\n" .
                "Coverage: {$agency->coverage_areas}\n" .
                "Base Rate: {$agency->currency} {$agency->base_rate}\n\n" .
                "Log in to the superadmin panel to approve or reject.",
                fn($m) => $m->to($adminEmail)->subject('New Delivery Agency Registration — ' . $agency->name)
            );
        } catch (\Throwable $e) {
            Log::warning('Delivery agency notification failed: ' . $e->getMessage());
        }
    }
}
