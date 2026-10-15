{{-- Phone/WhatsApp intlTelInput initialization + installment plan script --}}

<script>
    function playSound() {
        var sound = document.getElementById("audio");
        sound.play();
    }

    const doneTypingInterval = 200;
    const $input = $('#product-search-input');
    const $results = $('#product-results-container');
    const $noResults = $('#no-results-message');

    function clearResults() {
        $results.empty().css('padding', '0');
        $noResults.hide();
    }

    $(document).ready(function() {

        $('#product-search-input').focus();

        // NOTE: the initial product grid is loaded by pos.main.js (loadAllProducts()
        // on window load and on warehouse change), which loads ALL warehouse products.
        // A redundant featured-only fetch here was removed so the grid isn't loaded twice.

        let typingTimer;

        function searchProducts(search) {
            $results.css('padding', '0 10px 15px');
            $results.html('<div class="loader " title="4" style="border:none;min-height:300px"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve"><rect x="0" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="10" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.2s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect><rect x="20" y="0" width="4" height="10" fill="#333"><animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.4s" dur="0.6s" repeatCount="indefinite"></animateTransform></rect></svg></div>');
            $noResults.hide();

            $.ajax({
                url: '{{ url("/sales/search") }}',
                type: 'GET',
                data: {
                    warehouse_id: warehouse_id,
                    search: search
                },
                success: function (data) {
                    $results.empty();
                    if (data.length > 0) {
                        $noResults.hide();
                        data.forEach(function (product) {
                            let productHtml = '';
                            let displayStock = '';

                            if(authUser > 2) {
                                displayStock = '';
                            } else {
                                displayStock = ` | ${product.qty} {{ __('db.In Stock') }} `;
                            }

                            var batch_id = product.product_batch_id ? product.product_batch_id : '';

                            if (product.is_imei == '1' || product.is_imei === 1 || product.is_imei === true) {

                                // Check if IMEI already exists in the selected products
                                let imeiNumbersArray = [];
                                let exists = false;
                                $('.imei-number').each(function () {
                                    let val = $(this).val();
                                    imeiNumbersArray = val.split(",");
                                    if(imeiNumbersArray.includes(product.imei_number)) {
                                        exists = true;
                                        return;
                                    }
                                });

                                if((exists == false) && (product.imei_number !== null && $.trim(product.imei_number) !== '')){
                                    productHtml = `
                                        <div class="product-img" data-code="${product.code}"
                                                                data-qty="${product.qty}"
                                                                data-imei="${product.imei_number}"
                                                                data-embedded="${product.is_embeded}"
                                                                data-batch="${batch_id}"
                                                                data-price="${product.price}">
                                            ${product.name} (${product.code}) | ${product.price} | IMEI: ${product.imei_number}
                                        </div>
                                    `;
                                }else{
                                    $noResults.show();
                                }
                            } else if (product.product_batch_id != null) {
                                if(parseInt(product.qty) > 0){
                                    if(product.expired_date == 0) {
                                        product.expired_date = "{{__('db.expired')}}";
                                        var expired = "expired";
                                    }
                                    productHtml = `
                                        <div class="product-img ${expired}" data-code="${product.code}"
                                                                            data-qty="${product.qty}"
                                                                            data-imei="${product.is_imei}"
                                                                            data-embedded="${product.is_embeded}"
                                                                            data-batch="${batch_id}"
                                                                            data-price="${product.price}">
                                            ${product.name} (${product.code}) - ${product.expired_date} | ${product.price} ${displayStock}
                                        </div>
                                    `;
                                }
                            } else if (product.type == 'service' || product.type == 'digital') {
                                productHtml = `
                                    <div class="product-img" data-code="${product.code}"
                                                                        data-qty="N/A"
                                                                        data-imei="${product.is_imei}"
                                                                        data-embedded="${product.is_embeded}"
                                                                        data-batch="${batch_id}"
                                                                        data-price="${product.price}">
                                        ${product.name} (${product.code}) | ${product.price} ${displayStock}
                                    </div>
                                `;
                            } else {
                                if(parseInt(product.qty) > 0 || without_stock == 'yes'){
                                    productHtml = `
                                        <div class="product-img" data-code="${product.code}"
                                                                data-qty="${product.qty}"
                                                                data-imei="${product.is_imei}"
                                                                data-embedded="${product.is_embeded}"
                                                                data-batch="${batch_id}"
                                                                data-price="${product.price}">
                                            ${product.name} (${product.code}) | ${product.price} ${displayStock}
                                        </div>
                                    `;
                                }
                            }

                            $results.append(productHtml);
                        });

                        $('.product-img').on('click', function () {
                            clearResults();
                        });

                        // Auto-click if only one result
                        if (data.length === 1) {

                            //let product = data[0]; // ✅ define it properly

                            if (click === 0) {
                                $('#product-results-container .product-img').first().trigger('click');
                            }

                            clearResults();
                            click = 1;
                        }

                    } else {
                        clearResults();
                        $noResults.show();
                    }
                },
                error: function () {
                    $noResults.text("Error searching products.").show();
                }
            });
        }

        var click = 0;

        // Trigger on input
        $input.on('input', function () {
            const value = $(this).val().trim();
            if (value.length >= 3) {
                click = 0;
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => searchProducts(value), doneTypingInterval);
            } else {
                clearResults();
            }
        });

        // Trigger on paste
        $input.on('paste', function (e) {
            const pastedData = (e.originalEvent || e).clipboardData.getData('text');
            if (pastedData.length >= 3) {
                click = 0;
                searchProducts(pastedData.trim());
            }
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#product-results-container, #product-search-input').length) {
                clearResults();
            }
        });

        // ✅ Show modal and calculate base totals
        $('#installmentPlanBtn').on('click', function() {
            let baseTotal = parseFloat($('input[name="grand_total"]').val()) || 0;
            let additionalAmount = parseFloat($('#additional_amount').val()) || 0;
            let installment_total_price = baseTotal + additionalAmount;

            $('#installment_price').val(baseTotal.toFixed(2));
            $('#installment_total').val(installment_total_price.toFixed(2));

            updateSchedulePreview();
            $('#installmentPlanModal').modal('show');
        });

        function updateSchedulePreview() {
            let total = parseFloat($('#installment_total').val()) || 0;
            let downPayment = parseFloat($('#down_payment_id').val()) || 0;
            let months = parseInt($('#installment_months').val()) || 1;
            let remaining = total - downPayment;
            let installmentAmount = (months > 0) ? (remaining / months).toFixed(2) : 0;

            let tbody = $('#installmentScheduleTable tbody');
            tbody.empty();

            let date = new Date();
            for (let i = 1; i <= months; i++) {
                let nextDate = new Date(date);
                nextDate.setMonth(date.getMonth() + i);
                let dateString = nextDate.toISOString().split('T')[0];
                tbody.append(`<tr><td>${i}</td><td>${dateString}</td><td>${installmentAmount}</td></tr>`);
            }

            if (downPayment > 0) {
                $('#paymentFields').slideDown();
                $('#done-installment-modal').text('Create Instalment & Pay');
            } else {
                $('#paymentFields').slideUp();
                $('#done-installment-modal').text('Create Instalment');
            }
        }

        $('#additional_amount, #down_payment_id, #installment_months').on('input', function() {
            let baseTotal = parseFloat($('#installment_price').val()) || 0;
            let additional_amount = parseFloat($('#additional_amount').val()) || 0;
            let installment_total_price = baseTotal + additional_amount;

            $('#installment_total').val(installment_total_price.toFixed(2));
            updateSchedulePreview();
        });

        // ✅ When Close button clicked
        $('#close-installment-modal, #close-installment-modal-x').on('click', function() {
            $('#enable_installment').prop('checked', false);
            $('#installmentPlanModal').modal('hide');
        });

        // ✅ Reset modal fields every time it's closed
        $('#installmentPlanModal').on('hidden.bs.modal', function() {
            if (!$('#enable_installment').prop('checked')) {
                $(this).find('input[name="installment_plan[name]"]').val('12 Months');
                $(this).find('input[name="installment_plan[additional_amount]"]').val('0');
                $(this).find('input[name="installment_plan[down_payment]"]').val('0');
                $(this).find('input[name="installment_plan[months]"]').val('12');
                $(this).find('textarea[name="installment_plan[payment_note]"]').val('');
                $(this).find('select[name="installment_plan[paid_by_id]"]').val('1').trigger('change');
                $('#paymentFields').hide();
                $('#done-installment-modal').text('Create Instalment');
                $('#installmentScheduleTable tbody').empty();
            }
        });

        // ✅ When Done button clicked
        $('#done-installment-modal').on('click', function() {
            let months = parseInt($('#installment_months').val()) || 0;
            if (months < 1) {
                alert('Please enter valid number of months.');
                return;
            }

            $('input[name="grand_total"]').val($('#installment_total').val());
            $('input[name="total_price"]').val($('#installment_total').val());
            $('#grand-total').text(parseFloat($('#installment_total').val()).toFixed({{$general_setting->decimal}}));
            $('#grand-total-m').text(parseFloat($('#installment_total').val()).toFixed({{$general_setting->decimal}}));
            
            $('#enable_installment').prop('checked', true);
            $('#installmentPlanModal').modal('hide');
        });

    });
</script>
