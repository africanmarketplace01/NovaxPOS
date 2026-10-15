{{-- ================================================================ --}}
{{-- POS JAVASCRIPT CONFIGURATION --}}
{{-- All PHP-generated values for the POS JavaScript layer. --}}
{{-- JS files reference window.POS_CONFIG.xxx instead of Blade echoes. --}}
{{-- ================================================================ --}}
@php
    // Pre-render payment options HTML for the "Add More" button row
    $paymentOptionsHtml = '';
    if(in_array("cash", $options))        $paymentOptionsHtml .= '<option value="1">Cash</option>';
    if(in_array("gift_card", $options))   $paymentOptionsHtml .= '<option value="2">Gift Card</option>';
    if(in_array("card", $options))        $paymentOptionsHtml .= '<option value="3">Credit Card</option>';
    if(in_array("cheque", $options))      $paymentOptionsHtml .= '<option value="4">Cheque</option>';
    if(in_array("paypal", $options) && (strlen(env('PAYPAL_LIVE_API_USERNAME'))>0) && (strlen(env('PAYPAL_LIVE_API_PASSWORD'))>0) && (strlen(env('PAYPAL_LIVE_API_SECRET'))>0))
        $paymentOptionsHtml .= '<option value="5">Paypal</option>';
    if(in_array("deposit", $options))     $paymentOptionsHtml .= '<option value="6">Deposit</option>';
    if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
        $paymentOptionsHtml .= '<option value="7">Points</option>';
    if(in_array("credit", $options))      $paymentOptionsHtml .= '<option value="credit_sale">Credit Sale</option>';
    foreach($options as $opt) {
        if(!in_array($opt, ['cash','card','cheque','gift_card','deposit','paypal','pesapal','points'])) {
            $paymentOptionsHtml .= '<option value="'.e($opt).'">'.ucfirst($opt).'</option>';
        }
    }

    $isRestaurant = in_array('restaurant', explode(',', $general_setting->modules));
    $hasCashRegister = ($lims_pos_setting_data && $lims_pos_setting_data->cash_register);
    $hasRazorpay = in_array('razorpay', $options);
    $isSaaS = (bool)config('database.connections.saleprosaas_landlord');
    $hasStripe = ($lims_pos_setting_data && $lims_pos_setting_data->stripe_public_key);

    // Variables not passed by controller — derive them here
    $keybord_active = $lims_pos_setting_data ? (bool)$lims_pos_setting_data->keybord_active : false;
    $authUser = \Auth::user();
@endphp

<script>
/**
 * window.POS_CONFIG
 * Central configuration object injected by the server (Blade template).
 * All JS files in /public/js/pos/ read from this object instead of
 * containing inline PHP.  This eliminates Blade-in-JS syntax errors.
 */
window.POS_CONFIG = {

    /* ── Feature flags ─────────────────────────────────────────────── */
    isEditMode       : {{ isset($lims_sale_data) ? 'true' : 'false' }},
    isRestaurant     : {{ $isRestaurant     ? 'true' : 'false' }},
    hasCashRegister  : {{ $hasCashRegister  ? 'true' : 'false' }},
    hasRazorpay      : {{ $hasRazorpay      ? 'true' : 'false' }},
    isSaaS           : {{ $isSaaS           ? 'true' : 'false' }},

    /* ── General settings ───────────────────────────────────────────── */
    decimal     : {{ (int)($general_setting->decimal ?? 2) }},
    dateFormat  : <?php echo json_encode($general_setting->date_format ?? 'Y-m-d', JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    withoutStock: <?php echo json_encode($general_setting->without_stock ?? 'no', JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    modules     : <?php echo json_encode(explode(',', $general_setting->modules ?? ''), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Auth ───────────────────────────────────────────────────────── */
    roleId          : <?php echo json_encode(\Auth::user()->role_id, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    keyboardActive  : <?php echo json_encode($keybord_active, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    alertProduct    : <?php echo json_encode($alert_product, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    authUser        : <?php echo json_encode($authUser, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    allPermissions  : <?php echo json_encode($all_permission, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Currency & pricing ─────────────────────────────────────────── */
    currency        : <?php echo json_encode($currency, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    priceType       : <?php echo json_encode(session('price_type') ?? '', JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    taxRateAll      : <?php echo json_encode($tax_rate_all ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Lists / data ───────────────────────────────────────────────── */
    couponList          : <?php echo json_encode($lims_coupon_list, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    customerList        : <?php echo json_encode($lims_customer_list, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    rewardPointSetting  : <?php echo json_encode($lims_reward_point_setting_data, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── POS settings ───────────────────────────────────────────────── */
    productRowNumber : {{ $lims_pos_setting_data ? (int)$lims_pos_setting_data->product_number : 10 }},
    stripePublicKey  : <?php echo json_encode($hasStripe ? $lims_pos_setting_data->stripe_public_key : null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Edit / draft data ──────────────────────────────────────────── */
    productSale     : <?php echo json_encode(!empty($lims_product_sale_data) ? $lims_product_sale_data : null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    draftProductData    : <?php echo json_encode(isset($draft_product_data)    ? $draft_product_data    : null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    draftProductDiscount: <?php echo json_encode(isset($draft_product_discount) ? $draft_product_discount : null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── SaaS (only populated when SaaS module is active) ───────────── */
    numberOfInvoice  : <?php echo json_encode(isset($numberOfInvoice) ? $numberOfInvoice : null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Razorpay branding ──────────────────────────────────────────── */
    razorpay : {
        siteTitle : <?php echo json_encode(config('site_title'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        siteLogo  : <?php echo json_encode(asset('logo/' . config('site_logo')), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    },

    /* ── Pre-rendered HTML snippets ─────────────────────────────────── */
    /**
     * Payment method <option> elements for the "Add More" payment row.
     * Built server-side so that @@if / @@foreach logic never appears in JS.
     */
    paymentOptionsHtml : <?php echo json_encode($paymentOptionsHtml, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── URLs ───────────────────────────────────────────────────────── */
    urls : {
        getProducts         : '{{ url("sales/getproducts") }}',
        searchProduct       : '{{ url("sales/lims_product_search") }}',
        getCustomerGroup    : '{{ url("sales/getcustomergroup") }}',
        recentSale          : '{{ url("sales/recent-sale") }}',
        recentDraft         : '{{ url("/sales/recent-draft") }}',
        getSale             : '{{ url("sales/get-sale") }}',
        productSale         : '{{ url("sales/product_sale") }}',
        todaySale           : '{{ url("sales/today-sale") }}',
        todayProfit         : '{{ url("sales/today-profit") }}',
        checkBatchAvail     : '{{ url("check-batch-availability") }}',
        getGiftCard         : '{{ url("sales/get_gift_card") }}',
        markNotificationRead: '{{ url("notifications/mark-as-read") }}',
        checkCashRegister   : '{{ url("cash-register/check-availability") }}',
        cashRegisterDetails : '{{ url("cash-register/getDetails") }}',
        productImages       : '{{ url("/images/product") }}',
        posEdit             : '{{ url("/pos") }}',
        salesDelete         : '{{ url("/sales") }}',
        customerStore       : '{{ route("customer.store") }}',
        salesIndex          : '{{ route("sales.index") }}',
        @if(config('database.connections.saleprosaas_landlord'))
        packageFetchData    : '{{ route("package.fetchData", $general_setting->package_id) }}',
        @else
        packageFetchData    : null,
        @endif
        genInvoice          : '{{ url("sales/gen_invoice") }}',
        whatsappSend        : '{{ route("whatsapp.send") }}',
        posIndex            : '{{ url("/pos") }}',
        checkDiscount       : '{{ url("/") }}/sales/check-discount',
        supplierAll         : '{{ route("supplier.all") }}',
        supplierDue         : '{{ url("supplier-due") }}',
        setPriceType        : '{{ route("set.price.type") }}',
    },

    /* ── Translated labels used in dynamically-built HTML ───────────── */
    labels : {
        inStock                : <?php echo json_encode(__('db.In Stock'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        selectAdditionalProducts: <?php echo json_encode(__('db.Select Additional Products'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        payingAmount           : <?php echo json_encode(__('db.Paying Amount'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        paidBy                 : <?php echo json_encode(__('db.Paid By'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        cashReceived           : <?php echo json_encode(__('db.Cash Received'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        date                   : <?php echo json_encode(__('db.date'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        reference              : <?php echo json_encode(__('db.reference'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        warehouse              : <?php echo json_encode(__('db.Warehouse'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        saleStatus             : <?php echo json_encode(__('db.Sale Status'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        currency               : <?php echo json_encode(__('db.Currency'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        exchangeRate           : <?php echo json_encode(__('db.Exchange Rate'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        table                  : <?php echo json_encode(__('db.Table'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        attachDocument         : <?php echo json_encode(__('db.Attach Document'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        from                   : <?php echo json_encode(__('db.From'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        to                     : <?php echo json_encode(__('db.To'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        total                  : <?php echo json_encode(__('db.Total'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        orderTax               : <?php echo json_encode(__('db.Order Tax'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        orderDiscount          : <?php echo json_encode(__('db.Order Discount'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        couponDiscount         : <?php echo json_encode(__('db.Coupon Discount'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        shippingCost           : <?php echo json_encode(__('db.Shipping Cost'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        grandTotal             : <?php echo json_encode(__('db.grand total'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        paidAmount             : <?php echo json_encode(__('db.Paid Amount'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        due                    : <?php echo json_encode(__('db.Due'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        saleNote               : <?php echo json_encode(__('db.Sale Note'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        staffNote              : <?php echo json_encode(__('db.Staff Note'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        createdBy              : <?php echo json_encode(__('db.Created By'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        customer               : <?php echo json_encode(__('db.customer'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        giftCard               : <?php echo json_encode(__('db.Gift Card'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        chequeNumber           : <?php echo json_encode(__('db.Cheque Number'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        creditLimitExceeded    : <?php echo json_encode(__('db.Credit limit exceeded!'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        enableCustomerDisplay    : <?php echo json_encode(__('db.enable_customer_display'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        disableCustomerDisplay   : <?php echo json_encode(__('db.disable_customer_display'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        amountExceedsBalance     : <?php echo json_encode(__('db.Amount exceeds available balance'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        receiptPrinted           : <?php echo json_encode(__('db.The receipt has been successfully printed'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        invoiceSettingsError     : <?php echo json_encode(__('db.Please select either the 58mm or 80mm template as the default in Invoice Settings'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        submit                   : <?php echo json_encode(__('db.submit'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        saleDraftSuccess         : <?php echo json_encode(__('db.Sale successfully added to draft'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
        invoice                  : <?php echo json_encode(__('db.Invoice'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    },
    /* ── Tax name lookup (for edit product modal) ───────────────────── */
    taxNameAll      : <?php echo json_encode($tax_name_all ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Edit mode sale discount values ─────────────────────────────── */
    saleOrderDiscountType  : <?php echo json_encode(isset($lims_sale_data) ? $lims_sale_data->order_discount_type  : null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,
    saleOrderDiscountValue : <?php echo json_encode(isset($lims_sale_data) ? $lims_sale_data->order_discount_value : null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

    /* ── Today's date (for coupon expiry check) ──────────────────────── */
    todayDate       : <?php echo json_encode(date('Y-m-d'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT); ?>,

};
</script>
