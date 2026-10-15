/* ================================================================
 * POS Main JavaScript
 * All PHP values referenced via window.POS_CONFIG (see config.blade.php)
 * ================================================================ */

(function() { // IIFE to avoid polluting global scope

// Fail loudly instead of silently killing every handler if the config
// <script> didn't render (usually a stale compiled Blade view on the server).
if (typeof window.POS_CONFIG === 'undefined') {
    console.error('[POS] POS_CONFIG is undefined — the POS config script did not render before pos.main.js. On the server clear compiled views (delete storage/framework/views/*.php or run `php artisan view:clear`), then hard-reload (Ctrl+Shift+R).');
    if (typeof $ !== 'undefined') {
        $('.loading-message').hide();
        $('.table-container.main').html('<div class="alert alert-danger m-2">POS failed to initialise: configuration missing. Clear the server view cache and hard-refresh (Ctrl+Shift+R).</div>');
    }
    return;
}

var isEditMode = POS_CONFIG.isEditMode;

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    ////Start the code is for SaleproSaas///
    if (POS_CONFIG.isSaaS) {
        numberOfInvoice = POS_CONFIG.numberOfInvoice;
        $.ajax({
            type: 'GET',
            async: false,
            url: POS_CONFIG.urls.packageFetchData,
            success: function(data) {
                if(data['number_of_invoice'] > 0 && data['number_of_invoice'] <= numberOfInvoice) {
                    location.href = POS_CONFIG.urls.salesIndex;
                }
            }
        });
    }
    ////End the code is for SaleproSaas///

    ///NOT NEEDED - Check///
    $("ul#sale").siblings('a').attr('aria-expanded','true');
    $("ul#sale").addClass("show");
    $("ul#sale #sale-pos-menu").addClass("active");
    ///NOT NEEDED - Check///

    ///start code for mobile////
    var isMobile = false;
    if (($(window).width() < 767)) {
        isMobile = true;
    }

    if(isMobile ==  true){
        $('.loading-message').hide();
        $('.table-container').hide();
        $('.more-payment-options > div > div').addClass('dropdown-menu');
        $('#collapseProducts').addClass('collapse');
        $('#grand-total-m').html($('input[name="grand_total"]').val());
    }


    var public_key = POS_CONFIG.stripePublicKey;
    var without_stock = POS_CONFIG.withoutStock;
    var alert_product = POS_CONFIG.alertProduct;
    var currency = POS_CONFIG.currency;
    var valid;
    var authUser = POS_CONFIG.authUser;
    // array data depend on warehouse
    var lims_product_array = [];
    var product_code = [];
    var product_name = [];
    var product_qty = [];
    var product_type = [];
    var product_id = [];
    var product_list = [];
    var qty_list = [];

    // array data with selection
    var product_price = [];
    var wholesale_price = [];
    var cost = [];
    var product_discount = [];
    var tax_rate = [];
    var tax_name = [];
    var tax_method = [];
    var unit_name = [];
    var unit_operator = [];
    var unit_operation_value = [];
    var is_imei = [];
    var is_variant = [];
    var gift_card_amount = [];
    var gift_card_expense = [];

    // temporary array
    var temp_unit_name = [];
    var temp_unit_operator = [];
    var temp_unit_operation_value = [];

    var reward_point_setting = POS_CONFIG.rewardPointSetting;

    var product_row_number = POS_CONFIG.productRowNumber;
    var rowindex;
    var customer_group_rate;
    var row_product_price;
    var pos;
    var keyboard_active = POS_CONFIG.keyboardActive;
    var role_id = POS_CONFIG.roleId;
    var warehouse_id = $('#warehouse_id').val();
    var coupon_list = POS_CONFIG.couponList;
    var currency = POS_CONFIG.currency;
    var currencyChange = false;
    var all_permission = POS_CONFIG.allPermissions;
    var next_page_url;
    var lims_customer_list = POS_CONFIG.customerList;

    // Load the full product grid for the current warehouse ('all' = no filter)
    function loadAllProducts() {
        if (!warehouse_id) return;
        $(".table-container.main").children().remove();
        $.get(POS_CONFIG.urls.getProducts + '/' + warehouse_id + '/all/0')
            .done(function (response) { populateProduct(response); })
            .fail(function (xhr) {
                $(".table-container.main").html('<div class="alert alert-danger">Failed to load products (' + xhr.status + '). Please reload or contact support.</div>');
            })
            .always(function () { $('.loading-message').hide(); });
    }

    $(window).on('load', async function () {
        loadAllProducts();

        var customer_id = $('#customer_id').val();
        if (customer_id) {
            var cus_gr_rt = await $.get(POS_CONFIG.urls.getCustomerGroup+'/' + customer_id);
            customer_group_rate = (cus_gr_rt / 100);
        }

        if (POS_CONFIG.hasCashRegister) isCashRegisterAvailable(warehouse_id);


        //Get recents sale when clicking recent transaction button
        $.get(POS_CONFIG.urls.recentSale, function(data) {
            populateRecentSale(data);
        });
        //Get recents draft when clicking recent transaction button
        $.get(POS_CONFIG.urls.recentDraft, function(data) {
            populateRecentDraft(data);
        });

        if(isEditMode){
            processDraftData();
        }

        saveDataToLocalStorageForCustomerDisplay('clear_no');

    })

    ///category button
    $('#category-filter').on('click', function(e){
        e.stopPropagation();
        $('.filter-window').show('slide', {direction: 'right'}, 'fast');
        $('.category').show();
        $('.brand').hide();
        $('.products-m').hide();
        $(".table-container").removeClass('brand').removeClass('featured').addClass('category');
    });

    //click on category image on the filter window shown after clicking the category button
    $(document).on('click','.category-img', function(){
        let category_id = $(this).data('category');
        $('.filter-window').hide('slide', {direction: 'right'}, 'fast');
        $(".table-container").children().remove();
        $.get(POS_CONFIG.urls.getProducts+'/' + warehouse_id + '/category/'+ category_id, function(response) {
            populateProduct(response);
        }).fail(function(xhr) {
            $(".table-container").html('<div class="alert alert-danger">Failed to load products (' + xhr.status + ').</div>');
        });

        if(isMobile == true){
            $('.filter-window').show('slide', {direction: 'right'}, 'fast');
        }
    });

    ///brand button
    $('#brand-filter').on('click', function(e){
        e.stopPropagation();
        $('.filter-window').show('slide', {direction: 'right'}, 'fast');
        $('.brand').show();
        $('.category').hide();
        $('.products-m').hide();
        $(".table-container").removeClass('category').removeClass('featured').addClass('brand');
    });

    //click on brand image on the filter window shown after clicking the brand button
    $(document).on('click','.brand-img', function(){
        var brand_id = $(this).data('brand');
        $('.filter-window').hide('slide', {direction: 'right'}, 'fast');
        $(".table-container").children().remove();
        $.get(POS_CONFIG.urls.getProducts+'/' + warehouse_id + '/brand/'+ brand_id, function(response) {
            populateProduct(response);
        }).fail(function(xhr) {
            $(".table-container").html('<div class="alert alert-danger">Failed to load products (' + xhr.status + ').</div>');
        });

        if(isMobile == true){
            $('.filter-window').show('slide', {direction: 'right'}, 'fast');
        }
    });

    ///featured button
    $('#featured-filter').on('click', function(e){
        $(".table-container").removeClass('category').removeClass('brand').addClass('featured');

        $.get(POS_CONFIG.urls.getProducts+'/' + warehouse_id + '/featured/1', function(response) {
            populateProduct(response);
        }).fail(function(xhr) {
            $(".table-container").html('<div class="alert alert-danger">Failed to load products (' + xhr.status + ').</div>');
        });

        if(isMobile == true){
            e.stopPropagation();
            $(".product_list_mobile.table-container").show();
            $('.product_list_mobile').html('');
            let featured_products = $(".table-container .product-grid").clone();
            $('.product_list_mobile').html(featured_products);
            $('.filter-window').show('slide', {direction: 'right'}, 'fast');
            $('.brand').hide();
            $('.category').hide();
        }
    });

    //close button on filter-window
    $(document).on('click','.btn-close', function(e){
        $('.filter-window').hide('slide', {direction: 'right'}, 'fast');
        $(".table-container").removeClass('category').removeClass('brand').removeClass('featured');
        if(isMobile == true){
            $(".table-container").hide();
        }
    });

    /// Start Load more button function///
    $(document).on('click', '.load-more', function() {
        $.ajax({
            url: next_page_url,
            type: "get",
        }).done(function(response) {
            appendProduct(response);
        });
    });

    $('#warehouse_id').on('change', function() {
        warehouse_id = $(this).val();
        loadAllProducts();
        if (POS_CONFIG.hasCashRegister) isCashRegisterAvailable(warehouse_id);

        saveDataToLocalStorageForCustomerDisplay('clear_no');
    });

    $('#customer_id').on('change', function() {
        var customer_id = $(this).val();
        $.get(POS_CONFIG.urls.getCustomerGroup+'/' + customer_id, function(data) {
            customer_group_rate = (data / 100);
        });

        var customer_type = $(this).find(':selected').data('type');
        if (customer_type == 'walkin') {
            $('#installmentPlanBtn').attr('disabled', true);
        } else {
             if ($('table.order-list tbody tr').length > 0) {
                $('#installmentPlanBtn').removeAttr('disabled');
            }
        }
    });

    function isCashRegisterAvailable(warehouse_id) {
        if (!POS_CONFIG.hasCashRegister) return;
        $.ajax({
            url: POS_CONFIG.urls.checkCashRegister+'/'+warehouse_id,
            type: "GET",
            success:function(data) {
                if(data == 'false') {
                    //$("#pos-layout").addClass('d-none');
                    $("#register-details-btn").addClass('d-none');
                    $('#cash-register-modal select[name=warehouse_id]').val(warehouse_id);

                    if(role_id <= 2)
                        $("#cash-register-modal .warehouse-section").removeClass('d-none');
                    else
                        $("#cash-register-modal .warehouse-section").addClass('d-none');

                    $('#cash-register-modal').modal({
                        backdrop: 'static',
                        keyboard: false // Optional: Prevents closing with the Escape key as well
                    });

                    $('.selectpicker').selectpicker('refresh');
                    $("#cash-register-modal").modal('show');
                }
                else{
                    $("#register-details-btn").removeClass('d-none');
                    $("#register-details-btn").data('id', data);
                    $('input[name="cash_register"]').val(data);
                }
            }
        });
    }

    function populateProduct(response) {
        var tableData = '<div class="product-grid">';
        $.each(response.data['name'], function(index) {
            if(response.data['image'][index])
                image = response.data['image'][index];
            else
                image = 'zummXD2dvAtI.png';
            tableData += '<div class="product-img sound-btn" title="'+response.data['name'][index]+'" data-code = "'+response.data['code'][index]+'" data-qty="'+response.data['qty'][index]+'" data-imei="'+response.data['is_imei'][index]+'" data-embedded="'+response.data['is_embeded'][index]+'" data-batch="'+response.data['batch'][index]+'" data-price="'+response.data['price'][index]+'"><img src="'+POS_CONFIG.urls.productImages+'/'+image+'" width="100%" /><p>'+response.data['name'][index]+'</p><span>['+response.data['code'][index]+']</span> <span class="d-block">Qty: '+response.data['qty'][index]+'</span></div>';
        });

        tableData += '</div>';

        next_page_url = response.next_page_url;
        if (next_page_url) {
            tableData += '<button class="btn btn-primary btn-block load-more"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5 12 21m0 0-7.5-7.5M12 21V3" /></svg></button>';
        }
        $(".table-container").html(tableData);

        if(isMobile){
            $('.brand').hide();
            $('.category').hide();
            $('.products-m').show();
            $(".product_list_mobile.table-container").show();
        }else{
            $(".table-container").show();
        }
    }

    function appendProduct(response) {
        var tableData = '';
        $.each(response.data['name'], function(index) {
            if(response.data['image'][index])
                image = response.data['image'][index];
            else
                image = 'zummXD2dvAtI.png';
            tableData += '<div class="product-img sound-btn" title="'+response.data['name'][index]+'" data-code = "'+response.data['code'][index]+'" data-qty="'+response.data['qty'][index]+'" data-imei="'+response.data['is_imei'][index]+'" data-embedded="'+response.data['is_embeded'][index]+'" data-batch="'+response.data['batch'][index]+'" data-price="'+response.data['price'][index]+'"><img src="'+POS_CONFIG.urls.productImages+'/'+image+'" width="100%" /><p>'+response.data['name'][index]+'</p><span>'+response.data['code'][index]+'</span> <span class="d-block">Qty: '+response.data['qty'][index]+'</span></div>';
        });
        $(".table-container .product-grid").append(tableData);

        next_page_url = response.next_page_url;
        if (!next_page_url) {
            $('.load-more').remove();
        }
    }

    $(document).on('click', '.expired', function() {
        playSound();
        alert('Product is expired!');
        return false;
    });

    $(document).on('click', '.product-img', function() {
        playSound();

        clearResults();

        var customer_id = $('#customer_id').val();
        var warehouse_id = $('#warehouse_id').val();
        var biller_id = $('#biller_id').val();

        if (POS_CONFIG.isRestaurant) {
        var table_id = $('#table_id').val();
        var waiter_id = $('#waiter_id').val();
        var service_id = $('#service_id').val();
        }

        if(isMobile){
            $('.filter-window').hide('slide', {direction: 'right'}, 'fast');
        }
        if(!customer_id)
            alert('Please select Customer!');
        else if(!warehouse_id)
            alert('Please select Warehouse!');
        else if(!biller_id)
            alert('Please select Biller!');
        if (POS_CONFIG.isRestaurant) { if(!table_id && service_id == 1){
            alert('Please select Table!');
        }
        else if(!waiter_id && service_id == 1){
            alert('Please select Waiter!');
        } }
        else{
            var data = $(this).data();
            productSearch(data);
        }
    });

    function processDraftData() {
        if (POS_CONFIG.draftProductData) {
            let draft_product_data = POS_CONFIG.draftProductData;
            draft_product_data.forEach(function(product) {
                productSearch(product);
            });
        }
    }

    function productSearch(data) {

        var item_code = data.code;
        var pre_qty = 0;
        var flag = true;
        $(".product-code").each(function(i) {
            if ($(this).val().trim() == item_code) {
                rowindex = i;
                if(data.imei != 'null' && data.imei != '') {
                    imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .imei-number').val();
                    imeiNumbersArray = imeiNumbers.split(",");

                    if(imeiNumbersArray.includes(data.imei)) {
                        alert('Same imei or serial number is not allowed!');
                        flag = false;
                        $('#product-search-input').val('');
                        return;
                    }
                }
                pre_qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val();
            }
        });
        if(flag)
        {
            let product = {
                code: data.code,
                qty: data.qty,
                pre_qty: (parseFloat(pre_qty) + 1),
                imei: data.imei,
                embedded: data.embedded,
                batch: data.batch,
                price: data.price,
                customer_id: $('#customer_id').val()
            };
            //data += '?'+$('#customer_id').val()+'?'+(parseFloat(pre_qty) + 1);
            $.ajax({
                type: 'GET',
                async: false,
                url: POS_CONFIG.urls.searchProduct,
                data: {
                    data: product
                },
                success: function(data) {
                    // console.log(data)
                    if(data[23]) {
                        data[15] = 1;
                        pre_qty = 0;
                    }
                    if(pre_qty > 0 && data[21]) {
                        var old_batch = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.batch-no').val();

                        if(old_batch && old_batch != data[22]) {
                            pre_qty = 0;
                            data[15] = 1;
                        }

                    }
                    var flag = 1;
                    if (pre_qty > 0) {
                        var qty = data[15];
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
                        //pos = product_code.indexOf(data[1]);

                        if(POS_PRICE_TYPE === 'wholesale') {
                            product_price[rowindex] = parseFloat(data[16] * currency['exchange_rate']) + parseFloat(data[16] * currency['exchange_rate'] * customer_group_rate);
                        } else if(POS_PRICE_TYPE === 'retail') {
                            product_price[rowindex] = parseFloat(data[2] * currency['exchange_rate']) + parseFloat(data[2] * currency['exchange_rate'] * customer_group_rate);
                        }

                        // checkDiscount(String(qty), true); //check discount is already called in addNewProduct() function
                        flag = 0;
                    }
                    $("input[name='product_code_name']").val('');

                    if(flag){
                        addNewProduct(data);
                    }
                    else if(data[18] != 'null' && data[18] != '') {
                        var imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();
                        if(imeiNumbers)
                            imeiNumbers += ','+data[18];
                        else
                            imeiNumbers = data[18];
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(imeiNumbers);
                    }
                }
            });
        }

    }

    const productSale = POS_CONFIG.productSale;

    var POS_PRICE_TYPE = POS_CONFIG.priceType;

    var _inStockLabel = POS_CONFIG.labels.inStock;

    function addNewProduct(data){
        $('.payment-btn').removeAttr('disabled');
        var customer_type = $('#customer_id option:selected').data('type');
        if (customer_type != 'walkin') {
            $('#installmentPlanBtn').removeAttr('disabled');
        }
        var newRow = $('<tr id='+ data[1] +'>');
        var cols = '';
        temp_unit_name = (data[6]).split(',');
        //pos = product_code.indexOf(data[1]);

        let stockDisplay = '';

        if (all_permission.includes("cart-product-update")) {
            if(data[20].trim() == 'standard' || data[20].trim() == 'combo'){
                if (!data[18] || data[18] == 'null') {
                    stockDisplay = ` | ${_inStockLabel} : <span class="in-stock">` + data[19] + `</span>`;
                }
            }
            cols += '<td class="col-sm-5 col-6 product-title"><strong class="edit-product btn btn-link" data-toggle="modal" data-target="#editModal">' + data[0] + ' <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></strong><br><span>' + data[1] + '</span>' + stockDisplay + ' <strong class="product-price d-none"></strong>';
        } else {
            cols += '<td class="col-sm-5 col-6 product-title"><strong>' + data[0] + '<br><span>' + data[1] + '</span>' + stockDisplay + ' <strong class="product-price d-none"></strong>';
        }

        if(data[12]) {
            cols += '<br><input style="font-size:13px;padding:3px 25px 3px 10px;height:30px !important" type="text" class="form-control batch-no" value="'+data[22]+'" required/> <input type="hidden" class="product-batch-id" name="product_batch_id[]" value="'+data[21]+'"/>';
        }
        else {
            cols += '<input type="text" class="form-control batch-no d-none" disabled/> <input type="hidden" class="product-batch-id" name="product_batch_id[]"/>';
        }

        cols += '</td>';
        cols += '<td class="col-sm-2 product-price d-none d-md-block"></td>';
        cols += '<td class="col-sm-3" style="min-width:140px"><div class="input-group"><span class="input-group-btn">';

        // Always show delete button
        cols += '<button type="button" class="ibtnDel btn btn-danger btn-sm mr-2" style="padding:5px"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button></span>';

        // If no IMEI, show minus button
        if (!data[18] || data[18] == 'null') {
            cols += '<button type="button" class="btn btn-light minus mr-1" style="padding:5px"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg></button>';
        }

        // Input field
        cols += '<input type="text" name="qty[]" class="form-control qty numkey input-number" step="any" value="'+data[15]+'" max="'+data[19]+'" required><span class="input-group-btn">';

        // If no IMEI, show plus button
        if (!data[18] || data[18] == 'null') {
            cols += '<button type="button" class="btn btn-light plus ml-1" style="padding:5px"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg></button>';
        }

        cols += '</span></div></td>';

        cols += '<td class="col-sm-2 sub-total"></td>';
        cols += '<input type="hidden" class="product-code" name="product_code[]" value="' + data[1] + '"/>';
        cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[9] + '"/>';
        cols += '<input type="hidden" class="product_type" name="product_type[]" value="' + data[20] + '"/>';
        cols += '<input type="hidden" class="product_price" />';
        cols += '<input type="hidden" class="sale-unit" name="sale_unit[]" value="' + temp_unit_name[0] + '"/>';
        cols += '<input type="hidden" class="net_unit_price" name="net_unit_price[]" />';
        cols += '<input type="hidden" class="discount-value" name="discount[]" />';
        cols += '<input type="hidden" class="tax-rate" name="tax_rate[]" value="' + data[3] + '"/>';
        cols += '<input type="hidden" class="tax-value" name="tax[]" />';
        cols += '<input type="hidden" class="tax-name" value="'+data[4]+'" />';
        cols += '<input type="hidden" class="tax-method" value="'+data[5]+'" />';
        cols += '<input type="hidden" class="sale-unit-operator" value="'+data[7]+'" />';
        cols += '<input type="hidden" class="sale-unit-operation-value" value="'+data[8]+'" />';
        cols += '<input type="hidden" class="subtotal-value" name="subtotal[]" />';
        if(data[18] != 'null' && data[18] != '')
            cols += '<input type="hidden" class="imei-number" name="imei_number[]" value="'+data[18]+'" />';
        else
            cols += '<input type="hidden" class="imei-number" name="imei_number[]" value="" />';
        if(data[23]){
            cols += '<input type="hidden" class="topping_product" name="topping_product[]" value="" />';
            cols += '<input type="hidden" class="topping-price" name="topping-price" value="" />';
        }

        newRow.append(cols);

        if(keyboard_active==1) {
            $("table.order-list tbody").prepend(newRow).find('.qty').keyboard({usePreview: false, layout: 'custom', display: { 'accept'  : '&#10004;', 'cancel'  : '&#10006;' }, customLayout : {
            'normal' : ['1 2 3', '4 5 6', '7 8 9','0 {dec} {bksp}','{clear} {cancel} {accept}']}, restrictInput : true, preventPaste : true, autoAccept : true, css: { container: 'center-block dropdown-menu', buttonDefault: 'btn btn-light', buttonHover: 'btn-primary',buttonAction: 'active', buttonDisabled: 'disabled'},});
        }
        else
            $("table.order-list tbody").prepend(newRow);

        rowindex = newRow.index();

        // if selling on wholesale, retail or both, then show wholesale price in the product price and calculate the total according to it. Otherwise, show the regular price.
        if(POS_PRICE_TYPE === 'wholesale') {
             product_price.splice(rowindex, 0, parseFloat(data[16] * currency['exchange_rate']) + parseFloat(data[16] * currency['exchange_rate'] * customer_group_rate));
        } else if (POS_PRICE_TYPE === 'retail') {
            product_price.splice(rowindex, 0, parseFloat(data[2] * currency['exchange_rate']) + parseFloat(data[2] * currency['exchange_rate'] * customer_group_rate));
        }
        else {
             product_price.splice(rowindex, 0, parseFloat(data[2] * currency['exchange_rate']) + parseFloat(data[2] * currency['exchange_rate'] * customer_group_rate));
        }


        if(data[16])
            wholesale_price.splice(rowindex, 0, parseFloat(data[16] * currency['exchange_rate']) + parseFloat(data[16] * currency['exchange_rate'] * customer_group_rate));
        else
            wholesale_price.splice(rowindex, 0, (0).toFixed(POS_CONFIG.decimal));

        cost.splice(rowindex, 0, parseFloat(data[17] * currency['exchange_rate']));
        product_discount.splice(rowindex, 0, (0).toFixed(POS_CONFIG.decimal));
        tax_rate.splice(rowindex, 0, parseFloat(data[3]));
        tax_name.splice(rowindex, 0, data[4]);
        tax_method.splice(rowindex, 0, data[5]);
        unit_name.splice(rowindex, 0, data[6]);
        unit_operator.splice(rowindex, 0, data[7]);
        unit_operation_value.splice(rowindex, 0, data[8]);
        is_imei.splice(rowindex, 0, data[13]);
        is_variant.splice(rowindex, 0, data[14]);

        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_price').val(product_price[rowindex]);

        checkQuantity(data[15], true);
        checkDiscount(data[15], true);

        if(data[16] && POS_PRICE_TYPE != 'wholesale' && POS_PRICE_TYPE != 'retail') {
            populatePriceOption();
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.edit-product').click();
        }

        // if(data[18]) {
        //     $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(imeiNumbers);
        // }

        // reset productSale array if product has toppings and is added from recent sale or draft
        if (data[23] && Array.isArray(data[23]) && data[23].length > 0) {
            if(productSale && productSale.length > 0) {

                if (product_discount[rowindex] < 1) {
                    cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
                    if (POS_CONFIG.draftProductDiscount) {
                        if (product_discount[rowindex] < 1) {
                            draft_discounts = POS_CONFIG.draftProductDiscount['discount'];
                            product_discount[rowindex] = draft_discounts[cur_product_id];
                        }
                    }
                }

                // Find a match for current data[9] (product_id)
                let matchedIndex = productSale.findIndex(p => parseInt(p.product_id) === parseInt(data[9]));

                if (matchedIndex !== -1) {
                    let matchedProduct = productSale[matchedIndex];

                    // Parse toppings
                    let toppings = JSON.parse(matchedProduct.topping_id || '[]');

                    let toppingNames = toppings.map(t => t.name).join(", ");
                    let totalToppingPrice = toppings.reduce((sum, t) => sum + parseFloat(t.price), 0);

                    newRow.find('.product-title').append(`<br><small>Includes: ${toppingNames}</small>`);
                    newRow.find('.topping_product').val(matchedProduct.topping_id);
                    newRow.find('.topping-price').val(totalToppingPrice.toFixed(POS_CONFIG.decimal));

                    const currentPrice = parseFloat(newRow.find('.product-price').text()) || 0;
                    const newPrice = currentPrice + totalToppingPrice;
                    newPrice -= product_discount[rowindex];
                    newRow.find('.product-price').text(newPrice.toFixed(POS_CONFIG.decimal));
                    newRow.find('.sub-total').text(newPrice.toFixed(POS_CONFIG.decimal));

                    // Remove used item from array
                    productSale.splice(matchedIndex, 1);

                    calculateTotal();
                }

            }else{

                openToppingsModal(data, [], rowindex);

                var _selectAdditionalProductsLabel = POS_CONFIG.labels.selectAdditionalProducts;
                function openToppingsModal(data, selectedToppings = [], rowIndex = null) {
                    let modalContent = '<form id="product-selection-form">';
                    data[23].forEach(product => {
                        const selected = selectedToppings.find(t => t.id == product.id);
                        const isChecked = selected ? 'checked' : '';
                        const qty = selected ? selected.qty : 1;

                        modalContent += `
                            <div class="form-check d-flex align-items-center mb-1">
                                <div>
                                    <input class="form-check-input" type="checkbox" name="productOption" id="product_${product.id}" value="${product.id}" data-name="${product.name}" data-price="${product.price}" ${isChecked}>
                                    <label class="form-check-label" for="product_${product.id}">
                                        ${product.name} (${product.code}) - ${product.price}
                                    </label>
                                </div>
                                <input type="number" name="quantity_${product.id}" id="quantity_${product.id}" class="form-control form-control-sm" style="width: 80px;" min="1" value="${qty}">
                            </div>`;
                    });
                    modalContent += '</form>';

                    const modalHTML = `
                        <div class="modal fade" id="productSelectionModal" tabindex="-1" role="dialog" aria-labelledby="productSelectionModalLabel" aria-hidden="true" data-rowindex="${rowIndex}">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="productSelectionModalLabel">${_selectAdditionalProductsLabel}</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">${modalContent}</div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-primary" id="confirmSelection">Confirm</button>
                                    </div>
                                </div>
                            </div>
                        </div>`;

                    // Remove existing modal if any, then append and show new
                    $("#productSelectionModal").remove();
                    $("body").append(modalHTML);
                    $("#productSelectionModal").modal('show');
                }

                // Handle selection confirmation
                $("#confirmSelection").on('click', function () {
                    let selectedToppings = [];
                    let totalAdditionalPrice = 0;

                    if (product_discount[rowindex] < 1) {
                        cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
                        if (POS_CONFIG.draftProductDiscount) {
                            if (product_discount[rowindex] < 1) {
                                draft_discounts = POS_CONFIG.draftProductDiscount['discount'];
                                product_discount[rowindex] = draft_discounts[cur_product_id];
                            }
                        }
                    }

                    $("input[name='productOption']:checked").each(function () {
                        const qty = parseFloat($(`#quantity_${$(this).val()}`).val() || 1); // define qty first

                        const topping = {
                            id: $(this).val(),
                            name: $(this).data('name'),
                            qty: qty,
                            price: parseFloat($(this).data('price')) * qty
                        };

                        selectedToppings.push(topping);
                        totalAdditionalPrice += topping.price;
                    });

                    if (selectedToppings.length > 0) {
                        // Convert the selected toppings array to JSON
                        const selectedToppingsJson = JSON.stringify(selectedToppings);

                        // Append toppings to the main product row
                        const selectedProductNames = selectedToppings.map(t => `${t.name} (${t.qty})`).join(', ');

                        newRow.find('.product-title').append(`<br><small>Includes: ${selectedProductNames}</small>`);

                        newRow.find('.topping_product').val(selectedToppingsJson); // Store JSON in hidden field

                        // Update the total price
                        const currentPrice = parseFloat(newRow.find('.product-price').text()) || 0;
                        let newPrice = currentPrice + totalAdditionalPrice;
                        newPrice -= product_discount[rowindex];
                        newRow.find('.product-price').text(newPrice.toFixed(POS_CONFIG.decimal));
                        newRow.find('.sub-total').text(newPrice.toFixed(POS_CONFIG.decimal));
                        newRow.find('.topping-price').val(totalAdditionalPrice.toFixed(POS_CONFIG.decimal));
                    }

                    $("#productSelectionModal").modal('hide');
                    $(".modal-backdrop").remove();
                    $("#productSelectionModal").remove();
                    calculateTotal();
                });

                // Stop further processing until the modal is resolved
                return;
            }
        }
    }

    $('#currency').val(currency['id']);

    $('#currency').change(function(){
        var rate = $(this).find(':selected').data('rate');
        var prev_rate = currency['exchange_rate'];
        var currency_id = $(this).val();
        $('#exchange_rate').val(rate);
        //$('input[name="currency_id"]').val(currency_id);
        currency['exchange_rate'] = rate;
        $("table.order-list tbody .product-id").each(function(index) {
            rowindex = index;
            currencyChange = true;
            cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
            var qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
            var price = (parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val()) + parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val()));

            price = (price / prev_rate);
            checkDiscount(qty, true, price);
            couponDiscount();
        });
    });

    $(document).on("click", "#print-btn", function() {
        var divContents = document.getElementById("get-sale-details").innerHTML;
        var a = window.open('');
        a.document.write('<html>');
        a.document.write('<body>');
        a.document.write('<style>body{line-height: 1.15;-webkit-text-size-adjust: 100%;}.d-print-none{display:none}.text-left{text-align:left}.text-center{text-align:center}.text-right{text-align:right}.row{width:100%;margin-right: -15px;margin-left: -15px;}.col-md-12{width:100%;display:block;padding: 5px 15px;}.col-md-6{width: 50%;float:left;padding: 5px 15px;}table{width:100%;margin-top:30px;}th{text-aligh:left}td{padding:10px}table,th,td{border: 1px solid black; border-collapse: collapse;}</style><style>@@media print {.modal-dialog { max-width: 1000px;} }</style>');
        a.document.write(divContents);
        a.document.write('</body></html>');
        a.document.close();
        a.print();
        setTimeout(function(){a.close();},10);
    });

    function convertDate(isoDate) {
        var date = new Date(isoDate);
        var day = String(date.getDate()).padStart(2, '0');
        var month = String(date.getMonth() + 1).padStart(2, '0'); // Months are zero-based
        var year = date.getFullYear();

        if(POS_CONFIG.dateFormat == 'd-m-Y') {
            return day + '-' + month + '-' + year;
        } else if(POS_CONFIG.dateFormat == 'd/m/Y') {
            return day + '/' + month + '/' + year;
        } else if(POS_CONFIG.dateFormat == 'd.m.Y') {
            return day + '.' + month + '.' + year;
        } else if(POS_CONFIG.dateFormat == 'm-d-Y') {
            return month + '-' + day + '-' + year;
        } else if(POS_CONFIG.dateFormat == 'm/d/Y') {
            return month + '/' + day + '/' + year;
        } else if(POS_CONFIG.dateFormat == 'm.d.Y') {
            return month + '.' + day + '.' + year;
        } else if(POS_CONFIG.dateFormat == 'Y-m-d') {
            return year + '-' + month + '-' + day;
        } else if(POS_CONFIG.dateFormat == 'Y/m/d') {
            return year + '/' + month + '/' + day;
        } else if(POS_CONFIG.dateFormat == 'Y.m.d') {
            return year + '.' + month + '.' + day;
        }

    }

    if(keyboard_active==1){

        $("input.numkey:text").keyboard({
            usePreview: false,
            layout: 'custom',
            display: {
            'accept'  : '&#10004;',
            'cancel'  : '&#10006;'
            },
            customLayout : {
            'normal' : ['1 2 3', '4 5 6', '7 8 9','0 {dec} {bksp}','{clear} {cancel} {accept}']
            },
            restrictInput : true, // Prevent keys not in the displayed keyboard from being typed in
            preventPaste : true,  // prevent ctrl-v and right click
            autoAccept : true,
            css: {
                // input & preview
                // keyboard container
                container: 'center-block dropdown-menu', // jumbotron
                // default state
                buttonDefault: 'btn btn-light',
                // hovered button
                buttonHover: 'btn-primary',
                // Action keys (e.g. Accept, Cancel, Tab, etc);
                // this replaces "actionClass" option
                buttonAction: 'active'
            },
        });

        $('input[type="text"]').keyboard({
            usePreview: false,
            autoAccept: true,
            autoAcceptOnEsc: true,
            css: {
                // input & preview
                // keyboard container
                container: 'center-block dropdown-menu', // jumbotron
                // default state
                buttonDefault: 'btn btn-light',
                // hovered button
                buttonHover: 'btn-primary',
                // Action keys (e.g. Accept, Cancel, Tab, etc);
                // this replaces "actionClass" option
                buttonAction: 'active',
                // used when disabling the decimal button {dec}
                // when a decimal exists in the input area
                buttonDisabled: 'disabled'
            },
            change: function(e, keyboard) {
                    keyboard.$el.val(keyboard.$preview.val())
                    keyboard.$el.trigger('propertychange')
                }
        });

        $('textarea').keyboard({
            usePreview: false,
            autoAccept: true,
            autoAcceptOnEsc: true,
            css: {
                // input & preview
                // keyboard container
                container: 'center-block dropdown-menu', // jumbotron
                // default state
                buttonDefault: 'btn btn-light',
                // hovered button
                buttonHover: 'btn-primary',
                // Action keys (e.g. Accept, Cancel, Tab, etc);
                // this replaces "actionClass" option
                buttonAction: 'active',
                // used when disabling the decimal button {dec}
                // when a decimal exists in the input area
                buttonDisabled: 'disabled'
            },
            change: function(e, keyboard) {
                    keyboard.$el.val(keyboard.$preview.val())
                    keyboard.$el.trigger('propertychange')
                }
        });

        $('#lims_productcodeSearch').keyboard().autocomplete().addAutocomplete({
            // add autocomplete window positioning
            // options here (using position utility)
            position: {
            of: '#lims_productcodeSearch',
            my: 'top+18px',
            at: 'center',
            collision: 'flip'
            }
        });
    }
    // Add More Button of Multiple Payment Modal
    var _payingAmountLabel = POS_CONFIG.labels.payingAmount;
    var _paidByLabel = POS_CONFIG.labels.paidBy;
    var _cashReceivedLabel = POS_CONFIG.labels.cashReceived;
    var _cashInfoIcon = '<span class="dripicons-information" data-toggle="tooltip" title="Cash handed over to you. example: sale amount is 300. customer gives you 500. cash received: 500 " style="color:#004085;display:inline-block;font-size:15px;cursor:help;"></span>';
    $('.add-more').on("click", function(e) {
        e.preventDefault();
        var toPay = 0;
        var htmlText = `<div class="row new-row">
                            <div class="col-md-3 col-6 mt-2 paying-amount-container">
                                <label>${_payingAmountLabel} *</label>
                                <input type="text" name="paid_amount[]" value="` + toPay + `" class="form-control paid_amount numkey" step="any">
                            </div>
                            <div class="col-md-3 col-6 mt-2">
                                <input type="hidden" name="paid_by_id[]">
                                <label>${_paidByLabel}</label>
                                <select name="paid_by_id_select[]" class="form-control selectpicker">
                                    ${POS_CONFIG.paymentOptionsHtml}
                                </select>
                            </div>
                            <div class="col-md-3 col-5 mt-2 cash-received-container">
                                <label>${_cashReceivedLabel} ${_cashInfoIcon}</label>
                                <input type="text" name="paying_amount[]" class="form-control paying_amount numkey" required step="any">
                            </div>
                            <div class="col-1 mt-2">
                                <button class="btn btn-danger remove-row mt-4">X</button>
                            </div></div>`;
        $('.add-more-row').before(htmlText);
        var total_paid_amount = 0;
        $('.paid_amount').each(function(){
            var value = parseFloat($(this).val()) || 0;
            total_paid_amount += value;

        });
        var more_to_pay = ($("#grand-total").text() - total_paid_amount).toFixed(POS_CONFIG.decimal);
        $('.paid_amount:last').val(more_to_pay);
        $('.paying_amount:last').val(more_to_pay);
        $('.selectpicker').selectpicker('refresh');
        if ($('.qc').length) {
            $('.qc').data('initial', 1); // Update the data attribute
        }
        calculatePayingAmount();
    });

    $(document).on("click", ".remove-row", function() {
        $(this).parent().parent().remove();
        calculatePayingAmount();
        updateChange();
    });

    $('.customer-submit-btn').on("click", function() {
        var iti = window.intlTelInputGlobals.getInstance(input);
        var full_number = iti.getNumber();
        $('#full_phone').val(full_number);

        $.ajax({
            type:'POST',
            url:POS_CONFIG.urls.customerStore,
            data: $("#customer-form").serialize(),
            success:function(response) {
                key = response['id'];
                value = response['name']+' ['+response['phone_number']+']';
                $('select[name="customer_id"]').append('<option value="'+ key +'" data-type="'+response['type']+'">'+ value +'</option>');
                $('select[name="customer_id"]').val(key).trigger('change');
                $('.selectpicker').selectpicker('refresh');
                $("#addCustomer").modal('hide');
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;

                    // Clear old alerts
                    $('.alert-container').html('');

                    // Loop through all errors and create a separate alert for each message
                    $.each(errors, function (field, messages) {
                        $.each(messages, function (index, message) {
                            $('.alert-container').append(`
                                <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
                                    ${message}
                                </div>
                            `);
                        });
                    });
                }
            }
        });
    });

    $("li#notification-icon").on("click", function (argument) {
        $.get(POS_CONFIG.urls.markNotificationRead, function(data) {
            $("span.notification-number").text(alert_product);
        });
    });
    if (POS_CONFIG.hasCashRegister) {
    $("#register-details-btn").data('id');

    $("#register-details-btn").on("click", function (e) {
        e.preventDefault();
        $('#closing_row').hide();
        $('#submit_register').hide();
        $('#close_register').show();
        var cash_register_id = $(this).data('id');
        $.ajax({
            url: POS_CONFIG.urls.cashRegisterDetails+'/'+cash_register_id,
            type: "GET",
            success:function(data) {
                $('#register-details-modal #cash_in_hand').text(data['cash_in_hand'].toFixed(2));
                $('#register-details-modal #total_sale_amount').text(data['total_sale_amount'].toFixed(2));
                $('#register-details-modal #total_payment').text(data['total_payment'].toFixed(2));
                $('#register-details-modal #cash_payment').text(data['cash_payment'].toFixed(2));
                $('#register-details-modal #credit_card_payment').text(data['credit_card_payment'].toFixed(2));
                $('#register-details-modal #cheque_payment').text(data['cheque_payment'].toFixed(2));
                $('#register-details-modal #gift_card_payment').text(data['gift_card_payment'].toFixed(2));
                $('#register-details-modal #deposit_payment').text(data['deposit_payment'].toFixed(2));
                $('#register-details-modal #paypal_payment').text(data['paypal_payment'].toFixed(2));
                if (data.custom_methods) {
                    $('#custom-methods-container').empty();

                    $.each(data.custom_methods, function(key, value) {
                        let method_name = key.replace('_payment', '').replace(/_/g, ' ');
                        $('#custom-methods-container').append(
                            `<tr>
                                <td>${method_name.charAt(0).toUpperCase() + method_name.slice(1)}:</td>
                                <td id="${key}" class="text-right">${value.toFixed(2)}</td>
                            </tr>`
                        );
                    });
                }
                $('#register-details-modal #total_sale_return').text(data['total_sale_return'].toFixed(2));
                $('#register-details-modal #total_expense').text(data['total_expense'].toFixed(2));
                $('#register-details-modal #total_cash').text(data['total_cash'].toFixed(2));
                $('#register-details-modal input[name=actual_cash]').val(data['total_cash'].toFixed(2));
                $('#register-details-modal input[name=closing_balance]').val(data['total_cash'].toFixed(2));
                $('#register-details-modal #total_supplier_payment').text(data['total_supplier_payment'].toFixed(2));
                $('#register-details-modal input[name=cash_register_id]').val(cash_register_id);

                $('#register-details-modal').modal('show');
            }
        });
    });

    $("#close_register").on("click", function (e) {
        $('#closing_row').show();
        $('#submit_register').show();
        $(this).hide();
    });
    } // end if(POS_CONFIG.hasCashRegister)

    $("#today-sale-btn").on("click", function (e) {
        e.preventDefault();
        $.ajax({
            url: POS_CONFIG.urls.todaySale,
            type: "GET",
            success:function(data) {
                $('#today-sale-modal .total_sale_amount').text(data['total_sale_amount']);
                $('#today-sale-modal .total_payment').text(data['total_payment']);
                $('#today-sale-modal .cash_payment').text(data['cash_payment']);
                $('#today-sale-modal .credit_card_payment').text(data['credit_card_payment']);
                $('#today-sale-modal .cheque_payment').text(data['cheque_payment']);
                $('#today-sale-modal .gift_card_payment').text(data['gift_card_payment']);
                $('#today-sale-modal .deposit_payment').text(data['deposit_payment']);
                $('#today-sale-modal .paypal_payment').text(data['paypal_payment']);
                $('#today-sale-modal .total_sale_return').text(data['total_sale_return']);
                $('#today-sale-modal .total_expense').text(data['total_expense']);
                $('#today-sale-modal .total_cash').text(data['total_cash']);
            }
        });
        $('#today-sale-modal').modal('show');
    });

    $("#today-profit-btn").on("click", function (e) {
        e.preventDefault();
        calculateTodayProfit(0);
    });

    $("#today-profit-modal select[name=warehouseId]").on("change", function() {
        calculateTodayProfit($(this).val());
    });

    function calculateTodayProfit(warehouse_id) {
        $.ajax({
                url: POS_CONFIG.urls.todayProfit+'/' + warehouse_id,
                type: "GET",
                success:function(data) {
                    $('#today-profit-modal .product_revenue').text(data['product_revenue']);
                    $('#today-profit-modal .product_cost').text(data['product_cost']);
                    $('#today-profit-modal .expense_amount').text(data['expense_amount']);
                    $('#today-profit-modal .profit').text(data['profit']);
                }
            });
        $('#today-profit-modal').modal('show');
    }

    if(keyboard_active==1){
        $('#lims_productcodeSearch').bind('keyboardChange', function (e, keyboard, el) {
            var customer_id = $('#customer_id').val();
            var warehouse_id = $('#warehouse_id').val();
            var biller_id = $('#biller_id').val();

            if (POS_CONFIG.isRestaurant) {
            var table_id = $('#table_id').val();
            var waiter_id = $('#waiter_id').val();
            var service_id = $('#service_id').val();
            }

            temp_data = $('#lims_productcodeSearch').val();
            if(!customer_id){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Customer!');
            }
            else if(!warehouse_id){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Warehouse!');
            }
            else if(!biller_id){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Biller!');
            }
            if (POS_CONFIG.isRestaurant) { if(!table_id && service_id == 1){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Table!');
            }
            else if(!waiter_id && service_id == 1){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Waiter!');
            } }
        });
    }
    else{
        $('#lims_productcodeSearch').on('input', function(){
            var customer_id = $('#customer_id').val();
            var warehouse_id = $('#warehouse_id').val();
            var biller_id = $('#biller_id').val();

            if (POS_CONFIG.isRestaurant) {
            var table_id = $('#table_id').val();
            var waiter_id = $('#waiter_id').val();
            var service_id = $('#service_id').val();
            }

            temp_data = $('#lims_productcodeSearch').val();
            if(!customer_id){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Customer!');
            }
            else if(!warehouse_id){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Warehouse!');
            }
            else if(!biller_id){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Warehouse!');
            }
            if (POS_CONFIG.isRestaurant) { if(!table_id && service_id == 1){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Table!');
            }
            else if(!waiter_id && service_id == 1){
                $('#lims_productcodeSearch').val(temp_data.substring(0, temp_data.length - 1));
                alert('Please select Waiter!');
            } }
        });
    }

    $(document).on('click', '.view-sale', function(e) {
        e.preventDefault();
        sale_id = $(this).val();

        $.ajax({
            url: POS_CONFIG.urls.getSale+'/' + sale_id,
            type: 'GET',
            success: function(sale) {
                saleDetails(sale);
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            }
        });
        $('#recentTransaction').modal('hide')
    });

    $(document).on('click', '#close-btn', function () {
        $('#recentTransaction').modal('show')
    });

    function saleDetails(sale){

        var htmltext = '<strong>'+POS_CONFIG.labels.date+': </strong>'+sale[0]+
            '<br><strong>'+POS_CONFIG.labels.reference+': </strong>'+sale[1]+
            '<br><strong>'+POS_CONFIG.labels.warehouse+': </strong>'+sale[27]+
            '<br><strong>'+POS_CONFIG.labels.saleStatus+': </strong>'+sale[2]+
            '<br><strong>'+POS_CONFIG.labels.currency+': </strong>'+sale[31];

        if(sale[32])
            htmltext += '<br><strong>'+POS_CONFIG.labels.exchangeRate+': </strong>'+sale[32]+'<br>';
        else
            htmltext += '<br><strong>'+POS_CONFIG.labels.exchangeRate+': </strong>N/A<br>';
        if(sale[33])
            htmltext += '<strong>'+POS_CONFIG.labels.table+': </strong>'+sale[33]+'<br>';
        if(sale[30])
            htmltext += '<strong>'+POS_CONFIG.labels.attachDocument+': </strong><a href="documents/sale/'+sale[30]+'">Download</a><br>';

        htmltext += '<br><div class="row"><div class="col-md-6"><strong>'+POS_CONFIG.labels.from+':</strong><br>'+sale[3]+'<br>'+sale[4]+'<br>'+sale[5]+'<br>'+sale[6]+'<br>'+sale[7]+'<br>'+sale[8]+
        '</div><div class="col-md-6"><div class="float-right"><strong>'+POS_CONFIG.labels.to+':</strong><br>'+sale[9]+'<br>'+sale[10]+'<br>'+sale[11]+'<br>'+sale[12]+'</div></div></div>';

        $.get(POS_CONFIG.urls.productSale+'/' + sale[13], function(data){
            $(".product-sale-list tbody").remove();
            var name_code = data[0];
            var qty = data[1];
            var unit_code = data[2];
            var tax = data[3];
            var tax_rate = data[4];
            var discount = data[5];
            var subtotal = data[6];
            var batch_no = data[7];
            var return_qty = data[8];
            var is_delivered = data[9];
            // Check if data[10] exists
            var toppings = data[10] ? data[10] : [];
            var total_qty = 0;
            var newBody = $("<tbody>");

            $.each(name_code, function(index){
                var newRow = $("<tr>");
                var cols = '';
                cols += '<td><strong>' + (index+1) + '</strong></td>';
                cols += '<td>' + name_code[index];

                // Append topping names if toppings[index] exists
                if (toppings[index]) {
                    try {
                        // Parse and extract topping names
                        var toppingData = JSON.parse(toppings[index]);
                        var toppingNames = toppingData.map(topping => topping.name).join(', ');
                        cols += ' (' + toppingNames + ')';
                    } catch (error) {
                        console.error('Error parsing toppings for index', index, toppings[index], error);
                    }
                }

                cols += '</td>';
                cols += '<td>' + batch_no[index] + '</td>';
                cols += '<td>' + qty[index] + ' ' + unit_code[index] + '</td>';
                cols += '<td>' + return_qty[index] + '</td>';
                // Calculate unit price
                var unitPrice = parseFloat(subtotal[index] / qty[index]).toFixed(POS_CONFIG.decimal);

                // Calculate topping prices if toppings[index] exists
                var toppingPrices = '';
                if (toppings[index]) {
                    try {
                        var toppingData = JSON.parse(toppings[index]); // Parse topping data
                        toppingPrices = toppingData
                            .map(topping => parseFloat(topping.price).toFixed(POS_CONFIG.decimal)) // Extract and format each topping price
                            .join(' + '); // Join prices with '+'
                    } catch (error) {
                        console.error('Error calculating topping prices for index', index, toppings[index], error);
                    }
                }

                cols += '<td>' + unitPrice + ' (' + toppingPrices + ')</td>';
                cols += '<td>' + tax[index] + '(' + tax_rate[index] + '%)' + '</td>';
                cols += '<td>' + discount[index] + '</td>';
                // Update subtotal to include topping prices
                var toppingPricesRowTotal = 0;
                if (toppings[index]) {
                    try {
                        var toppingData = JSON.parse(toppings[index]);
                        toppingPricesRowTotal = toppingData.reduce((sum, topping) => sum + parseFloat(topping.price), 0);
                    } catch (error) {
                        console.error('Error calculating topping prices for index', index, toppings[index], error);
                    }
                }
                subtotal[index] = parseFloat(subtotal[index]) + toppingPricesRowTotal;
                cols += '<td>' + subtotal[index].toFixed(POS_CONFIG.decimal) + '</td>';
                cols += '<td>' + is_delivered[index] + '</td>';
                total_qty += parseFloat(qty[index]);
                newRow.append(cols);
                newBody.append(newRow);
            });

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=3><strong>'+POS_CONFIG.labels.total+':</strong></td>';
            cols += '<td>' + total_qty + '</td>';
            cols += '<td colspan=2></td>';
            cols += '<td>' + sale[14] + '</td>';
            cols += '<td>' + sale[15] + '</td>';
            cols += '<td>' + sale[16] + '</td>';
            cols += '<td></td>';
            newRow.append(cols);
            newBody.append(newRow);

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=9><strong>'+POS_CONFIG.labels.orderTax+':</strong></td>';
            cols += '<td>' + sale[17] + '(' + sale[18] + '%)' + '</td>';
            newRow.append(cols);
            newBody.append(newRow);

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=9><strong>'+POS_CONFIG.labels.orderDiscount+':</strong></td>';
            cols += '<td>' + sale[19] + '</td>';
            newRow.append(cols);
            newBody.append(newRow);
            if(sale[28]) {
                var newRow = $("<tr>");
                cols = '';
                cols += '<td colspan=9><strong>'+POS_CONFIG.labels.couponDiscount+' ['+sale[28]+']:</strong></td>';
                cols += '<td>' + sale[29] + '</td>';
                newRow.append(cols);
                newBody.append(newRow);
            }

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=9><strong>'+POS_CONFIG.labels.shippingCost+':</strong></td>';
            cols += '<td>' + sale[20] + '</td>';
            newRow.append(cols);
            newBody.append(newRow);

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=9><strong>'+POS_CONFIG.labels.grandTotal+':</strong></td>';
            cols += '<td>' + sale[21] + '</td>';
            newRow.append(cols);
            newBody.append(newRow);

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=9><strong>'+POS_CONFIG.labels.paidAmount+':</strong></td>';
            cols += '<td>' + sale[22] + '</td>';
            newRow.append(cols);
            newBody.append(newRow);

            var newRow = $("<tr>");
            cols = '';
            cols += '<td colspan=9><strong>'+POS_CONFIG.labels.due+':</strong></td>';
            cols += '<td>' + parseFloat(sale[21] - sale[22]).toFixed(POS_CONFIG.decimal) + '</td>';
            newRow.append(cols);
            newBody.append(newRow);

            $("table.product-sale-list").append(newBody);
        });
        var htmlfooter = '<p><strong>'+POS_CONFIG.labels.saleNote+':</strong> '+sale[23]+'</p><p><strong>'+POS_CONFIG.labels.staffNote+':</strong> '+sale[24]+'</p><strong>'+POS_CONFIG.labels.createdBy+':</strong><br>'+sale[25]+'<br>'+sale[26];
        $('#sale-content').html(htmltext);
        $('#sale-footer').html(htmlfooter);
        $('#get-sale-details').modal('show');
    }


    function populateRecentSale(data) {
        var tableData = '';
        $.each(data, function(index,sale) {
            tableData += '<tr>';
            tableData += '<td>' + convertDate(sale.created_at) + '</td>';
            tableData += '<td>' + sale.reference_no + '</td>';
            tableData += '<td>' + sale.name + '</td>';
            tableData += '<td>' + sale.grand_total + '</td>';

            tableData += '<td>'

            // if (all_permission.includes("sales-edit")) {
                tableData += '<button  type="button" class="btn btn-success btn-sm view-sale" title="View" data-toggle="modal" data-target="#get-sale-details" value="' + sale.id + '"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></button>&nbsp';
            // }
            if (all_permission.includes("sales-edit")) {
                tableData += '<a href="sales/' + sale.id + '/edit" class="btn btn-warning btn-sm" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></a>&nbsp';
            }
            if (all_permission.includes("sales-delete")) {
                tableData += '<form class="d-inline" action="'+ POS_CONFIG.urls.salesDelete +'/'+ sale.id +'" method="POST"><input name="_method" type="hidden" value="DELETE"><input type="hidden" name="_token" value="'+$("meta[name=\'csrf-token\']").attr("content")+'">';
                tableData += '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirmDelete()" title="Delete"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button>';
                tableData += '</form>';
            }
            tableData += '</td>'

            tableData += '</tr>';
        });

        $("#sale-latest tbody").html(tableData);
    }

    function populateRecentDraft(data) {
        var tableData = '';

        $.each(data, function(index,draft) {
            tableData += '<tr>';
            tableData += '<td>' + convertDate(draft.created_at) + '</td>';
            tableData += '<td>' + draft.reference_no + '</td>';
            tableData += '<td>' + draft.name + '</td>';
            tableData += '<td>' + draft.grand_total + '</td>';

            tableData += '<td>'

            if (all_permission.includes("sales-edit")) {
                tableData += '<a href="'+POS_CONFIG.urls.posIndex+'/' + draft.id + '" class="btn btn-warning btn-sm" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></a>&nbsp';
            }

            if (all_permission.includes("sales-delete")) {
                tableData += '<form class="d-inline" action="'+ POS_CONFIG.urls.salesDelete +'/'+ draft.id +'" method="POST"><input name="_method" type="hidden" value="DELETE"><input type="hidden" name="_token" value="'+$("meta[name=\'csrf-token\']").attr("content")+'">';
                tableData += '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirmDelete()" title="Delete"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></button>';
                tableData += '</form>';
            }
            tableData += '</td>'

            tableData += '</tr>';
        });

        $("#draft-latest tbody").html(tableData);
    }

    $("#myTable").on('click', '.plus', function() {
        var row = $(this).closest('tr');
        var qtyInput = row.find('.qty');

        var qty = parseFloat(qtyInput.val()) || 0;
        var max_qty = parseFloat(qtyInput.attr('max'));
        if(!qty)
            qty = 1;
        else if(!isNaN(max_qty) && qty >= max_qty && without_stock == 'no') {
            alert("Quantity cannot exceed available stock (" + max_qty + ").");
            return;
        }
        else
            qty = parseFloat(qty) + 1;
        if(is_variant[rowindex]){
            checkQuantity(String(qty), true);
        }else{
            checkDiscount(qty, true);
        }
    });

    $("#myTable").on('click', '.minus', function() {
        rowindex = $(this).closest('tr').index();
        var qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val()) - 1;
        if (qty > 0) {
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);

            if(is_variant[rowindex])
                checkQuantity(String(qty), true);
            else
                checkDiscount(qty, '3');
        }
        else {
            qty = 1;
        }

    });

    $(document).on("change", "select[name=price_option]", function () {
        $("#editModal input[name=edit_unit_price]").val($(this).val());
    });

    $("#myTable").on("change", ".batch-no", function () {
        rowindex = $(this).closest('tr').index();
        var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-id').val();
        var warehouse_id = $('#warehouse_id').val();
        $.get(POS_CONFIG.urls.checkBatchAvail+'/' + product_id + '/' + $(this).val() + '/' + warehouse_id, function(data) {
            if(data['message'] != 'ok') {
                alert(data['message']);
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.batch-no').val('');
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-batch-id').val('');
            }
            else {
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-batch-id').val(data['product_batch_id']);
                code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-code').val();
                //pos = product_code.indexOf(code);
                product_qty[pos] = data['qty'];
            }
        });
    });

    let previousqty = '';

    $("#myTable").on('focus', '.qty', function() {
        previousqty = $(this).val();
    });

    //Change quantity
    $("#myTable").on('focusout', '.qty', function () {

        let $input  = $(this);
        let value   = $.trim($input.val());
        let max     = parseFloat($input.attr('max'));
        rowindex = $input.closest('tr').index();

        // --- 1) Empty or non-numeric check
        if (value === "" || isNaN(value)) {
            $input.val(1);
            alert("Quantity must be a number.");
            return;
        }

        value = parseFloat(value);

        // --- 2) Must be greater than 0
        if (value <= 0) {
            $input.val(1);
            alert("Quantity must be greater than 0.");
            return;
        }

        // --- 3) Max attribute validation
        if (!isNaN(max) && value > max && without_stock == 'no') {
            $input.val(max);
            alert("Quantity cannot exceed available stock (" + max + ").");
            return;
        }

        // --- 4) Safe to continue with valid value
        $input.val(value);

        if(is_variant[rowindex]){
            checkQuantity(value, true);
        } else {
            checkDiscount(value, 'input');
        }
    });


    $("#myTable").on('click', '.qty', function() {
        rowindex = $(this).closest('tr').index();
    });

    //Delete product
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        playSound();
        rowindex = $(this).closest('tr').index();
        var qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
        checkDiscount(qty,false);
        product_price.splice(rowindex, 1);
        wholesale_price.splice(rowindex, 1);
        product_discount.splice(rowindex, 1);
        tax_rate.splice(rowindex, 1);
        tax_name.splice(rowindex, 1);
        tax_method.splice(rowindex, 1);
        unit_name.splice(rowindex, 1);
        unit_operator.splice(rowindex, 1);
        unit_operation_value.splice(rowindex, 1);
        $(this).closest("tr").remove();
        calculateTotal();
        if ($('#tbody-id tr').length < 1) {
            $('.payment-btn').attr('disabled', true);
            $('#installmentPlanBtn').attr('disabled', true);
        }
    });


    //Edit product
    $("table.order-list").on("click", ".edit-product", function() {
        rowindex = $(this).closest('tr').index();
        edit();
    });

    //Update product
    $('button[name="update_btn"]').on("click", function() {
        if(is_imei[rowindex]) {
            var imeiNumbers = '';
            $("#editModal .imei-numbers").each(function(i) {
                if (i)
                    imeiNumbers += ','+ $(this).val();
                else
                    imeiNumbers = $(this).val();
            });
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(imeiNumbers);
        }

        var edit_discount = $('input[name="edit_discount"]').val();
        var edit_qty = $('input[name="edit_qty"]').val();
        var edit_unit_price = $('input[name="edit_unit_price"]').val();

        if (parseFloat(edit_discount) > parseFloat(edit_unit_price)) {
            alert('Invalid Discount Input!');
            return;
        }

        if(edit_qty < 0) {
            $('input[name="edit_qty"]').val(1);
            edit_qty = 1;
            alert("Quantity can't be less than 0");
        }

        var tax_rate_all = POS_CONFIG.taxRateAll;

        tax_rate[rowindex]  = parseFloat(tax_rate_all[$('select[name="edit_tax_rate"]').val()]);
        tax_name[rowindex]  = $('select[name="edit_tax_rate"] option:selected').text();

        var product_type = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();

        product_discount[rowindex] = $('input[name="edit_discount"]').val();
        if(product_type == 'standard'){

            row_unit_operator= $('#edit_unit select').find(':selected').data('operator');
            row_unit_operation_value = $('#edit_unit select').find(':selected').data('operation-value');

            if (row_unit_operator == '*') {
                product_price[rowindex] = $('input[name="edit_unit_price"]').val() * row_unit_operation_value;
            } else {
                product_price[rowindex] = $('input[name="edit_unit_price"]').val() / row_unit_operation_value;
            }
            var position = $('select[name="edit_unit"]').val();
            var temp_operator = temp_unit_operator[position];
            var temp_operation_value = temp_unit_operation_value[position];
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sale-unit').val(temp_unit_name[position]);
            temp_unit_name.splice(position, 1);
            temp_unit_operator.splice(position, 1);
            temp_unit_operation_value.splice(position, 1);

            temp_unit_name.unshift($('select[name="edit_unit"] option:selected').text());
            temp_unit_operator.unshift(temp_operator);
            temp_unit_operation_value.unshift(temp_operation_value);

            unit_name[rowindex] = temp_unit_name.toString() + ',';
            unit_operator[rowindex] = temp_unit_operator.toString() + ',';
            unit_operation_value[rowindex] = temp_unit_operation_value.toString() + ',';

        }
        else {
            product_price[rowindex] = $('input[name="edit_unit_price"]').val();
        }
        checkDiscount(edit_qty, false);

        $('#editModal').modal('hide');
    });

    $('button[name="order_discount_btn"]').on("click", function() {
        calculateGrandTotal();
    });

    $('button[name="shipping_cost_btn"]').on("click", function() {
        calculateGrandTotal();
    });

    $('button[name="order_tax_btn"]').on("click", function() {
        calculateGrandTotal();
    });

    $(".coupon-check").on("click",function() {
        couponDiscount();
    });

    function updatePayingAmountWithDownPayment() {
        var downPayment = parseFloat($('input[name="installment_plan[down_payment]"]').val()) || 0;
        var grandTotal = parseFloat($('#grand-total').text()) || 0;
        if (downPayment > grandTotal) {
            alert('Down payment cannot exceed grand total.');
            $('input[name="installment_plan[down_payment]"]').val(grandTotal.toFixed(POS_CONFIG.decimal));
            downPayment = grandTotal;
        }
        return downPayment;
    }

    $(".payment-btn").on("click", function() {
        playSound();

        const decimalPlaces = POS_CONFIG.decimal;

        if ($('#enable_installment').is(':checked')) {
            let downPayment = parseFloat(updatePayingAmountWithDownPayment()) || 0;

            $('.paid_amount')
                .val(downPayment.toFixed(decimalPlaces));
            $('.paying_amount')
                .val(downPayment.toFixed(decimalPlaces))
                .prop('readonly', true);
        } else {
            let grandTotal = parseFloat($('#grand-total').text()) || 0;

            $('.paid_amount')
                .val(grandTotal.toFixed(decimalPlaces));
            $('.paying_amount')
                .val(grandTotal.toFixed(decimalPlaces))
                .prop('readonly', false);
        }

        $('.qc').data('initial', 1);
    });

    $("#draft-btn").on("click",function(){
        playSound();
        $('input[name="sale_status"]').val(3);
        $('input[name="paying_amount"]').prop('required',false);
        $('input[name="paid_amount"]').prop('required',false);
        var rownumber = $('table.order-list tbody tr:last').index();
        if (rownumber < 0) {
            alert("Please insert product to order table!");
        }
        else
            $('.payment-form').submit();
    });

    $("#submit-btn").on("click", function (e) {
        e.preventDefault();

        const paymentType = $('select[name="paid_by_id_select[]"]').val();
        const form = $('.payment-form');
        const csrf = $('meta[name="csrf-token"]').attr('content');

        // ✅ Gather installment data (if enabled)
        if ($("#enable_installment").is(":checked")) {
            const installmentData = {
                enabled: true,
                name: $('input[name="installment_plan[name]"]').val(),
                price: $('input[name="installment_plan[price]"]').val(),
                additional_amount: $('input[name="installment_plan[additional_amount]"]').val(),
                total_amount: $('input[name="installment_plan[total_amount]"]').val(),
                down_payment: $('input[name="installment_plan[down_payment]"]').val(),
                months: $('input[name="installment_plan[months]"]').val(),
                reference_type: $('input[name="installment_plan[reference_type]"]').val(),
                paid_by_id: $('select[name="installment_plan[paid_by_id]"]').val(),
                account_id: $('select[name="installment_plan[account_id]"]').val(),
                payment_note: $('textarea[name="installment_plan[payment_note]"]').val()
            };

            // 🟢 Append installment plan fields to the form before submitting
            $.each(installmentData, function (key, value) {
                if (value !== undefined && value !== null && value !== "") {
                    $('<input>').attr({
                        type: "hidden",
                        name: "installment_plan[" + key + "]",
                        value: value
                    }).appendTo(form);
                }
            });

            // Also include enable_installment flag
            $('<input>').attr({
                type: "hidden",
                name: "enable_installment",
                value: "1"
            }).appendTo(form);
        } else {
            $('<input>').attr({
                type: "hidden",
                name: "enable_installment",
                value: "0"
            }).appendTo(form);

        }

        if (paymentType === 'razorpay') {
            // ✅ 1. Validate required Razorpay fields
            let isValid = true;
            $('.razorpay.remove-element [required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('is-invalid');
                    isValid = false;
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!isValid) {
                alert('Please fill all required Razorpay fields.');
                return;
            }

            // ✅ 2. Prepare payment data
            let data = {
                name: $('input[name="customer_name"]').val(),
                email: $('input[name="customer_email"]').val(),
                phone: $('input[name="customer_phone"]').val(),
                amount: $('.paying_amount').val(),
                _token: csrf,
            };

            // ✅ 3. Create Razorpay order (via backend)
            $.post("/razorpay/pay", data, function (res) {
                const options = {
                    key: res.key, // from backend
                    amount: res.amount, // in paise
                    currency: "INR",
                    name: POS_CONFIG.razorpay.siteTitle,
                    description: "Order Payment",
                    image: POS_CONFIG.razorpay.siteLogo,
                    order_id: res.order_id,
                    prefill: {
                        name: data.name,
                        email: data.email,
                        contact: data.phone
                    },
                    theme: {
                        color: "#0C9DDA"
                    },
                    handler: function (response) {
                        // ✅ Verify payment on success
                        $.post("/razorpay/verify", {
                            _token: csrf,
                            razorpay_order_id: response.razorpay_order_id,
                            razorpay_payment_id: response.razorpay_payment_id,
                            razorpay_signature: response.razorpay_signature
                        }, function (verifyRes) {
                            if (verifyRes.status === 'success') {
                                $('<input>').attr({type:'hidden',name:'razorpay_payment_id',value:response.razorpay_payment_id}).appendTo(form);
                                $('<input>').attr({type:'hidden',name:'razorpay_order_id',value:response.razorpay_order_id}).appendTo(form);
                                $('<input>').attr({type:'hidden',name:'razorpay_signature',value:response.razorpay_signature}).appendTo(form);
                                form.off('submit').submit();
                            } else {
                                alert('Payment verification failed!');
                            }
                        });
                    },
                    modal: {
                        ondismiss: function () {
                            alert("UPI payment cancelled.");
                        }
                    },
                    // 🟢 Only show UPI option
                    method: {
                        upi: true,
                        card: false,
                        netbanking: false,
                        wallet: false,
                        emi: false,
                        paylater: false
                    },
                    upi: { flow: "intent" }
                };

                const rzp = new Razorpay(options);
                rzp.open();

                rzp.on('payment.failed', function (response) {
                    alert("Payment failed: " + response.error.description);
                });
            });

        } else {
            // ✅ Non-Razorpay → just submit normally
            form.off('submit').submit();
        }
    });

    function validateBatchAndExpiry() {
        let isValid = true;
        $('table.order-list tbody tr').each(function () {
            let batchInput = $(this).find('.batch-no');
            let batchId    = $(this).find('.product-batch-id').val();
            let expiryText = $(this).find('.expired-date').text().trim();

            // Only for batch-enabled products
            if (batchInput.length && !batchInput.hasClass('d-none')) {

                // ❌ Batch missing
                if (!batchInput.val()) {
                    alert('Batch number is required!');
                    batchInput.focus();
                    isValid = false;
                    return false;
                }

                // ❌ Invalid batch
                if (!batchId) {
                    alert('Invalid batch selected!');
                    batchInput.focus();
                    isValid = false;
                    return false;
                }

                // // ❌ Expiry missing
                // if (!expiryText) {
                //     alert('Expiry date not found!');
                //     isValid = false;
                //     return false;
                // }

                // // ❌ Expired (string)
                // if (expiryText.toLowerCase() === 'expired') {
                //     alert('Expired batch cannot be sold!');
                //     isValid = false;
                //     return false;
                // }

                // // ❌ Expired (date comparison)
                // let expiryDate = new Date(expiryText);
                // if (!isNaN(expiryDate) && expiryDate < new Date()) {
                //     alert('Expired batch cannot be sold!');
                //     isValid = false;
                //     return false;
                // }
            }
        });

        return isValid;
    }

    $("#gift-card-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('gift-card');
    });

    $("#credit-card-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('credit-card');
    });

    $("#cheque-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('cheque');
    });

    $("#cash-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('cash');
    });

    if (POS_CONFIG.hasRazorpay) {
    $("#razorpay-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('razorpay');
    });
    }

    $("#credit-sale-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('credit-sale');
    });

    $("#moneipoint-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('moneipoint');
    });

    $("#multiple-payment-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('multiplepay');
    });

    $("#deposit-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('deposit');
    });

    $("#point-btn").on("click",function(e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement('points');
    });

    $(".pay-options").on("click", function (e) {
        if (!validateBatchAndExpiry()) {
            e.preventDefault();
            return false;
        }
        appendRemoveElement($(this).val(), true);
    });

    function changeLabelText(labelText) {
        $("#received-paying").text(labelText);
    }
    function checkCreditLimit(){
        var selectedOption = $('#customer_id option:selected');
        var credit_limit   = parseFloat(selectedOption.data('credit-limit')) || 0;

        console.log('Credit Limit:', credit_limit);
        console.log('Selected Option:', selectedOption.val());

        if (credit_limit <= 0) {
            $('#submit-btn').prop('disabled', false);
            return;
        }

        var customer_id = $('#customer_id').val();

        $.ajax({
            url: '/customer/' + customer_id + '/due',
            type: 'GET',
            async: false,
            success: function(existing_due) {
                console.log('Existing Due Response:', existing_due);

                var current_grand_total = parseFloat($('input[name="grand_total"]').val()) || 0;
                var total_paying        = parseFloat($('.total_paying').text()) || 0;
                var new_due             = current_grand_total - total_paying;
                var total_due_after     = parseFloat(existing_due) + new_due;

                console.log('Grand Total:', current_grand_total);
                console.log('Total Paying:', total_paying);
                console.log('New Due:', new_due);
                console.log('Total Due After:', total_due_after);

                if (total_due_after > credit_limit) {
                    alert(
                        POS_CONFIG.labels.creditLimitExceeded +
                        '\nCredit Limit: ' + credit_limit +
                        '\nExisting Due: ' + parseFloat(existing_due).toFixed(2) +
                        '\nNew Due: ' + new_due.toFixed(2) +
                        '\nTotal Due After Sale: ' + total_due_after.toFixed(2)
                    );
                    $('#submit-btn').prop('disabled', true);
                } else {
                    $('#submit-btn').prop('disabled', false);
                }
            },
            error: function(xhr) {
                console.log('AJAX Error:', xhr.status, xhr.responseText);
            }
        });
    }

    function checkCreditLimitForMultiple(creditAmount) {
        var selectedOption = $('#customer_id option:selected');
        var credit_limit   = parseFloat(selectedOption.data('credit-limit')) || 0;

        if (credit_limit <= 0) {
            $('#submit-btn').prop('disabled', false);
            return;
        }

        var customer_id = $('#customer_id').val();

        $.ajax({
            url: '/customer/' + customer_id + '/due',
            type: 'GET',
            async: false,
            success: function(existing_due) {
                var total_due_after = parseFloat(existing_due) + parseFloat(creditAmount);

                if (total_due_after > credit_limit) {
                    alert(
                        POS_CONFIG.labels.creditLimitExceeded +
                        '\n\nCredit Limit: '        + credit_limit.toFixed(2) +
                        '\nExisting Due: '          + parseFloat(existing_due).toFixed(2) +
                        '\nNew Credit Amount: '     + parseFloat(creditAmount).toFixed(2) +
                        '\nTotal Due After Sale: '  + total_due_after.toFixed(2) +
                        '\n\nCredit Sale সিলেক্ট করা যাবে না।'
                    );
                    $('#submit-btn').prop('disabled', true);
                } else {
                    $('#submit-btn').prop('disabled', false);
                }
            }
        });
    }

    function appendRemoveElement(className, payOption = false){
        $('.payment-info').show();
        $('.points-info').hide();
        $('#print_invoice').prop('checked', true);
        ismultiplepayment = 0;
        $('.remove-element').remove();
        $('.selectpicker').selectpicker('refresh');
        $('select[name="paid_by_id_select[]"]').parent().parent().addClass('d-none');
        $('.paid_amount').parent().addClass('d-none');
        $('.paying_amount').parent().addClass('d-none');
        $('.add-more').parent().addClass('d-none');
        if ($('#enable_installment').is(':checked')) {
            var downPayment = updatePayingAmountWithDownPayment();
            $('.total_paying').text(downPayment);
            $('.total_payable').text($('input[name="installment_plan[total_amount]"]').val());
        } else {
            $('.total_paying').text($('#grand-total').text());
            $('.total_payable').text($('#grand-total').text());
        }
        $('.due').text(0);
        $('.new-row').remove();
        $('#submit-btn').prop('disabled',false);
        updateChange();

        $("#received-paying").html('Cash Received <span class="dripicons-information" data-toggle="tooltip" title="Cash handed over to you. example: sale amount is 300. customer gives you 500. cash received: 500 " style="color:#004085;display:inline-block;font-size:15px;cursor:help;"></span>');
        if (payOption) {
            $("#received-paying").text("Paying Amount");

            let $select = $('select[name="paid_by_id_select[]"]');
            if ($select.find(`option[value="${className}"]`).length === 0) {
                $select.append(`<option value="${className}">${className}</option>`);
            }
            $select.val(className);

            $('.paying_amount').parent().addClass('col-md-12').removeClass('col-md-3 d-none');
            $('.paying_amount').addClass('cash_paying_amount');
        }

        var appendElement = '';
        if (className == 'cash') {
            $('select[name="paid_by_id_select[]"]').val(1);
            $('.paying_amount').parent().addClass('col-md-12').removeClass('col-md-3 d-none');
            $('.paying_amount').addClass('cash_paying_amount');
        }
        else if (className == 'razorpay' && POS_CONFIG.hasRazorpay) {
            $('select[name="paid_by_id_select[]"]').val('razorpay');

            let customer_id = $('select[name="customer_id"]').val();
            let customer = lims_customer_list.find(c => c.id == customer_id);

            // fallback if customer not found
            let name = customer ? customer.name : '';
            let email = customer ? customer.email : '';
            let phone = customer ? customer.phone_number : '';

            if (customer.type === 'walkin') {
                name = email = phone = '';
            }

            appendElement = `
                <div class="form-group col-md-4 razorpay remove-element">
                    <label>${POS_CONFIG.labels.customer} *</label>
                    <input type="text" name="customer_name" class="form-control" value="${name}" required>
                </div>
                <div class="form-group col-md-4 razorpay remove-element">
                    <label>Customer Email</label>
                    <input type="email" name="customer_email" class="form-control" value="${email}" required>
                </div>
                <div class="form-group col-md-4 razorpay remove-element">
                    <label>Customer Phone *</label>
                    <input type="text" name="customer_phone" class="form-control" value="${phone}" required>
                </div>
            `;


            changeLabelText('Amount');
            $('#payment_receiver_id').attr('hidden', true);
            $('#print_invoice').prop('checked', false);
            $('.paying_amount').parent().addClass('col-md-12').removeClass('col-md-3 d-none');
            $('.paying_amount').addClass('cash_paying_amount');
            $('.paying_amount').prop('readonly', true);
        }
        else if (className == 'credit-sale') {
            $('select[name="paid_by_id_select[]"]').val('');
            $('.paying_amount').parent().addClass('col-md-12').removeClass('col-md-3 d-none');
            $('.paying_amount').addClass('cash_paying_amount');
            $('.paying_amount').val(0);
            $('input[name="paid_amount[]"]').val(0);
            $('.paid_amount').val(0);
            $('.due').text($('#grand-total').text());
            $('.total_paying').text(0);
            checkCreditLimit();
            // ✅ Pay Term auto-fill from customer
    var selected        = $('#customer_id option:selected');
    var pay_term_no     = selected.data('pay_term_no')     || '';
    var pay_term_period = selected.data('pay_term_period') || 'days';

    appendElement = `<div class="form-group col-md-12 credit-sale remove-element">
                        <div class="row">
                            <div class="col-md-12">
                                <label>Pay Term</label>
                                <div class="input-group">
                                    <input type="number"
                                        name="pay_term_no"
                                        id="pay_term_no"
                                        class="form-control"
                                        min="1"
                                        placeholder="e.g. 30"
                                        value="${pay_term_no}">
                                    <select name="pay_term_period" id="pay_term_period" class="form-control">
                                        <option value="days"   ${pay_term_period == 'days'   ? 'selected' : ''}>Days</option>
                                        <option value="months" ${pay_term_period == 'months' ? 'selected' : ''}>Months</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>`;
        }
        else if (className == 'gift-card') {
            $('select[name="paid_by_id_select[]"]').val(2);
            appendElement = `<div class="form-group col-md-12 gift-card remove-element">
                                <label>${POS_CONFIG.labels.giftCard} *</label>
                                <input type="hidden" name="gift_card_id">
                                <select id="gift_card_id_select" name="gift_card_id_select" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Gift Card..."></select>
                            </div>`;
            $.ajax({
                url: POS_CONFIG.urls.getGiftCard,
                type: "GET",
                dataType: "json",
                success:function(data) {
                    $('#add-payment select[name="gift_card_id_select"]').empty();
                    $.each(data, function(index) {
                        gift_card_amount[data[index]['id']] = data[index]['amount'];
                        gift_card_expense[data[index]['id']] = data[index]['expense'];
                        $('#add-payment select[name="gift_card_id_select"]').append('<option value="'+ data[index]['id'] +'">'+ data[index]['card_no'] +'</option>');
                    });
                    $('.selectpicker').selectpicker('refresh');
                    $('#gift_card_id_select').selectpicker('toggle');
                }
            });
        }
        else if (className == 'credit-card') {
            $('select[name="paid_by_id_select[]"]').val(3);
            appendElement = `<div class="form-group col-md-12 credit-card remove-element">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label>Card Number</label>
                                        <input class="form-control" name="card_number" class="card_name">
                                    </div>
                                    <div class="col-md-5">
                                        <label>Card Holder Name</label>
                                        <input class="form-control" name="card_holder_name">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Card Type</label>
                                        <select class="form-control" name="card_type">
                                            <option>Visa</option>
                                            <option>Master Card</option>
                                        </select>
                                    </div>
                                </div>
                            </div>`;
        }
        else if (className == 'cheque') {
            $('select[name="paid_by_id_select[]"]').val(4);
            appendElement = `<div class="form-group col-md-12 cheque remove-element">
                            <label>${POS_CONFIG.labels.chequeNumber} *</label>
                            <input type="text" name="cheque_no" class="form-control" value="" required>
                        </div>`;

        }
        else if (className == 'deposit') {
            $('select[name="paid_by_id_select[]"]').val(6);
            let customerId = $('#customer_id').val();
            let paidAmount = parseFloat($('input[name="paid_amount[]"]').val() || 0);
            let customerDeposit = parseFloat($('#customer_id option:selected').data('deposit') || 0);

            // If the deposit is 0 or less, the modal will not be shown
            if (customerDeposit <= 0 || isNaN(customerDeposit)) {
                alert('This customer has no deposit balance!');
                $('#add-payment').modal('hide');
                return;
            }

            // If the paid amount is greater than the deposit → show the multiple payment option
            if (paidAmount > customerDeposit) {
                alert('Amount exceeds customer deposit! Opening multiple payment option.');
                // console.log('Deposit limit:', customerDeposit);
                appendRemoveElement('multiplepay'); // multiple payment modal ওপেন করার ফাংশন
            }
            // If there is enough deposit balance, the deposit modal will be shown.
            else {
                $('#add-payment').modal('show');
            }
        }

        else if (className == 'points') {
            $('select[name="paid_by_id_select[]"]').val(7);
             redeemPoints();
        }
        else if (className == 'multiplepay') {
            ismultiplepayment = 1;
            $('select[name="paid_by_id_select[]"]').val(1);
            $('select[name="paid_by_id_select[]"]').parent().parent().removeClass('d-none');
            $('.paid_amount').parent().removeClass('d-none');
            $('.paying_amount').parent().removeClass('col-md-12 d-none').addClass('col-md-3');
            $('.paying_amount').removeClass('cash_paying_amount')
            $('.add-more').parent().removeClass('d-none');
        }
        $("#payment-select-row .row:eq(0)").append(appendElement);

    }
    // Trigger pointCalculation on body click anywhere
    if(reward_point_setting && reward_point_setting.is_active){
        $(document).on('click', 'body', function(e) {
            // Optional: prevent firing when clicking inside modal to avoid recursion
            if (!$(e.target).closest('#add-payment, input[name="paid_amount[]"], #customer_id, select[name="paid_by_id_select[]"]').length) {
                    updatePointBtnStatus();
            }
        });
    }

    // 1️⃣ Only check points and update button tooltip on body click
    function updatePointBtnStatus() {
        let $pointBtn = $('#point-btn');
        let paid_amount = parseFloat($('#subtotal').text() || 0);
        let customerPoints = parseFloat($('#customer_id option:selected').data('points')) || 0;
        let minOrderTotal = reward_point_setting['min_order_total_for_redeem'] || 0;
        let perPoint = reward_point_setting['redeem_amount_per_unit_rp'] || 1;
        let maxPoints = reward_point_setting['max_redeem_point'] || 0;

        let required_points = Math.ceil(paid_amount / perPoint);

        // Default: disable button
        $pointBtn.prop('disabled', true);

        // Build tooltip message
        let tooltipMessage = `Point Info:
        Customer Points: ${customerPoints}
        Required Points: ${required_points}
        Order Total: ${paid_amount}
        Minimum Order Total for Redeem: ${minOrderTotal}
        Maximum Order Total for Redeem: ${maxPoints}`;

        if (paid_amount <= 0) {
            tooltipMessage += `\n⚠️ Please enter a paid amount.`;
        } else if (minOrderTotal > 0 && paid_amount < minOrderTotal) {
            tooltipMessage += `\n⚠️ Order total must be at least ${minOrderTotal} to redeem points.`;
        } else if (required_points > customerPoints) {
            tooltipMessage += `\n⚠️ Not enough points to redeem.`;
        }else if(maxPoints < required_points || maxPoints <= 0){
             tooltipMessage += `\n⚠️ You can redeem a maximum of ${maxPoints} points.`;
        } else {
            // Enable button if all conditions pass
            $pointBtn.prop('disabled', false);
            tooltipMessage = "Click to redeem points";
        }

        $pointBtn.attr('title', tooltipMessage);
    }

    // 2️⃣ Full calculation when point button clicked
    function redeemPoints() {
        $('.payment-info').hide();
        let $pointBtn = $('#point-btn');
        let paid_amount = parseFloat($('#subtotal').text() || 0);
        let customerPoints = parseFloat($('#customer_id option:selected').data('points')) || 0;
        let minPoints = reward_point_setting['min_redeem_point'] || 0;
        let maxPoints = reward_point_setting['max_redeem_point'] || 0;
        let minOrderTotal = reward_point_setting['min_order_total_for_redeem'] || 0;
        let perPoint = reward_point_setting['redeem_amount_per_unit_rp'] || 1;

        let required_points = Math.ceil(paid_amount / perPoint);

        // Apply min/max limits
        // if (minPoints > 0 && required_points < minPoints) required_points = minPoints;
        // if (maxPoints > 0 && required_points > maxPoints) required_points = maxPoints;

        if (required_points > customerPoints) required_points = customerPoints;

        let remaining_points = customerPoints - required_points;
        let total_bill = parseFloat($('#grand-total').text()) || 0;

        // Update modal info
        $('.points-info').html(`
            <div class="mt-4">
                <h2>Points Info</h2>
                <hr/>
                <p class="text-light total_bill"><strong>Total Bill:</strong> ${total_bill}</p>
            </div>
            <div class="mt-4">
                <h2>Customer Points</h2>
                <p class="text-light customer_points">${customerPoints}</p>
            </div>
            <div class="mt-4">
                <h2>Used Points</h2>
                <p class="text-light used_points">${required_points}</p>
                <input type="hidden" name="redeem_point" value="${required_points}" />
            </div>
            <div class="mt-4">
                <h2>Remaining Points</h2>
                <p class="text-light remaining_points">${remaining_points}</p>
            </div>
        `);
        $('.points-info').show();

        $("input[name='used_points']").val(required_points);
    }

    $(document).on("change", 'select[name="paid_by_id_select[]"]', function() {
        updateChange();
        var id       = $(this).val();
        var $thisSelect = $(this);
        var appendElement = '';
        $(".payment-form").off("submit");
        $(this).parent().parent().siblings('.cash-received-container').addClass('d-none');
        $(this).parent().parent().siblings('.gift-card').remove();
        $(this).parent().parent().siblings('.credit-card').remove();
        $(this).parent().parent().siblings('.cheque').remove();
        $(this).parent().parent().siblings('.credit-sale-extra').remove();

        // ✅ Credit Sale select হলে আগে duplicate ও limit check করো
        if (id === 'credit_sale') {

            // ✅ Duplicate check — অন্য row তে credit_sale আছে কিনা
            var creditSaleAlreadyExists = false;
            $('select[name="paid_by_id_select[]"]').each(function() {
                if (!$(this).is($thisSelect) && $(this).val() === 'credit_sale') {
                    creditSaleAlreadyExists = true;
                    return false;
                }
            });

            if (creditSaleAlreadyExists) {
                alert('Credit Sale ইতোমধ্যে যোগ করা হয়েছে। একটির বেশি Credit Sale সম্ভব নয়।');
                $thisSelect.val('1').selectpicker('refresh');
                $thisSelect.closest('.row, .new-row').find('.cash-received-container').removeClass('d-none');
                return;
            }

            // ✅ Credit Amount হিসাব করো
            var grandTotal = parseFloat($('#grand-total').text()) || 0;
            var otherPaid  = 0;

            $('.paid_amount').each(function() {
                var $parentRow = $(this).closest('.new-row');
                if ($parentRow.length === 0) {
                    $parentRow = $(this).closest('#payment-select-row > .row');
                }
                if (!$parentRow.find($thisSelect).length) {
                    var $otherSelect = $parentRow.find('select[name="paid_by_id_select[]"]');
                    if ($otherSelect.val() !== 'credit_sale') {
                        otherPaid += parseFloat($(this).val()) || 0;
                    }
                }
            });

            var creditAmount = grandTotal - otherPaid;
            if (creditAmount < 0) creditAmount = 0;

            // ✅ Credit Limit check
            var selectedOption = $('#customer_id option:selected');
            var credit_limit   = parseFloat(selectedOption.data('credit-limit')) || 0;

            if (credit_limit > 0) {
                var customer_id = $('#customer_id').val();

                $.ajax({
                    url: '/customer/' + customer_id + '/due',
                    type: 'GET',
                    async: true,
                    success: function(existing_due) {
                        var total_due_after = parseFloat(existing_due) + parseFloat(creditAmount);

                        if (total_due_after > credit_limit) {
                            alert(
                                POS_CONFIG.labels.creditLimitExceeded +
                                '\n\nCredit Limit: '        + credit_limit.toFixed(2) +
                                '\nExisting Due: '          + parseFloat(existing_due).toFixed(2) +
                                '\nNew Credit Amount: '     + creditAmount.toFixed(2) +
                                '\nTotal Due After Sale: '  + total_due_after.toFixed(2) +
                                '\n\nCredit Sale'
                            );

                            // ❌ Revert to Cash
                            $thisSelect.val('1').selectpicker('refresh');
                            $thisSelect.closest('.row, .new-row')
                                .find('.cash-received-container')
                                .removeClass('d-none');

                            $('#submit-btn').prop('disabled', false);
                            return;
                        }

                        // ✅ Limit OK — proceed
                        proceedCreditSaleInMultiple($thisSelect, creditAmount);
                    }
                });

            } else {
                // Unlimited credit — proceed directly
                proceedCreditSaleInMultiple($thisSelect, creditAmount);
            }

            return; // async এর জন্য early return
        }
        //cash
        if(id == 1){
            $(this).parent().parent().siblings('.cash-received-container').removeClass('d-none');
        }
        //gift
        else if(id == 2) {
            appendElement = `<div class="form-group col-md-10 gift-card remove-element">
                                <label>${POS_CONFIG.labels.giftCard} *</label>
                                <input type="hidden" name="gift_card_id">
                                <select id="gift_card_id_select" name="gift_card_id_select" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Gift Card..."></select>
                            </div>`;
            $(this).closest('.col-md-3').after(appendElement);

            $.ajax({
                url: POS_CONFIG.urls.getGiftCard,
                type: "GET",
                dataType: "json",
                success:function(data) {
                    $('#add-payment select[name="gift_card_id_select"]').empty();
                    $.each(data, function(index) {
                        gift_card_amount[data[index]['id']] = data[index]['amount'];
                        gift_card_expense[data[index]['id']] = data[index]['expense'];
                        $('#add-payment select[name="gift_card_id_select"]').append('<option value="'+ data[index]['id'] +'">'+ data[index]['card_no'] +'</option>');
                    });
                    $('.selectpicker').selectpicker('refresh');
                }
            });
        }
        //credit
        else if (id == 3) {
            appendElement = `<div class="form-group col-md-10 credit-card remove-element">
                                <div class="row">
                                    <div class="col-md-5">
                                        <label>Card Number</label>
                                        <input class="form-control" name="card_number" class="card_name">
                                    </div>
                                    <div class="col-md-5">
                                        <label>Card Holder Name</label>
                                        <input class="form-control" name="card_holder_name">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Card Type</label>
                                        <select class="form-control" name="card_type">
                                            <option>Visa</option>
                                            <option>Master Card</option>
                                        </select>
                                    </div>
                                </div>
                            </div>`;
            $(this).closest('.col-md-3').after(appendElement);
        }
        //cheque
        else if (id == 4) {
            appendElement = `<div class="form-group col-md-10 cheque remove-element">
                            <label>${POS_CONFIG.labels.chequeNumber} *</label>
                            <input type="text" name="cheque_no" class="form-control" value="" required>
                        </div>`;
            $(this).closest('.col-md-3').after(appendElement);
        }

        //deposit
        else if(id == 6) {

        }
        //point
        if (id == '7') {
            let isValid = pointCalculation();
            if (!isValid) {
                let firstValue = $currentSelect.find('option:first').val();
                $currentSelect.val(firstValue);
                $currentSelect.selectpicker('refresh');
                return;
            }
        }

        else if (id == 'credit_sale') {
            var selected        = $('#customer_id option:selected');
            var pay_term_no     = selected.data('pay_term_no')     || '';
            var pay_term_period = selected.data('pay_term_period') || 'days';

            appendElement = `
                <div class="form-group col-md-10 credit-sale-extra">
                    <label>Pay Term</label>
                    <div class="input-group">
                        <input type="number"
                            name="pay_term_no"
                            class="form-control"
                            min="1"
                            placeholder="e.g. 30"
                            value="${pay_term_no}">
                        <select name="pay_term_period" class="form-control">
                            <option value="days"   ${pay_term_period == 'days'   ? 'selected' : ''}>Days</option>
                            <option value="months" ${pay_term_period == 'months' ? 'selected' : ''}>Months</option>
                        </select>
                    </div>
                </div>`;

            $(this).closest('.col-md-3').after(appendElement);

            // paid amount = grand total - অন্য payments এর sum
            var grandTotal = parseFloat($('#grand-total').text()) || 0;
            var otherPaid  = 0;

            $(this).closest('.row, .new-row').siblings().find('.paid_amount').each(function() {
                otherPaid += parseFloat($(this).val()) || 0;
            });

            // প্রথম row এর paid_amount বাদ দিতে হবে
            var $thisPaidAmount = $(this).closest('.row, .new-row').find('.paid_amount');
            var creditAmount    = grandTotal - otherPaid;
            if (creditAmount < 0) creditAmount = 0;

            $thisPaidAmount.val(creditAmount.toFixed(POS_CONFIG.decimal));

            // Paying amount 0 করে দাও (credit sale এ cash নেওয়া হয় না)
            $(this).parent().parent().siblings('.cash-received-container').addClass('d-none');

            // Credit limit check
            checkCreditLimitForMultiple(creditAmount);

            calculatePayingAmount();
            updateChange();
        }
    });

    function pointCalculation() {
        // ⬇️ last paid_amount[] input value
        let paid_amount = parseFloat(
            $('input[name="paid_amount[]"]').last().val()
        ) || 0;
        // console.log(paid_amount);
        let customer_id = $('#customer_id').val();
        let customerPoints = points[customer_id] || 0;

        let perPoint = reward_point_setting['redeem_amount_per_unit_rp'] || 1;
        let minPoints = reward_point_setting['min_redeem_point'] || 0;
        let maxPoints = reward_point_setting['max_redeem_point'] || 0;

        let required_point = Math.ceil(paid_amount / perPoint);

        if (minPoints > 0 && required_point < minPoints) {
            required_point = minPoints;
        }

        if (maxPoints > 0 && required_point > maxPoints) {
            required_point = maxPoints;
        }

        if (paid_amount <= 0) {
            alert('⚠️ Paid amount is required.');
            return false;
        }

        if (required_point > customerPoints) {
            alert(
                '⚠️ Not enough points\n' +
                'Required: ' + required_point +
                '\nAvailable: ' + customerPoints
            );
            return false;
        }

        $("input[name='used_points']").val(required_point);
        return true;
    }


    $(document).on("change", '#add-payment select[name="gift_card_id_select"]', function() {
        var balance = gift_card_amount[$(this).val()] - gift_card_expense[$(this).val()];
        $('#add-payment input[name="gift_card_id"]').val($(this).val());
        if (ismultiplepayment == 0) {
            if($('input[name="paid_amount[]"]').val() > balance){
                $('#submit-btn').prop('disabled',true);
                alert('Amount exceeds card balance! Gift Card balance: '+ balance);
            }else{
                $('#submit-btn').prop('disabled',false);
            }
        }else{
            // $(this).parent().parent().siblings('.paying-amount-container').children('.paid_amount').val(balance);
            updateChange();
        }

    });

    function change(paying_amount, paid_amount) {
        $("#change").text( parseFloat(paying_amount - paid_amount).toFixed(POS_CONFIG.decimal));
    }

    // Event listener for changes to paid_amount
    $(document).on("keyup", '.paid_amount', function() {
        let paid_amount = parseFloat($(this).val()) || 0;
        if(paid_amount < 0){
            $(this).val(0);
        }
        // Call the change function to update the change amount for this specific row
        calculatePayingAmount();
        updateChange();
    });
    // Event listener for changes to paid_amount
    $(document).on("keyup", '.paying_amount', function() {
        let paying_amount = parseFloat($(this).val()) || 0;
        if(paying_amount < 0){
            $(this).val(0);
        }
        updateChange();
    });

    $(document).on("blur", '.cash_paying_amount', function() {
        let paying_amount = parseFloat($(this).val()) || 0;
        let grandTotal = parseFloat($("#grand-total").text()) || 0;
        let paid_amount = 0;
        if(paying_amount < grandTotal){
            $('.paid_amount').val(paying_amount);
            $('.total_paying').text(paying_amount);
            $('.due').text(grandTotal - paying_amount);

            paid_amount = $('.paid_amount').val();

            checkCreditLimit();
        }else if(paying_amount > grandTotal){
            $('.paid_amount').val(grandTotal);
            $('.total_paying').text(grandTotal);
            $('.due').text(0);

            paid_amount = $('.paid_amount').val();
        }else if(paying_amount == grandTotal){
            $('.paid_amount').val(grandTotal);
            $('.total_paying').text(grandTotal);
            $('.due').text(0);
            paid_amount = $('.paid_amount').val();
        }

        if(paying_amount < 0){
            $(this).val(0);
        }
        updateChange();
    });

    // Update the change text for the specific row
    function updateChange() {
        let change = 0;
        $('select[name="paid_by_id_select[]"]').each(function() {
            if ($(this).val() == '1' || $(this).val() == '') {
                let $row = $(this).closest('.row');
                let paying_amount = parseFloat($row.find('.paying_amount').val()) || 0;
                let paid_amount = parseFloat($row.find('.paid_amount').val()) || 0;
                change += paying_amount - paid_amount;
            }
        });
        $('.change').text((change).toFixed(POS_CONFIG.decimal));

        saveDataToLocalStorageForCustomerDisplay('clear_no');
    }

    // Function to calculate the total and update the total_payable
    function calculatePayingAmount() {
    let cashTotal  = 0;
    let grandTotal = parseFloat($("#grand-total").text()) || 0;

    $('.paid_amount').each(function() {
        // এই paid_amount কোন row এ আছে সেটা বের করো
        var $parentRow = $(this).closest('.new-row');
        if ($parentRow.length === 0) {
            $parentRow = $(this).closest('#payment-select-row > .row');
        }

        var $select = $parentRow.find('select[name="paid_by_id_select[]"]');
        var payType = $select.val();

        // credit_sale হলে total paying এ যোগ করবো না
        if (payType === 'credit_sale') {
            return; // skip
        }

        if ($.isNumeric($(this).val())) {
            cashTotal += parseFloat($(this).val());
        }
    });

    var due = grandTotal - cashTotal;
    if (due < 0) due = 0;

    $('.total_paying').text(cashTotal.toFixed(POS_CONFIG.decimal));
    $('.due').text(due.toFixed(POS_CONFIG.decimal));

    checkCreditLimit();
}

    function confirmDelete() {
        if (confirm("Are you sure want to delete?")) {
            return true;
        }
        return false;
    }

    $('.transaction-btn-plus').on("click", function() {
        $(this).addClass('d-none');
        $('.transaction-btn-close').removeClass('d-none');
    });

    $('.transaction-btn-close').on("click", function() {
        $(this).addClass('d-none');
        $('.transaction-btn-plus').removeClass('d-none');
    });

    $('.coupon-btn-plus').on("click", function() {
        $(this).addClass('d-none');
        $('.coupon-btn-close').removeClass('d-none');
    });

    $('.coupon-btn-close').on("click", function() {
        $(this).addClass('d-none');
        $('.coupon-btn-plus').removeClass('d-none');
    });

    $(document).on('click', '.qc-btn', function(e) {
        if($(this).data('amount')) {
            if($('.qc').data('initial')) {
                $('input[name="paying_amount"]').val($(this).data('amount').toFixed(POS_CONFIG.decimal));
                $('.qc').data('initial', 0);
            }else {
                $('input[name="paying_amount"]').val( (parseFloat($('input[name="paying_amount"]').val()) + $(this).data('amount')).toFixed(POS_CONFIG.decimal));
            }

        }
        else
            $('input[name="paying_amount"]').val((0).toFixed(POS_CONFIG.decimal));
        change( $('input[name="paying_amount"]').val(), $('input[name="paid_amount"]').val() );
    });

    function populatePriceOption() {
        var product_price = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_price').val()).toFixed(POS_CONFIG.decimal);
        var ws_price = parseFloat(wholesale_price[rowindex]).toFixed(POS_CONFIG.decimal);

        $('#editModal select[name=price_option]').empty();
        if(ws_price > 0)
            $('#editModal select[name=price_option]').append('<option value="'+ ws_price +'">'+ ws_price +'</option>');
        $('#editModal select[name=price_option]').append('<option selected value="'+ product_price +'">'+ product_price +'</option>');

        $('.selectpicker').selectpicker('refresh');
    }

    function edit(){
        $(".imei-section").remove();
        if(is_imei[rowindex]) {

            var imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();

            if(imeiNumbers.length) {
                imeiArrays = [...new Set(imeiNumbers.split(","))];
                htmlText = `<div class="col-md-8 form-group imei-section">
                            <label>IMEI or Serial Numbers</label>
                            <div class="table-responsive">
                                <table id="imei-table" class="table table-hover">
                                    <tbody>`;
                for (var i = 0; i < imeiArrays.length; i++) {
                    htmlText += `<tr>
                                    <td>
                                        <input type="text" class="form-control imei-numbers" name="imei_numbers[]" value="`+imeiArrays[i]+`" />
                                    </td>
                                    <td>
                                        <button type="button" class="imei-del btn btn-sm btn-danger">X</button>
                                    </td>
                                </tr>`;
                }
                htmlText += `</tbody>
                                </table>
                            </div>
                        </div>`;
                $("#editModal .modal-element").append(htmlText);
            }
        }
        populatePriceOption();
        // $("#product-cost").text(cost[rowindex]);
        var row_product_name_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('td:nth-child(1) > strong:nth-child(1)').text();
        $('#modal_header').text(row_product_name_code);

        var qty = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val();
        $('input[name="edit_qty"]').val(qty);

        cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
        //     if (product_discount[rowindex] < 1) {

        //         product_discount[rowindex] = draft_discounts[cur_product_id];
        //     }

        $('input[name="edit_discount"]').val(parseFloat(product_discount[rowindex]).toFixed(POS_CONFIG.decimal));

        var tax_name_all = POS_CONFIG.taxNameAll;
        pos = tax_name_all.indexOf(tax_name[rowindex]);
        $('select[name="edit_tax_rate"]').val(pos);

        var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-code').val();
        var product_type = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();
        if(product_type == 'standard'){
            unitConversion();
            temp_unit_name = (unit_name[rowindex]).split(',');
            temp_unit_name.pop();
            temp_unit_operator = (unit_operator[rowindex]).split(',');
            temp_unit_operator.pop();
            temp_unit_operation_value = (unit_operation_value[rowindex]).split(',');
            temp_unit_operation_value.pop();

            $('select[name="edit_unit"]').empty();
            $.each(temp_unit_name, function(key, value) {
                $('select[name="edit_unit"]').append('<option data-operator="'+temp_unit_operator[key]+'" data-operation-value="'+temp_unit_operation_value[key]+'" value="' + key + '">' + value + '</option>');
            });
            $("#edit_unit").show();
        }
        else{
            row_product_price = product_price[rowindex];
            $("#edit_unit").hide();
        }
        $('input[name="edit_unit_price"]').val(row_product_price.toFixed(POS_CONFIG.decimal));
        $('.selectpicker').selectpicker('refresh');
    }

    //Delete imei
    $(document).on("click", "table#imei-table tbody .imei-del", function() {
        // Decrease qty
        var edit_qty = parseFloat($('input[name="edit_qty"]').val());
        edit_qty = (edit_qty - 1);
        $('input[name="edit_qty"]').val(edit_qty);

        // Check number of remaining IMEI for the same product
        let imeis = $('#tbody-id tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();

        let target = $(this).closest("tr").find('.imei-numbers').val();

        // Remove the row
        $(this).closest("tr").remove();

        // 1. Convert to array (remove spaces just in case)
        let arr = imeis.split(',').map(s => s.trim());

        // 2. Filter out the target IMEI
        arr = arr.filter(i => i !== target);

        // 3. Convert back to string
        let updated = arr.join(',');

        // Set the updated value back
        $('#tbody-id tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val(updated);

        if (edit_qty == 0) {
            $('#editModal').modal('hide');
            $('#tbody-id tr:eq('+rowindex+')').remove();
        }

        $('#tbody-id tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(edit_qty);
        checkDiscount(edit_qty,false);
        calculateTotal();
    });

    function couponDiscount() {
        var rownumber = $('table.order-list tbody tr:last').index();
        if (rownumber < 0) {
            alert("Please insert product to order table!")
        }
        else if($("#coupon-code").val() != ''){
            valid = 0;
            $.each(coupon_list, function(key, value) {
                if($("#coupon-code").val() == value['code']){
                    valid = 1;
                    todyDate = POS_CONFIG.todayDate;
                    if(parseFloat(value['quantity']) <= parseFloat(value['used']))
                        alert('This Coupon is no longer available');
                    else if(todyDate > value['expired_date'])
                        alert('This Coupon has expired!');
                    else if(value['type'] == 'fixed'){
                        if(parseFloat($('input[name="grand_total"]').val()) >= value['minimum_amount']) {
                            $('input[name="grand_total"]').val($('input[name="grand_total"]').val() - (value['amount'] * currency['exchange_rate']));
                            $('#grand-total').text(parseFloat($('input[name="grand_total"]').val()).toFixed(POS_CONFIG.decimal));
                            $('#grand-total-m').text(parseFloat($('input[name="grand_total"]').val()).toFixed(POS_CONFIG.decimal));
                            if(!isEditMode && !$('input[name="coupon_active"]').val()) {
                                alert('Congratulation! You got '+(value['amount'] * currency['exchange_rate'])+' '+currency['code']+' discount');
                            }
                            $(".coupon-check").prop("disabled",true);
                            $("#coupon-code").prop("disabled",true);
                            $('input[name="coupon_active"]').val(1);
                            $("#coupon-modal").modal('hide');
                            $('input[name="coupon_id"]').val(value['id']);
                            $('input[name="coupon_discount"]').val(value['amount'] * currency['exchange_rate']);
                            $('#coupon-text').text(parseFloat(value['amount'] * currency['exchange_rate']).toFixed(POS_CONFIG.decimal));
                        }
                        else
                            alert('Grand Total is not sufficient for discount! Required '+value['minimum_amount']+' '+currency['code']);
                    }
                    else{
                        var grand_total = $('input[name="grand_total"]').val();
                        var coupon_discount = grand_total * (value['amount'] / 100);
                        grand_total = grand_total - coupon_discount;
                        $('input[name="grand_total"]').val(grand_total);
                        $('#grand-total').text(parseFloat(grand_total).toFixed(POS_CONFIG.decimal));
                        $('#grand-total-m').text(parseFloat(grand_total).toFixed(POS_CONFIG.decimal));
                        if(!isEditMode && !$('input[name="coupon_active"]').val()) {
                            alert('Congratulation! You got '+value['amount']+'% discount');
                        }
                        $(".coupon-check").prop("disabled",true);
                        $("#coupon-code").prop("disabled",true);
                        $('input[name="coupon_active"]').val(1);
                        $("#coupon-modal").modal('hide');
                        $('input[name="coupon_id"]').val(value['id']);
                        $('input[name="coupon_discount"]').val(coupon_discount);
                        $('#coupon-text').text(parseFloat(coupon_discount).toFixed(POS_CONFIG.decimal));
                    }
                }
            });
            if(!valid)
                alert('Invalid coupon code!');
        }

        saveDataToLocalStorageForCustomerDisplay('clear_no');
    }

    function checkDiscount(qty, flag, price = 0) {
        var customer_id = $('#customer_id').val();
        var warehouse_id = $('#warehouse_id').val();
        var product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();

            $.ajax({
                type: 'GET',
                async: false,
                url: POS_CONFIG.urls.checkDiscount+'?qty='+qty+'&customer_id='+customer_id+'&product_id='+product_id+'&warehouse_id='+warehouse_id,
                success: function(data) {
                    if(product_price[rowindex].length == 0){
                        product_price[rowindex] = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product_price').val();
                    }
                    if(price > 0){
                        product_price[rowindex] = price;
                        product_price[rowindex] = parseFloat(product_price[rowindex] * currency['exchange_rate']) + parseFloat(product_price[rowindex] * currency['exchange_rate'] * customer_group_rate);
                    }

                    var productDiscount = parseFloat($('#discount').text());

                    if(flag == true)
                        $('#discount').text(productDiscount+data[2]);
                    else if(flag == false)
                        $('#discount').text(productDiscount-data[2]*qty);
                    else if(flag == 'input')
                        $('#discount').text(productDiscount-data[2]*previousqty+data[2]*qty);
                    else
                        $('#discount').text(productDiscount-data[2]);
                }
            });

        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .qty').val(qty);
        flag = true;
        checkQuantity(String(qty), flag);
    }

    function checkQuantity(sale_qty, flag) {
        var row_product_code = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-code').val();
        var qty = parseFloat($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').attr('max'));
        var product_type = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product_type').val();
        var imeiNumbers = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.imei-number').val();
        if(without_stock == 'no') {
            if(product_type.trim() == 'standard' || product_type.trim() == 'combo') {
                var operator = unit_operator[rowindex].split(',');
                var operation_value = unit_operation_value[rowindex].split(',');
                if(operator[0] == '*')
                    total_qty = sale_qty * operation_value[0];
                else if(operator[0] == '/')
                    total_qty = sale_qty / operation_value[0];
                if (total_qty > qty) {
                    if(imeiNumbers.length){
                        // console.log(sale_qty);
                        // sale_qty = (sale_qty + 1);
                    }else{
                        if (flag) {
                            // Clamp to max stock without recursion
                            if (operator[0] == '*' && parseFloat(operation_value[0]) > 0) {
                                sale_qty = Math.floor(qty / parseFloat(operation_value[0]));
                            } else if (operator[0] == '/' && parseFloat(operation_value[0]) > 0) {
                                sale_qty = Math.floor(qty * parseFloat(operation_value[0]));
                            } else {
                                sale_qty = qty;
                            }
                            if (sale_qty < 0) sale_qty = 0;
                            alert('Quantity exceeds stock quantity! Set to maximum: ' + sale_qty);
                        }
                        else {
                            edit();
                            return;
                        }
                    }

                    if(sale_qty == 0) {
                        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').remove();
                    }
                }
                $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(sale_qty);
            }
        }
        else
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(sale_qty);
        if(!flag) {
            $('#editModal').modal('hide');
            $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.qty').val(sale_qty);
        }
        calculateRowProductData(sale_qty);
    }

    function unitConversion() {
        var row_unit_operator = unit_operator[rowindex].slice(0, unit_operator[rowindex].indexOf(","));
        var row_unit_operation_value = unit_operation_value[rowindex].slice(0, unit_operation_value[rowindex].indexOf(","));

        if (row_unit_operator == '*') {
            row_product_price = product_price[rowindex] * row_unit_operation_value;
        } else {
            row_product_price = product_price[rowindex] / row_unit_operation_value;
        }
    }

    function calculateRowProductData(quantity) {
        // if (product_discount[rowindex] < 1) {
        //     cur_product_id = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product-id').val();
        //
        //     //     if (product_discount[rowindex] < 1) {

        //     //         product_discount[rowindex] = draft_discounts[cur_product_id];
        //     //         console.log(product_discount[rowindex]);
        //     //         if(product_discount[rowindex] == undefined || product_discount[rowindex] == null) {
        //     //             checkDiscount(1, false);
        //     //         }
        //     //     }
        //
        // }

        if(product_type[pos] == 'standard')
            unitConversion();
        else
            row_product_price = product_price[rowindex];
        if (tax_method[rowindex] == 1) {
            var net_unit_price = row_product_price - product_discount[rowindex];
            var tax = net_unit_price * quantity * (tax_rate[rowindex] / 100);
            var sub_total = (net_unit_price * quantity) + tax;

            if(parseFloat(quantity))
                var sub_total_unit = sub_total / quantity;
            else
                var sub_total_unit = sub_total;
        }
        else {
            var sub_total_unit = row_product_price - product_discount[rowindex];
            var net_unit_price = (100 / (100 + tax_rate[rowindex])) * sub_total_unit;
            var tax = (sub_total_unit - net_unit_price) * quantity;
            var sub_total = sub_total_unit * quantity;
        }

        var topping_price = ($('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.topping-price').val() * quantity) || 0;

        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.discount-value').val((product_discount[rowindex] * quantity).toFixed(POS_CONFIG.decimal));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-rate').val(tax_rate[rowindex].toFixed(POS_CONFIG.decimal));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.net_unit_price').val(net_unit_price.toFixed(POS_CONFIG.decimal));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.tax-value').val(tax.toFixed(POS_CONFIG.decimal));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.product-price').text(sub_total_unit.toFixed(POS_CONFIG.decimal));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.sub-total').text((sub_total+topping_price).toFixed(POS_CONFIG.decimal));
        $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')').find('.subtotal-value').val((sub_total+topping_price).toFixed(POS_CONFIG.decimal));

        calculateTotal();
    }

    function calculateTotal() {
        //Sum of quantity
        var total_qty = 0;
        $("table.order-list tbody .qty").each(function(index) {
            if ($(this).val() == '') {
                total_qty += 0;
            } else {
                total_qty += parseFloat($(this).val());
            }
        });
        $('input[name="total_qty"]').val(total_qty);

        //Sum of discount
        var total_discount = 0;
        $("table.order-list tbody .discount-value").each(function() {
            total_discount += parseFloat($(this).val());
        });

        $('input[name="total_discount"]').val(total_discount.toFixed(POS_CONFIG.decimal));

        //Sum of tax
        var total_tax = 0;
        $(".tax-value").each(function() {
            total_tax += parseFloat($(this).val());
        });

        $('input[name="total_tax"]').val(total_tax.toFixed(POS_CONFIG.decimal));

        //Sum of subtotal
        var total = 0;
        $(".sub-total").each(function() {
            total += parseFloat($(this).text());
        });

        if ($('enable_installment').is(':checked')) {
            $('input[name="total_price"]').val($('input[name="installment_plan[total_amount]"]').val());
        } else {
            $('input[name="total_price"]').val(total.toFixed(POS_CONFIG.decimal));
        }

        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        var item = $('table.order-list tbody tr:last').index();
        if (item == -1) {
            $('#order-discount-val').val(0);
        }
        var total_qty = parseFloat($('input[name="total_qty"]').val());
        var subtotal = parseFloat($('input[name="total_price"]').val());
        var order_tax = parseFloat($('select[name="order_tax_rate_select"]').val());
        var order_discount_type = $('select[name="order_discount_type_select"]').val();
        var order_discount_value = parseFloat($('input[name="order_discount_value"]').val());

        if (POS_CONFIG.isEditMode && POS_CONFIG.saleOrderDiscountType &&
            (order_discount_value === null || isNaN(order_discount_value) || order_discount_value < 1)) {
            order_discount_type = POS_CONFIG.saleOrderDiscountType;
            order_discount_value = parseFloat(POS_CONFIG.saleOrderDiscountValue);
        }

        if (!order_discount_value)
            order_discount_value = 0;

        if(order_discount_type == 'Flat') {
            if(!currencyChange) {
                var order_discount = parseFloat(order_discount_value);
            }
            else
                var order_discount = parseFloat(order_discount_value*currency['exchange_rate']);
        }
        else
            var order_discount = parseFloat(subtotal * (order_discount_value / 100));

        $("#discount").text(order_discount_value.toFixed(POS_CONFIG.decimal));
        $('input[name="order_discount"]').val(order_discount);
        $('#order-discount-val').val(order_discount_value);
        $('input[name="order_discount_type"]').val(order_discount_type);
        if(!currencyChange)
            var shipping_cost = parseFloat($('input[name="shipping_cost"]').val());
        else
            var shipping_cost = parseFloat($('input[name="shipping_cost"]').val() * currency['exchange_rate']);
        if (!shipping_cost)
            shipping_cost = 0;

        item = ++item + '(' + total_qty + ')';
        order_tax = (subtotal - order_discount) * (order_tax / 100);
        var grand_total = (subtotal + order_tax + shipping_cost) - order_discount;
        $('input[name="grand_total"]').val(grand_total.toFixed(POS_CONFIG.decimal));

        if($("#coupon-code").val() != '')
            couponDiscount();
        if(!currencyChange)
            var coupon_discount = parseFloat($('input[name="coupon_discount"]').val());
        else
            var coupon_discount = parseFloat($('input[name="coupon_discount"]').val() * currency['exchange_rate']);
        if (!coupon_discount)
            coupon_discount = 0;
        grand_total -= coupon_discount;

        $('#item').text(item);
        $('input[name="item"]').val($('table.order-list tbody tr:last').index() + 1);
        $('#subtotal').text(subtotal.toFixed(POS_CONFIG.decimal));
        $('#tax').text(order_tax.toFixed(POS_CONFIG.decimal));
        $('input[name="order_tax"]').val(order_tax.toFixed(POS_CONFIG.decimal));
        $('#shipping-cost').text(shipping_cost.toFixed(POS_CONFIG.decimal));
        $('input[name="shipping_cost"]').val(shipping_cost);
        $('#grand-total').text(grand_total.toFixed(POS_CONFIG.decimal));
        $('#grand-total-m').text(grand_total.toFixed(POS_CONFIG.decimal));
        $('input[name="grand_total"]').val(grand_total.toFixed(POS_CONFIG.decimal));
        currencyChange = false;

        saveDataToLocalStorageForCustomerDisplay('clear_no');
    }



    function cancel(rownumber) {
        while(rownumber >= 0) {
            product_price.pop();
            wholesale_price.pop();
            product_discount.pop();
            tax_rate.pop();
            tax_name.pop();
            tax_method.pop();
            unit_name.pop();
            unit_operator.pop();
            unit_operation_value.pop();
            $('table.order-list tbody tr:last').remove();
            rownumber--;
        }
        $('input[name="shipping_cost"]').val('');
        $('input[name="order_discount_value"]').val('');
        $('select[name="order_tax_rate_select"]').val(0);
        calculateTotal();
    }

    function confirmCancel() {
        playSound();
        if (confirm("Are you sure want to cancel?")) {
            cancel($('table.order-list tbody tr:last').index());
        }
        return false;
    }

    $(document).on('submit', '.payment-form', function(e) {
        e.preventDefault();

        $("table.order-list tbody .qty").each(function(index) {
            if ($(this).val() == '') {
                alert('One of products has no quantity!');
            }
        });

        var rownumber = $('table.order-list tbody tr:last').index();
        if (rownumber < 0) {
            alert("Please insert product to order table!")
        }
        else if(parseFloat($('input[name="total_qty"]').val()) <= 0) {
            alert('Product quantity is 0');
        }
        else {
            if ($('input[name="sale_status"]').val() == 1) {
                $("#submit-btn").prop('disabled', true).html('<span class="spinner-border text-light" role="status"></span>');
            }

            $('input[name="paid_by_id"]').val($('select[name="paid_by_id_select"]').val());
            $('select[name="paid_by_id_select[]"]').each(function(index) {
                $('input[name="paid_by_id[]"]').eq(index).val($(this).val());
            });
            $('input[name="order_tax_rate"]').val($('select[name="order_tax_rate_select"]').val());

            $.ajax({
                url: $('.payment-form').attr('action'), // The form's action URL
                type: $('.payment-form').attr('method'), // The form's method (GET or POST)
                data: $('.payment-form').serialize(), // Serialize the form data
                success: function(response) {

                    if ($('input[name="sale_status"]').val() == 1 ||
                        (POS_CONFIG.isRestaurant && $('input[name="sale_status"]').val() == 5)) {

                        var head = $('head').html();
                        $('.ui-helper-hidden-accessible').css('display','none');

                        let whatsappChecked = $('#send_whatsapp').is(':checked');
                        let printChecked = $('#print_invoice').is(':checked');
                        if (whatsappChecked) {
                            let customer_id = $('select[name="customer_id"]').val();
                            let customer = lims_customer_list.find(c => c.id == customer_id);
                            let whatsapp_number = customer?.wa_number?.replace(/\D/g, '') || '';
                            let link = POS_CONFIG.urls.genInvoice+'/' + response;

                            if (whatsapp_number != '') {
                                $.ajax({
                                    url: link,
                                    type: 'GET',
                                    success: function(data) {
                                        $.ajax({
                                            url: POS_CONFIG.urls.whatsappSend,
                                            type: "POST",
                                            data: {
                                                receiver_phone: [whatsapp_number],
                                                html_content: data,
                                                message: POS_CONFIG.labels.invoice,
                                            },
                                            success: function (res) {
                                                console.log("WhatsApp message sent!");
                                            },
                                            error: function (xhr) {
                                                console.log("Failed to send message!");
                                            }
                                        });
                                    }
                                });
                            }

                        }
                        if (printChecked) {
                            let link = POS_CONFIG.urls.genInvoice+'/' + response + '?is_print=true';
                            $.ajax({
                                url: link,
                                type: 'GET',
                                success: function(data) {
                                    if (data.trim() === 'receipt_printer') {
                                        alert(POS_CONFIG.labels.receiptPrinted);
                                    } else if (data.trim() === 'invoice_settings_error') {
                                        alert(POS_CONFIG.labels.invoiceSettingsError);
                                    } else {
                                        $('#pos-layout').css('display', 'none');
                                        $('head').html('');
                                        $('#print-layout').html(data);

                                        setTimeout(function() {
                                            window.print();
                                        }, 50);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error loading invoice:", error);
                                }
                            });
                        }
                        if (!whatsappChecked && !printChecked) {
                            location.replace(POS_CONFIG.urls.posIndex);
                        }

                        $("#submit-btn").prop('disabled', false).html(POS_CONFIG.labels.submit);
                        $('#add-payment').modal('hide');
                        cancel($('table.order-list tbody tr:last').index());

                        setTimeout(function() {
                            window.onafterprint = (event) => {
                                if(isMobile == false){
                                    $('#pos-layout').css('display','block');
                                    $('#print-layout').html('');
                                    $('head').html(head);
                                    location.replace(POS_CONFIG.urls.posIndex);
                                }
                            };
                        }, 100);

                        $('input[name="sale_id"]').val('');
                        $('input[name="draft"]').val('');
                        history.replaceState('', '', POS_CONFIG.urls.posIndex);

                        $.get(POS_CONFIG.urls.recentSale, function(data) {
                            populateRecentSale(data);
                        });
                    }
                    else if ($('input[name="sale_status"]').val() == 3) {
                        $('#pos-layout').prepend('<div class="alert alert-success alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+POS_CONFIG.labels.saleDraftSuccess+'</div>');
                        $('input[name="sale_status"]').val(1);
                        cancel($('table.order-list tbody tr:last').index());
                        $.get(POS_CONFIG.urls.recentDraft, function(data) {
                            populateRecentDraft(data);
                        });
                    }

                },
                error: function(xhr) {
                    console.log('Form submission failed.');
                }
            });

            saveDataToLocalStorageForCustomerDisplay('clear_all');
        }
    });

    if (POS_CONFIG.isRestaurant) {
    $('#service_id').change(function() {
        if($(this).val() == 1) {
            $('#table_id').prop('disabled', false);
            $('#table_id').selectpicker('refresh');


            $('#waiter_id').prop('disabled', false);
            $('#waiter_id').selectpicker('refresh');

            $('#table_id').prop('required', true);
            $('#waiter_id').prop('required', true);
        }
        else {
            $('#table_id').prop('disabled', true);
            $('#table_id').selectpicker('refresh');

            $('#waiter_id').prop('disabled', true);
            $('#waiter_id').selectpicker('refresh');

            $('#table_id').prop('required', false);
            $('#waiter_id').prop('required', false);
        }
    });
    } // end if(POS_CONFIG.isRestaurant)

    // Load suppliers when the add supplier payment button is clicked
    $(document).on('click', '.add-supplier-payment', function() {
        $('#add-supplier-payment form')[0].reset();
        $.ajax({
            url: POS_CONFIG.urls.supplierAll, // Laravel route helper
            type: "GET",
            dataType: "json",
            success: function(response) {
                let $supplierSelect = $('#supplier_list');
                $supplierSelect.empty(); // Clear existing options

                $supplierSelect.append('<option value="">Select Supplier</option>');

                $supplierSelect.append(response);

                // Refresh bootstrap-select
                $supplierSelect.selectpicker('refresh');
            },
            error: function(xhr) {
                console.error("Error loading suppliers:", xhr.responseText);
            }
        });
    });

    $(document).on('change', '#supplier_list', function() {
        $('input[name="balance"]').val('');
        let supplierId = $(this).val();

        if (supplierId) {
            $.ajax({
                url: POS_CONFIG.urls.supplierDue+'/' + supplierId,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    // response[0] = supplier data, response[1] = due
                    let due = response[0];
                    $('input[name="balance"]').val(due);
                },
                error: function(xhr) {
                    console.error("Error fetching supplier due:", xhr.responseText);
                    $('input[name="balance"]').val('');
                }
            });
        } else {
            $('input[name="balance"]').val('');
        }
    });




    // Expose functions called from HTML onclick attributes or inline scripts (must be global)
    window.confirmDelete   = confirmDelete;
    window.confirmCancel   = confirmCancel;
    window.populateProduct = populateProduct;

})(); // end IIFE
