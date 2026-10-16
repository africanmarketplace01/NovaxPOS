<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\SaasSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SaasSettings::allCached();
        return view('superadmin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'theme_primary'         => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_primary_light'   => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_accent'          => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_lime'            => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_dark'            => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_cyan'            => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'site_name'             => ['nullable', 'string', 'max:80'],
            'site_tagline'          => ['nullable', 'string', 'max:160'],
            'marketplace_name'      => ['nullable', 'string', 'max:80'],
            'support_email'         => ['nullable', 'email', 'max:120'],
            'support_phone'         => ['nullable', 'string', 'max:30'],
            'trial_days'            => ['nullable', 'integer', 'min:1', 'max:365'],
            'grace_days'            => ['nullable', 'integer', 'min:0', 'max:30'],
            'currency'              => ['nullable', 'string', 'max:10'],
            'yearly_savings_label'  => ['nullable', 'string', 'max:30'],
            'site_font'             => ['nullable', 'string', 'max:80'],
            'site_logo'             => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:1048576'],
            // Gateways
            'stripe_key'            => ['nullable', 'string', 'max:200'],
            'stripe_secret'         => ['nullable', 'string', 'max:200'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:200'],
            'paystack_public_key'   => ['nullable', 'string', 'max:200'],
            'paystack_secret_key'   => ['nullable', 'string', 'max:200'],
            // Bank Transfer
            'bank_transfer_enabled'      => ['nullable'],
            'bank_name'                  => ['nullable', 'string', 'max:100'],
            'bank_account_name'          => ['nullable', 'string', 'max:100'],
            'bank_account_number'        => ['nullable', 'string', 'max:50'],
            'bank_routing_number'        => ['nullable', 'string', 'max:50'],
            'bank_swift_code'            => ['nullable', 'string', 'max:20'],
            'bank_payment_instructions'  => ['nullable', 'string', 'max:1000'],
            // MTN Mobile Money
            'mtn_enabled'                => ['nullable'],
            'mtn_number'                 => ['nullable', 'string', 'max:30'],
            'mtn_account_name'           => ['nullable', 'string', 'max:100'],
            'mtn_instructions'           => ['nullable', 'string', 'max:1000'],
            // Orange Money
            'orange_enabled'             => ['nullable'],
            'orange_number'              => ['nullable', 'string', 'max:30'],
            'orange_account_name'        => ['nullable', 'string', 'max:100'],
            'orange_instructions'        => ['nullable', 'string', 'max:1000'],
            // SMTP
            'smtp_host'             => ['nullable', 'string', 'max:100'],
            'smtp_port'             => ['nullable', 'integer'],
            'smtp_username'         => ['nullable', 'string', 'max:100'],
            'smtp_password'         => ['nullable', 'string', 'max:200'],
            'smtp_encryption'       => ['nullable', 'in:tls,ssl,none'],
            'mail_from_name'        => ['nullable', 'string', 'max:80'],
            'mail_from_address'     => ['nullable', 'email', 'max:120'],
            // Notification toggles
            'notify_on_signup'      => ['nullable'],
            'notify_on_payment'     => ['nullable'],
            'notify_on_expiry'      => ['nullable'],
            'notify_on_expired'     => ['nullable'],
        ]);

        $allowed = [
            'theme_primary', 'theme_primary_light', 'theme_accent',
            'theme_lime', 'theme_dark', 'theme_cyan',
            'site_name', 'site_tagline', 'marketplace_name', 'support_email', 'support_phone',
            'trial_days', 'grace_days', 'currency', 'site_font', 'yearly_savings_label',
            // Gateways
            'stripe_key', 'stripe_secret', 'stripe_webhook_secret',
            'paystack_public_key', 'paystack_secret_key',
            // Bank Transfer
            'bank_name', 'bank_account_name', 'bank_account_number',
            'bank_routing_number', 'bank_swift_code', 'bank_payment_instructions',
            // MTN Mobile Money
            'mtn_number', 'mtn_account_name', 'mtn_instructions',
            // Orange Money
            'orange_number', 'orange_account_name', 'orange_instructions',
            // SMTP
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
            'smtp_encryption', 'mail_from_name', 'mail_from_address',
        ];

        $data = $request->only($allowed);

        // Notification checkboxes (unchecked = not present in request → store 0)
        foreach (['notify_on_signup','notify_on_payment','notify_on_expiry','notify_on_expired'] as $key) {
            $data[$key] = $request->boolean($key) ? '1' : '0';
        }

        // Manual payment method toggles
        $data['bank_transfer_enabled'] = $request->boolean('bank_transfer_enabled') ? '1' : '0';
        $data['mtn_enabled']           = $request->boolean('mtn_enabled') ? '1' : '0';
        $data['orange_enabled']        = $request->boolean('orange_enabled') ? '1' : '0';

        // ── Logo upload ──────────────────────────────────────────────────────
        if ($request->hasFile('site_logo')) {
            // Store in storage/app/landlord-logos/ — always writable on any OS.
            $logoDir = storage_path('app/landlord-logos');

            if (!is_dir($logoDir)) {
                mkdir($logoDir, 0775, true);
            }

            try {
                // Delete old logo from both storage and legacy public location
                $old = SaasSettings::get('site_logo');
                if ($old) {
                    @unlink($logoDir . '/' . $old);
                    @unlink(public_path('landlord/images/logo/' . $old));
                }

                $filename = date('YmdHis') . '_logo.' . $request->file('site_logo')->getClientOriginalExtension();
                $request->file('site_logo')->move($logoDir, $filename);
                $data['site_logo'] = $filename;

                // Best-effort copy to public/ for direct static serving via asset().
                // PHP copy() works on Windows where move_uploaded_file() may be restricted.
                $publicLogoDir = public_path('landlord/images/logo');
                if (!is_dir($publicLogoDir)) {
                    @mkdir($publicLogoDir, 0775, true);
                }
                @copy($logoDir . DIRECTORY_SEPARATOR . $filename, $publicLogoDir . DIRECTORY_SEPARATOR . $filename);
            } catch (\Throwable $e) {
                Log::error('Logo upload failed: ' . $e->getMessage());
                return back()->withInput()
                    ->with('not_permitted', 'Logo upload failed: ' . $e->getMessage());
            }
        }

        // Strip empty strings for secrets so we don't overwrite with blank
        foreach (['stripe_secret','stripe_webhook_secret','paystack_secret_key','smtp_password'] as $sk) {
            if (isset($data[$sk]) && $data[$sk] === '') {
                unset($data[$sk]);
            }
        }

        try {
            SaasSettings::bulkSet(array_filter($data, fn($v) => $v !== null));
        } catch (\Throwable $e) {
            Log::error('Settings save failed: ' . $e->getMessage());
            return back()->withInput()
                ->with('not_permitted', 'Failed to save settings: ' . $e->getMessage());
        }

        // Dynamically reconfigure the mailer from stored SMTP settings
        $this->reconfigureMailer();

        return redirect()->route('superadmin.settings.index')
            ->with('message', 'Settings saved successfully.');
    }

    /**
     * Remove the current site logo.
     */
    public function removeLogo()
    {
        $logo = SaasSettings::get('site_logo');
        if ($logo) {
            @unlink(storage_path('app/landlord-logos/' . $logo));
            @unlink(public_path('landlord/images/logo/' . $logo));
            SaasSettings::bulkSet(['site_logo' => null]);
        }
        return back()->with('message', 'Logo removed.');
    }

    /**
     * Serve the site logo from storage (public, no auth required).
     */
    public function serveLogo(string $filename)
    {
        // Sanitise — allow only safe filenames
        if (!preg_match('/^[\w\-]+\.(png|jpg|jpeg|svg|webp)$/i', $filename)) {
            abort(404);
        }

        // New location
        $path = storage_path('app/landlord-logos/' . $filename);
        if (!file_exists($path)) {
            // Legacy public location
            $path = public_path('landlord/images/logo/' . $filename);
        }
        if (!file_exists($path)) abort(404);

        return response()->file($path);
    }

    /**
     * Return theme CSS variables as JSON (used by JS live-preview).
     */
    public function themeJson()
    {
        $s = SaasSettings::allCached();
        return response()->json([
            '--lp-primary'       => $s['theme_primary']       ?? '#0066CC',
            '--lp-primary-light' => $s['theme_primary_light'] ?? '#00A3FF',
            '--lp-accent'        => $s['theme_accent']        ?? '#66FF33',
            '--lp-lime'          => $s['theme_lime']          ?? '#8DFF00',
            '--lp-dark'          => $s['theme_dark']          ?? '#003366',
            '--lp-cyan'          => $s['theme_cyan']          ?? '#00D4FF',
        ]);
    }

    /**
     * Send a test email to verify SMTP config.
     */
    public function testEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $this->reconfigureMailer();

        try {
            Mail::raw('This is a test email from ' . (SaasSettings::get('site_name') ?? 'NovaxPOS') . '. Your SMTP configuration is working correctly.', function ($m) use ($request) {
                $fromAddr = SaasSettings::get('mail_from_address') ?? config('mail.from.address');
                $fromName = SaasSettings::get('mail_from_name')    ?? config('mail.from.name');
                $m->to($request->email)
                  ->from($fromAddr, $fromName)
                  ->subject('Test Email — ' . (SaasSettings::get('site_name') ?? 'NovaxPOS'));
            });

            return response()->json(['success' => true, 'message' => 'Test email sent successfully.']);
        } catch (\Throwable $e) {
            Log::error('Test email failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Dynamically reconfigure Laravel's mailer from saas_settings SMTP values.
     * Called after saving settings and before sending test emails.
     */
    private function reconfigureMailer(): void
    {
        $s = SaasSettings::allCached();

        if (empty($s['smtp_host'])) return;

        $encryption = ($s['smtp_encryption'] ?? 'tls') === 'none'
            ? null
            : ($s['smtp_encryption'] ?? 'tls');

        config([
            'mail.default'                     => 'smtp',
            'mail.mailers.smtp.transport'      => 'smtp',
            'mail.mailers.smtp.host'           => $s['smtp_host'],
            'mail.mailers.smtp.port'           => (int) ($s['smtp_port'] ?? 587),
            'mail.mailers.smtp.username'       => $s['smtp_username']     ?? null,
            'mail.mailers.smtp.password'       => $s['smtp_password']     ?? null,
            'mail.mailers.smtp.encryption'     => $encryption,
            'mail.mailers.smtp.timeout'        => null,
            'mail.from.address'                => $s['mail_from_address'] ?? config('mail.from.address'),
            'mail.from.name'                   => $s['mail_from_name']    ?? config('mail.from.name'),
        ]);

        // Flush the cached mailer instance so the new config is picked up
        // on this request (MailManager caches resolved mailers by name).
        try {
            app('mail.manager')->forgetMailers();
        } catch (\Throwable $e) {
            // forgetMailers() was added in Laravel 9 — safe to ignore on older builds.
        }
    }
}
