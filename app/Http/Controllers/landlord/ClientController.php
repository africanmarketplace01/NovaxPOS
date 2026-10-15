<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Tenant;
use App\Models\landlord\Package;
use App\Models\landlord\TenantPayment;
use App\Models\landlord\TenantSupport;
use App\Models\landlord\SaasSettings;
use App\Mail\TenantCreate;
use App\Traits\TenantInfo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ClientController extends Controller
{
    use TenantInfo;

    public function index(Request $request)
    {
        $query = Tenant::with('package');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                  ->orWhere('company_name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->package_id) {
            $query->where('package_id', $request->package_id);
        }

        $tenants  = $query->latest()->paginate(20)->withQueryString();
        $packages = Package::active()->get();

        return view('superadmin.clients.index', compact('tenants', 'packages'));
    }

    public function show(string $id)
    {
        TenantSupport::syncExpiredStatuses(); // keep statuses fresh
        $tenant   = Tenant::with(['package', 'payments', 'supports' => fn($q) => $q->orderBy('ends_at', 'desc')])->findOrFail($id);
        $packages = Package::active()->get();
        return view('superadmin.clients.show', compact('tenant', 'packages'));
    }

    public function create()
    {
        $packages = Package::active()->get();
        return view('superadmin.clients.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant'       => 'required|alpha_num|min:3|max:30|unique:saleprosaas_landlord.tenants,id',
            'name'         => 'required|string|max:100',
            'email'        => 'required|email',
            'password'     => 'required|min:6',
            'phone_number' => 'nullable|string|max:30',
            'company_name' => 'nullable|string|max:100',
            'package_id'   => 'required|exists:saleprosaas_landlord.packages,id',
            'subscription_type' => 'required|in:free_trial,monthly,yearly',
        ]);

        $request->merge(['created_by_admin' => true]);

        $result = $this->createTenant($request);

        if ($result) return $result;

        return redirect()->route('superadmin.clients.index')
            ->with('message', 'Client created successfully.');
    }

    public function edit(string $id)
    {
        $tenant   = Tenant::findOrFail($id);
        $packages = Package::active()->get();
        return view('superadmin.clients.edit', compact('tenant', 'packages'));
    }

    public function update(Request $request, string $id)
    {
        $tenant = Tenant::findOrFail($id);

        // Capture BEFORE update — getOriginal() returns new value after update()
        $oldPackageId = $tenant->package_id;

        $validated = $request->validate([
            'company_name'  => 'nullable|string|max:100',
            'email'         => 'nullable|email',
            'phone_number'  => 'nullable|string|max:30',
            'package_id'    => 'nullable|exists:saleprosaas_landlord.packages,id',
            'status'        => 'nullable|boolean',
            'custom_domain' => 'nullable|string|max:253|regex:/^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z]{2,})+$/',
            'logo'          => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4048',
        ]);
        unset($validated['logo']); // handled separately below — never nullify existing

        // Handle logo upload separately (never overwrite with null)
        if ($request->hasFile('logo')) {
            $dir = storage_path('app/landlord-logos');
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            if ($tenant->logo && file_exists($dir . '/' . $tenant->logo)) {
                @unlink($dir . '/' . $tenant->logo);
            }
            $filename = date('YmdHis') . '_tenant_' . $tenant->id . '.' . $request->file('logo')->getClientOriginalExtension();
            $request->file('logo')->move($dir, $filename);
            $validated['logo'] = $filename;
        }

        // Merge custom_domain into tenant's data JSON column
        $customDomain = $request->input('custom_domain', '');
        $data = is_array($tenant->data) ? $tenant->data : json_decode($tenant->data ?? '{}', true);
        if ($customDomain) {
            $data['custom_domain'] = strtolower(trim($customDomain));
        } else {
            unset($data['custom_domain']);
        }
        $validated['data'] = $data;

        unset($validated['custom_domain']); // not a direct column
        $tenant->update($validated);

        // Re-apply plan permissions if package changed OR force_reapply flag sent
        $packageChanged = $request->package_id && $request->package_id != $oldPackageId;
        $forceReapply   = $request->boolean('force_reapply_permissions');

        if ($packageChanged || $forceReapply) {
            $pkgId   = $request->package_id ?? $oldPackageId;
            $package = Package::findOrFail($pkgId);
            $this->changePermission(
                $tenant,
                json_encode($package->features),
                $package->id,
                $tenant->expiry_date,        // use current saved expiry
                $tenant->subscription_type   // use current saved subscription type
            );
        }

        return back()->with('message', 'Client updated successfully.');
    }

    public function toggleStatus(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['status' => !$tenant->status]);
        $label = $tenant->status ? 'activated' : 'suspended';
        return back()->with('message', "Tenant {$label} successfully.");
    }

    public function extendExpiry(Request $request, string $id)
    {
        $request->validate(['days' => 'required|integer|min:1|max:3650']);
        $tenant  = Tenant::findOrFail($id);
        $current = $tenant->expiry_date ?? now();
        $tenant->update(['expiry_date' => $current->addDays($request->days)]);
        return back()->with('message', "Expiry extended by {$request->days} days.");
    }

    public function setSubscription(Request $request, string $id)
    {
        $request->validate([
            'subscription_type' => 'required|in:free_trial,monthly,yearly,one_time',
            'expiry_date'       => 'nullable|date',
        ]);

        $tenant = Tenant::findOrFail($id);
        $update = ['subscription_type' => $request->subscription_type];

        if ($request->expiry_date) {
            $update['expiry_date'] = $request->expiry_date;
        }

        $tenant->update($update);
        return back()->with('message', 'Subscription updated successfully.');
    }

    public function impersonate(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->run(function () {
            $adminUser = \App\Models\User::where('role_id', 1)->first();
            if ($adminUser) Auth::login($adminUser);
        });

        return redirect('http://' . $tenant->id . '.' . env('CENTRAL_DOMAIN'));
    }

    public function toggleEcommerce(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update(['ecommerce_enabled' => !$tenant->ecommerce_enabled]);
        $label = $tenant->ecommerce_enabled ? 'enabled' : 'disabled';
        return back()->with('message', "E-commerce store {$label} for {$tenant->company_name}.");
    }

    /* ═══════════════════════════════════════════════════════════════
     |  SUPPORT PLAN MANAGEMENT
     ╚══════════════════════════════════════════════════════════════ */

    public function setSupportPlan(Request $request, string $id)
    {
        $request->validate([
            'starts_at'   => 'required|date',
            'ends_at'     => 'required|date|after:starts_at',
            'amount'      => 'nullable|numeric|min:0',
            'currency'    => 'nullable|string|max:10',
            'payment_ref' => 'nullable|string|max:255',
            'status'      => 'nullable|in:pending,active,expired,cancelled',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $tenant = Tenant::findOrFail($id);

        TenantSupport::create([
            'tenant_id'   => $tenant->id,
            'starts_at'   => $request->starts_at,
            'ends_at'     => $request->ends_at,
            'amount'      => $request->amount ?? 0,
            'currency'    => strtoupper($request->currency ?? 'USD'),
            'payment_ref' => $request->payment_ref,
            'status'      => $request->status ?? 'active',
            'notes'       => $request->notes,
        ]);

        return back()->with('message', 'Support plan added successfully.');
    }

    public function updateSupportPlan(Request $request, string $id, int $supportId)
    {
        $request->validate([
            'status' => 'required|in:pending,active,expired,cancelled',
        ]);

        $support = TenantSupport::where('tenant_id', $id)->findOrFail($supportId);
        $support->update(['status' => $request->status]);

        return back()->with('message', 'Support plan status updated.');
    }

    public function deleteSupportPlan(string $id, int $supportId)
    {
        $support = TenantSupport::where('tenant_id', $id)->findOrFail($supportId);
        $support->delete();
        return back()->with('message', 'Support plan removed.');
    }

    public function sendWelcomeEmail(Request $request, string $id)
    {
        $tenant = Tenant::with('payments')->findOrFail($id);

        // SMTP is stored in SaasSettings (saas_settings key-value table), not MailSetting.
        $smtpHost = SaasSettings::get('smtp_host');
        if (! $smtpHost) {
            return back()->with('error', 'Mail settings are not configured. Please configure SMTP first.');
        }

        try {
            // Reconfigure the mailer from SaasSettings (mirrors SettingsController::reconfigureMailer)
            $encryption = (SaasSettings::get('smtp_encryption', 'tls') === 'none')
                ? null
                : SaasSettings::get('smtp_encryption', 'tls');

            config([
                'mail.default'                 => 'smtp',
                'mail.mailers.smtp.transport'  => 'smtp',
                'mail.mailers.smtp.host'       => $smtpHost,
                'mail.mailers.smtp.port'       => (int) (SaasSettings::get('smtp_port', 587)),
                'mail.mailers.smtp.username'   => SaasSettings::get('smtp_username'),
                'mail.mailers.smtp.password'   => SaasSettings::get('smtp_password'),
                'mail.mailers.smtp.encryption' => $encryption,
                'mail.mailers.smtp.timeout'    => null,
                'mail.from.address'            => SaasSettings::get('mail_from_address', config('mail.from.address')),
                'mail.from.name'               => SaasSettings::get('mail_from_name',    config('mail.from.name')),
            ]);

            try { app('mail.manager')->forgetMailers(); } catch (\Throwable $e) {}

            $siteName  = SaasSettings::get('site_title', config('app.name', 'NovaxPOS'));
            $siteEmail = SaasSettings::get('mail_from_address', config('mail.from.address', ''));
            $loginUrl  = 'http://' . $tenant->id . '.' . env('CENTRAL_DOMAIN') . '/login';
            $forgotUrl = $loginUrl . '?action=forgot';   // hint to open forgot-password

            $mail_data = [
                'email'                   => $tenant->email,
                'name'                    => $tenant->company_name ?? $tenant->id,
                'company_name'            => $tenant->company_name ?? $tenant->id,
                'subdomain'               => $tenant->id,
                'password'                => null,          // not available on resend
                'password_reset_url'      => $forgotUrl,    // shown instead of password
                'superadmin_company_name' => $siteName,
                'superadmin_email'        => $siteEmail,
                'login_url'               => $loginUrl,
                'subscription_type'       => $tenant->subscription_type ?? '',
                'expiry_date'             => $tenant->expiry_date
                                                ? \Carbon\Carbon::parse($tenant->expiry_date)->format('d M Y')
                                                : '',
            ];

            $mailable = new TenantCreate($mail_data);

            // ── Attach invoice PDF if requested ────────────────────────────────
            if ($request->boolean('attach_invoice')) {
                $payment = $tenant->payments()->where('status', 'completed')->latest()->first()
                        ?? $tenant->payments()->latest()->first();

                if ($payment) {
                    $invoiceData = [
                        'tenant'       => $tenant,
                        'payment'      => $payment,
                        'site_name'    => $siteName,
                        'site_email'   => $siteEmail,
                        'login_url'    => $loginUrl,
                    ];

                    $pdf = Pdf::loadView('landlord.mail.payment_receipt_pdf', $invoiceData)
                              ->setPaper('a4', 'portrait');

                    $filename = 'Invoice-' . strtoupper($tenant->id) . '-' . $payment->id . '.pdf';
                    $pdfContent = $pdf->output();

                    $mailable->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
                }
            }

            Mail::to($tenant->email)->send($mailable);

            return back()->with('message', 'Welcome email sent successfully to ' . $tenant->email . '.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[sendWelcomeEmail] Failed for ' . $tenant->id . ': ' . $e->getMessage());
            return back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        $tenant = Tenant::findOrFail($id);

        // Remove subdomain if on cPanel/Plesk
        try { $this->deleteSubdomain($tenant); } catch (\Throwable $e) {}

        $tenant->delete();

        return redirect()->route('superadmin.clients.index')
            ->with('message', 'Client deleted successfully.');
    }
}
