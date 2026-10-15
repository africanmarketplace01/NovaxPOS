<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Mail\TenantContractMail;
use App\Models\landlord\SaasSettings;
use App\Models\landlord\Tenant;
use App\Models\landlord\TenantContract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\landlord\MailSetting;
use App\Traits\MailInfo;

class ContractController extends Controller
{
    use MailInfo;

    // ── List all contracts ─────────────────────────────────────────────────

    public function index()
    {
        $contracts = TenantContract::with('tenant')
            ->latest()
            ->paginate(25);

        return view('superadmin.contracts.index', compact('contracts'));
    }

    // ── Show create form ───────────────────────────────────────────────────

    public function create(Request $request)
    {
        $tenants = Tenant::orderBy('company_name')->get(['id', 'company_name', 'email', 'package_id', 'subscription_type']);
        $selected = $request->tenant_id ? Tenant::find($request->tenant_id) : null;

        // Last used company logo for reuse
        $lastLogo = TenantContract::whereNotNull('company_logo')->latest()->value('company_logo');

        // Default scope items matching the contract template (the 15 SoW items)
        $defaultScopeItems = [
            'Point of Sale (POS) System',
            'Product & Category Management',
            'Purchase & Sales Management',
            'Sale & Purchase Returns',
            'Expense & Income Tracking',
            'Stock Transfer between warehouses',
            'Quotation Management',
            'Product Delivery Module',
            'Stock Count & Adjustment',
            'Reports & Analytics Dashboard',
            'HRM (HR Management)',
            'Accounting Module',
            'WhatsApp Cloud API Integration',
            'Multi-warehouse Support',
            'User Roles & Permissions',
        ];

        $settings = SaasSettings::allCached();

        return view('superadmin.contracts.create', compact('tenants', 'selected', 'defaultScopeItems', 'settings', 'lastLogo'));
    }

    // ── Store contract + generate PDF ─────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'tenant_id'                    => 'required|exists:saleprosaas_landlord.tenants,id',
            'document_type'                => 'required|in:full_contract,sla_only,sow_only',
            'effective_date'               => 'required|date',
            'contract_amount'              => 'nullable|numeric|min:0',
            'deposit_percent'              => 'required|integer|min:0|max:100',
            'balance_percent'              => 'required|integer|min:0|max:100',
            'hosting_months'               => 'required|integer|min:1',
            'support_months'               => 'required|integer|min:1',
            'warranty_days'                => 'required|integer|min:1',
            'timeline_days'                => 'required|integer|min:1',
            'include_scope'                => 'boolean',
            'scope_items'                  => 'nullable|array',
            'scope_items.*'                => 'string|max:300',
            'notes'                        => 'nullable|string|max:2000',
            // Editable sections
            'purpose_text'                 => 'nullable|string|max:3000',
            'software_description'         => 'nullable|string|max:3000',
            'system_name'                  => 'nullable|string|max:100',
            'sla_uptime'                   => 'nullable|string|max:20',
            'sla_critical_hours'           => 'nullable|integer|min:1',
            'sla_bugfix_days'              => 'nullable|integer|min:1',
            'sla_maintenance_notice_hours' => 'nullable|integer|min:1',
            'sla_support_hours'            => 'nullable|string|max:100',
            'sla_backup_days'              => 'nullable|integer|min:1',
            'termination_notice_days'      => 'nullable|integer|min:1',
            'governing_law'                => 'nullable|string|max:100',
            'company_logo'                 => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        // Handle company logo upload
        $companyLogoFilename = null;
        if ($request->hasFile('company_logo')) {
            $dir = storage_path('app/contract-logos');
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $filename = date('YmdHis') . '_clogo.' . $request->file('company_logo')->getClientOriginalExtension();
            $request->file('company_logo')->move($dir, $filename);
            $companyLogoFilename = $filename;
        } elseif ($request->input('reuse_company_logo')) {
            // Reuse the most recent company logo
            $companyLogoFilename = TenantContract::whereNotNull('company_logo')->latest()->value('company_logo');
        }

        $contract = TenantContract::create([
            'tenant_id'                    => $request->tenant_id,
            'document_type'                => $request->document_type,
            'effective_date'               => $request->effective_date,
            'contract_amount'              => $request->contract_amount,
            'deposit_percent'              => $request->deposit_percent,
            'balance_percent'              => $request->balance_percent,
            'hosting_months'               => $request->hosting_months,
            'support_months'               => $request->support_months,
            'warranty_days'                => $request->warranty_days,
            'timeline_days'                => $request->timeline_days,
            'include_scope'                => $request->boolean('include_scope'),
            'scope_items'                  => array_filter($request->input('scope_items', [])),
            'notes'                        => $request->notes,
            // Editable sections
            'purpose_text'                 => $request->purpose_text,
            'software_description'         => $request->software_description,
            'system_name'                  => $request->input('system_name', 'NovaxPOS'),
            'sla_uptime'                   => $request->input('sla_uptime', '99.5%'),
            'sla_critical_hours'           => $request->input('sla_critical_hours', 48),
            'sla_bugfix_days'              => $request->input('sla_bugfix_days', 5),
            'sla_maintenance_notice_hours' => $request->input('sla_maintenance_notice_hours', 24),
            'sla_support_hours'            => $request->input('sla_support_hours', 'Monday–Friday, 8 AM–6 PM (Liberia time)'),
            'sla_backup_days'              => $request->input('sla_backup_days', 30),
            'termination_notice_days'      => $request->input('termination_notice_days', 30),
            'governing_law'                => $request->input('governing_law', 'Republic of Liberia'),
            'company_logo'                 => $companyLogoFilename,
            'created_by'                   => Auth::guard('superadmin')->user()?->email ?? 'superadmin',
        ]);

        // Generate PDF immediately
        $this->generatePdf($contract);

        return redirect()->route('superadmin.contracts.show', $contract->id)
            ->with('message', 'Contract created and PDF generated successfully.');
    }

    // ── View contract ──────────────────────────────────────────────────────

    public function show(TenantContract $contract)
    {
        $contract->load('tenant');
        $settings = SaasSettings::allCached();
        return view('superadmin.contracts.show', compact('contract', 'settings'));
    }

    // ── Regenerate PDF ─────────────────────────────────────────────────────

    public function generatePdfAction(TenantContract $contract)
    {
        $this->generatePdf($contract);
        return back()->with('message', 'PDF regenerated successfully.');
    }

    // ── Download / Print PDF ───────────────────────────────────────────────

    public function download(TenantContract $contract)
    {
        if (!$contract->pdf_path || !file_exists(storage_path('app/' . $contract->pdf_path))) {
            $this->generatePdf($contract);
            $contract->refresh();
        }

        $filename = str_replace(' ', '_', $contract->getDocumentTypeLabel()) . '_' . $contract->tenant_id . '.pdf';
        return response()->file(storage_path('app/' . $contract->pdf_path), [
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Content-Type'        => 'application/pdf',
        ]);
    }

    // ── Email contract to tenant ───────────────────────────────────────────

    public function email(Request $request, TenantContract $contract)
    {
        $request->validate([
            'send_to_email' => 'required|email',
        ]);

        if (!$contract->pdf_path || !file_exists(storage_path('app/' . $contract->pdf_path))) {
            $this->generatePdf($contract);
            $contract->refresh();
        }

        $settings    = SaasSettings::allCached();
        $senderName  = $settings['site_name'] ?? 'BridgeNovaX Technologies';
        $senderEmail = $settings['mail_from_address'] ?? config('mail.from.address');

        try {
            $mail_setting = MailSetting::latest()->first();
            if ($mail_setting) {
                $this->setMailInfo($mail_setting);
            }

            Mail::to($request->send_to_email)->send(new TenantContractMail($contract, $senderName, $senderEmail));

            $contract->update([
                'sent_at'        => now(),
                'sent_to_email'  => $request->send_to_email,
            ]);

            return back()->with('message', 'Contract emailed to ' . $request->send_to_email . ' successfully.');
        } catch (\Throwable $e) {
            Log::error('Contract email failed: ' . $e->getMessage());
            return back()->with('not_permitted', 'Failed to send email: ' . $e->getMessage());
        }
    }

    // ── Delete ─────────────────────────────────────────────────────────────

    public function destroy(TenantContract $contract)
    {
        // Remove PDF file
        if ($contract->pdf_path && file_exists(storage_path('app/' . $contract->pdf_path))) {
            @unlink(storage_path('app/' . $contract->pdf_path));
        }
        $contract->delete();
        return redirect()->route('superadmin.contracts.index')
            ->with('message', 'Contract deleted.');
    }

    // ── Internal PDF generation ────────────────────────────────────────────

    private function logoBase64(?string $path): ?string
    {
        if (!$path || !file_exists($path)) return null;
        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    private function generatePdf(TenantContract $contract): void
    {
        $contract->load('tenant.package');
        $settings = SaasSettings::allCached();

        // Font from theme settings — falls back to a dompdf-safe sans-serif
        $themeSetting = \App\Models\ThemeSetting::active()->latest()->first();
        $fontFamily   = $themeSetting?->font_family ?: 'DejaVu Sans';

        // System logo (site logo from settings)
        $siteLogoFile   = $settings['site_logo'] ?? null;
        $systemLogoPath = $siteLogoFile ? storage_path('app/landlord-logos/' . $siteLogoFile) : null;
        $systemLogoB64  = $this->logoBase64($systemLogoPath);

        // Company logo (uploaded per contract)
        $companyLogoPath = $contract->company_logo ? storage_path('app/contract-logos/' . $contract->company_logo) : null;
        $companyLogoB64  = $this->logoBase64($companyLogoPath);

        // Resolve all dynamic values server-side. Use ?: so empty strings fall back too.
        //
        // IMPORTANT: $contractorCompany is the legal entity signing the contract
        // (BridgeNovaX Technologies). $systemName is the software product (NovaxPOS).
        // site_name in settings is the product name — keep them separate.
        $contractorCompany = $settings['contractor_company'] ?? 'BridgeNovaX Technologies';
        $contractorCEO     = $settings['contractor_ceo']     ?? 'Omaru A. Kamara';
        $contractorEmail   = $settings['mail_from_address']  ?? '';
        $clientName        = $contract->tenant->company_name ?: (string) $contract->tenant_id;
        $effectiveDate     = $contract->effective_date?->format('F j, Y') ?? date('F j, Y');
        $systemName        = $contract->system_name    ?: ($settings['site_name'] ?? 'NovaxPOS');
        $governingLaw      = $contract->governing_law  ?: 'Republic of Liberia';
        $termDays          = $contract->termination_notice_days ?: 30;

        $purposeText = $contract->purpose_text
            ?: "This Services Contract Agreement (\"Agreement\") is entered into as of {$effectiveDate}, by and between <strong>{$contractorCompany}</strong> (\"Contractor\") and <strong>{$clientName}</strong> (\"Client\"). This Agreement governs the development, delivery, and ongoing support of a {$systemName} management system tailored to the Client's business operations.";

        $softwareDesc = $contract->software_description
            ?: "The Contractor agrees to deliver a comprehensive, web-based Point of Sale and Business Management System — <strong>{$systemName}</strong> — featuring inventory management, sales and purchasing, financial reporting, HRM, multi-warehouse support, and role-based access control, as specified in the Scope of Work below.";

        $slaUptime      = $contract->sla_uptime                   ?: '99.5%';
        $slaCritical    = $contract->sla_critical_hours            ?: 48;
        $slaBugfix      = $contract->sla_bugfix_days               ?: 5;
        $slaMaintenance = $contract->sla_maintenance_notice_hours  ?: 24;
        $slaSupport     = $contract->sla_support_hours             ?: 'Monday–Friday, 8 AM–6 PM (Liberia time)';
        $slaBackup      = $contract->sla_backup_days               ?: 30;

        $pdf = Pdf::loadView('superadmin.contracts.pdf', [
            'contract'          => $contract,
            'settings'          => $settings,
            'systemLogoB64'     => $systemLogoB64,
            'companyLogoB64'    => $companyLogoB64,
            // Typography
            'fontFamily'        => $fontFamily,
            // Contractor identity (the company signing the contract)
            'contractorCompany' => $contractorCompany,
            'contractorCEO'     => $contractorCEO,
            'contractorEmail'   => $contractorEmail,
            // Pre-resolved content — blade renders directly
            'purposeText'       => $purposeText,
            'softwareDesc'      => $softwareDesc,
            'systemName'        => $systemName,
            'governingLaw'      => $governingLaw,
            'termDays'          => $termDays,
            'slaUptime'         => $slaUptime,
            'slaCritical'       => $slaCritical,
            'slaBugfix'         => $slaBugfix,
            'slaMaintenance'    => $slaMaintenance,
            'slaSupport'        => $slaSupport,
            'slaBackup'         => $slaBackup,
        ])->setPaper('a4', 'portrait');

        $dir = 'contracts';
        if (!is_dir(storage_path('app/' . $dir))) {
            mkdir(storage_path('app/' . $dir), 0755, true);
        }

        $filename = $dir . '/' . $contract->id . '_' . str_replace(' ', '_', $contract->document_type) . '.pdf';
        $pdf->save(storage_path('app/' . $filename));

        $contract->update(['pdf_path' => $filename]);
    }
}
