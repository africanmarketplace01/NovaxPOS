<ul id="side-main-menu" class="side-menu list-unstyled" style="margin-top:-20px;">

    {{-- ═══ DASHBOARD ═══ --}}
    <li>
        <a class="{{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('/dashboard') }}">
            <i class="dripicons-meter"></i><span>{{ __('db.dashboard') }}</span>
        </a>
    </li>

    {{-- ═══ POS — gated by plan feature 'pos' via sidebar_pos permission ══ --}}
    @can('sidebar_pos')
        <li>
            <a class="{{ request()->is('pos') ? 'active' : '' }}" href="{{ route('sale.pos') }}">
                <i class="dripicons-monitor"></i><span>POS Terminal</span>
            </a>
        </li>
    @endcan

    {{-- ═══ MARKETPLACE REQUESTS (customer product-sourcing requests) ═══ --}}
    <li>
        <a class="{{ request()->is('marketplace-requests*') ? 'active' : '' }}" href="{{ route('vendor.sourcing.index') }}">
            <i class="dripicons-message"></i><span>Marketplace Requests</span>
            @php $__openReq = \App\Http\Controllers\Marketplace\VendorSourcingController::openCountBadge(); @endphp
            @if($__openReq)<span class="badge badge-danger" style="margin-left:6px">{{ $__openReq }}</span>@endif
        </a>
    </li>

    {{-- ═══ MARKETPLACE ORDERS + LISTINGS (global marketplace) ═══ --}}
    @if (config('database.connections.saleprosaas_landlord'))
        <li>
            <a class="{{ request()->routeIs('store.orders') ? 'active' : '' }}" href="{{ route('store.orders') }}">
                <i class="dripicons-cart"></i><span>Orders</span>
            </a>
        </li>
        <li>
            <a class="{{ request()->routeIs('store.marketplace.listings') ? 'active' : '' }}" href="{{ route('store.marketplace.listings') }}">
                <i class="dripicons-store"></i><span>Manage Listings</span>
            </a>
        </li>
        <li>
            <a class="{{ request()->routeIs('store.delivery.*') ? 'active' : '' }}" href="{{ route('store.delivery.index') }}">
                <i class="dripicons-rocket"></i><span>Delivery</span>
            </a>
        </li>
    @endif

    {{-- ━━━ SECTION: SALES ━━━ --}}
    <li class="sidebar-section-label">
        <span>{{ __('db.Sale') }}</span>
    </li>

    @can('sidebar_sale')

        {{-- Sales --}}
        @canany(['sales-index', 'sales-add', 'sales-import'])
            <li>
                <a href="#sale" aria-expanded="false" data-toggle="collapse">
                    <i class="dripicons-cart"></i><span>{{ __('db.Sale') }}</span>
                </a>
                <ul id="sale" class="collapse list-unstyled">
                    @can('sales-index')
                        <li><a class="{{ request()->is('sales') ? 'active' : '' }}"
                                href="{{ route('sales.index') }}">{{ __('db.Sale List') }}</a></li>
                    @endcan
                    @can('sales-add')
                        <li><a class="{{ request()->is('sales/create') ? 'active' : '' }}"
                                href="{{ route('sales.create') }}">{{ __('db.Add Sale') }}</a></li>
                    @endcan
                    @can('sales-index')
                        <li><a class="{{ request()->is('installmentplan') ? 'active' : '' }}"
                                href="{{ route('installmentplan.index') }}">{{ __('db.Instalment List') }}</a></li>
                    @endcan
                    @can('sales-import')
                        <li><a class="{{ request()->is('sales/sale_by_csv') ? 'active' : '' }}"
                                href="{{ url('sales/sale_by_csv') }}">{{ __('db.Import Sale By CSV') }}</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany

        {{-- Returns & Exchanges --}}
        @canany(['returns-index', 'exchange-index'])
            <li>
                <a href="#sale-returns" aria-expanded="false" data-toggle="collapse">
                    <i class="dripicons-return"></i><span>Returns &amp; Exchanges</span>
                </a>
                <ul id="sale-returns" class="collapse list-unstyled">
                    @can('returns-index')
                        <li><a class="{{ request()->is('return-sale') ? 'active' : '' }}"
                                href="{{ route('return-sale.index') }}">{{ __('db.Sale Return') }}</a></li>
                    @endcan
                    @can('exchange-index')
                        <li><a class="{{ request()->is('exchange') ? 'active' : '' }}"
                                href="{{ route('exchange.index') }}">{{ __('db.Sale Exchange') }}</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany

        {{-- Promotions --}}
        @canany(['gift_card', 'coupon'])
            <li>
                <a href="#sale-promotions" aria-expanded="false" data-toggle="collapse">
                    <i class="dripicons-ticket"></i><span>Promotions</span>
                </a>
                <ul id="sale-promotions" class="collapse list-unstyled">
                    @can('gift_card')
                        <li><a class="{{ request()->is('gift_cards') ? 'active' : '' }}"
                                href="{{ route('gift_cards.index') }}">{{ __('db.Gift Card List') }}</a></li>
                    @endcan
                    @can('coupon')
                        <li><a class="{{ request()->is('coupons') ? 'active' : '' }}"
                                href="{{ route('coupons.index') }}">{{ __('db.Coupon List') }}</a></li>
                    @endcan
                </ul>
            </li>
        @endcanany

        {{-- Fulfillment --}}
        <li>
            <a href="#sale-fulfillment" aria-expanded="false" data-toggle="collapse">
                <i class="dripicons-rocket"></i><span>Fulfillment</span>
            </a>
            <ul id="sale-fulfillment" class="collapse list-unstyled">
                @can('delivery')
                    <li><a class="{{ request()->is('delivery') ? 'active' : '' }}"
                            href="{{ route('delivery.index') }}">{{ __('db.Delivery List') }}</a></li>
                @endcan
                @can('packing_slip_challan')
                    <li><a class="{{ request()->is('packing-slips') ? 'active' : '' }}"
                            href="{{ route('packingSlip.index') }}">{{ __('db.Packing Slip List') }}</a></li>
                    <li><a class="{{ request()->is('challans') ? 'active' : '' }}"
                            href="{{ route('challan.index') }}">{{ __('db.Challan List') }}</a></li>
                @endcan
                <li><a class="{{ request()->is('couriers') ? 'active' : '' }}"
                        href="{{ route('couriers.index') }}">{{ __('db.Courier List') }}</a></li>
            </ul>
        </li>

    @endcan

    @can('sidebar_quotation')
        @php $showQuotationMenu = false; @endphp
        @can('quotes-index')
            @php $showQuotationMenu = true; @endphp
        @endcan
        @can('quotes-add')
            @php $showQuotationMenu = true; @endphp
        @endcan
        @isset($showQuotationMenu)
            <li>
                <a href="#quotation" aria-expanded="false" data-toggle="collapse">
                    <i class="dripicons-document"></i><span>{{ __('db.Quotation') }}</span>
                </a>
                <ul id="quotation" class="collapse list-unstyled">
                    @can('quotes-index')
                        <li><a class="{{ request()->is('quotations') ? 'active' : '' }}"
                                href="{{ route('quotations.index') }}">{{ __('db.Quotation List') }}</a></li>
                    @endcan
                    @can('quotes-add')
                        <li><a class="{{ request()->is('quotations/create') ? 'active' : '' }}"
                                href="{{ route('quotations.create') }}">{{ __('db.Add Quotation') }}</a></li>
                    @endcan
                </ul>
            </li>
        @endisset
    @endcan

    @can('booking')
        <li>
            <a class="{{ request()->is('bookings/calendar') ? 'active' : '' }}"
                href="{{ route('booking.index') }}">
                <i class="dripicons-calendar"></i><span>{{ __('db.Booking') }}</span>
            </a>
        </li>
    @endcan

    {{-- ━━━ SECTION: INVENTORY ━━━ --}}
    <li class="sidebar-section-label">
        <span>Inventory</span>
    </li>

    @php
        $invActive = request()->is(
            'products','products/create','products/*',
            'category','brand','unit','products/print_barcode',
            'qty_adjustment','qty_adjustment/*','stock-count','damage-stock',
            'transfers','transfers/create','transfers/*',
            'purchases','purchases/create','purchases/*','return-purchase',
            'supplier',
            'report/stock','report/product_report','report/product-expiry',
            'report/product_quantity_alert','report/warehouse_stock'
        );
    @endphp
    <li>
        <a href="#inventory-main" aria-expanded="{{ $invActive ? 'true' : 'false' }}" data-toggle="collapse">
            <i class="dripicons-stack"></i><span>Inventory</span>
        </a>
        <ul id="inventory-main" class="collapse list-unstyled {{ $invActive ? 'show' : '' }}">

            {{-- ── 1. PRODUCTS ─────────────────────────────── --}}
            @can('sidebar_product')
            @php $invProdActive = request()->is('products','products/create','category','brand','unit','products/print_barcode'); @endphp
            <li>
                <a href="#inv-products" data-toggle="collapse"
                   aria-expanded="{{ $invProdActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $invProdActive ? '' : 'collapsed' }}">
                    <i class="dripicons-list" style="font-size:.8rem"></i> Products
                </a>
                <ul id="inv-products" class="collapse list-unstyled sidebar-sub-list {{ $invProdActive ? 'show' : '' }}">
                    @can('products-index')
                        <li><a class="{{ request()->is('products') ? 'active' : '' }}"
                                href="{{ route('products.index') }}">{{ __('db.product_list') }}</a></li>
                    @endcan
                    @can('products-add')
                        <li><a class="{{ request()->is('products/create') ? 'active' : '' }}"
                                href="{{ route('products.create') }}">{{ __('db.add_product') }}</a></li>
                    @endcan
                    @can('categories-index')
                        <li><a class="{{ request()->is('category') ? 'active' : '' }}"
                                href="{{ route('category.index') }}">{{ __('db.Product Categories') }}</a></li>
                    @endcan
                    @can('brand')
                        <li><a class="{{ request()->is('brand') ? 'active' : '' }}"
                                href="{{ route('brand.index') }}">{{ __('db.Brand') }}</a></li>
                    @endcan
                    @can('unit')
                        <li><a class="{{ request()->is('unit') ? 'active' : '' }}"
                                href="{{ route('unit.index') }}">{{ __('db.Units of Measure') }}</a></li>
                    @endcan
                    @can('print_barcode')
                        <li><a class="{{ request()->is('products/print_barcode') ? 'active' : '' }}"
                                href="{{ route('product.printBarcode') }}">{{ __('db.print_barcode') }}</a></li>
                    @endcan
                </ul>
            </li>
            @endcan

            {{-- ── 2. STOCK MANAGEMENT ──────────────────────── --}}
            @php $invStockActive = request()->is('qty_adjustment','qty_adjustment/*','stock-count','damage-stock','transfers','transfers/*'); @endphp
            <li>
                <a href="#inv-stock" data-toggle="collapse"
                   aria-expanded="{{ $invStockActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $invStockActive ? '' : 'collapsed' }}">
                    <i class="dripicons-archive" style="font-size:.8rem"></i> Stock Management
                </a>
                <ul id="inv-stock" class="collapse list-unstyled sidebar-sub-list {{ $invStockActive ? 'show' : '' }}">
                    @can('adjustment')
                        <li><a class="{{ request()->is('qty_adjustment') ? 'active' : '' }}"
                                href="{{ route('qty_adjustment.index') }}">{{ __('db.Stock Adjustments') }}</a></li>
                    @endcan
                    @can('sidebar_transfer')
                        @can('transfers-index')
                            <li><a class="{{ request()->is('transfers') ? 'active' : '' }}"
                                    href="{{ route('transfers.index') }}">{{ __('db.Stock Transfers') }}</a></li>
                        @endcan
                        @can('transfers-add')
                            <li><a class="{{ request()->is('transfers/create') ? 'active' : '' }}"
                                    href="{{ route('transfers.create') }}">{{ __('db.Add Transfer') }}</a></li>
                        @endcan
                        @can('transfers-import')
                            <li><a class="{{ request()->is('transfers/transfer_by_csv') ? 'active' : '' }}"
                                    href="{{ url('transfers/transfer_by_csv') }}">{{ __('db.Import Transfer By CSV') }}</a></li>
                        @endcan
                    @endcan
                    @can('stock_count')
                        <li><a class="{{ request()->is('stock-count') ? 'active' : '' }}"
                                href="{{ route('stock-count.index') }}">{{ __('db.Stock Count') }}</a></li>
                    @endcan
                    @can('damage-stock')
                        <li><a class="{{ request()->is('damage-stock') ? 'active' : '' }}"
                                href="{{ route('damage-stock.index') }}">{{ __('db.Stock History') }}</a></li>
                    @endcan
                    @can('product-qty-alert')
                        <li><a class="{{ request()->is('report/product_quantity_alert') ? 'active' : '' }}"
                                href="{{ route('report.qtyAlert') }}">{{ __('db.Low Stock Alerts') }}</a></li>
                    @endcan
                </ul>
            </li>

            {{-- ── 3. PURCHASING ────────────────────────────── --}}
            @can('sidebar_purchase')
            @php $invPurchActive = request()->is('purchases','purchases/*','return-purchase','supplier'); @endphp
            <li>
                <a href="#inv-purchasing" data-toggle="collapse"
                   aria-expanded="{{ $invPurchActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $invPurchActive ? '' : 'collapsed' }}">
                    <i class="dripicons-card" style="font-size:.8rem"></i> Purchasing
                </a>
                <ul id="inv-purchasing" class="collapse list-unstyled sidebar-sub-list {{ $invPurchActive ? 'show' : '' }}">
                    @can('suppliers-index')
                        <li><a class="{{ request()->is('supplier') ? 'active' : '' }}"
                                href="{{ route('supplier.index') }}">{{ __('db.Supplier List') }}</a></li>
                    @endcan
                    @can('purchases-index')
                        <li><a class="{{ request()->is('purchases') ? 'active' : '' }}"
                                href="{{ route('purchases.index') }}">{{ __('db.Purchase Orders') }}</a></li>
                    @endcan
                    @can('purchases-add')
                        <li><a class="{{ request()->is('purchases/create') ? 'active' : '' }}"
                                href="{{ route('purchases.create') }}">{{ __('db.Goods Received') }}</a></li>
                    @endcan
                    @can('purchases-import')
                        <li><a class="{{ request()->is('purchases/purchase_by_csv') ? 'active' : '' }}"
                                href="{{ url('purchases/purchase_by_csv') }}">{{ __('db.Import Purchase By CSV') }}</a></li>
                    @endcan
                    @can('purchase-return-index')
                        <li><a class="{{ request()->is('return-purchase') ? 'active' : '' }}"
                                href="{{ route('return-purchase.index') }}">{{ __('db.Purchase Return') }}</a></li>
                    @endcan
                </ul>
            </li>
            @endcan

            {{-- ── 4. REPORTS ───────────────────────────────── --}}
            @php $invRepActive = request()->is('report/stock','report/product_report','report/product-expiry','report/product_quantity_alert','report/warehouse_stock'); @endphp
            <li>
                <a href="#inv-reports" data-toggle="collapse"
                   aria-expanded="{{ $invRepActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $invRepActive ? '' : 'collapsed' }}">
                    <i class="dripicons-graph-bar" style="font-size:.8rem"></i> Reports
                </a>
                <ul id="inv-reports" class="collapse list-unstyled sidebar-sub-list {{ $invRepActive ? 'show' : '' }}">
                    @can('warehouse-stock-report')
                        <li><a class="{{ request()->is('report/warehouse_stock') ? 'active' : '' }}"
                                href="{{ route('report.warehouseStock') }}">{{ __('db.Inventory Valuation') }}</a></li>
                    @endcan
                    @can('stock-report')
                        <li><a class="{{ request()->is('report/stock') ? 'active' : '' }}"
                                href="{{ route('report.stock') }}">{{ __('db.Stock Movement') }}</a></li>
                    @endcan
                    @can('product-expiry-report')
                        <li><a class="{{ request()->is('report/product-expiry') ? 'active' : '' }}"
                                href="{{ route('report.productExpiry') }}">{{ __('db.Expiry Report') }}</a></li>
                    @endcan
                    @can('product-report')
                        <li><a class="{{ request()->is('report/product_report') ? 'active' : '' }}"
                                href="{{ url('report/product_report') }}">{{ __('db.Product Performance') }}</a></li>
                    @endcan
                    @can('product-qty-alert')
                        <li><a class="{{ request()->is('report/product_quantity_alert') ? 'active' : '' }}"
                                href="{{ route('report.qtyAlert') }}">{{ __('db.Low Stock Alerts') }}</a></li>
                    @endcan
                </ul>
            </li>

        </ul>
    </li>

    {{-- ━━━ SECTION: PEOPLE ━━━ --}}
    <li class="sidebar-section-label">
        <span>{{ __('db.People') }}</span>
    </li>

    @can('sidebar_people')
    @php $peopleActive = request()->is('customer','supplier','biller','user','sale-agents'); @endphp
    <li>
        <a href="#people-main" aria-expanded="{{ $peopleActive ? 'true' : 'false' }}" data-toggle="collapse">
            <i class="dripicons-user"></i><span>{{ __('db.People') }}</span>
        </a>
        <ul id="people-main" class="collapse list-unstyled {{ $peopleActive ? 'show' : '' }}">

            {{-- ── Customer ────────────────────────────────── --}}
            @can('customers-index')
            @php $custActive = request()->is('customer'); @endphp
            <li>
                <a href="#ppl-customer" data-toggle="collapse"
                   aria-expanded="{{ $custActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $custActive ? '' : 'collapsed' }}">
                    <i class="dripicons-user" style="font-size:.8rem"></i> {{ __('db.Customer') }}
                </a>
                <ul id="ppl-customer" class="collapse list-unstyled sidebar-sub-list {{ $custActive ? 'show' : '' }}">
                    <li><a class="{{ request()->is('customer') ? 'active' : '' }}"
                            href="{{ route('customer.index') }}">{{ __('db.Customer List') }}</a></li>
                </ul>
            </li>
            @endcan

            {{-- ── Supplier ─────────────────────────────────── --}}
            @can('suppliers-index')
            @php $suppActive = request()->is('supplier'); @endphp
            <li>
                <a href="#ppl-supplier" data-toggle="collapse"
                   aria-expanded="{{ $suppActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $suppActive ? '' : 'collapsed' }}">
                    <i class="dripicons-user-id" style="font-size:.8rem"></i> {{ __('db.Supplier') }}
                </a>
                <ul id="ppl-supplier" class="collapse list-unstyled sidebar-sub-list {{ $suppActive ? 'show' : '' }}">
                    <li><a class="{{ request()->is('supplier') ? 'active' : '' }}"
                            href="{{ route('supplier.index') }}">{{ __('db.Supplier List') }}</a></li>
                </ul>
            </li>
            @endcan

            {{-- ── Biller ───────────────────────────────────── --}}
            @can('billers-index')
            @php $billActive = request()->is('biller'); @endphp
            <li>
                <a href="#ppl-biller" data-toggle="collapse"
                   aria-expanded="{{ $billActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $billActive ? '' : 'collapsed' }}">
                    <i class="dripicons-card" style="font-size:.8rem"></i> {{ __('db.Biller') }}
                </a>
                <ul id="ppl-biller" class="collapse list-unstyled sidebar-sub-list {{ $billActive ? 'show' : '' }}">
                    <li><a class="{{ request()->is('biller') ? 'active' : '' }}"
                            href="{{ route('biller.index') }}">{{ __('db.Biller List') }}</a></li>
                </ul>
            </li>
            @endcan

            {{-- ── Users ────────────────────────────────────── --}}
            @php $usersActive = request()->is('user','sale-agents'); @endphp
            <li>
                <a href="#ppl-users" data-toggle="collapse"
                   aria-expanded="{{ $usersActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $usersActive ? '' : 'collapsed' }}">
                    <i class="dripicons-user-group" style="font-size:.8rem"></i> {{ __('db.Users') }}
                </a>
                <ul id="ppl-users" class="collapse list-unstyled sidebar-sub-list {{ $usersActive ? 'show' : '' }}">
                    @can('users-index')
                        <li><a class="{{ request()->is('user') ? 'active' : '' }}"
                                href="{{ route('user.index') }}">{{ __('db.User List') }}</a></li>
                    @endcan
                    @can('sale-agents')
                        <li><a class="{{ request()->is('sale-agents') ? 'active' : '' }}"
                                href="{{ route('sale-agents.index') }}">{{ __('db.Sale Agents') }}</a></li>
                    @endcan
                </ul>
            </li>

        </ul>
    </li>
    @endcan

    {{-- ━━━ SECTION: FINANCE ━━━ --}}
    <li class="sidebar-section-label">
        <span>Finance</span>
    </li>

    @php $financeActive = request()->is('expense_categories','expenses','income_categories','incomes'); @endphp
    <li>
        <a href="#finance-main" aria-expanded="{{ $financeActive ? 'true' : 'false' }}" data-toggle="collapse">
            <i class="dripicons-wallet"></i><span>Finance</span>
        </a>
        <ul id="finance-main" class="collapse list-unstyled {{ $financeActive ? 'show' : '' }}">

            {{-- ── Expense ──────────────────────────────────── --}}
            @can('sidebar_expense')
            @php $expActive = request()->is('expense_categories','expenses'); @endphp
            <li>
                <a href="#fin-expense" data-toggle="collapse"
                   aria-expanded="{{ $expActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $expActive ? '' : 'collapsed' }}">
                    <i class="dripicons-wallet" style="font-size:.8rem"></i> {{ __('db.Expense') }}
                </a>
                <ul id="fin-expense" class="collapse list-unstyled sidebar-sub-list {{ $expActive ? 'show' : '' }}">
                    @can('expense-categories')
                        <li><a class="{{ request()->is('expense_categories') ? 'active' : '' }}"
                                href="{{ route('expense_categories.index') }}">{{ __('db.Expense Category') }}</a></li>
                    @endcan
                    @can('expenses-index')
                        <li><a class="{{ request()->is('expenses') ? 'active' : '' }}"
                                href="{{ route('expenses.index') }}">{{ __('db.Expense List') }}</a></li>
                    @endcan
                    @can('expenses-add')
                        <li><a id="add-expense" href="">{{ __('db.Add Expense') }}</a></li>
                    @endcan
                </ul>
            </li>
            @endcan

            {{-- ── Income ───────────────────────────────────── --}}
            @can('sidebar_income')
            @php $incActive = request()->is('income_categories','incomes'); @endphp
            <li>
                <a href="#fin-income" data-toggle="collapse"
                   aria-expanded="{{ $incActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $incActive ? '' : 'collapsed' }}">
                    <i class="dripicons-rocket" style="font-size:.8rem"></i> {{ __('db.Income') }}
                </a>
                <ul id="fin-income" class="collapse list-unstyled sidebar-sub-list {{ $incActive ? 'show' : '' }}">
                    @can('income-categories')
                        <li><a class="{{ request()->is('income_categories') ? 'active' : '' }}"
                                href="{{ route('income_categories.index') }}">{{ __('db.Income Category') }}</a></li>
                    @endcan
                    @can('incomes-index')
                        <li><a class="{{ request()->is('incomes') ? 'active' : '' }}"
                                href="{{ route('incomes.index') }}">{{ __('db.Income List') }}</a></li>
                    @endcan
                    @can('incomes-add')
                        <li><a id="add-income" href="">{{ __('db.Add Income') }}</a></li>
                    @endcan
                </ul>
            </li>
            @endcan

        </ul>
    </li>

    {{-- ━━━ SECTION: ACCOUNTING ━━━ --}}
    <li class="sidebar-section-label">
        <span>{{ __('db.Accounting') }}</span>
    </li>

    @can('sidebar_accounting')
    @php $acctActive = request()->is('accounts','money-transfers','balancesheet','account-statement'); @endphp
    <li>
        <a href="#accounting-main" aria-expanded="{{ $acctActive ? 'true' : 'false' }}" data-toggle="collapse">
            <i class="dripicons-briefcase"></i><span>{{ __('db.Accounting') }}</span>
        </a>
        <ul id="accounting-main" class="collapse list-unstyled {{ $acctActive ? 'show' : '' }}">

            {{-- ── Accounts sub-group ───────────────────────── --}}
            @can('account-index')
            @php $actsActive = request()->is('accounts'); @endphp
            <li>
                <a href="#acct-accounts" data-toggle="collapse"
                   aria-expanded="{{ $actsActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $actsActive ? '' : 'collapsed' }}">
                    <i class="dripicons-briefcase" style="font-size:.8rem"></i> Accounts
                </a>
                <ul id="acct-accounts" class="collapse list-unstyled sidebar-sub-list {{ $actsActive ? 'show' : '' }}">
                    <li><a class="{{ request()->is('accounts') ? 'active' : '' }}"
                            href="{{ route('accounts.index') }}">{{ __('db.Account List') }}</a></li>
                    <li><a id="add-account" href="">{{ __('db.Add Account') }}</a></li>
                </ul>
            </li>
            @endcan

            {{-- ── Direct items ─────────────────────────────── --}}
            @can('money-transfer')
                <li><a class="{{ request()->is('money-transfers') ? 'active' : '' }}"
                        href="{{ route('money-transfers.index') }}">{{ __('db.Money Transfer') }}</a></li>
            @endcan
            @can('account-statement')
                <li><a id="account-statement"
                        class="{{ request()->is('account-statement') ? 'active' : '' }}">{{ __('db.Account Statement') }}</a></li>
            @endcan
            @can('balance-sheet')
                <li><a class="{{ request()->is('balancesheet') ? 'active' : '' }}"
                        href="{{ route('accounts.balancesheet') }}">{{ __('db.Balance Sheet') }}</a></li>
            @endcan

        </ul>
    </li>
    @endcan

    {{-- ━━━ SECTION: WORKFORCE ━━━ --}}
    @can('sidebar_hrm')
    @php $workActive = request()->is('employees','departments','designations','shift','attendance','attendance/self','holidays','overtime','leave-type','leave','payroll'); @endphp
    <li class="sidebar-section-label"><span>Workforce</span></li>
    <li>
        <a href="#workforce-main" aria-expanded="{{ $workActive ? 'true' : 'false' }}" data-toggle="collapse">
            <i class="dripicons-user-group"></i><span>Workforce</span>
        </a>
        <ul id="workforce-main" class="collapse list-unstyled {{ $workActive ? 'show' : '' }}">

            {{-- ── HRM sub-group ────────────────────────────── --}}
            <li>
                <a href="#work-hrm" data-toggle="collapse"
                   aria-expanded="{{ $workActive ? 'true' : 'false' }}"
                   class="sidebar-sub-toggle {{ $workActive ? '' : 'collapsed' }}">
                    <i class="dripicons-user-group" style="font-size:.8rem"></i> {{ __('db.HRM') }}
                </a>
                <ul id="work-hrm" class="collapse list-unstyled sidebar-sub-list {{ $workActive ? 'show' : '' }}">
                    @can('employees-index')
                        <li><a class="{{ request()->is('employees') ? 'active' : '' }}"
                                href="{{ route('employees.index') }}">{{ __('db.Employee') }}</a></li>
                    @endcan
                    @can('department')
                        <li><a class="{{ request()->is('departments') ? 'active' : '' }}"
                                href="{{ route('departments.index') }}">{{ __('db.Department') }}</a></li>
                    @endcan
                    @can('designations')
                        <li><a class="{{ request()->is('designations') ? 'active' : '' }}"
                                href="{{ route('designations.index') }}">{{ __('db.Designation') }}</a></li>
                    @endcan
                    @can('shift')
                        <li><a class="{{ request()->is('shift') ? 'active' : '' }}"
                                href="{{ route('shift.index') }}">{{ __('db.Shift') }}</a></li>
                    @endcan
                    @can('attendance')
                        <li><a class="{{ request()->is('attendance') ? 'active' : '' }}"
                                href="{{ route('attendance.index') }}">{{ __('db.Attendance') }}</a></li>
                    @endcan
                    {{-- 3.2 Self clock in/out — visible when linked employee exists --}}
                    @php $__selfEmp = \App\Models\Employee::where('user_id', Auth::id())->exists(); @endphp
                    @if ($__selfEmp)
                        <li><a class="{{ request()->is('attendance/self') ? 'active' : '' }}"
                                href="{{ route('attendance.self') }}">
                                <i class="dripicons-time-reverse mr-1"></i> My Clock In/Out
                            </a></li>
                    @endif
                    @can('holiday')
                        <li><a class="{{ request()->is('holidays') ? 'active' : '' }}"
                                href="{{ route('holidays.index') }}">{{ __('db.Holiday') }}</a></li>
                    @endcan
                    @can('overtime')
                        <li><a class="{{ request()->is('overtime') ? 'active' : '' }}"
                                href="{{ route('overtime.index') }}">{{ __('db.Overtime') }}</a></li>
                    @endcan
                    @can('leave-type')
                        <li><a class="{{ request()->is('leave-type') ? 'active' : '' }}"
                                href="{{ route('leave-type.index') }}">{{ __('db.Leave Type') }}</a></li>
                    @endcan
                    @can('leave')
                        <li><a class="{{ request()->is('leave') ? 'active' : '' }}"
                                href="{{ route('leave.index') }}">{{ __('db.Leaves') }}</a></li>
                    @endcan
                    @can('payroll')
                        <li><a class="{{ request()->is('payroll') ? 'active' : '' }}"
                                href="{{ route('payroll.index') }}">{{ __('db.Payroll') }}</a></li>
                    @endcan
                </ul>
            </li>

        </ul>
    </li>
    @endcan

    {{-- ━━━ SECTION: CHANNELS ━━━ --}}
    <li class="sidebar-section-label"><span>Channels</span></li>

    @can('sidebar_whatsapp')
        <li>
            <a href="#whatsapp" aria-expanded="false" data-toggle="collapse">
                <i class="dripicons-message"></i><span>{{ __('db.whatsapp') }}</span>
            </a>
            <ul id="whatsapp" class="collapse list-unstyled">
                <li><a class="{{ request()->is('whatsapp/settings') ? 'active' : '' }}"
                        href="{{ route('whatsapp.settings') }}">{{ __('db.whatsapp_settings') }}</a></li>
                <li><a class="{{ request()->is('whatsapp/templates') ? 'active' : '' }}"
                        href="{{ route('whatsapp.templates') }}">{{ __('db.message_templates') }}</a></li>
                <li><a class="{{ request()->is('whatsapp/send') ? 'active' : '' }}"
                        href="{{ route('whatsapp.send.page') }}">{{ __('db.send_message') }}</a></li>
            </ul>
        </li>
    @endcan

    {{-- ═══ Online Store — gated by plan feature 'online_store' ════════════ --}}
    @if (config('database.connections.saleprosaas_landlord'))
        @can('sidebar_online_store')
            <li>
                <a class="{{ request()->is('store-settings*') ? 'active' : '' }}"
                    href="{{ route('store.settings') }}">
                    <i class="dripicons-shopping-bag"></i><span>Online Store</span>
                </a>
            </li>
        @endcan
    @endif

    @if (\Auth::user()->role_id <= 2)
        <li>
            <a class="{{ request()->is('qr/') ? 'active' : '' }}" href="{{ route('qr.index') }}">
                <i class="fa fa-qrcode" style="font-size:20px"></i><span>{{ __('db.Catalogue QR') }}</span>
            </a>
        </li>
    @endif

    {{-- ━━━ SECTION: ADD-ONS ━━━ --}}
    @can('addons')
        @if (\Auth::user()->role_id != 5)
            @php $hasAddons = false; @endphp
            @if (in_array('manufacturing', explode(',', $general_setting->modules)))
                @can('sidebar_manufacturing')
                    @php $hasAddons = true; @endphp
                @endcan
            @endif
            @can('sidebar_repair')
                @php $hasAddons = true; @endphp
            @endcan
            @if (in_array('woocommerce', explode(',', $general_setting->modules)))
                @php $hasAddons = true; @endphp
            @endif
            @if (in_array('ecommerce', explode(',', $general_setting->modules)))
                @php $hasAddons = true; @endphp
            @endif
            @if (in_array('gym', explode(',', $general_setting->modules)))
                @php $hasAddons = true; @endphp
            @endif

            @if ($hasAddons)
                <li class="sidebar-section-label"><span>Add-ons</span></li>
            @endif

            @if (in_array('manufacturing', explode(',', $general_setting->modules)))
                @can('sidebar_manufacturing')
                    @php $showManufacturingMenu = false; @endphp
                    @can('production-view')
                        @php $showManufacturingMenu = true; @endphp
                    @endcan
                    @can('production-add')
                        @php $showManufacturingMenu = true; @endphp
                    @endcan
                    @can('recipe-view')
                        @php $showManufacturingMenu = true; @endphp
                    @endcan
                    @isset($showManufacturingMenu)
                        <li>
                            <a href="#manufacturing" aria-expanded="false" data-toggle="collapse">
                                <i class="fa fa-industry"></i><span>{{ __('db.Manufacturing') }}</span>
                            </a>
                            <ul id="manufacturing" class="collapse list-unstyled">
                                @can('production-view')
                                    <li><a class="{{ request()->is('manufacturing/productions') ? 'active' : '' }}"
                                            href="{{ route('manufacturing.productions.index') }}">{{ __('db.Production List') }}</a>
                                    </li>
                                @endcan
                                @can('production-add')
                                    <li><a class="{{ request()->is('manufacturing/productions/create') ? 'active' : '' }}"
                                            href="{{ route('manufacturing.productions.create') }}">{{ __('db.Add Production') }}</a>
                                    </li>
                                @endcan
                                @can('recipe-view')
                                    <li><a class="{{ request()->is('manufacturing/recipes') ? 'active' : '' }}"
                                            href="{{ route('manufacturing.recipes.index') }}">{{ __('db.Recipe') }}</a>
                                    </li>
                                @endcan
                            </ul>
                        </li>
                    @endisset
                @endcan
            @endif

            @can('sidebar_repair')
                @php $showRepairMenu = false; @endphp
                @can('repair-service-index')
                    @php $showRepairMenu = true; @endphp
                @endcan
                @can('repair-service-add')
                    @php $showRepairMenu = true; @endphp
                @endcan
                @isset($showRepairMenu)
                    <li>
                        <a href="#repair" aria-expanded="false" data-toggle="collapse">
                            <i class="fa fa-wrench"></i><span>{{ __('db.Repair') }}</span>
                        </a>
                        <ul id="repair" class="collapse list-unstyled">
                            @can('repair-dashboard')
                                <li><a class="{{ request()->is('repair/') ? 'active' : '' }}"
                                        href="{{ route('repair.dashboard') }}">{{ __('db.Repair Dashboard') }}</a></li>
                            @endcan
                            @can('repair-service-index')
                                <li><a class="{{ request()->is('repair/service') ? 'active' : '' }}"
                                        href="{{ route('repair.service.index') }}">{{ __('db.Service Jobs') }}</a></li>
                            @endcan
                            @can('repair-service-add')
                                <li><a class="{{ request()->is('repair/service/create') ? 'active' : '' }}"
                                        href="{{ route('repair.service.create') }}">{{ __('db.Add Service Job') }}</a></li>
                            @endcan
                            @can('repair-device-type')
                                <li><a class="{{ request()->is('repair/device-types*') ? 'active' : '' }}"
                                        href="{{ route('repair.device-types.index') }}">{{ __('db.Device Types') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endisset
            @endcan

            @if (in_array('woocommerce', explode(',', $general_setting->modules)))
                <li><a class="{{ request()->is('woocommerce') ? 'active' : '' }}"
                        href="{{ route('woocommerce.index') }}"><i
                            class="fa fa-wordpress"></i><span>WooCommerce</span></a></li>
            @endif

            @if (in_array('ecommerce', explode(',', $general_setting->modules)))
                <li>
                    <a href="#ecommerce" aria-expanded="false" data-toggle="collapse">
                        <i class="dripicons-shopping-bag"></i><span>eCommerce</span>
                    </a>
                    <ul id="ecommerce" class="collapse list-unstyled">
                        @include('ecommerce::backend.layout.sidebar-menu')
                    </ul>
                </li>
            @endif

            @if (in_array('project', explode(',', $general_setting->modules)))
                @include('project::backend.layout.sidebar-menu')
            @endif

            @if (in_array('restaurant', explode(',', $general_setting->modules)))
                @include('restaurant::backend.layout.sidebar-menu')
            @endif

            @if (in_array('gym', explode(',', $general_setting->modules)))
                <li>
                    <a href="#gym" aria-expanded="false" data-toggle="collapse">
                        <i class="dripicons-trophy"></i><span>Gym</span>
                    </a>
                    <ul id="gym" class="collapse list-unstyled">
                        <li><a class="{{ request()->is('gym/dashboard') ? 'active' : '' }}"
                                href="{{ route('gym.dashboard') }}">Dashboard</a></li>
                        <li><a class="{{ request()->is('gym/members*') ? 'active' : '' }}"
                                href="{{ route('gym.members.index') }}">Members</a></li>
                        <li><a class="{{ request()->is('gym/attendance*') ? 'active' : '' }}"
                                href="{{ route('gym.attendance.index') }}">Attendance</a></li>
                        <li><a class="{{ request()->is('gym/packages*') ? 'active' : '' }}"
                                href="{{ route('gym.packages.index') }}">Packages</a></li>
                        <li><a class="{{ request()->is('gym/classes*') ? 'active' : '' }}"
                                href="{{ route('gym.classes.index') }}">Classes</a></li>
                        <li><a class="{{ request()->is('gym/setting*') ? 'active' : '' }}"
                                href="{{ route('gym.settings.index') }}">Settings</a></li>
                    </ul>
                </li>
            @endif
        @endif
    @endcan

    {{-- ━━━ SECTION: REPORTS ━━━ --}}
    @can('sidebar_reports')
        @php $showReportMenu = false; @endphp
        @can('profit-loss')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('best-seller')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('product-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('stock-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('daily-sale')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('monthly-sale')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('daily-purchase')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('monthly-purchase')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('sale-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('sale-report-chart')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('payment-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('purchase-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('customer-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('due-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('supplier-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('supplier-due-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('warehouse-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('warehouse-stock-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('product-expiry-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('product-qty-alert')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('dso-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('user-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @can('biller-report')
            @php $showReportMenu = true; @endphp
        @endcan
        @if ($showReportMenu)
            <li class="sidebar-section-label"><span>{{ __('db.Reports') }}</span></li>
            <li>
                <a href="#report" aria-expanded="false" data-toggle="collapse">
                    <i class="dripicons-document-remove"></i><span>{{ __('db.Reports') }}</span>
                </a>
                <ul id="report" class="collapse list-unstyled">
                    @if ($role->id <= 2)
                        <li><a class="{{ request()->is('setting/activity-log') ? 'active' : '' }}"
                                href="{{ route('setting.activityLog') }}">{{ __('db.Activity Log') }}</a></li>
                    @endif
                    @can('profit-loss')
                        <li><a class="{{ request()->is('report/profit-loss') ? 'active' : '' }}"
                                href="{{ url('report/profit-loss') }}">{{ __('db.profit_loss_report') }}</a></li>
                    @endcan
                    @can('best-seller')
                        <li><a class="{{ request()->is('report/best_seller') ? 'active' : '' }}"
                                href="{{ url('report/best_seller') }}">{{ __('db.Best Seller') }}</a></li>
                    @endcan
                    @can('product-report')
                        <li><a class="{{ request()->is('report/product_report') ? 'active' : '' }}"
                                href="{{ url('report/product_report') }}">{{ __('db.Product Report') }}</a></li>
                    @endcan
                    @can('stock-report')
                        <li><a class="{{ request()->is('report/stock') ? 'active' : '' }}"
                                href="{{ route('report.stock') }}">{{ __('db.Stock Report') }}</a></li>
                    @endcan
                    @can('daily-sale')
                        <li><a class="{{ request()->is('report/daily_sale/' . date('Y') . '/' . date('m')) ? 'active' : '' }}"
                                href="{{ url('report/daily_sale/' . date('Y') . '/' . date('m')) }}">{{ __('db.Daily Sale') }}</a>
                        </li>
                    @endcan
                    @can('monthly-sale')
                        <li><a class="{{ request()->is('report/monthly_sale/' . date('Y')) ? 'active' : '' }}"
                                href="{{ url('report/monthly_sale/' . date('Y')) }}">{{ __('db.Monthly Sale') }}</a>
                        </li>
                    @endcan
                    @can('daily-purchase')
                        <li><a class="{{ request()->is('report/daily_purchase/' . date('Y') . '/' . date('m')) ? 'active' : '' }}"
                                href="{{ url('report/daily_purchase/' . date('Y') . '/' . date('m')) }}">{{ __('db.Daily Purchase') }}</a>
                        </li>
                    @endcan
                    @can('monthly-purchase')
                        <li><a class="{{ request()->is('report/monthly_purchase/' . date('Y')) ? 'active' : '' }}"
                                href="{{ url('report/monthly_purchase/' . date('Y')) }}">{{ __('db.Monthly Purchase') }}</a>
                        </li>
                    @endcan
                    @can('sale-report')
                        <li id="sale-report-menu">
                            <form action="{{ route('report.sale') }}" method="post" id="sale-report-form">
                                @csrf
                                <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}">
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}">
                                <input type="hidden" name="warehouse_id" value="0">
                                <a id="sale-report-link"
                                    class="{{ request()->is('report/sale_report') ? 'active' : '' }}"
                                    href="">{{ __('db.Sale Report') }}</a>
                            </form>
                        </li>
                    @endcan
                    <li><a class="{{ request()->is('report/challan-report') ? 'active' : '' }}"
                            href="{{ route('report.challan') }}">{{ __('db.Challan Report') }}</a></li>
                    @can('sale-report-chart')
                        <li id="sale-report-chart-menu">
                            <form action="{{ route('report.saleChart') }}" method="post"
                                id="sale-report-chart-form">
                                @csrf
                                <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}">
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}">
                                <input type="hidden" name="warehouse_id" value="0">
                                <input type="hidden" name="time_period" value="weekly">
                                <a id="sale-report-chart-link"
                                    class="{{ request()->is('report/sale-report-chart') ? 'active' : '' }}"
                                    href="">{{ __('db.Sale Report Chart') }}</a>
                            </form>
                        </li>
                    @endcan
                    @can('payment-report')
                        <li id="payment-report-menu">
                            <form action="{{ route('report.paymentByDate') }}" method="post"
                                id="payment-report-form">
                                @csrf
                                <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}">
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}">
                                <a id="payment-report-link"
                                    class="{{ request()->is('report/payment_report_by_date') ? 'active' : '' }}"
                                    href="">{{ __('db.Payment Report') }}</a>
                            </form>
                        </li>
                    @endcan
                    @can('purchase-report')
                        <li id="purchase-report-menu">
                            <form action="{{ route('report.purchase') }}" method="post" id="purchase-report-form">
                                @csrf
                                <input type="hidden" name="start_date" value="{{ date('Y-m') . '-' . '01' }}">
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}">
                                <input type="hidden" name="warehouse_id" value="0">
                                <a id="purchase-report-link"
                                    class="{{ request()->is('report/purchase') ? 'active' : '' }}"
                                    href="">{{ __('db.Purchase Report') }}</a>
                            </form>
                        </li>
                    @endcan
                    @can('customer-report')
                        <li><a id="customer-report-link"
                                class="{{ request()->is('report/customer_report') ? 'active' : '' }}"
                                href="">{{ __('db.Customer Report') }}</a></li>
                        <li><a id="customer-group-report-link"
                                class="{{ request()->is('report/customer-group') ? 'active' : '' }}"
                                href="">{{ __('db.Customer Group Report') }}</a></li>
                    @endcan
                    @can('due-report')
                        <li id="due-report-menu">
                            <form action="{{ route('report.customerDueByDate') }}" method="post"
                                id="customer-due-report-form">
                                @csrf
                                <input type="hidden" name="start_date"
                                    value="{{ date('Y-m-d', strtotime('-1 year')) }}">
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}">
                                <a id="due-report-link"
                                    class="{{ request()->is('report/customer-due-report') ? 'active' : '' }}"
                                    href="">{{ __('db.Customer Due Report') }}</a>
                            </form>
                        </li>
                    @endcan
                    @can('supplier-report')
                        <li><a id="supplier-report-link"
                                class="{{ request()->is('report/supplier') ? 'active' : '' }}"
                                href="">{{ __('db.Supplier Report') }}</a></li>
                    @endcan
                    @can('supplier-due-report')
                        <li id="supplier-due-report-menu">
                            <form action="{{ route('report.supplierDueByDate') }}" method="post"
                                id="supplier-due-report-form">
                                @csrf
                                <input type="hidden" name="start_date"
                                    value="{{ date('Y-m-d', strtotime('-1 year')) }}">
                                <input type="hidden" name="end_date" value="{{ date('Y-m-d') }}">
                                <a id="supplier-due-report-link"
                                    class="{{ request()->is('report/supplier-due-report') ? 'active' : '' }}"
                                    href="">{{ __('db.Supplier Due Report') }}</a>
                            </form>
                        </li>
                    @endcan
                    @can('warehouse-report')
                        <li><a id="warehouse-report-link"
                                class="{{ request()->is('report/warehouse_report') ? 'active' : '' }}">{{ __('db.Warehouse Report') }}</a>
                        </li>
                    @endcan
                    @can('warehouse-stock-report')
                        <li><a class="{{ request()->is('report/warehouse_stock') ? 'active' : '' }}"
                                href="{{ route('report.warehouseStock') }}">{{ __('db.Warehouse Stock Chart') }}</a>
                        </li>
                    @endcan
                    @can('product-expiry-report')
                        <li><a class="{{ request()->is('report/product-expiry') ? 'active' : '' }}"
                                href="{{ route('report.productExpiry') }}">{{ __('db.Product Expiry Report') }}</a>
                        </li>
                    @endcan
                    @can('product-qty-alert')
                        <li><a class="{{ request()->is('report/product_quantity_alert') ? 'active' : '' }}"
                                href="{{ route('report.qtyAlert') }}">{{ __('db.Product Quantity Alert') }}</a>
                        </li>
                    @endcan
                    @can('dso-report')
                        <li><a class="{{ request()->is('report/daily-sale-objective') ? 'active' : '' }}"
                                href="{{ route('report.dailySaleObjective') }}">{{ __('db.Daily Sale Objective Report') }}</a>
                        </li>
                    @endcan
                    @can('user-report')
                        <li><a id="user-report-link"
                                class="{{ request()->is('report/user_report') ? 'active' : '' }}"
                                href="">{{ __('db.User Report') }}</a></li>
                    @endcan
                    @can('biller-report')
                        <li><a id="biller-report-link"
                                class="{{ request()->is('report/biller_report') ? 'active' : '' }}">{{ __('db.Biller Report') }}</a>
                        </li>
                    @endcan
                    <li><a class="{{ request()->is('report/installment') ? 'active' : '' }}"
                            href="{{ route('report.installment') }}">{{ __('db.Instalment Report') }}</a></li>
                    <li><a class="{{ request()->is('cash-register') ? 'active' : '' }}"
                            href="{{ route('cashRegister.index') }}">{{ __('db.Cash Register') }}</a></li>
                    @can('report')
                        <li><a class="{{ request()->is('report/audit') ? 'active' : '' }}"
                                href="{{ route('report.audit') }}">
                                <i class="dripicons-document mr-1"></i> Audit Report
                            </a></li>
                    @endcan
                </ul>
            </li>
        @endif
    @endcan

    {{-- ━━━ SECTION: SETTINGS ━━━ --}}
    @php $showSettingsMenu = false; @endphp
    @if (\Auth::user()->role_id <= 2)
        @php $showSettingsMenu = true; @endphp
    @endif
    @can('invoice_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('role_permission')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('custom_field')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('discount_plan')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('discount')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('all_notification')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('send_notification')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('warehouse')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('customer_group')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('currency')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('tax')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('create_sms')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('backup_database')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('general_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('mail_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('reward_point_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('sms_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('payment_gateway_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('pos_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('hrm_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('barcode_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @can('language_setting')
        @php $showSettingsMenu = true; @endphp
    @endcan
    @isset($showSettingsMenu)
        <li class="sidebar-section-label"><span>System</span></li>
        <li>
            {{-- ⚙️ SETTINGS — top-level toggle --}}
            @php
                $settingActive = request()->is(
                    'setting/general_setting','user/profile/*','languages','currency','warehouse','tables',
                    'setting/pos_setting','setting/invoice','printers','barcodes','customer_group',
                    'discount-plans','discounts','setting/reward-point-setting',
                    'tax','setting/payment-gateways/list',
                    'setting/hrm_setting','role',
                    'setting/mail_setting','setting/sms_setting','smstemplates','setting/createsms','notifications',
                    'custom-fields','backup','setting/theme-settings'
                );
            @endphp
            <a href="#setting" aria-expanded="{{ $settingActive ? 'true' : 'false' }}" data-toggle="collapse">
                <i class="dripicons-gear"></i><span>{{ __('db.settings') }}</span>
            </a>
            <ul id="setting" class="collapse list-unstyled {{ $settingActive ? 'show' : '' }}">

                {{-- ── 1. GENERAL ──────────────────────────────────── --}}
                @php
                    $genActive = request()->is('setting/general_setting','user/profile/*','languages','currency','warehouse','tables','setting/theme-settings');
                @endphp
                <li>
                    <a href="#set-general" data-toggle="collapse"
                       aria-expanded="{{ $genActive ? 'true' : 'false' }}"
                       class="sidebar-sub-toggle {{ $genActive ? '' : 'collapsed' }}">
                        <i class="dripicons-home" style="font-size:.8rem"></i> General
                    </a>
                    <ul id="set-general" class="collapse list-unstyled sidebar-sub-list {{ $genActive ? 'show' : '' }}">
                        @can('general_setting')
                            <li><a class="{{ request()->is('setting/general_setting') ? 'active' : '' }}"
                                    href="{{ route('setting.general') }}">{{ __('db.General Settings') }}</a></li>
                        @endcan
                        <li><a class="{{ request()->is('user/profile/*') ? 'active' : '' }}"
                                href="{{ route('user.profile', ['id' => Auth::id()]) }}">{{ __('db.User Profile') }}</a></li>
                        @can('language_setting')
                            <li><a class="{{ request()->is('languages') ? 'active' : '' }}"
                                    href="{{ route('languages') }}">{{ __('db.Languages') }}</a></li>
                        @endcan
                        @can('currency')
                            <li><a class="{{ request()->is('currency') ? 'active' : '' }}"
                                    href="{{ route('currency.index') }}">{{ __('db.Currency') }}</a></li>
                        @endcan
                        @can('warehouse')
                            <li><a class="{{ request()->is('warehouse') ? 'active' : '' }}"
                                    href="{{ route('warehouse.index') }}">{{ __('db.Warehouse') }}</a></li>
                        @endcan
                        @if (\Auth::user()->role_id <= 2)
                            <li><a class="{{ request()->is('tables') ? 'active' : '' }}"
                                    href="{{ route('tables.index') }}">{{ __('db.Tables') }}</a></li>
                        @endif
                        @can('general_setting')
                            @if (in_array('api', explode(',', $general_setting->modules)) && !(config('database.connections.saleprosaas_landlord') && tenant()))
                                <li><a class="{{ request()->is('setting/theme-settings') ? 'active' : '' }}"
                                        href="{{ route('setting.themeSettings.index') }}">{{ __('db.app_theme_settings') }}</a></li>
                            @endif
                        @endcan
                    </ul>
                </li>

                {{-- ── 2. SALES & POS ──────────────────────────────── --}}
                @php
                    $salesActive = request()->is('setting/pos_setting','setting/invoice','printers','barcodes','customer_group','discount-plans','discounts','setting/reward-point-setting');
                @endphp
                <li>
                    <a href="#set-sales" data-toggle="collapse"
                       aria-expanded="{{ $salesActive ? 'true' : 'false' }}"
                       class="sidebar-sub-toggle {{ $salesActive ? '' : 'collapsed' }}">
                        <i class="dripicons-cart" style="font-size:.8rem"></i> Sales &amp; POS
                    </a>
                    <ul id="set-sales" class="collapse list-unstyled sidebar-sub-list {{ $salesActive ? 'show' : '' }}">
                        @can('pos_setting')
                            <li><a class="{{ request()->is('setting/pos_setting') ? 'active' : '' }}"
                                    href="{{ route('setting.pos') }}">{{ __('db.POS Settings') }}</a></li>
                        @endcan
                        @can('invoice_setting')
                            <li><a class="{{ request()->is('setting/invoice') ? 'active' : '' }}"
                                    href="{{ route('settings.invoice.index') }}">{{ __('db.Invoice Settings') }}</a></li>
                        @endcan
                        @if (\Auth::user()->role_id <= 2)
                            <li><a class="{{ request()->is('printers') ? 'active' : '' }}"
                                    href="{{ route('printers.index') }}">{{ __('db.Receipt Printers') }}</a></li>
                        @endif
                        @can('barcode_setting')
                            <li><a class="{{ request()->is('barcodes') ? 'active' : '' }}"
                                    href="{{ route('barcodes.index') }}">{{ __('db.Barcode Settings') }}</a></li>
                        @endcan
                        @can('customer_group')
                            <li><a class="{{ request()->is('customer_group') ? 'active' : '' }}"
                                    href="{{ route('customer_group.index') }}">{{ __('db.Customer Groups') }}</a></li>
                        @endcan
                        @can('discount_plan')
                            <li><a class="{{ request()->is('discount-plans') ? 'active' : '' }}"
                                    href="{{ route('discount-plans.index') }}">{{ __('db.Discount Plans') }}</a></li>
                        @endcan
                        @can('discount')
                            <li><a class="{{ request()->is('discounts') ? 'active' : '' }}"
                                    href="{{ route('discounts.index') }}">{{ __('db.Discounts') }}</a></li>
                        @endcan
                        @can('reward_point_setting')
                            <li><a class="{{ request()->is('setting/reward-point-setting') ? 'active' : '' }}"
                                    href="{{ route('setting.rewardPoint') }}">{{ __('db.Reward Point Settings') }}</a></li>
                        @endcan
                    </ul>
                </li>

                {{-- ── 3. FINANCE & TAX ────────────────────────────── --}}
                @php
                    $financeActive = request()->is('tax','setting/payment-gateways/list');
                @endphp
                <li>
                    <a href="#set-finance" data-toggle="collapse"
                       aria-expanded="{{ $financeActive ? 'true' : 'false' }}"
                       class="sidebar-sub-toggle {{ $financeActive ? '' : 'collapsed' }}">
                        <i class="dripicons-wallet" style="font-size:.8rem"></i> Finance &amp; Tax
                    </a>
                    <ul id="set-finance" class="collapse list-unstyled sidebar-sub-list {{ $financeActive ? 'show' : '' }}">
                        @can('tax')
                            <li><a class="{{ request()->is('tax') ? 'active' : '' }}"
                                    href="{{ route('tax.index') }}">{{ __('db.Tax') }}</a></li>
                        @endcan
                        @can('payment_gateway_setting')
                            <li><a class="{{ request()->is('setting/payment-gateways/list') ? 'active' : '' }}"
                                    href="{{ route('setting.gateway') }}">{{ __('db.Payment Gateways') }}</a></li>
                        @endcan
                    </ul>
                </li>

                {{-- ── 4. HUMAN RESOURCES ──────────────────────────── --}}
                @php
                    $hrActive = request()->is('setting/hrm_setting','role');
                @endphp
                <li>
                    <a href="#set-hr" data-toggle="collapse"
                       aria-expanded="{{ $hrActive ? 'true' : 'false' }}"
                       class="sidebar-sub-toggle {{ $hrActive ? '' : 'collapsed' }}">
                        <i class="dripicons-user-group" style="font-size:.8rem"></i> Human Resources
                    </a>
                    <ul id="set-hr" class="collapse list-unstyled sidebar-sub-list {{ $hrActive ? 'show' : '' }}">
                        @can('hrm_setting')
                            <li><a class="{{ request()->is('setting/hrm_setting') ? 'active' : '' }}"
                                    href="{{ route('setting.hrm') }}">{{ __('db.HRM Settings') }}</a></li>
                        @endcan
                        @can('role_permission')
                            <li><a class="{{ request()->is('role') ? 'active' : '' }}"
                                    href="{{ route('role.index') }}">{{ __('db.Roles & Permissions') }}</a></li>
                        @endcan
                    </ul>
                </li>

                {{-- ── 5. COMMUNICATION ────────────────────────────── --}}
                @php
                    $commActive = request()->is('setting/mail_setting','setting/sms_setting','smstemplates','setting/createsms','notifications');
                @endphp
                <li>
                    <a href="#set-comm" data-toggle="collapse"
                       aria-expanded="{{ $commActive ? 'true' : 'false' }}"
                       class="sidebar-sub-toggle {{ $commActive ? '' : 'collapsed' }}">
                        <i class="dripicons-mail" style="font-size:.8rem"></i> Communication
                    </a>
                    <ul id="set-comm" class="collapse list-unstyled sidebar-sub-list {{ $commActive ? 'show' : '' }}">
                        @can('mail_setting')
                            <li><a class="{{ request()->is('setting/mail_setting') ? 'active' : '' }}"
                                    href="{{ route('setting.mail') }}">{{ __('db.Mail Settings') }}</a></li>
                        @endcan
                        @can('sms_setting')
                            <li><a class="{{ request()->is('setting/sms_setting') ? 'active' : '' }}"
                                    href="{{ route('setting.sms') }}">{{ __('db.SMS Settings') }}</a></li>
                        @endcan
                        @can('role_permission')
                            <li><a class="{{ request()->is('smstemplates') ? 'active' : '' }}"
                                    href="{{ route('smstemplates.index') }}">{{ __('db.SMS Templates') }}</a></li>
                        @endcan
                        @can('create_sms')
                            <li><a class="{{ request()->is('setting/createsms') ? 'active' : '' }}"
                                    href="{{ route('setting.createSms') }}">{{ __('db.Create SMS') }}</a></li>
                        @endcan
                        {{-- Notifications sub-group --}}
                        @php
                            $notifActive = request()->is('notifications');
                        @endphp
                        <li>
                            <a href="#set-notif" data-toggle="collapse"
                               aria-expanded="{{ $notifActive ? 'true' : 'false' }}"
                               class="sidebar-sub-toggle {{ $notifActive ? '' : 'collapsed' }}" style="padding-left:1.5rem">
                                <i class="dripicons-bell" style="font-size:.75rem"></i> Notifications
                            </a>
                            <ul id="set-notif" class="collapse list-unstyled sidebar-sub-list {{ $notifActive ? 'show' : '' }}" style="padding-left:.5rem">
                                @can('all_notification')
                                    <li><a class="{{ request()->is('notifications') ? 'active' : '' }}"
                                            href="{{ route('notifications.index') }}">{{ __('db.Notification Templates') }}</a></li>
                                @endcan
                                @can('send_notification')
                                    <li><a href="#" id="send-notification">{{ __('db.Send Notifications') }}</a></li>
                                @endcan
                            </ul>
                        </li>
                    </ul>
                </li>

                {{-- ── 6. SYSTEM ADMINISTRATION ────────────────────── --}}
                @php
                    $sysActive = request()->is('custom-fields','backup');
                @endphp
                <li>
                    <a href="#set-sys" data-toggle="collapse"
                       aria-expanded="{{ $sysActive ? 'true' : 'false' }}"
                       class="sidebar-sub-toggle {{ $sysActive ? '' : 'collapsed' }}">
                        <i class="dripicons-stack" style="font-size:.8rem"></i> System Administration
                    </a>
                    <ul id="set-sys" class="collapse list-unstyled sidebar-sub-list {{ $sysActive ? 'show' : '' }}">
                        @can('custom_field')
                            <li><a class="{{ request()->is('custom-fields') ? 'active' : '' }}"
                                    href="{{ route('custom-fields.index') }}">{{ __('db.Custom Fields') }}</a></li>
                        @endcan
                        @can('backup_database')
                            <li><a class="{{ request()->is('backup') ? 'active' : '' }}"
                                    href="{{ route('setting.backup') }}">{{ __('db.Backup Database') }}</a></li>
                        @endcan
                    </ul>
                </li>

            </ul>
        </li>
    @endisset

    {{-- ━━━ API TOKENS — gated by plan feature 'api_access' ━━━ --}}
    @can('sidebar_api_token')
        <li class="sidebar-section-label"><span>Developer</span></li>
        <li>
            <a class="{{ request()->is('api-tokens*') ? 'active' : '' }}"
                href="{{ route('api-tokens.index') }}">
                <i class="dripicons-code"></i><span>API Tokens</span>
            </a>
        </li>
    @endcan

    {{-- ━━━ SUPPORT & DOCS ━━━ --}}
    @if (config('database.connections.saleprosaas_landlord'))
        @php
            // Read from landlord saas_settings (key-value store), not from tenant general_settings
            $disable_tenant_support_tickets = (bool) \App\Models\landlord\SaasSettings::get('disable_tenant_support_tickets', false);
        @endphp
        @if (!$disable_tenant_support_tickets)
            <li class="sidebar-section-label"><span>Support</span></li>
            <li>
                <a class="{{ request()->is('tickets') ? 'active' : '' }}"
                    href="{{ route('tickets.index') }}">
                    <i class="dripicons-ticket"></i><span>{{ __('db.support_tickets') }}</span>
                </a>
            </li>
        @endif
    @endif

    <li>
        <a class="{{ request()->is('user-guide') ? 'active' : '' }}" href="{{ url('user-guide') }}">
            <i class="dripicons-document"></i><span>User Guide</span>
        </a>
    </li>

</ul>

{{-- Sidebar section label CSS --}}
<style>
    .sidebar-section-label {
        padding: 16px 16px 4px !important;
        pointer-events: none;
        list-style: none;
    }

    .sidebar-section-label span {
        display: block;
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #9ca3af;
        opacity: .7;
        border-top: 1px solid rgba(0, 0, 0, .06);
        padding-top: 10px;
    }

    .dark-mode .sidebar-section-label span {
        color: #6b7280;
        border-color: rgba(255, 255, 255, .06);
    }

    /* ── Fix collapse arrow (FA4 glyph \f107 fails with async CSS load) ── */
    .side-navbar li a[data-toggle=collapse]::before {
        font-family: 'Font Awesome 6 Free', 'FontAwesome', sans-serif !important;
        font-weight: 900 !important;
    }

    /* ── Settings sub-group toggles ── */
    .sidebar-sub-toggle {
        display: flex !important;
        align-items: center;
        gap: 6px;
        font-size: .8rem !important;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: 7px 14px 7px 18px !important;
        color: #6b7280 !important;
        cursor: pointer;
        position: relative;
    }
    .sidebar-sub-toggle:hover {
        color: #374151 !important;
        background: rgba(0,0,0,.04);
        border-radius: 6px;
    }
    .dark-mode .sidebar-sub-toggle {
        color: #9ca3af !important;
    }
    .dark-mode .sidebar-sub-toggle:hover {
        color: #d1d5db !important;
        background: rgba(255,255,255,.05);
    }
    .sidebar-sub-list {
        padding-left: .75rem !important;
        border-left: 2px solid rgba(0,0,0,.06);
        margin-left: 1.4rem;
        margin-bottom: 2px;
    }
    .dark-mode .sidebar-sub-list {
        border-color: rgba(255,255,255,.08);
    }
    .sidebar-sub-list li a {
        font-size: .82rem !important;
        padding: 5px 10px !important;
    }
</style>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              