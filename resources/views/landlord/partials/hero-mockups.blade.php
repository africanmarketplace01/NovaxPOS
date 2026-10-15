{{--
    Hero background slides: HTML mockups of the main NovaxPOS screens.
    Drawn rather than screenshotted so they stay sharp at every size and need
    no tenant data. The shop ("Monrovia Mart") and figures are illustrative.
    Order must match $heroSlides in landing.blade.php.
--}}
@php
    // Sidebar icons shared by the app-window mockups.
    $rail = ['fa-cash-register', 'fa-boxes-stacked', 'fa-chart-line', 'fa-store', 'fa-truck', 'fa-gear'];
@endphp

{{-- 1. POS till --}}
<div class="hero__slide is-active" data-slide aria-hidden="false">
    <div class="mock" role="img" aria-label="NovaxPOS point of sale screen with product grid and cart">
        <div class="mock__bar"><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__url">monroviamart &rsaquo; Point of sale</span></div>
        <div class="mock__app">
            <div class="mock__rail">@foreach ($rail as $i => $icon)<i class="fas {{ $icon }} @if ($i === 0) is-on @endif"></i>@endforeach</div>
            <div class="mock__main">
                <div class="mock-pos">
                    <div>
                        <div class="mock__head">
                            <div class="mock__search"><i class="fas fa-barcode"></i>&nbsp; Scan or search products&hellip;</div>
                            <span class="mock__sub">Till 1 &middot; Main branch</span>
                        </div>
                        <div class="mock-pos__chips">
                            <span class="mock-pos__chip is-on">All</span><span class="mock-pos__chip">Groceries</span><span class="mock-pos__chip">Drinks</span><span class="mock-pos__chip">Household</span><span class="mock-pos__chip">Stationery</span>
                        </div>
                        <div class="mock-pos__items">
                            @foreach ([['Rice 25kg', '32.00', 'fa-wheat-awn', 1], ['Cooking Oil 5L', '14.50', 'fa-bottle-droplet', 2], ['Soft Drinks 12pk', '9.00', 'fa-wine-bottle', 7], ['Sugar 2kg', '3.75', 'fa-cubes-stacked', 3], ['Bath Soap', '1.25', 'fa-soap', 4], ['Exercise Book', '0.80', 'fa-book', 5], ['Tomato Paste', '2.40', 'fa-jar', 6], ['Water 1.5L', '4.50', 'fa-droplet', 8]] as [$name, $price, $icon, $sw])
                                <div class="mock-pos__item">
                                    <div class="mock-pos__img sw-{{ $sw }}"><i class="fas {{ $icon }}"></i></div>
                                    <div class="mock-pos__name">{{ $name }}</div>
                                    <div class="mock-pos__price">${{ $price }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mock-cart">
                        <div class="mock__h" style="font-size:1.1em">Current sale</div>
                        <div class="mock__sub" style="margin-bottom:.6em">Walk-in customer</div>
                        <div class="mock-cart__row"><span>Rice 25kg <span class="mock-cart__qty">&times;1</span></span><b>$32.00</b></div>
                        <div class="mock-cart__row"><span>Cooking Oil 5L <span class="mock-cart__qty">&times;2</span></span><b>$29.00</b></div>
                        <div class="mock-cart__row"><span>Soft Drinks 12pk <span class="mock-cart__qty">&times;1</span></span><b>$9.00</b></div>
                        <div class="mock-cart__tot">
                            <div class="mock-cart__line"><span>Subtotal</span><span>$70.00</span></div>
                            <div class="mock-cart__line"><span>Tax (5%)</span><span>$3.50</span></div>
                            <div class="mock-cart__grand"><span>Total</span><span>$73.50</span></div>
                            <div class="mock-cart__pay"><span>Cash</span><span class="is-on">Card</span><span>MoMo</span></div>
                            <div class="mock-cart__cta">Charge $73.50</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 2. Inventory --}}
<div class="hero__slide" data-slide aria-hidden="true">
    <div class="mock" role="img" aria-label="NovaxPOS inventory screen with stock levels across warehouses">
        <div class="mock__bar"><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__url">monroviamart &rsaquo; Inventory</span></div>
        <div class="mock__app">
            <div class="mock__rail">@foreach ($rail as $i => $icon)<i class="fas {{ $icon }} @if ($i === 1) is-on @endif"></i>@endforeach</div>
            <div class="mock__main">
                <div class="mock__head">
                    <div><div class="mock__h">Inventory</div><div class="mock__sub">3 warehouses &middot; 1,248 products</div></div>
                    <div class="mock__search"><i class="fas fa-magnifying-glass"></i>&nbsp; Search stock&hellip;</div>
                    <span class="mock__btn"><i class="fas fa-plus"></i> Add product</span>
                </div>
                <table class="mock-table">
                    <thead><tr><th>Product</th><th>Warehouse</th><th>Level</th><th class="num">Qty</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ([['Rice 25kg', 'RC-025', 'Main store', 74, 148, 'ok'], ['Cooking Oil 5L', 'OL-005', 'Main store', 52, 62, 'ok'], ['Sugar 2kg', 'SG-002', 'Sinkor branch', 9, 9, 'low'], ['Water 1.5L', 'WT-150', 'Main store', 0, 0, 'out'], ['Exercise Book', 'EB-100', 'Paynesville', 88, 420, 'ok'], ['Bath Soap', 'SP-012', 'Main store', 14, 14, 'low']] as [$name, $sku, $wh, $pct, $qty, $st])
                            <tr>
                                <td><b>{{ $name }}</b> <span class="mock-sku">{{ $sku }}</span></td>
                                <td>{{ $wh }}</td>
                                <td><div class="mock-bar"><span style="width:{{ max($pct, 2) }}%;@if ($st === 'low') background:#f59e0b;@elseif ($st === 'out') background:#ef4444;@endif"></span></div></td>
                                <td class="num">{{ $qty }}</td>
                                <td>
                                    @if ($st === 'ok')<span class="mock__pill mock__pill--ok">In stock</span>
                                    @elseif ($st === 'low')<span class="mock__pill mock__pill--low">Low stock</span>
                                    @else<span class="mock__pill mock__pill--out">Out of stock</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- 3. Sales reports --}}
<div class="hero__slide" data-slide aria-hidden="true">
    <div class="mock" role="img" aria-label="NovaxPOS sales report with revenue chart and top products">
        <div class="mock__bar"><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__url">monroviamart &rsaquo; Reports</span></div>
        <div class="mock__app">
            <div class="mock__rail">@foreach ($rail as $i => $icon)<i class="fas {{ $icon }} @if ($i === 2) is-on @endif"></i>@endforeach</div>
            <div class="mock__main">
                <div class="mock__head">
                    <div><div class="mock__h">Sales overview</div><div class="mock__sub">This week &middot; all branches</div></div>
                    <span class="mock__pill mock__pill--info"><i class="fas fa-wand-magic-sparkles"></i> AI forecast +18.6%</span>
                </div>
                <div class="mock-kpis">
                    @foreach ([['Revenue', '$48,920', '+12.4%'], ['Orders', '1,284', '+8.1%'], ['Avg. basket', '$38.10', '+3.9%'], ['Gross profit', '$14,670', '+10.2%']] as [$label, $value, $delta])
                        <div class="mock__card"><div class="mock-kpi__label">{{ $label }}</div><div class="mock-kpi__value">{{ $value }}</div><div class="mock-kpi__delta">&uarr; {{ $delta }}</div></div>
                    @endforeach
                </div>
                <div class="mock-report">
                    <div class="mock__card">
                        <b>Revenue by day</b>
                        <div class="mock-chart">
                            @foreach ([['Mon', 46], ['Tue', 58], ['Wed', 52], ['Thu', 70], ['Fri', 84], ['Sat', 100], ['Sun', 64]] as [$day, $h])
                                <div class="mock-chart__col"><div class="mock-chart__bar" style="height:{{ $h }}%"></div><span class="mock-chart__lbl">{{ $day }}</span></div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mock__card">
                        <b>Top products</b>
                        <div class="mock-rank" style="margin-top:.9em">
                            @foreach ([['Rice 25kg', '$9,450', 100], ['Cooking Oil 5L', '$6,120', 65], ['Soft Drinks', '$4,980', 53], ['Sugar 2kg', '$3,310', 35]] as [$name, $amt, $w])
                                <div class="mock-rank__row"><span>{{ $name }}</span><b>{{ $amt }}</b><div class="mock-bar"><span style="width:{{ $w }}%"></span></div></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 4. Online storefront --}}
<div class="hero__slide" data-slide aria-hidden="true">
    <div class="mock" role="img" aria-label="NovaxPOS online storefront on the marketplace">
        <div class="mock__bar"><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__url">marketplace &rsaquo; Monrovia Mart</span></div>
        <div class="mock__main" style="min-height:34em">
            <div class="mock__head">
                <div><div class="mock__h">Monrovia Mart</div><div class="mock__sub"><i class="fas fa-star" style="color:#f59e0b"></i> 4.8 &middot; Groceries &amp; household &middot; Open now</div></div>
                <div class="mock__search"><i class="fas fa-magnifying-glass"></i>&nbsp; Search this store&hellip;</div>
                <span class="mock__btn"><i class="fas fa-cart-shopping"></i> Cart (3)</span>
            </div>
            <div class="mock-shop__banner">
                <div><b>Order online, delivered today</b><span>Stock synced live from the store&rsquo;s POS</span></div>
                <span class="mock__pill" style="background:var(--np-accent);color:#0a2a00">Free delivery over $50</span>
            </div>
            <div class="mock-shop__grid">
                @foreach ([['Rice 25kg', '$32.00', 'fa-wheat-awn', 1], ['Cooking Oil 5L', '$14.50', 'fa-bottle-droplet', 2], ['Soft Drinks 12pk', '$9.00', 'fa-wine-bottle', 7], ['Bath Soap 6pk', '$6.90', 'fa-soap', 4]] as [$name, $price, $icon, $sw])
                    <div class="mock-shop__item">
                        <div class="mock-shop__img sw-{{ $sw }}"><i class="fas {{ $icon }}"></i></div>
                        <div class="mock-shop__info"><b>{{ $name }}</b><div style="color:var(--np-primary);font-weight:800">{{ $price }}</div><div class="mock-shop__add">Add to cart</div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- 5. Delivery --}}
<div class="hero__slide" data-slide aria-hidden="true">
    <div class="mock" role="img" aria-label="NovaxPOS delivery tracking for marketplace orders">
        <div class="mock__bar"><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__dot"></span><span class="mock__url">monroviamart &rsaquo; Online orders</span></div>
        <div class="mock__app">
            <div class="mock__rail">@foreach ($rail as $i => $icon)<i class="fas {{ $icon }} @if ($i === 4) is-on @endif"></i>@endforeach</div>
            <div class="mock__main">
                <div class="mock__head">
                    <div><div class="mock__h">Online orders</div><div class="mock__sub">Assigned to verified delivery partners</div></div>
                    <span class="mock__pill mock__pill--ok">12 delivered today</span>
                </div>
                <div class="mock-delivery">
                    <table class="mock-table">
                        <thead><tr><th>Order</th><th>Area</th><th>Partner</th><th>Status</th></tr></thead>
                        <tbody>
                            @foreach ([['#MM-2041', 'Sinkor', 'SwiftRide', 'info', 'On the way'], ['#MM-2040', 'Congo Town', 'CityDash', 'low', 'Packing'], ['#MM-2039', 'Paynesville', 'SwiftRide', 'ok', 'Delivered'], ['#MM-2038', 'Mamba Point', 'CityDash', 'ok', 'Delivered'], ['#MM-2037', 'Bushrod', 'SwiftRide', 'ok', 'Delivered']] as [$no, $area, $partner, $tone, $status])
                                <tr><td><b>{{ $no }}</b></td><td>{{ $area }}</td><td>{{ $partner }}</td><td><span class="mock__pill mock__pill--{{ $tone }}">{{ $status }}</span></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mock__card">
                        <b>#MM-2041 &middot; Sinkor</b>
                        <div class="mock__sub" style="margin-bottom:1em">SwiftRide &middot; ETA 18 min</div>
                        <div class="mock-track">
                            @foreach ([['Order placed', '10:02', 'done'], ['Packed at store', '10:20', 'done'], ['Picked up by SwiftRide', '10:41', 'done'], ['Out for delivery', 'Now', 'now'], ['Delivered', '—', '']] as [$step, $time, $state])
                                <div class="mock-track__step">
                                    <span class="mock-track__dot @if ($state === 'done') is-done @elseif ($state === 'now') is-now @endif">@if ($state === 'done')<i class="fas fa-check"></i>@elseif ($state === 'now')<i class="fas fa-truck"></i>@endif</span>
                                    <div><b>{{ $step }}</b><div class="mock__sub">{{ $time }}</div></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
