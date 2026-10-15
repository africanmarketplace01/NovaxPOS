@extends('backend.layout.main')
@section('content')

<x-error-message key="not_permitted" />

<style>
@media print {
    * { font-size:12px; line-height:20px; }
    td,th { padding:5px 0; }
    .hidden-print { display:none !important; }
    @page { size: landscape; margin:0 !important; }
    .barcodelist { max-width:378px; }
    .barcodelist img { max-width:150px; }
}
/* Filter bar */
.barcode-filter-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 18px 20px;
    margin-bottom: 20px;
}
.barcode-filter-card label { font-weight: 600; font-size: 13px; }
.filter-badge {
    display: inline-block;
    background: #dcfce7; color: #166534;
    font-size: 11px; font-weight: 700;
    padding: 2px 8px; border-radius: 999px;
    margin-left: 6px; vertical-align: middle;
}
.price-type-col { font-size: 11px; }
#auto-load-spinner { display:none; }
</style>

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>{{ __('db.print_barcode') }}</h4>
                        <span id="auto-load-spinner" class="text-muted" style="font-size:13px;">
                            <i class="fa fa-spinner fa-spin"></i> Loading products…
                        </span>
                    </div>

                    <form id="preview_setting_form" onsubmit="return false">
                        @csrf
                        <div class="card-body">

                            {{-- ══ FILTER BAR ════════════════════════════════════════ --}}
                            <div class="barcode-filter-card">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label>{{ __('db.Warehouse') }}</label>
                                        <select id="filter_warehouse" class="form-control selectpicker" data-live-search="true">
                                            <option value="">— All Warehouses —</option>
                                            @foreach($warehouses as $wid => $wname)
                                                <option value="{{ $wid }}">{{ $wname }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label>{{ __('db.Choose Your Date') }}</label>
                                        <div class="input-group">
                                            <input type="text" id="barcode_date_range"
                                                   class="form-control"
                                                   placeholder="Select date range…" readonly>
                                            <div class="input-group-append">
                                                <span class="input-group-text"><i class="fa fa-calendar"></i></span>
                                            </div>
                                        </div>
                                        {{-- hidden inputs read by loadProductsByFilter() --}}
                                        <input type="hidden" id="filter_date_from">
                                        <input type="hidden" id="filter_date_to">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="button" id="btn_load_products" class="btn btn-primary btn-block">
                                            <i class="fa fa-refresh"></i> Load Products
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-2" style="font-size:12px;color:#64748b;">
                                    <i class="fa fa-info-circle"></i>
                                    Changing warehouse or date range auto-loads matching products.
                                    Products with a <strong>wholesale price</strong> will print <span class="filter-badge">2 barcodes</span> (Retail + Wholesale).
                                </div>
                            </div>

                            {{-- ══ MANUAL PRODUCT SEARCH ══════════════════════════════ --}}
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <label>{{ __('db.add_product') }} <small class="text-muted">(or search manually below)</small></label>
                                    <div class="search-box input-group">
                                        <button type="button" class="btn btn-secondary btn-lg"><i class="fa fa-barcode"></i></button>
                                        <input type="text" name="product_code_name" id="lims_productcodeSearch"
                                               placeholder="{{ __('db.Please type product code and select') }}"
                                               class="form-control" />
                                    </div>
                                </div>
                            </div>

                            {{-- ══ PRODUCT TABLE ══════════════════════════════════════ --}}
                            <div class="table-responsive mt-2 mb-4">
                                <table id="myTable" class="table table-hover order-list">
                                    <thead>
                                        <tr>
                                            <th>{{ __('db.name') }}</th>
                                            <th>{{ __('db.Code') }}</th>
                                            <th>{{ __('db.Quantity') }}</th>
                                            <th>Retail Price</th>
                                            <th>Wholesale Price</th>
                                            <th>Barcodes</th>
                                            <th><i class="dripicons-trash"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($preLoadedproducts as $key => $p)
                                        <tr>
                                            <td>{{ $p[0] }}</td>
                                            <td class="product-code">{{ $p[1] }}</td>
                                            <td><input type="number" class="form-control qty" name="products[{{ $key }}][quantity]" value="1" min="1" style="width:70px"></td>
                                            <td>
                                                <input type="text" class="form-control" readonly
                                                       name="products[{{ $key }}][product_price]" value="{{ $p[2] }}" style="width:90px">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control wholesale-price-input"
                                                       name="products[{{ $key }}][wholesale_price]"
                                                       value="{{ $p['wholesale_price'] ?? '' }}"
                                                       placeholder="—" style="width:90px">
                                            </td>
                                            <td class="price-type-col">
                                                <span class="barcode-count-label">—</span>
                                            </td>
                                            <td>
                                                <button type="button" class="ibtnDel btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>
                                                <input type="hidden" name="products[{{ $key }}][product_id]"          value="{{ $p[8] }}">
                                                <input type="hidden" name="products[{{ $key }}][product_name]"        value="{{ $p[0] }}">
                                                <input type="hidden" name="products[{{ $key }}][sub_sku]"             value="{{ $p[1] }}">
                                                <input type="hidden" name="products[{{ $key }}][default_price]"       value="{{ $p[2] }}">
                                                <input type="hidden" name="products[{{ $key }}][product_promo_price]" value="{{ $p[4] ?? '' }}">
                                                <input type="hidden" name="products[{{ $key }}][currency]"            value="{{ $p[5] ?? '' }}">
                                                <input type="hidden" name="products[{{ $key }}][currency_position]"   value="{{ $p[6] ?? '' }}">
                                                <input type="hidden" name="products[{{ $key }}][brand_name]"          value="{{ $p[11] ?? '' }}">
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div id="empty-msg" style="display:none;text-align:center;padding:24px;color:#94a3b8;">
                                    <i class="fa fa-barcode fa-2x mb-2"></i><br>No products loaded. Use the filters above or search manually.
                                </div>
                            </div>

                            <hr>

                            {{-- ══ LABEL OPTIONS ══════════════════════════════════════ --}}
                            <label style="font-size:16px;">{{ __('db.Information on Label') }}</label>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <input type="checkbox" name="print[name]" checked value="1"> {{ __('db.Product Name') }}
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <span>Size:</span>
                                        <input class="form-control" type="number" name="print[name_size]" value="15" style="width:70px">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <input type="checkbox" name="print[price]" checked value="1"> {{ __('db.Price') }}
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <span>Size:</span>
                                        <input class="form-control" type="number" name="print[price_size]" value="15" style="width:70px">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <input type="checkbox" name="print[promo_price]" checked value="1"> {{ __('db.Promotional Price') }}
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <span>Size:</span>
                                        <input class="form-control" type="number" name="print[promo_price_size]" value="15" style="width:70px">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <input type="checkbox" name="print[business_name]" checked value="1"> {{ __('db.Business Name') }}
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <span>Size:</span>
                                        <input class="form-control" type="number" name="print[business_name_size]" value="15" style="width:70px">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <input type="checkbox" name="print[brand_name]" checked value="1"> {{ __('db.Brand') }}
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <span>Size:</span>
                                        <input class="form-control" type="number" name="print[brand_name_size]" value="15" style="width:70px">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="print[variations]" value="1">
                            <input type="hidden" name="print[variations_size]" value="17">
                            <input type="hidden" name="print[packing_date]" value="1">
                            <input type="hidden" name="print[packing_date_size]" value="12">

                            <hr>

                            {{-- ══ PAPER SIZE ════════════════════════════════════════ --}}
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label style="font-size:16px;">{{ __('db.paper_size') }} *</label>
                                    <select name="barcode_setting" class="form-control">
                                        @foreach($barcode_settings as $key => $value)
                                            <option value="{{ $key }}" {{ !empty($default) && $default->id == $key ? 'selected' : '' }}>{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="button" id="labels_preview" class="btn btn-primary btn-big">
                                        <i class="fa fa-print me-1"></i> {{ __('db.submit') }}
                                    </button>
                                    <a href="{{ route('barcodes.create') }}" class="btn btn-info ms-2">
                                        <i class="dripicons-plus"></i> {{ __('db.Add New Setting') }}
                                    </a>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script type="text/javascript">
    // ── Sidebar active state ──────────────────────────────────────
    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    $("ul#product #printBarcode-menu").addClass("active");

    // ── Autocomplete product code list ────────────────────────────
    <?php $productArray = []; ?>
    var lims_product_code = [
    @foreach($lims_product_list_without_variant as $product)
        <?php $productArray[] = htmlspecialchars($product->code . ' (' . preg_replace('/[\n\r]/', '', htmlspecialchars($product->name)) . ')'); ?>
    @endforeach
    @foreach($lims_product_list_with_variant as $product)
        <?php $productArray[] = htmlspecialchars($product->item_code . ' (' . preg_replace('/[\n\r]/', '', htmlspecialchars($product->name)) . ')'); ?>
    @endforeach
    <?php echo '"' . implode('","', $productArray) . '"'; ?>
    ];

    var key = {{ count($preLoadedproducts) + 1 }};

    // ── Update "Barcodes" count column ────────────────────────────
    function updateBarcodeCounts() {
        $('table.order-list tbody tr').each(function() {
            var wholesaleVal = $(this).find('.wholesale-price-input').val();
            var count = (wholesaleVal && parseFloat(wholesaleVal) > 0) ? '2 (Retail + Wholesale)' : '1 (Retail)';
            $(this).find('.barcode-count-label').text(count);
        });
    }

    // ── Build a new product row HTML ──────────────────────────────
    function buildProductRow(data, idx) {
        var wholesalePrice = data.wholesale_price || '';
        var cols = '';
        cols += '<td>' + data.name + '</td>';
        cols += '<td class="product-code">' + data.code + '</td>';
        cols += '<td><input type="number" class="form-control qty" name="products[' + idx + '][quantity]" value="1" min="1" style="width:70px"></td>';
        cols += '<td><input type="text" class="form-control" readonly name="products[' + idx + '][product_price]" value="' + data.price + '" style="width:90px"></td>';
        cols += '<td><input type="text" class="form-control wholesale-price-input" name="products[' + idx + '][wholesale_price]" value="' + wholesalePrice + '" placeholder="—" style="width:90px"></td>';
        cols += '<td class="price-type-col"><span class="barcode-count-label">—</span></td>';
        cols += '<td>';
        cols += '<button type="button" class="ibtnDel btn btn-danger btn-sm"><i class="dripicons-trash"></i></button>';
        cols += '<input type="hidden" name="products[' + idx + '][product_id]"          value="' + data.product_id + '">';
        cols += '<input type="hidden" name="products[' + idx + '][product_name]"        value="' + data.name + '">';
        cols += '<input type="hidden" name="products[' + idx + '][sub_sku]"             value="' + data.code + '">';
        cols += '<input type="hidden" name="products[' + idx + '][default_price]"       value="' + data.price + '">';
        cols += '<input type="hidden" name="products[' + idx + '][product_promo_price]" value="' + (data.promo_price || '') + '">';
        cols += '<input type="hidden" name="products[' + idx + '][currency]"            value="' + (data.currency || '') + '">';
        cols += '<input type="hidden" name="products[' + idx + '][currency_position]"   value="left">';
        cols += '<input type="hidden" name="products[' + idx + '][brand_name]"          value="' + (data.brand_name || '') + '">';
        cols += '</td>';
        return '<tr>' + cols + '</tr>';
    }

    // ── Load products from server by warehouse + date range ───────
    function loadProductsByFilter() {
        var warehouseId = $('#filter_warehouse').val();
        var dateFrom    = $('#filter_date_from').val();
        var dateTo      = $('#filter_date_to').val();

        $('#auto-load-spinner').show();

        $.ajax({
            type: 'GET',
            url: '{{ route("product.barcodeByWarehouse") }}',
            data: { warehouse_id: warehouseId, date_from: dateFrom, date_to: dateTo },
            success: function(data) {
                $('table.order-list tbody').empty();
                key = 1;

                if (data.length === 0) {
                    $('#empty-msg').show();
                } else {
                    $('#empty-msg').hide();
                    $.each(data, function(i, product) {
                        $('table.order-list tbody').append(buildProductRow(product, key));
                        key++;
                    });
                }
                updateBarcodeCounts();
            },
            complete: function() { $('#auto-load-spinner').hide(); }
        });
    }

    // ── Date range picker ─────────────────────────────────────────
    $('#barcode_date_range').daterangepicker({
        autoUpdateInput: false,
        autoApply: true,
        locale: { cancelLabel: 'Clear' },
        ranges: {
            'Today':        [moment(), moment()],
            'Yesterday':    [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days':  [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month':   [moment().startOf('month'), moment().endOf('month')],
            'Last Month':   [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
        }
    });

    $('#barcode_date_range').on('apply.daterangepicker', function(ev, picker) {
        var from = picker.startDate.format('YYYY-MM-DD');
        var to   = picker.endDate.format('YYYY-MM-DD');
        $(this).val(from + ' to ' + to);
        $('#filter_date_from').val(from);
        $('#filter_date_to').val(to);
        loadProductsByFilter();
    });

    $('#barcode_date_range').on('cancel.daterangepicker', function() {
        $(this).val('');
        $('#filter_date_from').val('');
        $('#filter_date_to').val('');
        loadProductsByFilter();
    });

    // ── Auto-trigger on warehouse change ─────────────────────────
    $('#filter_warehouse').on('change', function() {
        loadProductsByFilter();
    });
    $('#btn_load_products').on('click', function() {
        loadProductsByFilter();
    });

    // ── Update count when wholesale price edited manually ─────────
    $(document).on('input', '.wholesale-price-input', function() {
        var tr = $(this).closest('tr');
        var val = $(this).val();
        var count = (val && parseFloat(val) > 0) ? '2 (Retail + Wholesale)' : '1 (Retail)';
        tr.find('.barcode-count-label').text(count);
    });

    // ── Manual autocomplete search ────────────────────────────────
    $('#lims_productcodeSearch').autocomplete({
        source: function(request, response) {
            var matcher = new RegExp('.?' + $.ui.autocomplete.escapeRegex(request.term), 'i');
            response($.grep(lims_product_code, function(item) {
                return matcher.test(item);
            }));
        },
        select: function(event, ui) {
            $(this).val('');
            $.ajax({
                type: 'GET',
                url: '{{ url("products/lims_product_search") }}',
                data: { data: ui.item.value, warehouse_id: $('#filter_warehouse').val(), barcode: true },
                success: function(responseData) {
                    var data = responseData[0];
                    var sku  = data[1];
                    var dupe = false;
                    $('.product-code').each(function() {
                        if ($(this).text() === sku) { dupe = true; }
                    });
                    if (dupe) { alert('Duplicate product — already in the list.'); return; }

                    $('#empty-msg').hide();
                    var product = {
                        product_id:     data[8],
                        name:           data[0],
                        code:           data[1],
                        price:          data[2],
                        wholesale_price: data['wholesale_price'] || '',
                        promo_price:    data[4],
                        currency:       data[5],
                        brand_name:     data[11] || ''
                    };
                    $('table.order-list tbody').append(buildProductRow(product, key));
                    key++;
                    updateBarcodeCounts();
                }
            });
        }
    });

    // ── Delete row ────────────────────────────────────────────────
    $(document).on('click', '.ibtnDel', function() {
        $(this).closest('tr').remove();
        if ($('table.order-list tbody tr').length === 0) {
            $('#empty-msg').show();
        }
    });

    // ── Print / Preview ───────────────────────────────────────────
    // Uses a hidden POST form to avoid HTTP 414 (URI Too Long) when
    // many products are selected — GET query strings have server limits.
    $('#labels_preview').on('click', function() {
        if ($('table.order-list tbody tr').length === 0) {
            alert('Please load or add at least one product first.');
            return;
        }

        var $postForm = $('<form>', {
            method: 'POST',
            action: '{{ route("print.label") }}',
            target: '_blank',
            style:  'display:none'
        });

        // CSRF token required for POST
        $postForm.append($('<input>', { type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));

        // Copy all form fields: text/number/hidden always; checkboxes only when checked
        $('form#preview_setting_form').find('[name]').each(function() {
            var $el  = $(this);
            var type = ($el.attr('type') || '').toLowerCase();
            if (type === 'checkbox' && !$el.is(':checked')) { return; }
            $postForm.append($('<input>', { type: 'hidden', name: $el.attr('name'), value: $el.val() }));
        });

        $('body').append($postForm);
        $postForm.submit();
        $postForm.remove();
    });

    // ── Init ──────────────────────────────────────────────────────
    updateBarcodeCounts();
    if ($('table.order-list tbody tr').length === 0) {
        $('#empty-msg').show();
    }
</script>
@endpush
