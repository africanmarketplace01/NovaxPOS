<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 20mm 18mm 20mm 18mm; }
    * { margin:0; padding:0; box-sizing:border-box; }
    html, body { width:100%; }
    body { font-family: '{{ $fontFamily }}', DejaVu Sans, sans-serif; font-size:10.5pt; color:#1a1a2e; line-height:1.6; }

    /* ── Header ── */
    .header { border-bottom:3px solid #1a3c6b; padding-bottom:16px; margin-bottom:22px; }
    .header-table { width:100%; border-collapse:collapse; }
    .header-logo-cell { width:18%; vertical-align:middle; }
    .header-info-cell { vertical-align:middle; text-align:center; padding:0 10px; }
    .header-logo-cell img { max-height:64px; max-width:110px; display:block; }
    .header-logo-left  img { margin-right:auto; }
    .header-logo-right img { margin-left:auto; }
    .header .company { font-size:19pt; font-weight:bold; color:#1a3c6b; letter-spacing:1px; }
    .header .doc-title { font-size:13pt; font-weight:bold; margin-top:5px; color:#2c5282; }
    .header .subtitle { font-size:9.5pt; color:#64748b; margin-top:4px; }
    .logo-placeholder { width:110px; height:64px; display:inline-block; }

    /* ── Parties block ── */
    .parties { background:#f0f4ff; border:1px solid #c7d8f5; border-radius:6px; padding:14px 18px; margin-bottom:20px; }
    .parties-grid { display:table; width:100%; }
    .party { display:table-cell; width:50%; vertical-align:top; padding:0 8px; }
    .party:first-child { padding-left:0; border-right:1px solid #c7d8f5; }
    .party:last-child  { padding-right:0; padding-left:18px; }
    .party-role { font-size:8pt; text-transform:uppercase; letter-spacing:.08em; color:#64748b; margin-bottom:4px; }
    .party-name { font-size:12pt; font-weight:bold; color:#1a3c6b; }
    .party-detail { font-size:9.5pt; color:#475569; margin-top:2px; }

    /* ── Section headers ── */
    .section { margin-bottom:18px; page-break-inside:avoid; }
    .section-title { font-size:11.5pt; font-weight:bold; color:#1a3c6b; border-bottom:1.5px solid #c7d8f5;
                     padding-bottom:4px; margin-bottom:10px; }
    .section-title .num { background:#1a3c6b; color:#fff; font-size:9pt; padding:1px 7px;
                          border-radius:3px; margin-right:6px; }
    p { margin-bottom:8px; }

    /* ── Scope list ── */
    .scope-list { margin:0; padding-left:0; list-style:none; }
    .scope-list li { padding:4px 0 4px 24px; position:relative; border-bottom:1px solid #f1f5f9; font-size:10.5pt; }
    .scope-list li:last-child { border-bottom:none; }
    .scope-list li::before { content: "✓"; position:absolute; left:0; color:#2c5282; font-weight:bold; }

    /* ── Cost table ── */
    .cost-table { width:100%; border-collapse:collapse; margin:8px 0; }
    .cost-table th { background:#1a3c6b; color:#fff; padding:7px 12px; font-size:10pt; text-align:left; }
    .cost-table td { padding:7px 12px; border-bottom:1px solid #e2e8f0; font-size:10.5pt; }
    .cost-table tr:nth-child(even) td { background:#f8fafc; }
    .cost-table .total td { font-weight:bold; background:#eef2ff; color:#1a3c6b; }

    /* ── SLA table ── */
    .sla-table { width:100%; border-collapse:collapse; margin:8px 0; }
    .sla-table th { background:#2c5282; color:#fff; padding:6px 10px; font-size:10pt; }
    .sla-table td { padding:6px 10px; border-bottom:1px solid #e2e8f0; font-size:10pt; }
    .sla-table tr:nth-child(even) td { background:#f8fafc; }

    /* ── Signature block ── */
    .sig-section { margin-top:30px; page-break-inside:avoid; }
    .sig-grid { display:table; width:100%; }
    .sig-col { display:table-cell; width:50%; vertical-align:top; padding:0 12px; }
    .sig-col:first-child { padding-left:0; }
    .sig-col:last-child  { padding-right:0; }
    .sig-box { border:1px solid #cbd5e1; border-radius:6px; padding:18px 16px; min-height:120px; }
    .sig-role { font-size:9pt; color:#64748b; text-transform:uppercase; letter-spacing:.06em; margin-bottom:4px; }
    .sig-name { font-size:11.5pt; font-weight:bold; color:#1a3c6b; }
    .sig-line { border-top:1.5px solid #64748b; margin-top:40px; padding-top:4px; font-size:9pt; color:#64748b; }
    .sig-date { margin-top:10px; font-size:9.5pt; color:#475569; }

    .footer { margin-top:30px; text-align:center; font-size:8.5pt; color:#94a3b8;
              border-top:1px solid #e2e8f0; padding-top:10px; }

    .highlight-box { background:#fffbeb; border:1px solid #fcd34d; border-radius:5px; padding:10px 14px; margin:10px 0; }
    .page-break { page-break-after:always; }
</style>
</head>
<body>

@php
    $tenant  = $contract->tenant;
    $settings = $settings ?? [];

    // $contractorCompany, $contractorCEO, $contractorEmail,
    // $purposeText, $softwareDesc, $systemName, $governingLaw, $termDays,
    // $slaUptime, $slaCritical, $slaBugfix, $slaMaintenance, $slaSupport, $slaBackup
    // — all pre-resolved by the controller and passed as view variables.

    // Client info from tenant table
    $clientName    = $tenant->company_name ?: (string) $tenant->id;
    $clientEmail   = $tenant->email ?? '';
    $clientPhone   = $tenant->phone_number ?? '';
    $clientAccount = $tenant->id ?? '';
    $clientPlan    = optional($tenant->package)->name ?? '';
    $clientSubType = $tenant->subscription_type ?? '';
    $clientExpiry  = $tenant->expiry_date?->format('F j, Y') ?? '';

    // Contract metadata
    $effectiveDate  = $contract->effective_date?->format('F j, Y') ?? date('F j, Y');
    $amount         = $contract->contract_amount;
    $depositAmt     = $contract->getDepositAmount();
    $balanceAmt     = $contract->getBalanceAmount();
    $isFullContract = $contract->document_type === 'full_contract';
    $isSlaOnly      = $contract->document_type === 'sla_only';
    $isSowOnly      = $contract->document_type === 'sow_only';
@endphp

{{-- ════════════════════════════════════════════════════ --}}
{{-- HEADER --}}
{{-- ════════════════════════════════════════════════════ --}}
<div class="header">
    <table class="header-table">
        <tr>
            {{-- Company logo — LEFT --}}
            <td class="header-logo-cell header-logo-left">
                @if(!empty($companyLogoB64))
                    <img src="{{ $companyLogoB64 }}" alt="Company Logo">
                @else
                    <div class="logo-placeholder"></div>
                @endif
            </td>

            {{-- Centre: contractor company + document title + date --}}
            <td class="header-info-cell">
                <div class="company">{{ strtoupper($contractorCompany) }}</div>
                <div class="doc-title">{{ strtoupper($contract->getDocumentTypeLabel()) }}</div>
                <div class="subtitle">Effective Date: {{ $effectiveDate }}</div>
            </td>

            {{-- System logo — RIGHT --}}
            <td class="header-logo-cell header-logo-right" style="text-align:right;">
                @if(!empty($systemLogoB64))
                    <img src="{{ $systemLogoB64 }}" alt="System Logo" style="margin-left:auto;">
                @else
                    <div class="logo-placeholder"></div>
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- PARTIES --}}
<div class="parties">
    <div class="parties-grid">
        <div class="party">
            <div class="party-role">The Client</div>
            <div class="party-name">{{ $clientName }}</div>
            @if($clientEmail)
            <div class="party-detail"><strong>Email:</strong> {{ $clientEmail }}</div>
            @endif
            @if($clientPhone)
            <div class="party-detail"><strong>Phone:</strong> {{ $clientPhone }}</div>
            @endif
            @if($clientAccount)
            <div class="party-detail"><strong>Account ID:</strong> {{ $clientAccount }}</div>
            @endif
            @if($clientPlan)
            <div class="party-detail"><strong>Plan:</strong> {{ $clientPlan }}@if($clientSubType) ({{ ucfirst($clientSubType) }})@endif</div>
            @endif
            @if($clientExpiry)
            <div class="party-detail"><strong>Subscription Expiry:</strong> {{ $clientExpiry }}</div>
            @endif
        </div>
        <div class="party">
            <div class="party-role">The Contractor</div>
            <div class="party-name">{{ $contractorCompany }}</div>
            <div class="party-detail"><strong>CEO:</strong> {{ $contractorCEO }}</div>
            @if($contractorEmail)
            <div class="party-detail"><strong>Email:</strong> {{ $contractorEmail }}</div>
            @endif
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════════════ --}}
{{-- FULL CONTRACT --}}
{{-- ════════════════════════════════════════════════════ --}}
@if($isFullContract)

<div class="section">
    <div class="section-title"><span class="num">1</span> Purpose</div>
    <p>{!! $purposeText !!}</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">2</span> Software Description</div>
    <p>{!! $softwareDesc !!}</p>
</div>

@if($contract->include_scope && !empty($contract->scope_items))
<div class="section">
    <div class="section-title"><span class="num">3</span> Scope of Work</div>
    <p>The following deliverables are included in this agreement:</p>
    <ul class="scope-list">
        @foreach($contract->scope_items as $i => $item)
            <li>{{ ($i + 1) }}. {{ $item }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="section">
    <div class="section-title"><span class="num">{{ $contract->include_scope ? '4' : '3' }}</span> Project Timeline</div>
    <p>The Contractor shall deliver the fully functional system within <strong>{{ $contract->timeline_days }} days</strong> from the effective date of this agreement, subject to timely provision of required information and cooperation by the Client.</p>
</div>

@if($amount)
<div class="section">
    <div class="section-title"><span class="num">{{ $contract->include_scope ? '5' : '4' }}</span> Cost</div>
    <table class="cost-table">
        <thead><tr><th>Description</th><th>Amount (USD)</th></tr></thead>
        <tbody>
            <tr><td>Total Contract Value</td><td><strong>${{ number_format($amount, 2) }}</strong></td></tr>
            <tr><td>Deposit ({{ $contract->deposit_percent }}%) — payable before work begins</td><td>${{ number_format($depositAmt, 2) }}</td></tr>
            <tr><td>Balance ({{ $contract->balance_percent }}%) — payable on delivery</td><td>${{ number_format($balanceAmt, 2) }}</td></tr>
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $contract->include_scope ? '6' : '5' }}</span> Payment Terms</div>
    <p>A deposit of <strong>{{ $contract->deposit_percent }}% (USD {{ number_format($depositAmt, 2) }})</strong> is due prior to commencement of work. The remaining balance of <strong>{{ $contract->balance_percent }}% (USD {{ number_format($balanceAmt, 2) }})</strong> is payable upon successful delivery and Client acceptance of the system. Payments may be made via bank transfer, MTN Mobile Money, or Orange Money.</p>
</div>
@endif

<div class="section">
    <div class="section-title"><span class="num">{{ $contract->include_scope ? ($amount ? '7' : '6') : ($amount ? '5' : '4') }}</span> Software License</div>
    <p>Upon receipt of full payment, the Client is granted a non-exclusive, non-transferable license to use the {{ $systemName }} system for their business operations. The Contractor retains all intellectual property rights to the underlying source code, framework, and platform.</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $contract->include_scope ? ($amount ? '8' : '7') : ($amount ? '6' : '5') }}</span> Hosting &amp; Infrastructure</div>
    <p>The Contractor will provide hosting services for <strong>{{ $contract->hosting_months }} months</strong> from the delivery date, inclusive of the contract value. Subsequent hosting renewals will be negotiated separately. The Client's data will be hosted on a dedicated subdomain under the Contractor's infrastructure.</p>
</div>

<div class="page-break"></div>

@endif {{-- end full_contract --}}

{{-- ════════════════════════════════════════════════════ --}}
{{-- SLA SECTION (included in full_contract and sla_only) --}}
{{-- ════════════════════════════════════════════════════ --}}
@if($isFullContract || $isSlaOnly)

@if($isSlaOnly)
{{-- SLA-only gets its own intro --}}
<div class="section">
    <div class="section-title"><span class="num">1</span> Scope of This SLA</div>
    <p>This Service Level Agreement ("SLA") is entered into as of {{ $effectiveDate }} between <strong>{{ $contractorCompany }}</strong> ("Contractor") and <strong>{{ $clientName }}</strong> ("Client"). This SLA defines the standards of service, support response times, and uptime commitments for the {{ $systemName }} system.</p>
</div>
@endif

<div class="section">
    <div class="section-title">
        <span class="num">{{ $isFullContract ? ($contract->include_scope ? ($amount ? '9' : '8') : ($amount ? '7' : '6')) : '2' }}</span>
        Service Level Agreement (SLA)
    </div>
    <p>The Contractor commits to the following service standards:</p>
    <table class="sla-table">
        <thead><tr><th>Metric</th><th>Commitment</th></tr></thead>
        <tbody>
            <tr><td>System Uptime</td><td>{{ $slaUptime }} monthly uptime target</td></tr>
            <tr><td>Critical Issue Response</td><td>Within {{ $slaCritical }} hours of report</td></tr>
            <tr><td>Bug Fix Turnaround</td><td>Within {{ $slaBugfix }} business days (non-critical)</td></tr>
            <tr><td>Scheduled Maintenance</td><td>{{ $slaMaintenance }}-hour advance notice to Client</td></tr>
            <tr><td>Support Hours</td><td>{{ $slaSupport }}</td></tr>
            <tr><td>Data Backup</td><td>Daily automated backups retained for {{ $slaBackup }} days</td></tr>
        </tbody>
    </table>
</div>

<div class="section">
    <div class="section-title">
        <span class="num">{{ $isFullContract ? ($contract->include_scope ? ($amount ? '10' : '9') : ($amount ? '8' : '7')) : '3' }}</span>
        Warranty
    </div>
    <p>The Contractor warrants that the delivered software will perform substantially as described for <strong>{{ $contract->warranty_days }} days</strong> from the date of delivery. During this period, the Contractor will correct any defects at no additional charge. This warranty does not cover issues arising from Client misuse, unauthorized modifications, or third-party integrations.</p>
</div>

<div class="section">
    <div class="section-title">
        <span class="num">{{ $isFullContract ? ($contract->include_scope ? ($amount ? '11' : '10') : ($amount ? '9' : '8')) : '4' }}</span>
        Post-Delivery Support
    </div>
    <p>The Contractor will provide technical support for <strong>{{ $contract->support_months }} months</strong> after delivery, covering system usage assistance, bug resolution, and minor feature adjustments. Extended support thereafter is subject to a separate support retainer agreement.</p>
</div>

@if($isSlaOnly)
{{-- SLA-only signatures --}}
<div class="section">
    <div class="section-title"><span class="num">5</span> Governing Law</div>
    <p>This SLA shall be governed by and construed in accordance with the laws of the <strong>{{ $governingLaw }}</strong>. Any disputes arising under this agreement shall be resolved through good-faith negotiation.</p>
</div>
@endif

@endif {{-- end SLA section --}}

{{-- ════════════════════════════════════════════════════ --}}
{{-- SCOPE OF WORK ONLY --}}
{{-- ════════════════════════════════════════════════════ --}}
@if($isSowOnly)
<div class="section">
    <div class="section-title"><span class="num">1</span> Scope of Work</div>
    <p>This Scope of Work ("SOW") is entered into as of {{ $effectiveDate }} between <strong>{{ $contractorCompany }}</strong> ("Contractor") and <strong>{{ $clientName }}</strong> ("Client"). This SOW defines the specific deliverables agreed upon for this engagement.</p>
    @if($contract->include_scope && !empty($contract->scope_items))
    <ul class="scope-list" style="margin-top:10px;">
        @foreach($contract->scope_items as $i => $item)
            <li>{{ ($i + 1) }}. {{ $item }}</li>
        @endforeach
    </ul>
    @endif
</div>

@if($amount)
<div class="section">
    <div class="section-title"><span class="num">2</span> Compensation</div>
    <table class="cost-table">
        <thead><tr><th>Description</th><th>Amount (USD)</th></tr></thead>
        <tbody>
            <tr><td>Total SOW Value</td><td><strong>${{ number_format($amount, 2) }}</strong></td></tr>
        </tbody>
    </table>
</div>
@endif

<div class="section">
    <div class="section-title"><span class="num">{{ $amount ? '3' : '2' }}</span> Acceptance Criteria</div>
    <p>Deliverables will be deemed accepted when the Client confirms in writing that all agreed scope items function as specified. The Client has 5 business days after delivery to review and raise objections; failure to respond constitutes acceptance.</p>
</div>
@endif

{{-- ════════════════════════════════════════════════════ --}}
{{-- REMAINING CLAUSES (full contract only) --}}
{{-- ════════════════════════════════════════════════════ --}}
@if($isFullContract)

@php $n = $contract->include_scope ? ($amount ? 12 : 11) : ($amount ? 10 : 9); @endphp

<div class="section">
    <div class="section-title"><span class="num">{{ $n++ }}</span> Change Requests</div>
    <p>Any work requested outside the agreed Scope of Work shall be documented as a Change Request. Both parties must agree on the additional cost and timeline before work commences. Change requests will not be treated as a breach of this agreement.</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $n++ }}</span> Client Responsibilities</div>
    <p>The Client agrees to: (a) provide timely feedback and approvals; (b) designate a primary point of contact; (c) provide any content, logos, or data needed for setup; (d) ensure adequate internet connectivity for system access; and (e) refrain from unauthorized modification of the system.</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $n++ }}</span> Contractor Responsibilities</div>
    <p>The Contractor agrees to: (a) deliver the system as specified within the agreed timeline; (b) maintain the confidentiality of all Client data; (c) provide training on system usage upon delivery; (d) ensure the system meets security best practices; and (e) notify the Client promptly of any delays.</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $n++ }}</span> Data Ownership</div>
    <p>All data entered by the Client into the system remains the exclusive property of the Client. The Contractor will not use, sell, or share Client data for any purpose other than delivering the agreed services. Upon termination, the Client may request a full data export.</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $n++ }}</span> Intellectual Property</div>
    <p>The Contractor retains all intellectual property rights to the {{ $systemName }} platform, its codebase, design patterns, and infrastructure. The Client is granted a license to use the system, not ownership of its underlying technology. Custom modules developed specifically for the Client will be co-owned unless otherwise agreed in writing.</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $n++ }}</span> Confidentiality</div>
    <p>Both parties agree to keep confidential all proprietary information shared during this engagement. Neither party shall disclose the other's confidential information to third parties without prior written consent. This obligation survives the termination of this agreement.</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $n++ }}</span> Termination</div>
    <p>Either party may terminate this agreement with <strong>{{ $termDays }} days'</strong> written notice. In the event of termination by the Client after work has commenced, the Client forfeits the deposit. If the Contractor fails to deliver as agreed, the deposit shall be refunded in full. All confidentiality obligations remain in effect after termination.</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $n++ }}</span> Limitation of Liability</div>
    <p>The Contractor's total liability under this agreement shall not exceed the total amount paid by the Client. Neither party shall be liable for indirect, incidental, or consequential damages arising from the use or inability to use the system.</p>
</div>

<div class="section">
    <div class="section-title"><span class="num">{{ $n++ }}</span> Governing Law</div>
    <p>This Agreement shall be governed by and construed in accordance with the laws of the <strong>{{ $governingLaw }}</strong>. Any disputes shall first be attempted through good-faith negotiation; failing resolution within {{ $termDays }} days, disputes shall be referred to arbitration under the applicable laws.</p>
</div>

@endif {{-- end full contract clauses --}}

{{-- ════════════════════════════════════════════════════ --}}
{{-- SIGNATURES --}}
{{-- ════════════════════════════════════════════════════ --}}
<div class="sig-section">
    <div class="section-title" style="margin-bottom:16px;">Signatures</div>
    <p style="font-size:10pt;color:#475569;margin-bottom:16px;">
        By signing below, both parties agree to the terms and conditions set forth in this {{ $contract->getDocumentTypeLabel() }}.
    </p>

    <div class="sig-grid">
        <div class="sig-col">
            <div class="sig-box">
                <div class="sig-role">Client</div>
                <div class="sig-name">{{ $clientName }}</div>
                <div class="sig-line">Authorized Signature</div>
                <div class="sig-date">Date: ___________________________</div>
                <div style="margin-top:8px;font-size:9pt;color:#64748b;">Print Name: ________________________</div>
                <div style="margin-top:4px;font-size:9pt;color:#64748b;">Title: _____________________________</div>
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-box">
                <div class="sig-role">Contractor</div>
                <div class="sig-name">{{ $contractorCompany }}</div>
                <div style="font-size:9.5pt;color:#475569;margin-top:2px;">{{ $contractorCEO }}, CEO</div>
                <div class="sig-line">Authorized Signature</div>
                <div class="sig-date">Date: ___________________________</div>
            </div>
        </div>
    </div>

    {{-- Witnesses --}}
    <div class="sig-grid" style="margin-top:16px;">
        <div class="sig-col">
            <div class="sig-box">
                <div class="sig-role">Witness 1</div>
                <div class="sig-line">Signature</div>
                <div style="margin-top:8px;font-size:9pt;color:#64748b;">Print Name: ________________________</div>
                <div class="sig-date">Date: ___________________________</div>
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-box">
                <div class="sig-role">Witness 2</div>
                <div class="sig-line">Signature</div>
                <div style="margin-top:8px;font-size:9pt;color:#64748b;">Print Name: ________________________</div>
                <div class="sig-date">Date: ___________________________</div>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    Generated by {{ $contractorCompany }} &bull; {{ $effectiveDate }} &bull; Confidential
</div>

</body>
</html>
