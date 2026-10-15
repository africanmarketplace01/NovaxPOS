@extends('backend.layout.main')
@section('title', 'User Guide')
@section('content')

@php
    // System theme from superadmin (landlord) settings — falls back to defaults
    try { $ugTheme = \App\Models\landlord\SaasSettings::allCached(); } catch (\Throwable $e) { $ugTheme = []; }
    $ugPrimary      = $ugTheme['theme_primary']       ?? '#7c5cc4';
    $ugPrimaryLight = $ugTheme['theme_primary_light'] ?? '#4f46e5';
    $ugHex = ltrim($ugPrimary, '#');
    $ugRgb = hexdec(substr($ugHex,0,2)).','.hexdec(substr($ugHex,2,2)).','.hexdec(substr($ugHex,4,2));
@endphp

@push('css')
<style>
:root {
    --ug-primary: {{ $ugPrimary }};
    --ug-primary-light: {{ $ugPrimaryLight }};
    --ug-primary-rgb: {{ $ugRgb }};
}
.ug-hero {
    background: linear-gradient(135deg, var(--ug-primary) 0%, var(--ug-primary-light) 100%);
    color: #fff;
    padding: 3rem 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}
.ug-hero::before {
    content: '';
    position: absolute;
    right: -60px; top: -60px;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
}
.ug-toc {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e4e6fc;
    padding: 1.5rem;
    position: sticky;
    top: 80px;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
}
.dark-mode .ug-toc { background: #283046; border-color: #3b4253; }
.ug-toc a {
    display: block;
    color: #6b7280;
    font-size: .82rem;
    padding: 4px 0 4px 12px;
    text-decoration: none;
    border-left: 2px solid transparent;
    transition: all .15s;
}
.ug-toc a:hover, .ug-toc a.active {
    color: var(--ug-primary);
    border-left-color: var(--ug-primary);
}
.ug-toc .toc-section { font-weight: 700; font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; color: #9ca3af; margin-top: 1rem; margin-bottom: 4px; padding-left: 0; border: none; }
.ug-section {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e4e6fc;
    margin-bottom: 1.5rem;
    overflow: hidden;
}
.dark-mode .ug-section { background: #283046; border-color: #3b4253; }
.ug-section-header {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid #e4e6fc;
    display: flex;
    align-items: center;
    gap: .75rem;
    cursor: pointer;
}
.dark-mode .ug-section-header { border-color: #3b4253; }
.ug-section-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--ug-primary), var(--ug-primary-light));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 17px; flex-shrink: 0;
}
.ug-section-title { font-weight: 700; font-size: 1rem; margin: 0; }
.ug-section-body { padding: 1.5rem; }
.ug-step {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.25rem;
}
.ug-step:last-child { margin-bottom: 0; }
.ug-step-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    background: var(--ug-primary);
    color: #fff;
    font-size: .78rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    margin-top: 2px;
}
.ug-step-content h6 { margin: 0 0 4px; font-weight: 600; font-size: .9rem; }
.ug-step-content p { margin: 0; font-size: .85rem; color: #6b7280; line-height: 1.6; }
.dark-mode .ug-step-content p { color: #9ca3af; }
.ug-tip {
    background: rgba(var(--ug-primary-rgb),.07);
    border-left: 3px solid var(--ug-primary);
    border-radius: 0 8px 8px 0;
    padding: .75rem 1rem;
    font-size: .83rem;
    margin-top: 1rem;
}
.ug-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
.ug-table th { background: #f8f9fc; font-weight: 600; padding: .6rem .8rem; border-bottom: 2px solid #e4e6fc; text-align: left; }
.dark-mode .ug-table th { background: #343d55; border-color: #3b4253; }
.ug-table td { padding: .55rem .8rem; border-bottom: 1px solid #f1f2f6; vertical-align: top; }
.dark-mode .ug-table td { border-color: #3b4253; }
.ug-badge { display:inline-block;padding:2px 8px;border-radius:20px;font-size:.72rem;font-weight:600; }
.ug-badge-primary { background:rgba(var(--ug-primary-rgb),.12);color:var(--ug-primary); }
.ug-badge-success { background:rgba(16,185,129,.12);color:#059669; }
.ug-shortcut { display:inline-block;background:#f1f2f6;border:1px solid #ddd;border-radius:4px;padding:2px 7px;font-family:monospace;font-size:.78rem;color:#374151; }
.dark-mode .ug-shortcut { background:#343d55;border-color:#4b5563;color:#d0d2d6; }
</style>
@endpush

<div class="container-fluid" style="padding: 1.5rem;">

    {{-- Hero --}}
    <div class="ug-hero d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div style="font-size:2rem;margin-bottom:.5rem;"><i class="fas fa-book-open"></i></div>
            <h1 style="font-size:1.75rem;font-weight:800;margin:0 0 .5rem;">NovaxPOS User Guide</h1>
            <p style="opacity:.8;margin:0;font-size:.9rem;max-width:520px;">
                Everything you need to run your POS system — from your first sale to advanced reporting.
                Bookmark this page or export it as a PDF for offline reference.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-start">
            <a href="{{ route('user-guide.print') }}" target="_blank"
               style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);padding:9px 20px;border-radius:8px;font-weight:600;font-size:.85rem;text-decoration:none;">
                <i class="fas fa-print" style="margin-right:4px;"></i> Print / Export PDF
            </a>
            <a href="{{ route('sale.pos') }}"
               style="background:#fff;color:var(--ug-primary);padding:9px 20px;border-radius:8px;font-weight:700;font-size:.85rem;text-decoration:none;">
                <i class="fas fa-bolt" style="margin-right:4px;"></i> Open POS
            </a>
        </div>
    </div>

    <div class="row g-4">

        {{-- Table of Contents --}}
        <div class="col-lg-3 d-none d-lg-block">
            <div class="ug-toc">
                <div style="font-weight:700;font-size:.85rem;margin-bottom:.75rem;">Contents</div>
                <div class="toc-section">Getting Started</div>
                <a href="#gs-overview">System Overview</a>
                <a href="#gs-login">Logging In</a>
                <a href="#gs-navigation">Navigation Guide</a>
                <div class="toc-section">Sales</div>
                <a href="#pos">POS Terminal</a>
                <a href="#sales-list">Sales Management</a>
                <a href="#quotations">Quotations</a>
                <a href="#returns">Returns & Exchanges</a>
                <div class="toc-section">Inventory</div>
                <a href="#products">Products</a>
                <a href="#categories">Categories & Brands</a>
                <a href="#purchases">Purchases</a>
                <a href="#transfers">Stock Transfers</a>
                <a href="#adjustments">Adjustments</a>
                <div class="toc-section">People</div>
                <a href="#customers">Customers</a>
                <a href="#suppliers">Suppliers</a>
                <a href="#billers">Billers</a>
                <a href="#users">Users & Roles</a>
                <div class="toc-section">Finance</div>
                <a href="#expenses">Expenses</a>
                <a href="#income">Income</a>
                <a href="#accounting">Accounting</a>
                <div class="toc-section">Reports</div>
                <a href="#reports">Reports Overview</a>
                <div class="toc-section">Settings</div>
                <a href="#settings">General Settings</a>
                <a href="#pos-settings">POS Settings</a>
                <div class="toc-section">Online Store</div>
                <a href="#store">Online Store Setup</a>
                <div class="toc-section">Support</div>
                <a href="#support">Support & Tickets</a>
            </div>
        </div>

        {{-- Content --}}
        <div class="col-lg-9">

            {{-- ── GETTING STARTED ── --}}
            <div id="gs-overview" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-house"></i></div>
                    <h2 class="ug-section-title">System Overview</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1.25rem;">
                        <strong>{{ $general_setting->site_title ?? 'NovaxPOS' }}</strong> is a comprehensive Point-of-Sale and business management platform. It covers the full retail workflow: inventory, purchasing, sales via POS or invoice, customer management, HR, accounting, and analytics.
                    </p>
                    <div class="row g-3">
                        @foreach([
                            ['fas fa-cash-register','POS Terminal','Fast cashier screen for in-person sales, barcode scanning, payment processing'],
                            ['fas fa-boxes-stacked','Inventory','Products, categories, brands, stock counts, adjustments and barcode printing'],
                            ['fas fa-sack-dollar','Sales & Finance','Invoices, quotations, returns, expenses, income and accounting accounts'],
                            ['fas fa-users','People','Customers, suppliers, billers, users and role-based permissions'],
                            ['fas fa-chart-line','Reports','Profit/loss, sales charts, customer dues, stock alerts and more'],
                            ['fas fa-gear','Settings','General config, invoice templates, payment gateways, taxes and more'],
                        ] as $card)
                        <div class="col-md-4">
                            <div style="background:#f8f9fc;border-radius:10px;padding:1rem;height:100%;">
                                <div style="font-size:1.3rem;margin-bottom:.4rem;color:var(--ug-primary);"><i class="{{ $card[0] }}"></i></div>
                                <div style="font-weight:600;font-size:.88rem;margin-bottom:.25rem;">{{ $card[1] }}</div>
                                <div style="font-size:.8rem;color:#6b7280;">{{ $card[2] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div id="gs-login" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-right-to-bracket"></i></div>
                    <h2 class="ug-section-title">Logging In</h2>
                </div>
                <div class="ug-section-body">
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Open the Login Page</h6><p>Navigate to your store URL. You will be redirected to the login screen.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Enter Credentials</h6><p>Type your email address and password, then click <strong>Login</strong>. Use your administrator-supplied credentials on first login.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">3</div><div class="ug-step-content"><h6>Dashboard</h6><p>After login you land on the Dashboard showing today's sales summary, low stock alerts, and quick-action shortcuts.</p></div></div>
                    <div class="ug-tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:4px;"></i> <strong>Forgot password?</strong> Use the "Forgot Password" link on the login page to receive a reset email.</div>
                </div>
            </div>

            <div id="gs-navigation" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-compass"></i></div>
                    <h2 class="ug-section-title">Navigation Guide</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">The left sidebar is organised into logical sections for easy access:</p>
                    <table class="ug-table">
                        <thead><tr><th>Section</th><th>What's Inside</th></tr></thead>
                        <tbody>
                            <tr><td><strong>Sales</strong></td><td>Grouped into: <strong>Sales</strong> (Sale list, Add sale, Instalment list, Import by CSV), <strong>Returns &amp; Exchanges</strong> (Sale return, Sale exchange), <strong>Promotions</strong> (Gift cards, Coupons), <strong>Fulfillment</strong> (Delivery, Packing slips, Challans, Couriers) — plus Quotations and Booking</td></tr>
                            <tr><td><strong>Inventory</strong></td><td>Products, Categories, Brands, Units, Barcode printing, Adjustments, Transfers, Stock count, Stock history, Low-stock alerts, Purchase orders, Goods received, Purchase returns, Valuation &amp; movement reports</td></tr>
                            <tr><td><strong>People</strong></td><td>Customers, Suppliers, Billers, Users, Sale Agents</td></tr>
                            <tr><td><strong>Finance</strong></td><td>Expenses and Income (categories, lists, entries)</td></tr>
                            <tr><td><strong>Accounting</strong></td><td>Accounts, Money transfers, Account statements, Balance sheet</td></tr>
                            <tr><td><strong>Workforce</strong></td><td>Employees, Departments, Designations, Shifts, Attendance, Holidays, Overtime, Leave, Payroll (if HRM is enabled)</td></tr>
                            <tr><td><strong>Channels</strong></td><td>WhatsApp messaging, Online Store, Catalogue QR</td></tr>
                            <tr><td><strong>Add-ons</strong></td><td>Manufacturing, Repair &amp; service jobs, WooCommerce, eCommerce, Gym (when enabled)</td></tr>
                            <tr><td><strong>Reports</strong></td><td>All business analytics reports and the Activity Log</td></tr>
                            <tr><td><strong>System</strong></td><td>Settings (General, POS, Invoice, Barcode, Tax, Payment gateways…), Warehouses, Tables, Languages, Currency, Roles &amp; Permissions, Backup</td></tr>
                            <tr><td><strong>Developer</strong></td><td>API Tokens for integrations</td></tr>
                            <tr><td><strong>Support</strong></td><td>Support tickets and this User Guide</td></tr>
                        </tbody>
                    </table>
                    <div class="ug-tip mt-3"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:4px;"></i> Click the <strong><i class="fas fa-bars"></i></strong> button in the top-left to collapse the sidebar for more screen space. Click again to expand.</div>
                </div>
            </div>

            {{-- ── POS ── --}}
            <div id="pos" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-cash-register"></i></div>
                    <h2 class="ug-section-title">POS Terminal</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">The POS is the fastest way to process in-person sales. Navigate to <strong>Sales → POS</strong> or click the POS shortcut in the sidebar.</p>
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Select Warehouse & Biller</h6><p>At the top of the POS screen, choose the active warehouse and biller for this session.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Add Products</h6><p>Search by name or barcode in the search bar, or browse by category. Click a product card to add it to the cart. Use a barcode scanner for speed.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">3</div><div class="ug-step-content"><h6>Adjust Quantities & Discounts</h6><p>In the cart, click the quantity to change it, or apply a line-item discount percentage directly.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">4</div><div class="ug-step-content"><h6>Select Customer (Optional)</h6><p>Search for an existing customer or add a new one on the fly. Required for due-payment tracking and loyalty points.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">5</div><div class="ug-step-content"><h6>Apply Payment</h6><p>Click <strong>Pay</strong>. Enter the amount for each payment method (cash, card, mobile money, etc). Click <strong>Submit</strong> to complete the sale.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">6</div><div class="ug-step-content"><h6>Print Receipt</h6><p>A receipt is generated automatically. Choose to print it, send by email, or share via WhatsApp.</p></div></div>
                    <div class="ug-tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:4px;"></i> <strong>Keyboard shortcuts:</strong> <span class="ug-shortcut">F2</span> focus search · <span class="ug-shortcut">F4</span> open payment · <span class="ug-shortcut">Esc</span> clear cart</div>
                </div>
            </div>

            {{-- ── SALES MANAGEMENT ── --}}
            <div id="sales-list" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-clipboard-list"></i></div>
                    <h2 class="ug-section-title">Sales Management</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Go to <strong>Sales → Sale List</strong> to see all transactions.</p>
                    <table class="ug-table">
                        <thead><tr><th>Action</th><th>How</th></tr></thead>
                        <tbody>
                            <tr><td>View sale detail</td><td>Click the eye icon on any row</td></tr>
                            <tr><td>Add a sale (invoice)</td><td>Sales → Add Sale, then fill customer, products, payment</td></tr>
                            <tr><td>Record partial payment</td><td>Open sale detail → click <strong>Add Payment</strong></td></tr>
                            <tr><td>Print invoice</td><td>Open detail → <strong>Print</strong> button</td></tr>
                            <tr><td>Import sales via CSV</td><td>Sales → Import Sale By CSV</td></tr>
                            <tr><td>Export to Excel/PDF</td><td>Use the DataTable export buttons above the table</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="quotations" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-file-lines"></i></div>
                    <h2 class="ug-section-title">Quotations</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Create price quotes for customers without recording a sale. When approved, convert to an actual sale in one click.</p>
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Create Quotation</h6><p>Go to <strong>Sales → Quotation → Add Quotation</strong>. Fill in customer, products and validity date.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Send to Customer</h6><p>Open the quotation and click <strong>Print</strong> or <strong>Email</strong> to share the PDF with your customer.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">3</div><div class="ug-step-content"><h6>Convert to Sale</h6><p>When the customer approves, open the quotation and click <strong>Convert to Sale</strong>. All line items transfer automatically.</p></div></div>
                </div>
            </div>

            <div id="returns" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-rotate-left"></i></div>
                    <h2 class="ug-section-title">Returns & Exchanges</h2>
                </div>
                <div class="ug-section-body">
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Sale Return</h6><p>Go to <strong>Sales → Returns &amp; Exchanges → Sale Return</strong>. Find the original sale, enter returned quantities and reason. Stock is automatically restocked.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Purchase Return</h6><p>Go to <strong>Inventory → Purchase Return</strong> to return goods to a supplier.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">3</div><div class="ug-step-content"><h6>Sale Exchange</h6><p>Go to <strong>Sales → Returns &amp; Exchanges → Sale Exchange</strong> to swap items in an existing sale.</p></div></div>
                </div>
            </div>

            {{-- ── PRODUCTS ── --}}
            <div id="products" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-boxes-stacked"></i></div>
                    <h2 class="ug-section-title">Products</h2>
                </div>
                <div class="ug-section-body">
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Add a Product</h6><p>Go to <strong>Inventory → Products → Add Product</strong>. Fill in the name, SKU/barcode, category, cost price, selling price, and opening stock.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Set Pricing Tiers</h6><p>You can set a <strong>Retail Price</strong>, <strong>Wholesale Price</strong>, and <strong>Promotional Price</strong> per product. The POS applies the appropriate price based on customer type.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">3</div><div class="ug-step-content"><h6>Product Variants</h6><p>For products with size/colour variations, enable <strong>Variants</strong> during product creation and define the variant options.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">4</div><div class="ug-step-content"><h6>Print Barcodes</h6><p>Go to <strong>Inventory → Print Barcode</strong>. Filter by warehouse and date range, adjust quantities, and print retail and wholesale labels.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">5</div><div class="ug-step-content"><h6>Stock Count</h6><p>Go to <strong>Inventory → Stock Count</strong> to reconcile physical counts against system stock.</p></div></div>
                    <div class="ug-tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:4px;"></i> <strong>Tip:</strong> Set a <strong>minimum stock quantity</strong> on each product to receive low-stock alerts on the dashboard and in reports.</div>
                </div>
            </div>

            <div id="categories" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-tags"></i></div>
                    <h2 class="ug-section-title">Categories & Brands</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Organise products with categories (hierarchical) and brands. Both are managed under <strong>Inventory → Products</strong>.</p>
                    <table class="ug-table">
                        <thead><tr><th>Item</th><th>Purpose</th><th>Where</th></tr></thead>
                        <tbody>
                            <tr><td>Category</td><td>Group products (e.g. Electronics, Clothing). Supports parent/child.</td><td>Inventory → Products → Category</td></tr>
                            <tr><td>Brand</td><td>Track product manufacturer or brand name.</td><td>Inventory → Products → Brand</td></tr>
                            <tr><td>Unit</td><td>Define units of measure (pcs, kg, litre, box…).</td><td>Inventory → Products → Unit</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="purchases" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-bag-shopping"></i></div>
                    <h2 class="ug-section-title">Purchases</h2>
                </div>
                <div class="ug-section-body">
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Add Purchase</h6><p>Go to <strong>Inventory → Purchase Orders → Add Purchase</strong>. Select supplier, warehouse, and add product lines with quantities and cost prices. Stock is automatically updated on save.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Record Payment to Supplier</h6><p>Open a purchase → click <strong>Add Payment</strong> to record partial or full payments.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">3</div><div class="ug-step-content"><h6>Purchase Return</h6><p>Open a purchase → click <strong>Return</strong>, or go to Inventory → Purchase Return.</p></div></div>
                </div>
            </div>

            <div id="transfers" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-right-left"></i></div>
                    <h2 class="ug-section-title">Stock Transfers</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Move stock between warehouses. Go to <strong>Inventory → Stock Transfers → Add Transfer</strong>.</p>
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Select From/To Warehouse</h6><p>Choose the source and destination warehouses and the transfer date.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Add Products</h6><p>Search and add each product with the quantity to transfer. Click Save.</p></div></div>
                    <div class="ug-tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:4px;"></i> Transfers reduce stock at the source warehouse and increase it at the destination immediately upon saving.</div>
                </div>
            </div>

            <div id="adjustments" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-scale-balanced"></i></div>
                    <h2 class="ug-section-title">Stock Adjustments</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Use adjustments to correct discrepancies, write off damaged goods, or add untracked stock. Go to <strong>Inventory → Stock Adjustments</strong>.</p>
                    <table class="ug-table">
                        <thead><tr><th>Type</th><th>Effect</th></tr></thead>
                        <tbody>
                            <tr><td>Addition (+)</td><td>Increases stock quantity</td></tr>
                            <tr><td>Subtraction (−)</td><td>Decreases stock quantity</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── PEOPLE ── --}}
            <div id="customers" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-user"></i></div>
                    <h2 class="ug-section-title">Customers</h2>
                </div>
                <div class="ug-section-body">
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Add Customer</h6><p>Go to <strong>People → Customer List → Add Customer</strong>. Enter name, phone, email, address and customer group.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Customer Groups</h6><p>Assign customers to groups (e.g. Wholesale, Retail, VIP). Groups can have special pricing tiers applied automatically in the POS.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">3</div><div class="ug-step-content"><h6>Customer Ledger</h6><p>View all transactions, balances, and due amounts from the customer detail page.</p></div></div>
                    <div class="ug-tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:4px;"></i> Use <strong>Customer Report</strong> in Reports to see purchase history, dues and loyalty points per customer.</div>
                </div>
            </div>

            <div id="suppliers" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-industry"></i></div>
                    <h2 class="ug-section-title">Suppliers</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Manage all your vendors under <strong>People → Supplier List</strong>. Each supplier has a purchase history and outstanding balance that you can track through the Supplier Report.</p>
                </div>
            </div>

            <div id="billers" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-file-invoice"></i></div>
                    <h2 class="ug-section-title">Billers</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">A biller is the business identity that issues an invoice or receipt — typically a branch, outlet, or legal entity. Every sale is recorded against a biller, and its name, logo and contact details appear on the printed invoice.</p>
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Add a Biller</h6><p>Go to <strong>People → Biller List → Add Biller</strong>. Enter the name, company name, VAT/registration number, email, phone and address that should appear on invoices.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Assign to Users</h6><p>When creating a user (<strong>People → User List</strong>), assign their default biller. Sales made by that user are recorded under it automatically.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">3</div><div class="ug-step-content"><h6>Select at the POS</h6><p>At the top of the POS screen you can switch the active biller for the session — useful when one till serves multiple branches or entities.</p></div></div>
                    <div class="ug-tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:4px;"></i> Use the <strong>Biller Report</strong> in Reports to compare sales performance across billers/branches.</div>
                </div>
            </div>

            <div id="users" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-users"></i></div>
                    <h2 class="ug-section-title">Users & Roles</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Control who can access what using role-based permissions.</p>
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Create a Role</h6><p>Go to <strong>System → Roles &amp; Permissions</strong>. Create roles like "Cashier", "Manager", "Warehouse Staff" and tick the permissions each role should have.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Add a User</h6><p>Go to <strong>People → User List → Add User</strong>. Assign the role, warehouse and biller.</p></div></div>
                    <div class="ug-tip"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:4px;"></i> Roles 1 (Super Admin) and 2 (Admin) have full access by default. Custom roles see only what you grant them.</div>
                </div>
            </div>

            {{-- ── FINANCE ── --}}
            <div id="expenses" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <h2 class="ug-section-title">Expenses</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Track all business outgoings. Go to <strong>Finance → Expense</strong>.</p>
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Create Expense Category</h6><p>First set up categories (e.g. Rent, Utilities, Salaries) so expenses can be grouped in reports.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Add Expense</h6><p>Go to Expense List → Add Expense. Select category, amount, account, reference and attach a receipt image.</p></div></div>
                </div>
            </div>

            <div id="income" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-sack-dollar"></i></div>
                    <h2 class="ug-section-title">Income</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Record non-sale income (rent, commissions, etc.) under <strong>Finance → Income</strong>. Works the same as expenses — create categories first, then add income entries.</p>
                </div>
            </div>

            <div id="accounting" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-book"></i></div>
                    <h2 class="ug-section-title">Accounting</h2>
                </div>
                <div class="ug-section-body">
                    <table class="ug-table">
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

            {{-- ── REPORTS ── --}}
            <div id="reports" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-chart-line"></i></div>
                    <h2 class="ug-section-title">Reports Overview</h2>
                </div>
                <div class="ug-section-body">
                    <table class="ug-table">
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
                    <div class="ug-tip mt-3"><i class="fas fa-lightbulb" style="color:var(--ug-primary);margin-right:4px;"></i> All reports support date range filtering and can be exported to <strong>Excel, PDF or CSV</strong> using the export buttons.</div>
                </div>
            </div>

            {{-- ── SETTINGS ── --}}
            <div id="settings" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-gear"></i></div>
                    <h2 class="ug-section-title">General Settings</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Go to <strong>System → Settings → General Settings</strong> to configure your business profile.</p>
                    <table class="ug-table">
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

            <div id="pos-settings" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-sliders"></i></div>
                    <h2 class="ug-section-title">POS Settings</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Go to <strong>System → POS Settings</strong> to customise the cashier screen.</p>
                    <table class="ug-table">
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

            {{-- ── ONLINE STORE ── --}}
            <div id="store" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-cart-shopping"></i></div>
                    <h2 class="ug-section-title">Online Store</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Share a public product catalogue and accept online orders. Go to <strong>Channels → Online Store</strong>.</p>
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Enable the Store</h6><p>Toggle <strong>Store Enabled</strong> to make it publicly accessible at your store URL.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Configure Details</h6><p>Add your store name, description, banner image, and WhatsApp number for order notifications.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">3</div><div class="ug-step-content"><h6>Share QR Code</h6><p>Click <strong>Generate QR</strong> to get a printable QR code customers can scan to visit your store.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">4</div><div class="ug-step-content"><h6>Manage Orders</h6><p>Incoming orders appear in <strong>Online Store → Orders</strong>. Update statuses from Pending → Processing → Shipped → Delivered.</p></div></div>
                </div>
            </div>

            {{-- ── SUPPORT ── --}}
            <div id="support" class="ug-section">
                <div class="ug-section-header">
                    <div class="ug-section-icon"><i class="fas fa-ticket"></i></div>
                    <h2 class="ug-section-title">Support & Tickets</h2>
                </div>
                <div class="ug-section-body">
                    <p style="font-size:.88rem;color:#6b7280;margin-bottom:1rem;">Need help? Submit a support ticket and the platform administrator will respond.</p>
                    <div class="ug-step"><div class="ug-step-num">1</div><div class="ug-step-content"><h6>Open a Ticket</h6><p>Go to <strong>Support → Support Tickets → Create Ticket</strong>. Describe your issue, add screenshots if helpful, and submit.</p></div></div>
                    <div class="ug-step"><div class="ug-step-num">2</div><div class="ug-step-content"><h6>Track Status</h6><p>Ticket statuses: <span class="ug-badge ug-badge-primary">Open</span> → <span class="ug-badge" style="background:rgba(245,158,11,.12);color:#d97706;">In Progress</span> → <span class="ug-badge ug-badge-success">Resolved</span></p></div></div>
                </div>
            </div>

        </div>{{-- /col --}}
    </div>{{-- /row --}}
</div>
@endsection

@push('scripts')
<script>
// Highlight active TOC item on scroll
(function(){
    var links = document.querySelectorAll('.ug-toc a:not(.toc-section)');
    var sections = [];
    links.forEach(function(a){
        var id = a.getAttribute('href');
        if(id && id.startsWith('#')) {
            var el = document.querySelector(id);
            if(el) sections.push({el:el, a:a});
        }
    });
    window.addEventListener('scroll', function(){
        var scrollY = window.scrollY + 100;
        var current = null;
        sections.forEach(function(s){
            if(s.el.offsetTop <= scrollY) current = s;
        });
        links.forEach(function(a){ a.classList.remove('active'); });
        if(current) current.a.classList.add('active');
    }, {passive:true});
})();
</script>
@endpush
