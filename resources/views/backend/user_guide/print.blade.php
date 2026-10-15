<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ config('app.name', 'NovaxPOS') }} — User Guide</title>
@php
    // System theme from superadmin (landlord) settings — falls back to defaults
    try { $ugTheme = \App\Models\landlord\SaasSettings::allCached(); } catch (\Throwable $e) { $ugTheme = []; }
    $ugPrimary      = $ugTheme['theme_primary']       ?? '#4f46e5';
    $ugPrimaryLight = $ugTheme['theme_primary_light'] ?? '#7c3aed';
    $ugHex = ltrim($ugPrimary, '#');
    $ugRgb = hexdec(substr($ugHex,0,2)).','.hexdec(substr($ugHex,2,2)).','.hexdec(substr($ugHex,4,2));
@endphp
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/v4-shims.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<style>
:root {
    --ug-primary: {{ $ugPrimary }};
    --ug-primary-light: {{ $ugPrimaryLight }};
    --ug-primary-rgb: {{ $ugRgb }};
}
/* ── Base ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
/* Ensure theme colors survive printing */
@media print { * { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
@page { size: A4; margin: 15mm 18mm; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10.5pt; color: #1a1a2e; line-height: 1.6; }
a { color: var(--ug-primary); text-decoration: none; }

/* ── Print controls (hidden when printing) ── */
.print-bar {
    position: fixed; top: 0; left: 0; right: 0;
    background: var(--ug-primary); color: #fff;
    padding: 10px 24px;
    display: flex; align-items: center; justify-content: space-between;
    font-size: 13px;
    z-index: 1000;
}
.print-bar button {
    background: #fff; color: var(--ug-primary);
    border: none; padding: 7px 20px; border-radius: 6px;
    font-weight: 700; font-size: 13px; cursor: pointer;
}
@media print { .print-bar { display: none !important; } body { margin-top: 0; } }

body { margin-top: 50px; }
@media print { body { margin-top: 0; } }

/* ── Cover Page ── */
.cover {
    page-break-after: always;
    display: flex; flex-direction: column;
    min-height: 270mm;
    background: linear-gradient(160deg, var(--ug-primary) 0%, var(--ug-primary-light) 100%);
    color: #fff;
    padding: 50mm 30mm;
}
.cover-logo { font-size: 40pt; margin-bottom: 12mm; }
.cover-title { font-size: 30pt; font-weight: 800; margin-bottom: 6mm; }
.cover-sub { font-size: 13pt; opacity: .8; margin-bottom: 20mm; }
.cover-meta { font-size: 9pt; opacity: .6; }
.cover-watermark {
    position: absolute; right: 20mm; bottom: 20mm;
    font-size: 120pt; opacity: .06; font-weight: 900; color: #fff;
    line-height: 1;
}

/* ── TOC ── */
.toc-page { page-break-after: always; padding: 10mm 0; }
.toc-page h2 { font-size: 18pt; color: var(--ug-primary); margin-bottom: 8mm; border-bottom: 2px solid #e4e6fc; padding-bottom: 4mm; }
.toc-entry { display: flex; justify-content: space-between; padding: 3px 0; font-size: 10pt; border-bottom: 1px dotted #ddd; }
.toc-entry a { color: #1a1a2e; }
.toc-section-head { font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: #9ca3af; margin-top: 6mm; margin-bottom: 2mm; }

/* ── Sections ── */
.doc-section { page-break-inside: avoid; margin-bottom: 10mm; }
.section-header {
    background: linear-gradient(135deg, var(--ug-primary), var(--ug-primary-light));
    color: #fff;
    padding: 6mm 8mm;
    border-radius: 6px;
    margin-bottom: 4mm;
    display: flex; align-items: center; gap: 4mm;
}
.section-icon { font-size: 18pt; }
.section-title { font-size: 14pt; font-weight: 700; }
.section-body { padding: 2mm 0; }

/* ── Steps ── */
.step { display: flex; gap: 4mm; margin-bottom: 4mm; }
.step-num {
    width: 6mm; height: 6mm; min-width: 6mm;
    border-radius: 50%; background: var(--ug-primary); color: #fff;
    font-size: 7pt; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    margin-top: 1mm;
}
.step-content h6 { font-size: 10pt; font-weight: 600; margin-bottom: 1mm; }
.step-content p { font-size: 9.5pt; color: #374151; }

/* ── Tables ── */
.doc-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin: 3mm 0; }
.doc-table th { background: rgba(var(--ug-primary-rgb),.07); font-weight: 700; padding: 3mm 4mm; border: 1px solid #d4d4f7; text-align: left; }
.doc-table td { padding: 2.5mm 4mm; border: 1px solid #e5e7eb; vertical-align: top; }
.doc-table tr:nth-child(even) td { background: #fafaff; }

/* ── Tip Box ── */
.tip {
    background: rgba(var(--ug-primary-rgb),.07);
    border-left: 3px solid var(--ug-primary);
    border-radius: 0 6px 6px 0;
    padding: 3mm 5mm;
    font-size: 9pt;
    margin: 3mm 0;
    color: #374151;
}

/* ── Card Grid ── */
.card-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 4mm; margin: 4mm 0; }
.mini-card { background: #f8f9fc; border-radius: 6px; padding: 4mm; }
.mini-card-icon { font-size: 14pt; margin-bottom: 2mm; }
.mini-card-title { font-size: 9pt; font-weight: 700; margin-bottom: 1mm; }
.mini-card-desc { font-size: 8.5pt; color: #6b7280; }

/* ── Page Numbers ── */
@page { @bottom-right { content: counter(page) " / " counter(pages); font-size: 8pt; color: #9ca3af; } }
</style>
</head>
<body>

{{-- Print Bar --}}
<div class="print-bar">
    <span><i class="fas fa-file-pdf"></i> {{ config('app.name', 'NovaxPOS') }} — User Guide &nbsp;|&nbsp; PDF-ready booklet</span>
    <button onclick="window.print()"><i class="fas fa-print"></i> Print / Save PDF</button>
</div>

{{-- ══ COVER PAGE ══ --}}
<div class="cover" style="position:relative;">
    <div class="cover-logo"><i class="fas fa-book-open"></i></div>
    <div class="cover-title">{{ config('app.name', 'NovaxPOS') }}<br>User Guide</div>
    <div class="cover-sub">Complete Reference for Store Administrators &amp; Staff</div>
    <div class="cover-meta">
        Generated: {{ now()->format('d F Y') }}<br>
        Version: {{ env('VERSION', '1.0') }}
    </div>
    <div class="cover-watermark">POS</div>
</div>

{{-- ══ TABLE OF CONTENTS ══ --}}
<div class="toc-page">
    <h2>Table of Contents</h2>
    <div class="toc-section-head">Getting Started</div>
    <div class="toc-entry"><a href="#gs-overview">System Overview</a><span>3</span></div>
    <div class="toc-entry"><a href="#gs-login">Logging In</a><span>3</span></div>
    <div class="toc-entry"><a href="#gs-nav">Navigation Guide</a><span>3</span></div>
    <div class="toc-section-head">Sales</div>
    <div class="toc-entry"><a href="#pos">POS Terminal</a><span>4</span></div>
    <div class="toc-entry"><a href="#sales-list">Sales Management</a><span>4</span></div>
    <div class="toc-entry"><a href="#quotations">Quotations</a><span>5</span></div>
    <div class="toc-entry"><a href="#returns">Returns &amp; Exchanges</a><span>5</span></div>
    <div class="toc-section-head">Inventory</div>
    <div class="toc-entry"><a href="#products">Products</a><span>6</span></div>
    <div class="toc-entry"><a href="#categories">Categories &amp; Brands</a><span>6</span></div>
    <div class="toc-entry"><a href="#purchases">Purchases</a><span>7</span></div>
    <div class="toc-entry"><a href="#transfers">Stock Transfers</a><span>7</span></div>
    <div class="toc-entry"><a href="#adjustments">Stock Adjustments</a><span>7</span></div>
    <div class="toc-section-head">People</div>
    <div class="toc-entry"><a href="#customers">Customers</a><span>8</span></div>
    <div class="toc-entry"><a href="#suppliers">Suppliers</a><span>8</span></div>
    <div class="toc-entry"><a href="#billers">Billers</a><span>8</span></div>
    <div class="toc-entry"><a href="#users">Users &amp; Roles</a><span>8</span></div>
    <div class="toc-section-head">Finance</div>
    <div class="toc-entry"><a href="#expenses">Expenses</a><span>9</span></div>
    <div class="toc-entry"><a href="#income">Income</a><span>9</span></div>
    <div class="toc-entry"><a href="#accounting">Accounting</a><span>9</span></div>
    <div class="toc-section-head">Reports &amp; Settings</div>
    <div class="toc-entry"><a href="#reports">Reports Overview</a><span>10</span></div>
    <div class="toc-entry"><a href="#settings">General Settings</a><span>10</span></div>
    <div class="toc-entry"><a href="#pos-settings">POS Settings</a><span>11</span></div>
    <div class="toc-section-head">Online Store &amp; Support</div>
    <div class="toc-entry"><a href="#store">Online Store</a><span>11</span></div>
    <div class="toc-entry"><a href="#support">Support &amp; Tickets</a><span>12</span></div>
</div>

{{-- ══ GETTING STARTED ══ --}}
<div id="gs-overview" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-house"></i></span><span class="section-title">System Overview</span></div>
    <div class="section-body">
        <p style="margin-bottom:4mm;">{{ config('app.name', 'NovaxPOS') }} is a comprehensive Point-of-Sale and business management platform covering the full retail workflow: inventory, purchasing, sales via POS or invoice, customer management, HR, accounting, and analytics.</p>
        <div class="card-grid">
            <div class="mini-card"><div class="mini-card-icon"><i class="fas fa-cart-shopping"></i></div><div class="mini-card-title">POS Terminal</div><div class="mini-card-desc">Fast cashier screen for in-person sales, barcode scanning, payment processing</div></div>
            <div class="mini-card"><div class="mini-card-icon"><i class="fas fa-boxes-stacked"></i></div><div class="mini-card-title">Inventory</div><div class="mini-card-desc">Products, categories, brands, stock counts, adjustments, barcode printing</div></div>
            <div class="mini-card"><div class="mini-card-icon"><i class="fas fa-sack-dollar"></i></div><div class="mini-card-title">Sales & Finance</div><div class="mini-card-desc">Invoices, quotations, returns, expenses, income and accounting</div></div>
            <div class="mini-card"><div class="mini-card-icon"><i class="fas fa-users"></i></div><div class="mini-card-title">People</div><div class="mini-card-desc">Customers, suppliers, billers, users and role-based permissions</div></div>
            <div class="mini-card"><div class="mini-card-icon"><i class="fas fa-chart-line"></i></div><div class="mini-card-title">Reports</div><div class="mini-card-desc">Profit/loss, sales charts, customer dues, stock alerts and more</div></div>
            <div class="mini-card"><div class="mini-card-icon"><i class="fas fa-gear"></i></div><div class="mini-card-title">Settings</div><div class="mini-card-desc">General config, invoice templates, payment gateways, taxes</div></div>
        </div>
    </div>
</div>

<div id="gs-login" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-right-to-bracket"></i></span><span class="section-title">Logging In</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Open the Login Page</h6><p>Navigate to your store URL. You will be redirected to the login screen.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Enter Credentials</h6><p>Type your email address and password, then click Login. Use your administrator-supplied credentials on first login.</p></div></div>
        <div class="step"><div class="step-num">3</div><div class="step-content"><h6>Dashboard</h6><p>After login you land on the Dashboard showing today's sales summary, low stock alerts, and quick-action shortcuts.</p></div></div>
        <div class="tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:2mm;"></i> <strong>Forgot password?</strong> Use the "Forgot Password" link on the login page to receive a reset email.</div>
    </div>
</div>

<div id="gs-nav" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-compass"></i></span><span class="section-title">Navigation Guide</span></div>
    <div class="section-body">
        <table class="doc-table">
            <thead><tr><th>Section</th><th>What's Inside</th></tr></thead>
            <tbody>
                <tr><td><strong>Sales</strong></td><td>Grouped into: <strong>Sales</strong> (Sale list, Add sale, Instalment list, Import by CSV), <strong>Returns &amp; Exchanges</strong> (Sale return, Sale exchange), <strong>Promotions</strong> (Gift cards, Coupons), <strong>Fulfillment</strong> (Delivery, Packing slips, Challans, Couriers) — plus Quotations and Booking</td></tr>
                <tr><td><strong>Inventory</strong></td><td>Products, Categories, Brands, Units, Barcode printing, Adjustments, Transfers, Stock count, Stock history, Low-stock alerts, Purchase orders, Goods received, Purchase returns, Valuation &amp; movement reports</td></tr>
                <tr><td><strong>People</strong></td><td>Customers, Suppliers, Billers, Users, Sale Agents</td></tr>
                <tr><td><strong>Finance</strong></td><td>Expenses and Income (categories, lists, entries)</td></tr>
                <tr><td><strong>Accounting</strong></td><td>Accounts, Money transfers, Account statements, Balance sheet</td></tr>
                <tr><td><strong>Workforce</strong></td><td>Employees, Departments, Designations, Shifts, Attendance, Holidays, Overtime, Leave, Payroll (if HRM enabled)</td></tr>
                <tr><td><strong>Channels</strong></td><td>WhatsApp messaging, Online Store, Catalogue QR</td></tr>
                <tr><td><strong>Add-ons</strong></td><td>Manufacturing, Repair &amp; service jobs, WooCommerce, eCommerce, Gym (when enabled)</td></tr>
                <tr><td><strong>Reports</strong></td><td>All business analytics reports and the Activity Log</td></tr>
                <tr><td><strong>System</strong></td><td>Settings (General, POS, Invoice, Barcode, Tax, Payment gateways…), Warehouses, Tables, Languages, Currency, Roles &amp; Permissions, Backup</td></tr>
                <tr><td><strong>Developer</strong></td><td>API Tokens for integrations</td></tr>
                <tr><td><strong>Support</strong></td><td>Support tickets and User Guide</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ══ POS ══ --}}
<div id="pos" class="doc-section" style="page-break-before:always;">
    <div class="section-header"><span class="section-icon"><i class="fas fa-cash-register"></i></span><span class="section-title">POS Terminal</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Select Warehouse & Biller</h6><p>At the top of the POS screen, choose the active warehouse and biller for this session.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Add Products</h6><p>Search by name or barcode. Click a product card to add it to the cart. Use a barcode scanner for speed.</p></div></div>
        <div class="step"><div class="step-num">3</div><div class="step-content"><h6>Adjust Quantities & Discounts</h6><p>In the cart, click the quantity to change it, or apply a line-item discount percentage.</p></div></div>
        <div class="step"><div class="step-num">4</div><div class="step-content"><h6>Select Customer (Optional)</h6><p>Search for an existing customer or add a new one. Required for due-payment tracking.</p></div></div>
        <div class="step"><div class="step-num">5</div><div class="step-content"><h6>Apply Payment</h6><p>Click Pay. Enter the amount for each payment method. Click Submit to complete.</p></div></div>
        <div class="step"><div class="step-num">6</div><div class="step-content"><h6>Print Receipt</h6><p>A receipt is generated. Choose to print it, send by email, or share via WhatsApp.</p></div></div>
        <div class="tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:2mm;"></i> <strong>Keyboard shortcuts:</strong> F2 = focus search · F4 = open payment · Esc = clear cart</div>
    </div>
</div>

<div id="sales-list" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-clipboard-list"></i></span><span class="section-title">Sales Management</span></div>
    <div class="section-body">
        <table class="doc-table">
            <thead><tr><th>Action</th><th>How</th></tr></thead>
            <tbody>
                <tr><td>View sale detail</td><td>Click the eye icon on any row</td></tr>
                <tr><td>Add a sale (invoice)</td><td>Sales → Add Sale, fill customer, products, payment</td></tr>
                <tr><td>Record partial payment</td><td>Open sale detail → click Add Payment</td></tr>
                <tr><td>Print invoice</td><td>Open detail → Print button</td></tr>
                <tr><td>Import sales via CSV</td><td>Sales → Import Sale By CSV</td></tr>
                <tr><td>Export to Excel/PDF</td><td>Use DataTable export buttons above the table</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="quotations" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-file-lines"></i></span><span class="section-title">Quotations</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Create Quotation</h6><p>Sales → Quotation → Add Quotation. Fill in customer, products and validity date.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Send to Customer</h6><p>Open the quotation and click Print or Email to share the PDF.</p></div></div>
        <div class="step"><div class="step-num">3</div><div class="step-content"><h6>Convert to Sale</h6><p>When approved, open the quotation and click Convert to Sale. All line items transfer automatically.</p></div></div>
    </div>
</div>

<div id="returns" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-rotate-left"></i></span><span class="section-title">Returns & Exchanges</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Sale Return</h6><p>Sales → Returns &amp; Exchanges → Sale Return. Find the original sale, enter returned quantities. Stock is automatically restocked.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Purchase Return</h6><p>Inventory → Purchase Return to return goods to a supplier.</p></div></div>
        <div class="step"><div class="step-num">3</div><div class="step-content"><h6>Sale Exchange</h6><p>Sales → Returns &amp; Exchanges → Sale Exchange to swap items in an existing sale.</p></div></div>
    </div>
</div>

{{-- ══ INVENTORY ══ --}}
<div id="products" class="doc-section" style="page-break-before:always;">
    <div class="section-header"><span class="section-icon"><i class="fas fa-boxes-stacked"></i></span><span class="section-title">Products</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Add a Product</h6><p>Inventory → Products → Add Product. Fill in name, SKU/barcode, category, cost price, selling price, and opening stock.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Set Pricing Tiers</h6><p>Set a Retail Price, Wholesale Price, and Promotional Price per product.</p></div></div>
        <div class="step"><div class="step-num">3</div><div class="step-content"><h6>Product Variants</h6><p>For size/colour variations, enable Variants during product creation and define the options.</p></div></div>
        <div class="step"><div class="step-num">4</div><div class="step-content"><h6>Print Barcodes</h6><p>Inventory → Print Barcode. Filter by warehouse and date, adjust quantities, print retail and wholesale labels.</p></div></div>
        <div class="step"><div class="step-num">5</div><div class="step-content"><h6>Stock Count</h6><p>Inventory → Stock Count to reconcile physical counts against system stock.</p></div></div>
        <div class="tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:2mm;"></i> Set a <strong>minimum stock quantity</strong> on each product to receive low-stock alerts.</div>
    </div>
</div>

<div id="categories" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-tags"></i></span><span class="section-title">Categories & Brands</span></div>
    <div class="section-body">
        <table class="doc-table">
            <thead><tr><th>Item</th><th>Purpose</th><th>Where</th></tr></thead>
            <tbody>
                <tr><td>Category</td><td>Group products. Supports parent/child hierarchies.</td><td>Inventory → Products → Category</td></tr>
                <tr><td>Brand</td><td>Track manufacturer or brand name.</td><td>Inventory → Products → Brand</td></tr>
                <tr><td>Unit</td><td>Define units of measure (pcs, kg, litre, box…).</td><td>Inventory → Products → Unit</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="purchases" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-bag-shopping"></i></span><span class="section-title">Purchases</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Add Purchase</h6><p>Inventory → Purchase Orders → Add Purchase. Select supplier, warehouse, add product lines. Stock auto-updates on save.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Record Supplier Payment</h6><p>Open a purchase → Add Payment to record partial or full payments.</p></div></div>
        <div class="step"><div class="step-num">3</div><div class="step-content"><h6>Purchase Return</h6><p>Open a purchase → Return, or go to Inventory → Purchase Return.</p></div></div>
    </div>
</div>

<div id="transfers" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-right-left"></i></span><span class="section-title">Stock Transfers</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Select Warehouses</h6><p>Inventory → Transfers → Add Transfer. Choose source and destination warehouses and date.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Add Products</h6><p>Search and add each product with the quantity to transfer. Click Save.</p></div></div>
        <div class="tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:2mm;"></i> Transfers reduce stock at the source warehouse and increase it at the destination immediately.</div>
    </div>
</div>

<div id="adjustments" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-scale-balanced"></i></span><span class="section-title">Stock Adjustments</span></div>
    <div class="section-body">
        <table class="doc-table">
            <thead><tr><th>Type</th><th>Effect</th></tr></thead>
            <tbody>
                <tr><td>Addition (+)</td><td>Increases stock quantity</td></tr>
                <tr><td>Subtraction (−)</td><td>Decreases stock quantity</td></tr>
            </tbody>
        </table>
        <p style="margin-top:3mm;font-size:9.5pt;color:#374151;">Use adjustments to correct discrepancies, write off damaged goods, or add untracked stock. Go to Inventory → Adjustment.</p>
    </div>
</div>

{{-- ══ PEOPLE ══ --}}
<div id="customers" class="doc-section" style="page-break-before:always;">
    <div class="section-header"><span class="section-icon"><i class="fas fa-user"></i></span><span class="section-title">Customers</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Add Customer</h6><p>People → Customer List → Add Customer. Enter name, phone, email, address and customer group.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Customer Groups</h6><p>Groups (e.g. Wholesale, Retail, VIP) can have special pricing tiers applied automatically in POS.</p></div></div>
        <div class="step"><div class="step-num">3</div><div class="step-content"><h6>Customer Ledger</h6><p>View all transactions, balances, and dues from the customer detail page.</p></div></div>
    </div>
</div>

<div id="suppliers" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-industry"></i></span><span class="section-title">Suppliers</span></div>
    <div class="section-body">
        <p style="font-size:9.5pt;color:#374151;">Manage all vendors under People → Supplier List. Each supplier has purchase history and outstanding balance viewable through the Supplier Report.</p>
    </div>
</div>

<div id="billers" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-file-invoice"></i></span><span class="section-title">Billers</span></div>
    <div class="section-body">
        <p style="font-size:9.5pt;color:#374151;margin-bottom:3mm;">A biller is the business identity that issues an invoice or receipt — typically a branch, outlet, or legal entity. Every sale is recorded against a biller, and its details appear on the printed invoice.</p>
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Add a Biller</h6><p>People → Biller List → Add Biller. Enter the name, company, VAT/registration number, email, phone and address for invoices.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Assign to Users</h6><p>When creating a user, assign their default biller. Their sales are recorded under it automatically.</p></div></div>
        <div class="step"><div class="step-num">3</div><div class="step-content"><h6>Select at the POS</h6><p>Switch the active biller at the top of the POS screen — useful when one till serves multiple branches.</p></div></div>
        <div class="tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:2mm;"></i> Use the Biller Report to compare sales performance across billers/branches.</div>
    </div>
</div>

<div id="users" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-users"></i></span><span class="section-title">Users & Roles</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Create a Role</h6><p>System → Roles &amp; Permissions. Create roles like "Cashier", "Manager" and tick the permissions each role should have.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Add a User</h6><p>People → User List → Add User. Assign the role, warehouse and biller.</p></div></div>
        <div class="tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:2mm;"></i> Roles 1 (Super Admin) and 2 (Admin) have full access by default. Custom roles see only what you grant them.</div>
    </div>
</div>

{{-- ══ FINANCE ══ --}}
<div id="expenses" class="doc-section" style="page-break-before:always;">
    <div class="section-header"><span class="section-icon"><i class="fas fa-money-bill-wave"></i></span><span class="section-title">Expenses</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Create Expense Category</h6><p>Set up categories (e.g. Rent, Utilities, Salaries) so expenses can be grouped in reports.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Add Expense</h6><p>Finance → Expense → Expense List → Add Expense. Select category, amount, account, reference and attach a receipt image.</p></div></div>
    </div>
</div>

<div id="income" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-sack-dollar"></i></span><span class="section-title">Income</span></div>
    <div class="section-body">
        <p style="font-size:9.5pt;color:#374151;">Record non-sale income (rent, commissions, etc.) under Finance → Income. Works the same as expenses — create categories first, then add income entries.</p>
    </div>
</div>

<div id="accounting" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-book"></i></span><span class="section-title">Accounting</span></div>
    <div class="section-body">
        <table class="doc-table">
            <thead><tr><th>Feature</th><th>What It Does</th><th>Location</th></tr></thead>
            <tbody>
                <tr><td>Accounts</td><td>Manage bank and cash accounts</td><td>Accounting → Account List</td></tr>
                <tr><td>Money Transfer</td><td>Move funds between accounts</td><td>Accounting → Money Transfer</td></tr>
                <tr><td>Balance Sheet</td><td>Assets vs liabilities overview</td><td>Accounting → Balance Sheet</td></tr>
                <tr><td>Account Statement</td><td>Full ledger for any account</td><td>Accounting → Account Statement</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ══ REPORTS ══ --}}
<div id="reports" class="doc-section" style="page-break-before:always;">
    <div class="section-header"><span class="section-icon"><i class="fas fa-chart-line"></i></span><span class="section-title">Reports Overview</span></div>
    <div class="section-body">
        <table class="doc-table">
            <thead><tr><th>Report</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td>Profit & Loss</td><td>Net profit over a selected date range</td></tr>
                <tr><td>Best Seller</td><td>Top products by quantity or revenue</td></tr>
                <tr><td>Product Report</td><td>Purchase vs sale history per product</td></tr>
                <tr><td>Stock Report</td><td>Current stock levels across all warehouses</td></tr>
                <tr><td>Daily/Monthly Sale</td><td>Sales broken down by day or month</td></tr>
                <tr><td>Sale Report</td><td>Filterable sale transactions with payment details</td></tr>
                <tr><td>Payment Report</td><td>Payment method breakdown over time</td></tr>
                <tr><td>Customer Report</td><td>Purchase history and balances per customer</td></tr>
                <tr><td>Customer Due</td><td>Outstanding balances owed by customers</td></tr>
                <tr><td>Supplier Due</td><td>Amounts owed to suppliers</td></tr>
                <tr><td>Warehouse Stock Chart</td><td>Visual stock levels per warehouse</td></tr>
                <tr><td>Product Expiry</td><td>Items approaching or past expiry date</td></tr>
                <tr><td>Qty Alert</td><td>Products below minimum stock threshold</td></tr>
                <tr><td>Cash Register</td><td>Opening/closing balances per shift</td></tr>
            </tbody>
        </table>
        <div class="tip mt-3"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:2mm;"></i> All reports support date range filtering and can be exported to Excel, PDF or CSV.</div>
    </div>
</div>

{{-- ══ SETTINGS ══ --}}
<div id="settings" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-gear"></i></span><span class="section-title">General Settings</span></div>
    <div class="section-body">
        <table class="doc-table">
            <thead><tr><th>Setting</th><th>What to Enter</th></tr></thead>
            <tbody>
                <tr><td>Site Title</td><td>Your business name (appears on receipts and reports)</td></tr>
                <tr><td>Logo</td><td>Upload your company logo (PNG recommended, 200×60px)</td></tr>
                <tr><td>Currency</td><td>Set the currency symbol and position</td></tr>
                <tr><td>Tax</td><td>Default tax rate applied to sales</td></tr>
                <tr><td>Modules</td><td>Enable/disable optional modules (HRM, Manufacturing, etc.)</td></tr>
                <tr><td>Theme</td><td>Choose light or dark mode for the interface</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="pos-settings" class="doc-section">
    <div class="section-header"><span class="section-icon"><i class="fas fa-sliders"></i></span><span class="section-title">POS Settings</span></div>
    <div class="section-body">
        <table class="doc-table">
            <thead><tr><th>Setting</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td>Keyboard Shortcut</td><td>Enable/disable keyboard shortcuts in POS</td></tr>
                <tr><td>Product View</td><td>Grid or list mode for the product display</td></tr>
                <tr><td>Auto Focus Search</td><td>Cursor auto-focuses the product search on load</td></tr>
                <tr><td>Print Receipt</td><td>Auto-open print dialog after each sale</td></tr>
                <tr><td>Receipt Printer</td><td>Select the thermal printer for this terminal</td></tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ══ ONLINE STORE ══ --}}
<div id="store" class="doc-section" style="page-break-before:always;">
    <div class="section-header"><span class="section-icon"><i class="fas fa-cart-shopping"></i></span><span class="section-title">Online Store</span></div>
    <div class="section-body">
        <div class="step"><div class="step-num">1</div><div class="step-content"><h6>Enable the Store</h6><p>Toggle Store Enabled in Channels → Online Store to make it publicly accessible.</p></div></div>
        <div class="step"><div class="step-num">2</div><div class="step-content"><h6>Configure Details</h6><p>Add store name, description, banner image, and WhatsApp number for order notifications.</p></div></div>
        <div class="step"><div class="step-num">3</div><div class="step-content"><h6>Share QR Code</h6><p>Click Generate QR to get a printable QR code customers can scan.</p></div></div>
        <