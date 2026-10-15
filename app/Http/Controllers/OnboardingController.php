<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class OnboardingController extends Controller
{
    /**
     * Check if the current tenant has completed onboarding.
     * Called by a middleware or from HomeController::dashboard().
     */
    public static function needsOnboarding(): bool
    {
        if (!config('database.connections.saleprosaas_landlord')) {
            return false; // Not a SaaS install
        }

        try {
            $gs = DB::table('general_settings')->latest()->first();
            return $gs && isset($gs->onboarding_completed) && !$gs->onboarding_completed;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Show the onboarding wizard.
     */
    public function show()
    {
        // If already completed, redirect to dashboard
        if (!self::needsOnboarding()) {
            return redirect()->route('dashboard');
        }

        $currencies = [
            'USD' => 'USD — US Dollar',
            'EUR' => 'EUR — Euro',
            'GBP' => 'GBP — British Pound',
            'CAD' => 'CAD — Canadian Dollar',
            'AUD' => 'AUD — Australian Dollar',
            'NGN' => 'NGN — Nigerian Naira',
            'GHS' => 'GHS — Ghanaian Cedi',
            'KES' => 'KES — Kenyan Shilling',
            'ZAR' => 'ZAR — South African Rand',
        ];

        $timezones = \DateTimeZone::listIdentifiers();

        return view('onboarding.wizard', compact('currencies', 'timezones'));
    }

    /**
     * Process each wizard step via AJAX.
     */
    public function step(Request $request)
    {
        $step = (int) $request->input('step');

        switch ($step) {
            case 1: return $this->stepStore($request);
            case 2: return $this->stepCurrency($request);
            case 3: return $this->stepAdmin($request);
            case 4: return $this->stepComplete($request);
            default: return response()->json(['success' => false, 'message' => 'Invalid step.'], 422);
        }
    }

    /* ── Step 1: Store Details ──────────────────────────────────────────── */
    private function stepStore(Request $request)
    {
        $request->validate([
            'site_title'    => 'required|string|max:100',
            'business_type' => 'nullable|string|max:60',
            'timezone'      => 'nullable|timezone',
        ]);

        DB::table('general_settings')->update([
            'site_title' => $request->site_title,
        ]);

        // Store business_type and timezone in general_settings if columns exist
        if (DB::getSchemaBuilder()->hasColumn('general_settings', 'timezone')) {
            DB::table('general_settings')->update(['timezone' => $request->timezone]);
        }

        return response()->json(['success' => true]);
    }

    /* ── Step 2: Currency & Regional ───────────────────────────────────── */
    private function stepCurrency(Request $request)
    {
        $request->validate([
            'currency' => 'required|string|max:10',
        ]);

        // general_settings.currency stores the currencies table ID, not the code.
        // Look up the currency row by code; fall back to first active if not found.
        $currencyRow = DB::table('currencies')
            ->where('code', $request->currency)
            ->first();

        if (!$currencyRow) {
            $currencyRow = DB::table('currencies')->where('is_active', true)->first();
        }

        if ($currencyRow && DB::getSchemaBuilder()->hasColumn('general_settings', 'currency')) {
            DB::table('general_settings')->update(['currency' => $currencyRow->id]);
        }

        return response()->json(['success' => true]);
    }

    /* ── Step 3: Admin / First User ─────────────────────────────────────── */
    private function stepAdmin(Request $request)
    {
        // This step is informational — admin user is already set from registration
        // Optionally update admin display name
        $request->validate([
            'admin_name' => 'nullable|string|max:100',
        ]);

        if ($request->admin_name) {
            $userId = auth()->id();
            if ($userId) {
                DB::table('users')->where('id', $userId)->update(['name' => $request->admin_name]);
            }
        }

        return response()->json(['success' => true]);
    }

    /* ── Step 4: Complete ──────────────────────────────────────────────── */
    private function stepComplete(Request $request)
    {
        try {
            if (DB::getSchemaBuilder()->hasColumn('general_settings', 'onboarding_completed')) {
                DB::table('general_settings')->update(['onboarding_completed' => true]);
            }
        } catch (\Throwable $e) {
            // Don't block the redirect if marking complete fails
        }

        return response()->json(['success' => true, 'redirect' => route('dashboard')]);
    }

    /**
     * Skip onboarding entirely.
     */
    public function skip()
    {
        DB::table('general_settings')->update(['onboarding_completed' => true]);
        return redirect()->route('dashboard')->with('message', 'Onboarding skipped. You can configure settings anytime.');
    }
}
