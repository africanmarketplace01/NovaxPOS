/* ================================================================
 * POS Customer Display & Utility Scripts
 * ================================================================ */


    const display = document.querySelector('.display');
    const buttons = document.querySelectorAll('.btn');

    let currentInput = '';
    let operator = null;
    let previousInput = '';

    function updateDisplay() {
        display.value = currentInput || previousInput || '0';
    }

    function calculate() {
        let result;
        const prev = parseFloat(previousInput);
        const current = parseFloat(currentInput);

        if (isNaN(prev) || isNaN(current)) return;

        switch (operator) {
            case '+':
                result = prev + current;
                break;
            case '-':
                result = prev - current;
                break;
            case 'x':
                result = prev * current;
                break;
            case '÷':
                result = prev / current;
                break;
            case '%':
                result = prev % current;
                break;
            default:
                return;
        }
        currentInput = result.toString();
        operator = null;
        previousInput = '';
    }

    buttons.forEach(button => {
        button.addEventListener('click', (e) => {
            const value = e.target.textContent;

            if (e.target.classList.contains('number') || value === '.') {
                currentInput += value;
            } else if (e.target.classList.contains('operator')) {
                if (currentInput === '') return;
                if (previousInput !== '') calculate();
                operator = value;
                previousInput = currentInput;
                currentInput = '';
            } else if (e.target.classList.contains('equals')) {
                calculate();
            } else if (e.target.classList.contains('ac')) {
                currentInput = '';
                operator = null;
                previousInput = '';
            } else if (e.target.classList.contains('ce')) {
                currentInput = currentInput.slice(0, -1);
            }

            updateDisplay();
        });
    });

    updateDisplay();

    $('#expense-amount').on('input', function() {
        var value = $(this).val();
        if (value < 0) {
            alert('Amount cannot be negative');
            $(this).val('');
        } else if (isNaN(value)) {
            alert('Please enter a valid number');
            $(this).val('');
        } else {
            var cash_register_id = $("#register-details-btn").data('id');
            if(cash_register_id){
                $.ajax({
                    url: POS_CONFIG.urls.cashRegisterDetails+'/'+cash_register_id,
                    type: "GET",
                    success:function(data) {
                        if(parseFloat(value) > parseFloat(data['total_cash'])) {
                            alert(POS_CONFIG.labels.amountExceedsBalance);
                            $('#expense-amount').val('');
                        }
                    }
                })
            }
        }
    });

    $('#supplier-amount').on('input', function() {
        var value = $(this).val();
        if (value < 0) {
            alert('Amount cannot be negative');
            $(this).val('');
        } else if (isNaN(value)) {
            alert('Please enter a valid number');
            $(this).val('');
        } else {
            var cash_register_id = $("#register-details-btn").data('id');
            if(cash_register_id){
                $.ajax({
                    url: POS_CONFIG.urls.cashRegisterDetails+'/'+cash_register_id,
                    type: "GET",
                    success:function(data) {
                        if(parseFloat(value) > parseFloat(data['total_cash'])) {
                            alert(POS_CONFIG.labels.amountExceedsBalance);
                            $('#supplier-amount').val('');
                        }
                    }
                })
            }
        }
    });

    $(document).on("click", "#print-last-receipt", function(e){
        e.preventDefault();
        let link = $(this).attr('href');
        $.ajax({
            url: link,
            type: 'GET',
            success: function(data) {
                if (data.trim() === 'receipt_printer') {
                    alert(POS_CONFIG.labels.receiptPrinted);
                } else if (data.trim() === 'invoice_settings_error') {
                    alert(POS_CONFIG.labels.invoiceSettingsError);
                } else {
                    location.href = link;
                }
            },
            error: function(xhr, status, error) {
                console.error("Error loading invoice:", error);
            }
        });
    });

    $('#price_type').on('changed.bs.select', function () {

        let selectedType = $(this).val();

        $.ajax({
            url: POS_CONFIG.urls.setPriceType,
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                price_type: selectedType
            },
            success: function(response){

                // STORE IN JS GLOBAL VARIABLE
                window.POS_PRICE_TYPE = response.price_type;

                console.log("Current price type:", window.POS_PRICE_TYPE);
            }
        });

    });

    /*-------------START CUSTOMER DISPLAY-----------*/
    function openCustomerDisplay(url){
        var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : window.screenX;
        var dualScreenTop  = window.screenTop != undefined ? window.screenTop : window.screenY;

        var width  = screen.width;
        var height = screen.height;

        var left = dualScreenLeft + width;
        var top  = dualScreenTop;

        window.customerDisplay = window.open(
            url,
            "customer_display",
            "width="+width+",height="+height+",top="+top+",left="+left
        );
    }

    // toggle button click
    $(document).on("click","#customer-display",function(e){
        e.preventDefault();
        var url = $(this).attr("href");

        if(localStorage.getItem("customer_display_enabled") == "yes"){
            // disable
            localStorage.removeItem("customer_display_enabled");

            $(this)
            .removeClass("active")
            .addClass("inactive")
            .attr("title", "'+POS_CONFIG.labels.enableCustomerDisplay+'");

            // close customer display window
            if(window.customerDisplay && !window.customerDisplay.closed){
                window.customerDisplay.close();
            }
            // ✅ interval stop
            clearInterval(customerDisplayInterval);
        }else{
            // enable
            localStorage.setItem("customer_display_enabled","yes");

            $(this)
            .removeClass("inactive")
            .addClass("active")
            .attr("title", "'+POS_CONFIG.labels.disableCustomerDisplay+'");

            if(!window.customerDisplay || window.customerDisplay.closed){
                openCustomerDisplay(url);
            }else{
                window.customerDisplay.focus();
            }

            // 🔧 interval restart
            customerDisplayInterval = setInterval(function(){
                if(localStorage.getItem("customer_display_enabled") == "yes"){
                    if(!window.customerDisplay || window.customerDisplay.closed){
                        openCustomerDisplay($("#customer-display").attr("href"));
                    }
                }
            }, 3000);
        }

        $(this).tooltip('dispose').tooltip();
    });

    var customerDisplayInterval;

    $(document).ready(function(){
        if(localStorage.getItem("customer_display_enabled") == "yes"){
            $("#customer-display")
            .removeClass("inactive")
            .addClass("active")
            .attr("title", "'+POS_CONFIG.labels.disableCustomerDisplay+'");
            // window check
            if(!window.customerDisplay || window.customerDisplay.closed){
                openCustomerDisplay($("#customer-display").attr("href"));
            }

        }else{
            $("#customer-display")
            .removeClass("active")
            .addClass("inactive")
            .attr("title", "'+POS_CONFIG.labels.enableCustomerDisplay+'");
        }

        $("#customer-display").tooltip('dispose').tooltip();

        // auto reopen interval
        customerDisplayInterval = setInterval(function(){
            if(localStorage.getItem("customer_display_enabled") == "yes"){
                if(!window.customerDisplay || window.customerDisplay.closed){
                    openCustomerDisplay($("#customer-display").attr("href"));
                }
            }
        }, 3000);
    });
    // POS window close হলে customer display close
    window.addEventListener("beforeunload", function () {
        if(window.customerDisplay && !window.customerDisplay.closed){
            window.customerDisplay.close();
        }
    });
    /*-------------END CUSTOMER DISPLAY-----------*/

    $('#add-payment').on('shown.bs.modal', function (e) {
        saveDataToLocalStorageForCustomerDisplay('clear_no');
    });
    $('#add-payment').on('hidden.bs.modal', function (e) {
        saveDataToLocalStorageForCustomerDisplay('clear_partial');
    });

    function saveDataToLocalStorageForCustomerDisplay(is_clear_local_storage) {
        if(is_clear_local_storage == 'clear_all'){
            localStorage.setItem("customer_display_data_array", JSON.stringify([]));
            return false;
        }

        let products = [];

        $("#myTable tbody tr").each(function () {
            let name = $(this).find(".product-title strong").clone()
                        .children("svg").remove().end()
                        .text().trim();

            let price = $(this).find(".product-price.d-none.d-md-block").text().trim();
            let qty   = $(this).find("input.qty").val();
            let subtotal = $(this).find(".sub-total").text().trim();

            products.push({
                name: name,
                price: price,
                qty: qty,
                subtotal: subtotal
            });
        });

        let CashReceived = 0;
        $("input[name='paying_amount[]']").each(function() {
            let val = parseFloat($(this).val()) || 0.00;
            CashReceived += val;
        });
        CashReceived = CashReceived.toFixed(POS_CONFIG.decimal);

        let customer_display_data_array = {
            customer: $("#customer_id option:selected").text(),
            products: products,
            item: $("#item").text(),
            subtotal: $("#subtotal").text(),
            discount: $("#discount").text(),
            couponText: $("#coupon-text").text(),
            tax: $("#tax").text(),
            shippingCost: $("#shipping-cost").text(),
            totalPayable: $("#grand-total").text(),
            CashReceived: CashReceived,
            totalPaying: $(".total_paying").text(),
            change: $(".change").text(),
            due: $(".due").text(),
        };

        if (is_clear_local_storage == 'clear_partial') {
            customer_display_data_array.CashReceived = (0).toFixed(POS_CONFIG.decimal);
            customer_display_data_array.totalPaying = (0).toFixed(POS_CONFIG.decimal);
            customer_display_data_array.change = (0).toFixed(POS_CONFIG.decimal);
            customer_display_data_array.due = (0).toFixed(POS_CONFIG.decimal);

        }

        localStorage.setItem("customer_display_data_array", JSON.stringify(customer_display_data_array));
    }
    /*-------------End Customer Display-----------*/

