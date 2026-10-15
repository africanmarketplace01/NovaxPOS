/* ================================================================
 * Purchase Index — Main JavaScript
 * All PHP values referenced via window.PURCHASE_CONFIG (see
 * resources/views/backend/purchase/partials/scripts/config.blade.php)
 * ================================================================ */

(function () {

    var C = window.PURCHASE_CONFIG;
    var L = C.labels;

    /* ── CSRF setup ──────────────────────────────────────────────────── */
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    /* ── Sidebar active state ────────────────────────────────────────── */
    $('ul#purchase').siblings('a').attr('aria-expanded', 'true');
    $('ul#purchase').addClass('show');
    $('ul#purchase #purchase-list-menu').addClass('active');

    /* ── Date pickers ────────────────────────────────────────────────── */
    $(function () {
        $('#payment_at').datepicker({
            format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true
        }).datepicker('setDate', new Date());

        $('#edit_payment_at').datepicker({
            format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true
        });
    });

    /* ── Filter toggle ───────────────────────────────────────────────── */
    $('#toggle-filter').on('click', function () {
        $('#filter-card').slideToggle('slow');
    });

    /* ── Restore filter values ───────────────────────────────────────── */
    $('#warehouse_id').val(C.warehouseId);
    $('#purchase-status').val(C.purchaseStatus);
    $('#payment-status').val(C.paymentStatus);
    $('.selectpicker').selectpicker('refresh');

    /* ── Confirm helpers ─────────────────────────────────────────────── */
    function confirmDelete()        { return confirm(L.confirmDelete); }
    function confirmDeletePayment() { return confirm(L.confirmDelPayment); }
    window.confirmDelete        = confirmDelete;
    window.confirmDeletePayment = confirmDeletePayment;

    /* ── DataTable columns ───────────────────────────────────────────── */
    var columns;
    if (C.showProductDetails) {
        columns = [
            { data: 'key'             },
            { data: 'date'            },
            { data: 'reference_no'    },
            { data: 'created_by'      },
            { data: 'supplier'        },
            { data: 'products'        },
            { data: 'products_qty'    },
            { data: 'purchase_status' },
            { data: 'grand_total'     },
            { data: 'returned_amount' },
            { data: 'paid_amount'     },
            { data: 'due'             },
            { data: 'currency'        },
            { data: 'pay_term'        },
            { data: 'due_date'        },
            { data: 'payment_status'  },
        ];
    } else {
        columns = [
            { data: 'key'             },
            { data: 'date'            },
            { data: 'reference_no'    },
            { data: 'created_by'      },
            { data: 'supplier'        },
            { data: 'purchase_status' },
            { data: 'grand_total'     },
            { data: 'returned_amount' },
            { data: 'paid_amount'     },
            { data: 'due'             },
            { data: 'currency'        },
            { data: 'pay_term'        },
            { data: 'due_date'        },
            { data: 'payment_status'  },
        ];
    }

    C.fieldName.forEach(function (f) { columns.push({ data: f }); });
    columns.push({ data: 'options' });

    /* ── Non-orderable column targets ────────────────────────────────── */
    var targets = C.showProductDetails
        ? [0, 3, 4, 5, 6, 7, 9, 11, 12, 13, 14]
        : [0, 3, 4, 5, 7, 9, 10, 11, 12];

    /* ── Export / action buttons ─────────────────────────────────────── */
    var buttons = [];

    if (C.canExport) {
        buttons.push([
            {
                extend: 'pdf',
                text: '<i title="export to pdf" class="fa fa-file-pdf-o"></i>',
                exportOptions: { columns: ':visible:Not(.not-exported)', rows: ':visible' },
                action: function (e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer: true,
            },
            {
                extend: 'excel',
                text: '<i title="export to excel" class="dripicons-document-new"></i>',
                exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible' },
                action: function (e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer: true,
            },
            {
                extend: 'csv',
                text: '<i title="export to csv" class="fa fa-file-text-o"></i>',
                exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible' },
                action: function (e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer: true,
            },
            {
                extend: 'print',
                text: '<i title="print" class="fa fa-print"></i>',
                exportOptions: { columns: ':visible:not(.not-exported)', rows: ':visible' },
                action: function (e, dt, button, config) {
                    datatable_sum(dt, true);
                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    datatable_sum(dt, false);
                },
                footer: true,
            },
        ]);
    }

    buttons.push([
        {
            text: '<i title="delete" class="dripicons-cross"></i>',
            className: 'buttons-delete',
            action: function (e, dt) {
                if (C.userVerified !== '1') { alert(L.demoDisabled); return; }

                var purchase_ids = [];
                $(':checkbox:checked').each(function (i) {
                    if (i) {
                        var purchase = $(this).closest('tr').data('purchase');
                        if (purchase) purchase_ids[i - 1] = purchase[3];
                    }
                });

                if (!purchase_ids.length) { alert(L.nothingSelected); return; }
                if (!confirm(L.confirmDelete)) return;

                $.ajax({
                    type: 'POST',
                    url: C.urls.deleteBySelect,
                    data: { purchaseIdArray: purchase_ids },
                    success: function (res) {
                        if (!res || !Array.isArray(res.deleted)) {
                            alert(res.message || 'Unexpected server response');
                            return;
                        }
                        res.deleted.forEach(function (id) {
                            var row = $('#purchase-table tbody tr[data-id="' + id + '"]');
                            if (row.length) dt.row(row).remove();
                        });
                        dt.draw(false);
                        alert(res.message || 'Deleted');
                    },
                    error: function (xhr) {
                        var ct = xhr.getResponseHeader('Content-Type') || '';
                        if (ct.indexOf('text/html') !== -1) {
                            alert(L.sessionExpired);
                            window.location = C.urls.login;
                            return;
                        }
                        var json = null;
                        try { json = xhr.responseJSON; } catch (ex) {}
                        alert((json && json.message) ? json.message : 'Delete failed: ' + xhr.status);
                    },
                });
            },
        },
        {
            extend: 'colvis',
            text: '<i title="column visibility" class="fa fa-eye"></i>',
            columns: ':gt(0)',
        },
    ]);

    /* ── DataTable init ──────────────────────────────────────────────── */
    var purchaseTable = $('#purchase-table').DataTable({
        processing : true,
        serverSide : true,
        ajax: {
            url  : C.urls.purchaseData,
            type : 'post',
            data : function (d) {
                d.all_permission  = C.allPermission;
                d.starting_date   = $('input[name=starting_date]').val();
                d.ending_date     = $('input[name=ending_date]').val();
                d.warehouse_id    = $('#warehouse_id').val();
                d.purchase_status = $('#purchase-status').val();
                d.payment_status  = $('#payment-status').val();
            },
        },
        createdRow: function (row, data) {
            $(row).addClass('purchase-link');
            $(row).attr('data-purchase', data['purchase']);
        },
        columns     : columns,
        order       : [['1', 'desc']],
        lengthMenu  : [[10, 25, 50, -1], [10, 25, 50, 'All']],
        dom         : '<"row"lfB>rtip',
        buttons     : buttons,
        language: {
            lengthMenu : '_MENU_ ' + L.recordsPerPage,
            info       : '<small>' + L.showing + ' _START_ - _END_ (_TOTAL_)</small>',
            search     : L.search,
            paginate   : {
                previous: '<i class="dripicons-chevron-left"></i>',
                next    : '<i class="dripicons-chevron-right"></i>',
            },
        },
        columnDefs: [
            { orderable: false, targets: targets },
            {
                targets : [0],
                render  : function (data, type) {
                    if (type === 'display') {
                        data = '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>';
                    }
                    return data;
                },
                checkboxes: {
                    selectRow     : true,
                    selectAllRender: '<div class="checkbox"><input type="checkbox" class="dt-checkboxes"><label></label></div>',
                },
            },
        ],
        select      : { style: 'multi', selector: 'td:first-child' },
        drawCallback: function () { datatable_sum(this.api(), false); },
    });

    if (!C.canDelete) { $('.buttons-delete').addClass('d-none'); }

    /* ── Column-sum footer ───────────────────────────────────────────── */
    function datatable_sum(dt, selectedOnly) {
        var dec = C.decimal;

        function colSum(col) {
            if (selectedOnly && dt.rows('.selected').any()) {
                var rows = dt.rows('.selected').indexes();
                return dt.cells(rows, col, { page: 'current' }).data().sum().toFixed(dec);
            }
            return dt.column(col, { page: 'current' }).data().sum().toFixed(dec);
        }

        if (C.showProductDetails) {
            $(dt.column(8).footer()).html(colSum(8));
            $(dt.column(9).footer()).html(colSum(9));
            $(dt.column(10).footer()).html(colSum(10));
            $(dt.column(11).footer()).html(colSum(11));
        } else {
            $(dt.column(6).footer()).html(colSum(6));
            $(dt.column(7).footer()).html(colSum(7));
            $(dt.column(8).footer()).html(colSum(8));
            $(dt.column(9).footer()).html(colSum(9));
        }
    }

    /* ── Filter change → reload ──────────────────────────────────────── */
    $('#warehouse_id, #purchase-status, #payment-status').on('change', function () {
        purchaseTable.ajax.reload();
    });

    $('.daterangepicker-field').on('apply.daterangepicker', function (ev, picker) {
        $('input[name=starting_date]').val(picker.startDate.format('YYYY-MM-DD'));
        $('input[name=ending_date]').val(picker.endDate.format('YYYY-MM-DD'));
        purchaseTable.ajax.reload();
    });

    purchaseTable.on('preXhr.dt', function () { $('#filter-loading').show(); });
    purchaseTable.on('xhr.dt',    function () { $('#filter-loading').hide(); });

    /* ── Purchase details modal ──────────────────────────────────────── */
    var currentPurchase;

    function purchaseDetails(purchase) {
        currentPurchase = purchase;

        var html = L.date + ': ' + purchase[0]
            + '<br>' + L.reference + ': ' + purchase[1]
            + '<br>' + L.purchaseStatus + ': ' + purchase[2]
            + '<br>' + L.currency + ': ' + purchase[26];

        html += purchase[27]
            ? '<br>' + L.exchangeRate + ': ' + purchase[27] + '<br>'
            : '<br>' + L.exchangeRate + ': N/A<br>';

        if (purchase[25])
            html += L.attachDocument + ': <a href="' + C.urls.documents + '/purchase/' + purchase[25] + '">Download</a><br>';
        if (purchase[28])
            html += L.paymentTerm + ': ' + purchase[28] + ' ' + purchase[29] + '<br>';
        if (purchase[30])
            html += L.dueDate + ': ' + purchase[30] + '<br>';

        html += '<br><div class="row">'
            + '<div class="col-md-6">' + L.from + ':<br>'
            + purchase[7] + '<br>' + purchase[8] + '<br>' + purchase[9]
            + '<br>' + purchase[10] + '<br>' + purchase[11] + '<br>' + purchase[12]
            + '</div>'
            + '<div class="col-md-6"><div class="float-right">' + L.to + ':<br>'
            + purchase[4] + '<br>' + purchase[5] + '<br>' + purchase[6]
            + '</div></div></div>';

        $('#purchase-content').html(html);
        $('#purchase-footer').html(
            '<p>' + L.note + ': ' + purchase[22] + '</p>'
            + L.createdBy + ':<br>' + purchase[23] + '<br>' + purchase[24]
        );

        $('table.product-purchase-list tbody').remove();

        $.get(C.urls.productPurchase + '/' + purchase[3], function (data) {
            if (data === 'Something is wrong!') {
                var newBody = $('<tbody>');
                newBody.append($('<tr>').append('<td colspan="8">' + L.somethingWrong + '</td>'));
                $('table.product-purchase-list').append(newBody);
                return;
            }

            var name_code = data[0], qty = data[1], unit_code = data[2],
                tax = data[3], tax_rate = data[4], discount = data[5],
                subtotal = data[6], batch_no = data[7], returned = data[8];

            var newBody = $('<tbody>');
            $.each(name_code, function (i) {
                var cols = '<td>' + (i + 1) + '</td>'
                    + '<td>' + name_code[i] + '</td>'
                    + '<td>' + batch_no[i] + '</td>'
                    + '<td>' + qty[i] + ' ' + unit_code[i] + '</td>'
                    + '<td>' + returned[i] + '</td>'
                    + '<td>' + parseFloat(subtotal[i] / qty[i]).toFixed(C.decimal) + '</td>'
                    + '<td>' + tax[i] + '(' + tax_rate[i] + '%)</td>'
                    + '<td>' + discount[i] + '</td>'
                    + '<td>' + subtotal[i] + '</td>';
                newBody.append($('<tr>').append(cols));
            });

            // Totals rows
            newBody.append($('<tr>').append(
                '<td colspan="6">' + L.total + ':</td>'
                + '<td>' + purchase[13] + '</td>'
                + '<td>' + purchase[14] + '</td>'
                + '<td>' + purchase[15] + '</td>'
            ));
            newBody.append($('<tr>').append(
                '<td colspan="8">' + L.orderTax + ':</td>'
                + '<td>' + purchase[16] + '(' + purchase[17] + '%)</td>'
            ));
            newBody.append($('<tr>').append(
                '<td colspan="8">' + L.orderDiscount + ':</td><td>' + purchase[18] + '</td>'
            ));
            newBody.append($('<tr>').append(
                '<td colspan="8">' + L.shippingCost + ':</td><td>' + purchase[19] + '</td>'
            ));
            newBody.append($('<tr>').append(
                '<td colspan="8">' + L.grandTotal + ':</td><td>' + purchase[20] + '</td>'
            ));
            newBody.append($('<tr>').append(
                '<td colspan="8">' + L.paidAmount + ':</td><td>' + purchase[21] + '</td>'
            ));
            if (purchase[31] != 0) {
                newBody.append($('<tr>').append(
                    '<td colspan="8">' + L.returnAmount + ':</td><td>' + purchase[31] + '</td>'
                ));
            }
            newBody.append($('<tr>').append(
                '<td colspan="8">' + L.due + ':</td>'
                + '<td>' + (purchase[20] - purchase[31] - purchase[21]) + '</td>'
            ));

            $('table.product-purchase-list').append(newBody);
        });

        $('#purchase-details').modal('show');
    }

    $(document).on('click', 'tr.purchase-link td:not(:first-child, :last-child)', function () {
        purchaseDetails($(this).parent().data('purchase'));
    });

    $(document).on('click', '.view', function () {
        purchaseDetails(
            $(this).parent().parent().parent().parent().parent().data('purchase')
        );
    });

    /* ── Print ───────────────────────────────────────────────────────── */
    $('#print-btn').on('click', function () {
        var purchase = currentPurchase;
        var a = window.open('');
        a.document.write('<html><head>');
        a.document.write('<style>');
        a.document.write('body{font-family:sans-serif;line-height:1.5;font-size:13px;}');
        a.document.write('h3{text-align:center;margin-bottom:2px;}');
        a.document.write('table{width:100%;border-collapse:collapse;margin-top:15px;}');
        a.document.write('th,td{border:1px solid #000;padding:7px;text-align:left;}');
        a.document.write('.no-border td{border:none;padding:0;vertical-align:top;}');
        a.document.write('</style></head><body>');

        a.document.write('<h3>' + C.siteTitle + '</h3>');
        a.document.write('<p style="text-align:center"><i>' + L.purchaseDetails + '</i></p>');

        a.document.write('<table class="no-border" style="margin-top:5px;"><tr>');

        // Left column
        a.document.write('<td style="width:50%;padding-left:0;vertical-align:top;">');
        a.document.write('<span>' + L.date      + ':</span> ' + purchase[0] + '<br>');
        a.document.write('<span>' + L.reference + ':</span> ' + purchase[1] + '<br>');
        a.document.write('<span>' + L.purchaseStatus + ':</span> ' + purchase[2] + '<br>');
        a.document.write('<span>' + L.currency  + ':</span> ' + purchase[26] + '<br>');
        a.document.write('<span>' + L.exchangeRate + ':</span> ' + (purchase[27] ? purchase[27] : 'N/A') + '<br>');
        if (purchase[28])
            a.document.write('<span>' + L.paymentTerm + ':</span> ' + purchase[28] + ' ' + purchase[29] + '<br>');
        if (purchase[30])
            a.document.write('<span>' + L.dueDate + ':</span> ' + purchase[30] + '<br>');
        if (purchase[25])
            a.document.write('<span>' + L.attachDocument + ':</span> ' + purchase[25] + '<br>');
        a.document.write('</td>');

        // Right column
        a.document.write('<td style="width:50%;padding-right:0;vertical-align:top;text-align:right;">');
        a.document.write('<strong>' + L.from + ':</strong><br>');
        [7, 8, 9, 10, 11, 12].forEach(function (i) {
            a.document.write((purchase[i] ? purchase[i] : '') + '<br>');
        });
        a.document.write('</td>');
        a.document.write('</tr></table>');

        a.document.write('<p style="margin:5px 5px 10px 0;">');
        a.document.write('<strong>' + L.to + ':</strong> ');
        a.document.write((purchase[4] ? purchase[4] : '') + ' &nbsp;|&nbsp; ');
        a.document.write((purchase[5] ? purchase[5] : '') + ' &nbsp;|&nbsp; ');
        a.document.write((purchase[6] ? purchase[6] : ''));
        a.document.write('</p>');

        a.document.write(document.querySelector('table.product-purchase-list').outerHTML);
        a.document.write('<br><p><strong>' + L.note + ':</strong> ' + purchase[22] + '</p>');
        a.document.write('<p><strong>' + L.createdBy + ':</strong> ' + purchase[23] + ' | ' + purchase[24] + '</p>');
        a.document.write('</body></html>');
        a.document.close();
        a.focus();
        setTimeout(function () { a.print(); a.close(); }, 500);
    });

    /* ── Add payment modal ───────────────────────────────────────────── */
    $(document).on('click', 'table.purchase-list tbody .add-payment', function () {
        $('#cheque').hide();
        $('.card-element').hide();
        $('select[name="paid_by_id"]').val(1);

        var purchase_id   = String($(this).data('id'));
        var balance       = parseFloat(String($(this).data('due')).replace(/,/g, '') * $(this).data('exchange_rate')) || 0;
        var currency_name = $(this).data('currency_name');
        var currency_id   = $(this).data('currency_id');
        var exchange_rate = parseFloat($(this).data('exchange_rate')) || 1;

        $('input[name="amount"]').val(balance);
        $('input[name="balance"]').val(balance);
        $('input[name="paying_amount"]').val(balance);
        $('input[name="purchase_id"]').val(purchase_id);

        $('#currency_display').text(currency_name);
        $('#exchange_rate_display').text(exchange_rate.toFixed(2));
        $('#currency_id').val(currency_id);
        $('#exchange_rate').val(exchange_rate);
    });

    /* ── View payments modal ─────────────────────────────────────────── */
    $(document).on('click', 'table.purchase-list tbody .get-payment', function () {
        var id = String($(this).data('id'));

        $.get(C.urls.getPayment + '/' + id, function (data) {
            var payment_date = data[0], payment_reference = data[1],
                paid_amount  = data[2], paying_method     = data[3],
                payment_id   = data[4], payment_note      = data[5],
                cheque_no    = data[6], change            = data[7],
                paying_amount= data[8], account_name      = data[9],
                account_id   = data[10],payment_at        = data[11],
                payment_document = data[12];

            $('table.payment-list tbody').remove();
            var newBody = $('<tbody>');

            $.each(payment_date, function (i) {
                var cols = '<td>' + payment_date[i]      + '</td>'
                    + '<td>' + payment_reference[i] + '</td>'
                    + '<td>' + account_name[i]      + '</td>'
                    + '<td>' + paid_amount[i]        + '</td>'
                    + '<td>' + paying_method[i]      + '</td>'
                    + '<td>' + payment_at[i]         + '</td>'
                    + '<td><div class="btn-group">'
                    + '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                    + 'Action<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>'
                    + '</button>'
                    + '<ul class="dropdown-menu edit-options dropdown-menu-right dropdown-default" user="menu">';

                if (payment_document[i])
                    cols += '<li><a href="' + C.urls.documents + '/add-payment/' + payment_document[i]
                        + '" target="_blank" class="btn btn-link"><i class="fa fa-file"></i> ' + L.viewDocument + '</a></li>'
                        + '<li class="divider"></li>';

                if (C.canPayEdit)
                    cols += '<li><button type="button" class="btn btn-link edit-btn"'
                        + ' data-id="' + payment_id[i] + '" data-clicked=false'
                        + ' data-toggle="modal" data-target="#edit-payment">'
                        + '<i class="dripicons-document-edit"></i> ' + L.edit + '</button></li>'
                        + '<li class="divider"></li>';

                if (C.canPayDelete)
                    cols += '<form action="' + C.urls.deletePayment + '" method="POST">'
                        + '<input type="hidden" name="_token" value="' + C.csrfToken + '">'
                        + '<li><input type="hidden" name="id" value="' + payment_id[i] + '" />'
                        + ' <button type="submit" class="btn btn-link"'
                        + ' onclick="return confirmDeletePayment()">'
                        + '<i class="dripicons-trash"></i> ' + L.delete + '</button></li></form>';

                cols += '</ul></div></td>';
                newBody.append($('<tr>').append(cols));
            });

            $('table.payment-list').append(newBody);
            $('#view-payment').modal('show');
        });
    });

    /* ── Edit payment modal ──────────────────────────────────────────── */
    $(document).on('click', 'table.payment-list .edit-btn', function () {
        $('.edit-btn').attr('data-clicked', true);
        $('.card-element').hide();
        $('#edit-cheque').hide();
        $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', false);

        var id = String($(this).data('id'));

        // Re-fetch current payment state from .get-payment response (stored in outer scope via closure)
        // We delegate to the get-payment handler's data; use a data attribute on the row instead.
        // Find matching payment_id from the last-fetched data:
        $('#edit-payment').data('target-id', id);
        $('#view-payment').modal('hide');
    });

    // When edit-payment modal opens, populate from stored payment list data
    $('#edit-payment').on('show.bs.modal', function () {
        // Delegate: populate was done inline via edit-btn handler below
    });

    $(document).on('click', 'table.payment-list .edit-btn', function () {
        var id = String($(this).data('id'));

        // Rebuild from view-payment data stored in DOM via data attributes on rows
        var $row = $(this).closest('tr');
        // The edit modal is populated from the original AJAX data; re-fetch it
        $.get(C.urls.getPayment + '/' + $('input[name="purchase_id"]').val(), function (data) {
            var payment_id  = data[4], cheque_no    = data[6],
                change      = data[7], paying_amount= data[8],
                account_id  = data[10],payment_at   = data[11],
                paid_amount = data[2], paying_method = data[3],
                payment_note = data[5];

            $.each(payment_id, function (i) {
                if (String(payment_id[i]) !== id) return;

                $('input[name="payment_id"]').val(payment_id[i]);
                $('#edit-payment select[name="account_id"]').val(account_id[i]);
                $('input[name="edit_amount"]').val(paid_amount[i]);
                $('input[name="edit_paying_amount"]').val(paying_amount[i]);
                $('.change').last().text(change[i]);
                $('textarea[name="edit_payment_note"]').val(payment_note[i]);
                $('input[name="payment_at"]').last().val(payment_at[i]);

                $('.card-element').hide();
                $('#edit-cheque').hide();
                $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', false);

                if (paying_method[i] === 'Cash') {
                    $('select[name="edit_paid_by_id"]').val(1);
                } else if (paying_method[i] === 'Credit Card') {
                    $('select[name="edit_paid_by_id"]').val(3);
                    if (C.hasStripe) {
                        $.getScript('vendor/stripe/checkout.js');
                        $('.card-element').show();
                    }
                    $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', true);
                } else {
                    $('select[name="edit_paid_by_id"]').val(4);
                    $('#edit-cheque').show();
                    $('input[name="edit_cheque_no"]').val(cheque_no[i]).attr('required', true);
                }

                $('.selectpicker').selectpicker('refresh');
                return false; // break $.each
            });
        });
    });

    /* ── Payment method toggles (Add modal) ──────────────────────────── */
    $('select[name="paid_by_id"]').on('change', function () {
        var id = $(this).val();
        $('input[name="cheque_no"]').attr('required', false);
        $('.payment-form').off('submit');
        if (id == 3) {
            $.getScript('vendor/stripe/checkout.js');
            $('.card-element').show();
            $('#cheque').hide();
        } else if (id == 4) {
            $('#cheque').show();
            $('.card-element').hide();
            $('input[name="cheque_no"]').attr('required', true);
        } else {
            $('.card-element').hide();
            $('#cheque').hide();
        }
    });

    /* ── Payment method toggles (Edit modal) ─────────────────────────── */
    $('select[name="edit_paid_by_id"]').on('change', function () {
        var id = $(this).val();
        $('input[name="edit_cheque_no"]').attr('required', false);
        $('.payment-form').off('submit');
        if (id == 3) {
            $('.edit-btn').attr('data-clicked', true);
            $.getScript('vendor/stripe/checkout.js');
            $('.card-element').show();
            $('#edit-cheque').hide();
        } else if (id == 4) {
            $('#edit-cheque').show();
            $('.card-element').hide();
            $('input[name="edit_cheque_no"]').attr('required', true);
        } else {
            $('.card-element').hide();
            $('#edit-cheque').hide();
        }
    });

    /* ── Amount change calculations ──────────────────────────────────── */
    var dec = C.decimal;

    $('input[name="paying_amount"]').on('input', function () {
        $('.change').first().text(
            parseFloat($(this).val() - $('input[name="amount"]').val()).toFixed(dec)
        );
    });

    $('input[name="amount"]').on('input', function () {
        var val     = parseFloat($(this).val());
        var paying  = parseFloat($('input[name="paying_amount"]').val());
        var balance = parseFloat($('input[name="balance"]').val());
        if (val > paying)  { alert(L.payingAmtBigger); $(this).val(''); return; }
        if (val > balance) { alert(L.payingAmtDue);    $(this).val(''); return; }
        $('.change').first().text(parseFloat(paying - val).toFixed(dec));
    });

    $('input[name="edit_amount"]').on('input', function () {
        var val    = parseFloat($(this).val());
        var paying = parseFloat($('input[name="edit_paying_amount"]').val());
        if (val > paying) { alert(L.payingAmtBigger); $(this).val(''); return; }
        $('.change').last().text(parseFloat(paying - val).toFixed(dec));
    });

    $('input[name="edit_paying_amount"]').on('input', function () {
        $('.change').last().text(
            parseFloat($(this).val() - $('input[name="edit_amount"]').val()).toFixed(dec)
        );
    });

    /* ── Payment form submit validation ──────────────────────────────── */
    $(document).on('submit', '.payment-form', function (e) {
        var paying = parseFloat($('input[name="paying_amount"]').val());
        var amount = parseFloat($('#amount').val());
        if (paying < amount) {
            alert(L.payingAmtBigger);
            $('input[name="amount"]').val('');
            $('.change').first().text(parseFloat(paying - amount).toFixed(dec));
            e.preventDefault();
            return;
        }
        var editPaying = parseFloat($('input[name="edit_paying_amount"]').val());
        var editAmount = parseFloat($('input[name="edit_amount"]').val());
        if (editPaying < editAmount) {
            alert(L.payingAmtBigger);
            $('input[name="edit_amount"]').val('');
            $('.change').last().text(parseFloat(editPaying - editAmount).toFixed(dec));
            e.preventDefault();
            return;
        }
        $('#edit-payment select[name="edit_paid_by_id"]').prop('disabled', false);
    });

}());
