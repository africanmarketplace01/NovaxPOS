/* ================================================================
 * POS Keyboard Shortcuts
 * ================================================================ */


    const POS_SHORTCUTS = {
        focus_customer: {
            keys: 'shift+c',
            action: () => {
                $('#customer_id').selectpicker('toggle');
            }
        },
        draft_sale: {
            keys: 'shift+d',
            action: () => $('#draft-btn').click()
        },
        discount: {
            keys: 'shift+e',
            action: () => {
                $('#order-discount-modal').modal('show');
            }
        },
        add_payment: {
            keys: 'shift+f',
            action: () => $('#cash-btn').click()
        },
        coupon_modal: {
            keys: 'shift+k',
            action: () => {
                $('#coupon-modal').modal('show');
            }
        },
        print_last_receipt: {
            keys: 'shift+p',
            action: () => $('#print-last-receipt').click()
        },
        shipping_cost: {
            keys: 'shift+q',
            action: () =>  {
                $('#shipping-cost-modal').modal('show');
            }
        },
        cash_register_details: {
            keys: 'shift+r',
            action: () => $('#register-details-btn').click()
        },
        focus_search: {
            keys: 'shift+s',
            action: () => $('#product-search-input').focus()
        },
        order_tax: {
            keys: 'shift+x',
            action: () => {
                $('#order-tax').modal('show');
            }
        },

    ...(POS_CONFIG.isRestaurant ? {
    restaurant_table: {
        keys: 'shift+t',
        action: () => { $('#table_id').selectpicker('toggle'); }
    },
    focus_waiter: {
        keys: 'shift+w',
        action: () => { $('#waiter_id').selectpicker('toggle'); }
    }
    } : {}),
    };

    Object.values(POS_SHORTCUTS).forEach(shortcut => {
        Mousetrap.bind(shortcut.keys, function(e) {
            e.preventDefault();
            shortcut.action();
            return false;
        });
    });

    Mousetrap.stopCallback = function (e, element) {

        // Allow Enter key for barcode scanner
        if (e.key === "Enter") return false;

        // Disable shortcuts inside input fields
        return element.tagName === 'INPUT' ||
            element.tagName === 'TEXTAREA' ||
            element.tagName === 'SELECT' ||
            (element.contentEditable && element.contentEditable === 'true');
    };
