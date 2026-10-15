/* ================================================================
 * Product Index — Main JavaScript
 * All PHP values referenced via window.PRODUCT_CONFIG (see
 * resources/views/backend/product/partials/scripts/config.blade.php)
 * ================================================================ */

(function () {

    /* ── SaaS package limit check ────────────────────────────────────── */
    if (PRODUCT_CONFIG.isSaaS) {
        if (localStorage.getItem('message')) {
            alert(localStorage.getItem('message'));
            localStorage.removeItem('message');
        }
        $.ajax({
            type: 'GET',
            async: false,
            url: PRODUCT_CONFIG.urls.fetchPackageData,
            success: function (data) {
                if (data['number_of_product'] > 0 && data['number_of_product'] <= PRODUCT_CONFIG.numberOfProduct) {
                    $('a.add-product-btn').addClass('d-none');
                }
            }
        });
    }

    /* ── CSRF setup ──────────────────────────────────────────────────── */
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    /* ── Helpers ─────────────────────────────────────────────────────── */
    function confirmDelete() {
        return confirm('Are you sure want to delete?');
    }
    window.confirmDelete = confirmDelete;

    /* ── DataTable columns ───────────────────────────────────────────── */
    var columns = [
        { data: 'key' }, { data: 'name' }, { data: 'image_path' },
        { data: 'code' }, { data: 'brand' }, { data: 'category' },
        { data: 'qty' }, { data: 'unit' }, { data: 'price' }
    ];
    if (PRODUCT_CONFIG.roleId <= 2) {
        columns.push({ data: 'cost' });
        columns.push({ data: 'stock_worth' });
    }
    PRODUCT_CONFIG.fieldName.forEach(function (f) { columns.push({ data: f }); });
    columns.push({ data: 'options' });

    /* ── State ───────────────────────────────────────────────────────── */
    var warehouse = [], variant = [], qty = [];
    var htmltext, slidertext;
    var product_id = [];

    /* ── Filter selects — restore from config ────────────────────────── */
    $('#warehouse_id').val(PRODUCT_CONFIG.warehouseId);
    $('#product_type').val(PRODUCT_CONFIG.productType);
    $('#brand_id').val(PRODUCT_CONFIG.brandId);
    $('#category_id').val(PRODUCT_CONFIG.categoryId);
    $('#unit_id').val(PRODUCT_CONFIG.unitId);
    $('#tax_id').val(PRODUCT_CONFIG.taxId);
    $('#imeiorvariant').val(PRODUCT_CONFIG.imeiorvariant);
    $('#stock_filter').val(PRODUCT_CONFIG.stockFilter);

    /* ── Select-all checkbox ─────────────────────────────────────────── */
    $('#select_all').on('change', function () {
        $("tbody input[type='checkbox']").prop('checked', $(this).is(':checked'));
    });

    /* ── Row click → product details ─────────────────────────────────── */
    $(document).on('click', 'tr.product-link td:not(:first-child, :last-child)', function () {
        productDetails($(this).parent().data('product'), $(this).parent().data('imagedata'));
    });
    $(document).on('click', '.view', function () {
        var $row = $(this).closest('tr');
        productDetails($row.data('product'), $row.data('imagedata'));
    });

    /* ── Print button ────────────────────────────────────────────────── */
    $('#print-btn').on('click', function () {
        var bodyContent = document.querySelector('#product-details .modal-body').innerHTML;
        var imageSrc    = $('#slider-content img').first().attr('src') || '';
        var newWin      = window.open('', '', 'width=1100,height=700');
        newWin.document.open();
        newWin.document.write(`
            <html>
            <head>
                <title>Product Details</title>
                <link rel="stylesheet" href="${PRODUCT_CONFIG.urls.bootstrapCss}">
                <style>
                    body { font-family: Arial, sans-serif; padding: 30px; color: #333; }
                    .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                    .product-top { display: flex; gap: 30px; margin-bottom: 25px; }
                    .product-image { width: 250px; }
                    .product-image img { width: 100%; border: 1px solid #ddd; padding: 5px; border-radius: 6px; }
                    .product-info { flex: 1; }
                    .section-title { margin-top: 30px; margin-bottom: 10px; font-weight: 600; border-left: 4px solid #007bff; padding-left: 10px; font-size: 16px; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    table thead th { background: #f2f2f2; font-weight: 600; border: 1px solid #ddd; padding: 8px; text-align: left; }
                    table tbody td { border: 1px solid #ddd; padding: 8px; }
                    .footer-note { margin-top: 40px; font-size: 12px; text-align: right; color: #777; }
                    @media print { body { -webkit-print-color-adjust: exact; } }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h3>Product Details Report</h3>
                    <small>Generated on: ${new Date().toLocaleString()}</small>
                </div>
                <div class="product-top">
                    <div class="product-image">
                        ${imageSrc ? `<img src="${imageSrc}">` : ''}
                    </div>
                    <div class="product-info">
                        ${$('#product-content').html()}
                    </div>
                </div>
                ${$('#product-warehouse-section').html() || ''}
                ${$('#product-variant-section').html() || ''}
                ${$('#product-variant-warehouse-section').html() || ''}
                ${$('.item-list').prop('outerHTML') || ''}
                <div class="footer-note">System Generated Report</div>
            </body>
            </html>
        `);
        newWin.document.close();
        newWin.onload = function () { newWin.focus(); newWin.print(); newWin.close(); };
    });

    /* ── productDetails ──────────────────────────────────────────────── */
    function productDetails(product, imagedata) {
        product[11] = product[11].replace(/@/g, '"');
        var L = PRODUCT_CONFIG.labels;
        htmltext = slidertext = '';

        htmltext = '<p>' + L.type     + ': ' + product[0]  +
            '</p><p>' + L.name        + ': ' + product[1]  +
            '</p><p>' + L.code        + ': ' + product[2]  +
            '</p><p>' + L.brand       + ': ' + product[3]  +
            '</p><p>' + L.category    + ': ' + product[4]  +
            '</p><p>' + L.quantity    + ': ' + product[17] +
            '</p><p>' + L.unit        + ': ' + product[5]  +
            (PRODUCT_CONFIG.roleId < 3 ? '</p><p>' + L.cost + ': ' + product[6] : '') +
            '</p><p>' + L.price       + ': ' + product[7]  +
            '</p><p>' + L.tax         + ': ' + product[8]  +
            '</p><p>' + L.taxMethod   + ' : ' + product[9] +
            '</p><p>' + L.alertQty    + ' : ' + product[10] +
            '</p><p>' + L.productDetails + ': </p>' + product[11];

        if (product[18]) {
            var product_image = product[18].split(',');
            if (product_image.length > 1) {
                slidertext = '<div id="product-img-slider" class="carousel slide" data-ride="carousel"><div class="carousel-inner">';
                for (var i = 0; i < product_image.length; i++) {
                    slidertext += '<div class="carousel-item' + (!i ? ' active' : '') + '">' +
                        '<img src="images/product/' + product_image[i] + '" height="300" width="100%"></div>';
                }
                slidertext += '</div>' +
                    '<a class="carousel-control-prev" href="#product-img-slider" data-slide="prev">' +
                    '<span class="carousel-control-prev-icon" aria-hidden="true"></span><span class="sr-only">Previous</span></a>' +
                    '<a class="carousel-control-next" href="#product-img-slider" data-slide="next">' +
                    '<span class="carousel-control-next-icon" aria-hidden="true"></span><span class="sr-only">Next</span></a></div>';
            } else {
                slidertext = '<img src="images/product/' + product[18] + '" height="300" width="100%">';
            }
        } else {
            slidertext = '<img src="images/product/zummXD2dvAtI.png" height="300" width="100%">';
        }

        // Reset tables
        $('#combo-header').text('');
        $('table.item-list thead, table.item-list tbody').remove();
        $('table.product-warehouse-list thead, table.product-warehouse-list tbody').remove();
        $('.product-variant-list thead, .product-variant-list tbody').remove();
        $('.product-variant-warehouse-list thead, .product-variant-warehouse-list tbody').remove();
        $('#product-warehouse-section, #product-variant-section, #product-variant-warehouse-section').addClass('d-none');

        // Combo products
        if (product[0] === 'combo') {
            $('#combo-header').text(L.comboProducts);
            var product_list    = product[13].split(',');
            var variant_list    = product[14].split(',');
            var qty_list        = product[15].split(',');
            var price_list      = product[16].split(',');
            var combo_unit      = product[20].split(',');
            var wastage_percent = product[21].split(',');

            var newHead = $('<thead>'), newBody = $('<tbody>');
            newHead.append($('<tr>').append(
                '<th>' + L.product + '</th><th>' + L.wastagePercent + '</th><th>' + L.quantity + '</th><th>' + L.price + '</th>'
            ));
            $(product_list).each(function (i) {
                if (!variant_list[i]) variant_list[i] = 0;
                $.get(PRODUCT_CONFIG.urls.productGetData + '/' + product_list[i] + '/' + variant_list[i], function (data) {
                    var newRow = $('<tr>');
                    newRow.append(
                        '<td>' + data['name'] + ' [' + data['code'] + ']</td>' +
                        '<td>' + wastage_percent[i] + '</td>' +
                        '<td>' + qty_list[i] + '(' + combo_unit[i] + ')</td>' +
                        '<td>' + price_list[i] + '</td>'
                    );
                    newBody.append(newRow);
                });
            });
            $('table.item-list').append(newHead).append(newBody);
        }

        // Variants
        if (product[0] === 'standard' || product[0] === 'combo') {
            if (product[19]) {
                $.get(PRODUCT_CONFIG.urls.variantData + '/' + product[12], function (variantData) {
                    var newHead = $('<thead>'), newBody = $('<tbody>');
                    newHead.append($('<tr>').append(
                        '<th>' + L.variant + '</th><th>' + L.itemCode + '</th><th>' + L.additionalCost + '</th><th>' + L.additionalPrice + '</th><th>' + L.qty + '</th>'
                    ));
                    $.each(variantData, function (i) {
                        var row = $('<tr>');
                        row.append(
                            '<td>' + variantData[i]['name'] + '</td>' +
                            '<td>' + variantData[i]['item_code'] + '</td>' +
                            '<td>' + (variantData[i]['additional_cost']  || 0) + '</td>' +
                            '<td>' + (variantData[i]['additional_price'] || 0) + '</td>' +
                            '<td>' + variantData[i]['qty'] + '</td>'
                        );
                        newBody.append(row);
                    });
                    $('table.product-variant-list').append(newHead).append(newBody);
                });
                $('#product-variant-section').removeClass('d-none');
            }

            // Warehouse quantities (admin/owner only)
            if (PRODUCT_CONFIG.roleId <= 2) {
                $.get(PRODUCT_CONFIG.urls.productWarehouse + '/' + product[12], function (data) {
                    if (data.product_warehouse[0].length !== 0) {
                        warehouse     = data.product_warehouse[0];
                        qty           = data.product_warehouse[1];
                        var batch        = data.product_warehouse[2];
                        var expired_date = data.product_warehouse[3];
                        var imei_numbers = data.product_warehouse[4];

                        var newHead = $('<thead>'), newBody = $('<tbody>');
                        newHead.append($('<tr>').append(
                            '<th>' + L.warehouse + '</th><th>' + L.batchNo + '</th><th>' + L.expiredDate + '</th><th>' + L.quantity + '</th><th>' + L.imeiNumbers + '</th>'
                        ));
                        $.each(warehouse, function (index) {
                            var imeiCell = (imei_numbers.length <= index)
                                ? 'N/A'
                                : imei_numbers[index].split(',').join(',<br/>');
                            var row = $('<tr>');
                            row.append(
                                '<td>' + warehouse[index] + '</td>' +
                                '<td>' + batch[index] + '</td>' +
                                '<td>' + expired_date[index] + '</td>' +
                                '<td>' + qty[index] + '</td>' +
                                '<td style="max-height:100px;overflow-y:auto;word-break:break-word;white-space:normal;display:block;padding-right:10px;">' + imeiCell + '</td>'
                            );
                            newBody.append(row);
                            $('table.product-warehouse-list').append(newHead).append(newBody);
                        });
                        $('#product-warehouse-section').removeClass('d-none');
                    }

                    if (data.product_variant_warehouse[0].length !== 0) {
                        warehouse = data.product_variant_warehouse[0];
                        variant   = data.product_variant_warehouse[1];
                        qty       = data.product_variant_warehouse[2];

                        var newHead = $('<thead>'), newBody = $('<tbody>');
                        newHead.append($('<tr>').append(
                            '<th>' + L.warehouse + '</th><th>' + L.variant + '</th><th>' + L.quantity + '</th>'
                        ));
                        $.each(warehouse, function (index) {
                            var row = $('<tr>');
                            row.append(
                                '<td>' + warehouse[index] + '</td>' +
                                '<td>' + variant[index]   + '</td>' +
                                '<td>' + qty[index]       + '</td>'
                            );
                            newBody.append(row);
                            $('table.product-variant-warehouse-list').append(newHead).append(newBody);
                        });
                        $('#product-variant-warehouse-section').removeClass('d-none');
                    }
                });
            }
        }

        $('#product-content').html(htmltext);
        $('#slider-content').html(slidertext);
        $('#product-details').modal('show');
        $('#product-img-slider').carousel(0);
    }

    /* ── Toggle filter card ──────────────────────────────────────────── */
    $('#toggle-filter').on('click', function () {
        $('#filter-card').slideToggle('slow');
    });

    /* ── DataTable export / delete buttons ───────────────────────────── */
    var buttons = [];

    if (PRODUCT_CONFIG.canExport) {
        buttons.push([
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible', stripHtml: false },
                customize: function (doc) {
                    for (var i = 1; i < doc.content[1].table.body.length; i++) {
                        if (doc.content[1].table.body[i][0].text.indexOf('<img src=') !== -1) {
                            var imagehtml = doc.content[1].table.body[i][0].text;
                            var src       = /<img.*?src=['"](.*?)['"]/.exec(imagehtml)[1];
                            var tempImage = new Image();
                            tempImage.src = src;
                            var canvas    = document.createElement('canvas');
                            canvas.width  = tempImage.width;
                            canvas.height = tempImage.height;
                            canvas.getContext('2d').drawImage(tempImage, 0, 0);
                            var imagedata = canvas.toDataURL('image/png');
                            delete doc.content[1].table.body[i][0].text;
                            doc.content[1].table.body[i][0].image = imagedata;
                            doc.content[1].table.body[i][0].fit   = [30, 30];
                        }
                    }
                }
            },
            {
                extend: 'excelHtml5',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: {
                    columns: ':not(.not-exported)',
                    format: { body: function (data, row, column) {
                        if (column === 0) { var $c = $('<div>').html(data); $c.find('img').remove(); data = $.trim($c.text()); }
                        return data;
                    }}
                }
            },
            {
                extend: 'csvHtml5',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: {
                    columns: ':not(.not-exported)',
                    format: { body: function (data, row, column) {
                        if (column === 0) { var $c = $('<div>').html(data); $c.find('img').remove(); data = $.trim($c.text()); }
                        return data;
                    }}
                }
            },
            {
                extend: 'print',
                title: '',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible', stripHtml: false },
                repeatingHead: {
                    logo: PRODUCT_CONFIG.logoUrl,
                    logoPosition: 'left',
                    logoStyle: '',
                    title: '<h3>' + PRODUCT_CONFIG.labels.productList + '</h3>'
                }
            }
        ]);
    }

    if (PRODUCT_CONFIG.canDelete) {
        buttons.push([{
            text: '<i title="delete" class="dripicons-cross"></i>',
            className: 'buttons-delete',
            action: function (e, dt) {
                if (PRODUCT_CONFIG.userVerified === '1') {
                    product_id.length = 0;
                    $(':checkbox:checked').each(function (i) {
                        if (i) {
                            var product_data = $(this).closest('tr').data('product');
                            if (product_data) product_id[i - 1] = product_data[12];
                        }
                    });
                    if (product_id.length && confirmDelete()) {
                        $.ajax({
                            type: 'POST',
                            url: PRODUCT_CONFIG.urls.deleteBySelect,
                            data: { productIdArray: product_id },
                            success: function (data) {
                                alert(data);
                                dt.rows({ page: 'current', selected: true }).remove().draw(false);
                            }
                        });
                    } else if (!product_id.length) {
                        alert('No product is selected!');
                    }
                } else {
                    alert('This feature is disable for demo!');
                }
            }
        }]);
    }

    buttons.push([{
        extend: 'colvis',
        text: '<i title="column visibility" class="fa fa-eye"></i>',
        columns: ':gt(0)'
    }]);

    /* ── DataTable init ──────────────────────────────────────────────── */
    $(document).ready(function () {
        var table = $('#product-data-table').DataTable({
            responsive: false,
            fixedHeader: { header: true, footer: true },
            processing: true,
            serverSide: true,
            ajax: {
                url: PRODUCT_CONFIG.urls.productData,
                type: 'post',
                data: function (d) {
                    d.all_permission = PRODUCT_CONFIG.allPermission;
                    d.warehouse_id   = $('#warehouse_id').val();
                    d.product_type   = $('#product_type').val();
                    d.brand_id       = $('#brand_id').val();
                    d.category_id    = $('#category_id').val();
                    d.unit_id        = $('#unit_id').val();
                    d.tax_id         = $('#tax_id').val();
                    d.imeiorvariant  = $('#imeiorvariant').val();
                    d.stock_filter   = $('#stock_filter').val();
                }
            },
            createdRow: function (row, data) {
                $(row).addClass('product-link')
                      .attr('data-product',   data['product'])
                      .attr('data-imagedata', data['imagedata']);
            },
            columns: columns,
            language: {
                lengthMenu : '_MENU_ ' + PRODUCT_CONFIG.labels.recordsPerPage,
                info       : '<small>' + PRODUCT_CONFIG.labels.showing + ' _START_ - _END_ (_TOTAL_)</small>',
                search     : PRODUCT_CONFIG.labels.search,
                paginate   : {
                    previous: '<i class="dripicons-chevron-left"></i>',
                    next    : '<i class="dripicons-chevron-right"></i>'
                }
            },
            order: [['1', 'asc']],
            columnDefs: [
                {
                    orderable: false,
                    targets: PRODUCT_CONFIG.roleId <= 2 ? [0, 11] : [0, 9]
                },
                {
                    targets: 2,
                    visible: false,
                    className: 'export-image-path'
                },
                {
                    render: function (data, type) {
                        if (type === 'display') {
                            data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                        }
                        return data;
                    },
                    checkboxes: {
                        selectRow      : true,
                        selectAllRender: '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>'
                    },
                    targets: [0]
                }
            ],
            select    : { style: 'multi', selector: 'td:first-child' },
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            dom       : '<"row"lfB>rtip',
            buttons   : buttons.flat(),
        });

        /* Filter change → reload */
        $('#warehouse_id, #product_type, #brand_id, #category_id, #unit_id, #tax_id, #imeiorvariant, #stock_filter')
            .on('change', function () { table.ajax.reload(); });

        /* Loading indicator */
        table.on('preXhr.dt', function () { $('#filter-loading').show(); });
        table.on('xhr.dt',    function () { $('#filter-loading').hide(); });

        $('.selectpicker').selectpicker('refresh');
    });

})();
