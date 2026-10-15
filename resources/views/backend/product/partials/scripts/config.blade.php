{{-- ================================================================ --}}
{{-- PRODUCT INDEX — PHP → JS CONFIG BRIDGE --}}
{{-- All Blade/PHP values the JS layer needs. --}}
{{-- JS files reference window.PRODUCT_CONFIG.xxx, never Blade echoes. --}}
{{-- ================================================================ --}}
@php
    $isSaaS    = (bool) config('database.connections.saleprosaas_landlord');
    // Use $all_permission array (passed by controller) to avoid triggering Laravel's
    // policy gate resolution, which can fail if ModelPolicy auto-discovery is broken.
    $canExport = in_array('product_export',   $all_permission);
    $canDelete = in_array('products-delete',  $all_permission);
@endphp
<script>
window.PRODUCT_CONFIG = {

    /* ── Feature flags ─────────────────────────────────────────────── */
    isSaaS    : {{ $isSaaS    ? 'true' : 'false' }},
    canExport : {{ $canExport ? 'true' : 'false' }},
    canDelete : {{ $canDelete ? 'true' : 'false' }},

    /* ── SaaS package limit (only populated when isSaaS is true) ───── */
    numberOfProduct : <?php echo json_encode($isSaaS && isset($numberOfProduct) ? $numberOfProduct : null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Auth / permissions ─────────────────────────────────────────── */
    roleId        : <?php echo json_encode($role_id,        JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    allPermission : <?php echo json_encode($all_permission, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    userVerified  : <?php echo json_encode(env('USER_VERIFIED'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Custom fields ──────────────────────────────────────────────── */
    fieldName : <?php echo json_encode($field_name, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Active filter values (from session / controller) ───────────── */
    warehouseId  : <?php echo json_encode($warehouse_id,  JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    productType  : <?php echo json_encode($product_type,  JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    brandId      : <?php echo json_encode($brand_id,      JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    categoryId   : <?php echo json_encode($category_id,   JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    unitId       : <?php echo json_encode($unit_id,       JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    taxId        : <?php echo json_encode($tax_id,        JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    imeiorvariant: <?php echo json_encode($imeiorvariant, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    stockFilter  : <?php echo json_encode($stock_filter,  JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── URLs ───────────────────────────────────────────────────────── */
    urls : {
        productData     : '{{ url("products/product-data") }}',
        productGetData  : '{{ url("products/getdata") }}',
        variantData     : '{{ url("products/variant-data") }}',
        productWarehouse: '{{ url("products/product_warehouse") }}',
        deleteBySelect  : '{{ url("products/deletebyselection") }}',
        bootstrapCss    : '{{ asset("vendor/bootstrap/css/bootstrap.min.css") }}',
        @if($isSaaS)
        fetchPackageData: '{{ route("package.fetchData", $general_setting->package_id) }}',
        @else
        fetchPackageData: null,
        @endif
    },

    /* ── Branding ───────────────────────────────────────────────────── */
    logoUrl : <?php echo json_encode(url('logo', $general_setting->site_logo), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Labels (all translatable strings used in JS) ───────────────── */
    labels : {
        type            : <?php echo json_encode(__('db.Type'),                         JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        name            : <?php echo json_encode(__('db.name'),                         JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        code            : <?php echo json_encode(__('db.Code'),                         JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        brand           : <?php echo json_encode(__('db.Brand'),                        JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        category        : <?php echo json_encode(__('db.category'),                     JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        quantity        : <?php echo json_encode(__('db.Quantity'),                     JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        unit            : <?php echo json_encode(__('db.Unit'),                         JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        cost            : <?php echo json_encode(__('db.Cost'),                         JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        price           : <?php echo json_encode(__('db.Price'),                        JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        tax             : <?php echo json_encode(__('db.Tax'),                          JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        taxMethod       : <?php echo json_encode(__('db.Tax Method'),                   JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        alertQty        : <?php echo json_encode(__('db.Alert Quantity'),               JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        productDetails  : <?php echo json_encode(__('db.Product Details'),              JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        comboProducts   : <?php echo json_encode(__('db.Combo Products'),               JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        product         : <?php echo json_encode(__('db.product'),                      JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        wastagePercent  : <?php echo json_encode(__('db.Wastage Percent'),              JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        variant         : <?php echo json_encode(__('db.Variant'),                      JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        itemCode        : <?php echo json_encode(__('db.Item Code'),                    JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        additionalCost  : <?php echo json_encode(__('db.Additional Cost'),              JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        additionalPrice : <?php echo json_encode(__('db.Additional Price'),             JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        qty             : <?php echo json_encode(__('db.qty'),                          JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        warehouse       : <?php echo json_encode(__('db.Warehouse'),                    JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        batchNo         : <?php echo json_encode(__('db.Batch No'),                     JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        expiredDate     : <?php echo json_encode(__('db.Expired Date'),                 JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        imeiNumbers     : <?php echo json_encode(__('db.IMEI or Serial Numbers'),       JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        recordsPerPage  : <?php echo json_encode(__('db.records per page'),             JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        showing         : <?php echo json_encode(__('db.Showing'),                      JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        search          : <?php echo json_encode(__('db.Search'),                       JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        productList     : <?php echo json_encode(__('db.Product List'),                 JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    },
};
</script>
