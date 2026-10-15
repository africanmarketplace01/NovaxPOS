{{-- ================================================================ --}}
{{-- PURCHASE INDEX — PHP → JS CONFIG BRIDGE                       --}}
{{-- All Blade/PHP values the JS layer needs.                       --}}
{{-- JS files reference window.PURCHASE_CONFIG.xxx, never Blade.   --}}
{{-- ================================================================ --}}
@php
    $canExport    = in_array('purchase_export',   $all_permission);
    $canDelete    = in_array('purchases-delete',  $all_permission);
    $canPayEdit   = in_array('purchase-payment-edit',   $all_permission);
    $canPayDelete = in_array('purchase-payment-delete', $all_permission);
    $hasStripe    = $lims_pos_setting_data
                    && strlen($lims_pos_setting_data->stripe_public_key  ?? '') > 0
                    && strlen($lims_pos_setting_data->stripe_secret_key  ?? '') > 0;
@endphp
<script>
window.PURCHASE_CONFIG = {

    /* ── Feature flags ─────────────────────────────────────────────── */
    canExport    : {{ $canExport    ? 'true' : 'false' }},
    canDelete    : {{ $canDelete    ? 'true' : 'false' }},
    canPayEdit   : {{ $canPayEdit   ? 'true' : 'false' }},
    canPayDelete : {{ $canPayDelete ? 'true' : 'false' }},
    hasStripe    : {{ $hasStripe    ? 'true' : 'false' }},
    showProductDetails : <?php echo json_encode((bool) $general_setting->show_products_details_in_purchase_table, JSON_HEX_TAG); ?>,

    /* ── Settings ───────────────────────────────────────────────────── */
    decimal    : <?php echo json_encode($general_setting->decimal,    JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    siteTitle  : <?php echo json_encode($general_setting->site_title, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Auth / permissions ─────────────────────────────────────────── */
    allPermission : <?php echo json_encode($all_permission,      JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    userVerified  : <?php echo json_encode(env('USER_VERIFIED'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    csrfToken     : <?php echo json_encode(csrf_token(),         JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Stripe ─────────────────────────────────────────────────────── */
    stripePublicKey : <?php echo json_encode($lims_pos_setting_data->stripe_public_key ?? null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Active filter values ───────────────────────────────────────── */
    startingDate  : <?php echo json_encode($starting_date,  JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    endingDate    : <?php echo json_encode($ending_date,    JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    warehouseId   : <?php echo json_encode($warehouse_id,   JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    purchaseStatus: <?php echo json_encode($purchase_status,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    paymentStatus : <?php echo json_encode($payment_status, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Custom fields ──────────────────────────────────────────────── */
    fieldName : <?php echo json_encode($field_name, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── URLs ───────────────────────────────────────────────────────── */
    urls : {
        purchaseData    : '{{ url("purchases/purchase-data") }}',
        getPayment      : '{{ url("purchases/getpayment") }}',
        productPurchase : '{{ url("purchases/product_purchase") }}',
        deleteBySelect  : '{{ url("purchases/deletebyselection") }}',
        addPayment      : '{{ route("purchase.add-payment") }}',
        updatePayment   : '{{ route("purchase.update-payment") }}',
        deletePayment   : '{{ route("purchase.delete-payment") }}',
        documents       : '{{ url("documents") }}',
        login           : '{{ url("login") }}',
    },

    /* ── Labels (all translatable strings used in JS) ───────────────── */
    labels : {
        purchaseDetails  : <?php echo json_encode(__('db.Purchase Details'),  JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        date             : <?php echo json_encode(__('db.date'),              JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        reference        : <?php echo json_encode(__('db.reference'),         JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        purchaseStatus   : <?php echo json_encode(__('db.Purchase Status'),   JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        currency         : <?php echo json_encode(__('db.Currency'),          JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        exchangeRate     : <?php echo json_encode(__('db.Exchange Rate'),     JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        attachDocument   : <?php echo json_encode(__('db.Attach Document'),   JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        from             : <?php echo json_encode(__('db.From'),              JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        to               : <?php echo json_encode(__('db.To'),               JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        orderTax         : <?php echo json_encode(__('db.Order Tax'),         JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        orderDiscount    : <?php echo json_encode(__('db.Order Discount'),    JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        shippingCost     : <?php echo json_encode(__('db.Shipping Cost'),     JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        grandTotal       : <?php echo json_encode(__('db.grand total'),       JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        paidAmount       : <?php echo json_encode(__('db.Paid Amount'),       JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        returnAmount     : <?php echo json_encode(__('db.Return Amount'),     JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        due              : <?php echo json_encode(__('db.Due'),               JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        note             : <?php echo json_encode(__('db.Note'),              JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        createdBy        : <?php echo json_encode(__('db.Created By'),        JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        total            : <?php echo json_encode(__('db.Total'),             JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        recordsPerPage   : <?php echo json_encode(__('db.records per page'),  JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        showing          : <?php echo json_encode(__('db.Showing'),           JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        search           : <?php echo json_encode(__('db.Search'),            JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        paymentTerm      : 'Payment Term',
        dueDate          : 'Due Date',
        edit             : 'Edit',
        delete           : 'Delete',
        viewDocument     : 'View Document',
        somethingWrong   : 'Something is wrong!',
        nothingSelected  : 'Nothing is selected!',
        demoDisabled     : 'This feature is disable for demo!',
        confirmDelete    : 'Are you sure want to delete?',
        confirmDelPayment: 'Are you sure want to delete? If you delete this money will be refunded',
        sessionExpired   : 'Session expired or not authenticated. Please login again.',
        payingAmtBigger  : 'Paying amount cannot be bigger than recieved amount',
        payingAmtDue     : 'Paying amount cannot be bigger than due amount',
    },
};
</script>
