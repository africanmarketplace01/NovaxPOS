{{-- ============================================================ --}}
{{-- POS CSS / Inline Styles  (SaaS-theme restyle)                --}}
{{-- ------------------------------------------------------------ --}}
{{-- Restyle only: no HTML structure or JS bindings were changed. --}}
{{-- Every accent is driven by the platform theme variables        --}}
{{--   --lp-primary / --lp-primary-light / --lp-accent /           --}}
{{--   --lp-lime / --lp-dark / --lp-cyan                           --}}
{{-- defined in backend/layout/top-head.blade.php.                 --}}
{{-- ============================================================ --}}

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.9/css/intlTelInput.css"/>
<style type="text/css">
    /* ── Local theme tokens (fall back gracefully) ─────────────── */
    :root{
        --pos-primary:      var(--lp-primary, #0066CC);
        --pos-primary-2:    var(--lp-primary-light, #00A3FF);
        --pos-accent:       var(--lp-accent, #66FF33);
        --pos-dark:         var(--lp-dark, #003366);
        --pos-cyan:         var(--lp-cyan, #00D4FF);
        --pos-ink:          #2b3445;
        --pos-muted:        #7a869a;
        --pos-line:         #e8ecf3;
        --pos-surface:      #ffffff;
        --pos-bg:           #f4f6fb;
        --pos-radius:       12px;
        --pos-radius-sm:    8px;
        --pos-shadow:       0 2px 10px rgba(15, 40, 90, .06);
        --pos-shadow-hover: 0 8px 22px rgba(15, 40, 90, .14);
        /* soft theme tints */
        --pos-tint:      color-mix(in srgb, var(--pos-primary) 7%,  #fff);
        --pos-tint-2:    color-mix(in srgb, var(--pos-primary) 12%, #fff);
        --pos-primary-soft: color-mix(in srgb, var(--pos-primary) 14%, #fff);
    }

    body{color: var(--pos-ink);font-family:'Inter',sans-serif;background:var(--pos-bg)}
    .pos-page .container-fluid {padding: 0 15px}
    .pos-page .side-navbar {top: 0}
    section.pos-section {padding: 8px 0}
    .pos-page .table-fixed {margin-bottom: 0}
    .pos-text {line-height: 1.8}
    .pos-page section header {padding: 0 0}

    .bootstrap-select-sm .btn {font-size: 13px;padding: 3px 25px 3px 10px;height: 32px !important}
    .minus,.plus {padding: .35rem .75rem}
    .numkey.qty {font-size: 13px;padding: 0 0;max-width: 50px;text-align: center}
    .sub-total{font-weight:600;}
    .pos .bootstrap-select button {padding-right: 21px !important}
    .pos .bootstrap-select.form-control:not([class*=col-]) {width: 100px}
    .pos-page .order-list .btn {padding: 2px 5px}
    .pos-page [class=row] {margin-left:-10px;margin-right:-10px}
    .pos-page [class*=col-] {padding: 0 7px}
    .pos-page #myTable [class*=col-] {padding: .5rem}

    /* ── Order / cart table ────────────────────────────────────── */
    .pos-page #myTable tr th{
        background:#f8f9fa;
        background:var(--pos-tint);
        color:var(--pos-dark);
        font-weight:600;
        border-bottom:2px solid color-mix(in srgb, var(--pos-primary) 25%, #fff);
    }
    .product-btns{margin:0 -5px}
    .edit-product{white-space: break-spaces;font-size:13px;font-weight:600;text-align:left;padding:0 0!important;color:var(--pos-ink)}
    .edit-product i{color:var(--pos-primary)}
    .product-title span{font-size:12px}
    label{font-size:13px}
    #tbody-id tr td {font-size:13px;padding: 6px 4px;border-bottom:1px solid var(--pos-line)}
    #tbody-id tr:hover td{background:var(--pos-tint)}
    table,tr,td {border-collapse: collapse;}

    /* ── Floating top fields (search, customer, warehouse …) ───── */
    .top-fields{margin-top:10px;position: relative;}
    .top-fields label {font-size:11px;margin-left:10px;padding:0 4px;position:absolute;top:-8px;z-index:9;background:var(--pos-surface);color:var(--pos-primary);font-weight:600;border-radius:4px}
    .top-fields input,.top-fields .btn{font-size:13px;height:38px}
    .top-fields input,.top-fields .form-control{border:1px solid var(--pos-line);border-radius:var(--pos-radius-sm);transition:border-color .15s, box-shadow .15s}
    .top-fields input:focus,.top-fields .form-control:focus{border-color:var(--pos-primary);box-shadow:0 0 0 3px color-mix(in srgb, var(--pos-primary) 18%, transparent);outline:none}

    /* ── Product grid cards ────────────────────────────────────── */
    .product-grid{align-items: stretch; display: flex;flex-wrap: wrap;padding: 0;margin: 0;width: 100%;}
    .product-grid > div{
        background:var(--pos-surface);
        border-radius:var(--pos-radius);
        border:1px solid var(--pos-line);
        box-shadow:var(--pos-shadow);
        transition:transform .15s ease, border-color .15s ease, box-shadow .15s ease;
        margin:5px;overflow:hidden;padding:.6rem;position:relative;
        max-width:300px;min-width:100px;vertical-align:top;
        width:calc(100%/4 - 10px);
    }
    .product-grid > div:hover{
        border-color:var(--pos-primary);
        box-shadow:var(--pos-shadow-hover);
        transform:translateY(-3px);
        cursor:pointer;
    }
    .product-grid > div p{
        color: var(--pos-ink);font-size:12px;font-weight:600;margin:10px 0 2px;min-height:34px;
        display:-webkit-box;-webkit-line-clamp:2;overflow:hidden;text-overflow:ellipsis;-webkit-box-orient:vertical;
    }
    /* price / qty line under product name */
    .product-grid > div span{font-size:12px;font-weight:600;color:var(--pos-primary)}
    .product-grid .loader{background:none;box-shadow:none;margin:0;padding:0}
    .product-img{margin-bottom:0;padding:12px 7px 2px;text-align:center}
    .category-img img,.product-img img{height:56px;max-width:100%;width:auto;object-fit:contain}

    /* ── Category / brand tiles ────────────────────────────────── */
    .category-img p,.brand-img p{color: var(--pos-dark);font-size:12px;font-weight:600;margin-top:6px}
    .brand-img,.category-img{display:flex;flex-direction:column;justify-content:center;align-items:center;
        background:var(--pos-surface);border:1px solid var(--pos-line);border-radius:var(--pos-radius);
        box-shadow:var(--pos-shadow);padding:.5rem;transition:border-color .15s, box-shadow .15s, transform .15s}
    .brand-img:hover,.category-img:hover{border-color:var(--pos-primary);box-shadow:var(--pos-shadow-hover);transform:translateY(-2px)}
    .brand-img img{max-width:70%}
    .category-img:hover p,.brand-img:hover p{color:var(--pos-primary)}

    /* ── Buttons / actions ─────────────────────────────────────── */
    .btn-custom{font-size:13px;}
    .load-more{margin-top:15px;border-radius:var(--pos-radius-sm)}
    .load-more:disabled{opacity:0.5}
    .ui-helper-hidden-accessible{display:none!important}

    /* ── Totals block ──────────────────────────────────────────── */
    .totals strong {font-size:14px;line-height: 2;color:var(--pos-dark)}
    .totals .totals-title {color: var(--pos-muted);font-size:14px}
    #register-details-modal table tr td {padding: .35rem 0}
    .date-choice.dropdown-toggle::after {display: none}
    .country-phone-group .bootstrap-select{display: none !important}

    /* ── Panels / scroll areas ─────────────────────────────────── */
    .transaction-list{height:48vh;overflow-y:auto;width:100%}
    .table-container{height:calc(100vh - 160px);overflow-y:auto}

    /* ── Payment options bar (fixed bottom) ────────────────────── */
    .payment-options{background-color:var(--pos-surface);bottom:0;left:0;padding:4px 10px;position:fixed;width:100%;z-index:999;
        border-top:3px solid var(--pos-primary);box-shadow:0 -4px 16px rgba(15,40,90,.08)}
    .dark-mode .payment-options{background-color:#141b2e}
    .payment-options .column-5{float:left;margin:7px 0;padding:0 5px}
    .more-payment-options.column-5{margin:0;padding:0}
    #print-layout{padding: 0 0;margin: 0 0;}

    nav.navbar .nav-item {margin-left: 13px}
    nav.navbar .nav-item:first-child {margin-left: 0}

    @@media (max-width: 575px) {
        nav.navbar .dropdown-menu {left: 0;}
        .product-grid > div {width: calc(100%/3 - 10px);}
    }
    @@media (max-width: 375px) {
        .product-grid > div {width: calc(100%/2 - 10px);}
    }
    @@media all and (max-width:767px){
        section.pos-section {padding: 0 5px}
        nav.navbar{margin: 0 -10px}
        .pos-form{padding:0 0 !important}
        .payment-options {padding: 5px 0}
        .payment-options .column-5{margin:5px 0;}
        .payment-options .btn-sm{font-size:12px;}
        .more-payment-options, .more-payment-options .btn-group{width:100%}
        .more-payment-options.column-5{padding:0 5px;}
        .product-btns{margin:0 -15px 10px -15px}
        .product-btns .btn{font-size: 12px;}
        .more-options{margin-top: 0;}
        .transaction-list {height: 35vh;}
        .filter-window{position:fixed;}
    }

    @@media print {
        .hidden-print {display: none !important;}
    }

    #print-layout * {font-size: 10px;line-height: 20px;font-family: 'Ubuntu', sans-serif;text-transform: capitalize;}
    #print-layout .btn {padding: 7px 10px;text-decoration: none;border: none;display: block;text-align: center;margin: 7px;cursor:pointer;}
    #print-layout .btn-info {background-color: #999;color: #FFF;}
    #print-layout .btn-primary {background-color: var(--pos-primary);color: #FFF;width: 100%;}
    #print-layout td,
    #print-layout th,
    #print-layout tr,
    #print-layout table {border-collapse: collapse;}
    #print-layout tr {border-bottom: 1px dotted #ddd; display:block}
    #print-layout td,#print-layout th {padding: 7px 0;}
    #print-layout table {width: 100%;}
    #print-layout .centered {display: block;text-align: center;align-content: center;}
    #print-layout small{font-size:10px;}

    @@media print {
        #print-layout * {font-size:10px!important;line-height: 20px;}
        #print-layout table {width: 100%;margin: 0 0;}
        #print-layout td,#print-layout th {padding: 5px 0;}
        #print-layout .hidden-print {display: none !important;}
    }

    /* ── Loaders ───────────────────────────────────────────────── */
    .loader{display: block;max-width: 100% !important; min-width: 100% !important;text-align: center;vertical-align: middle;width: 100% !important; margin-top: 50px}
    .product-grid .loader{margin-top: 25%;}
    .loader svg path,.loader svg rect{fill: var(--pos-primary);}

    /* ── Navbar / icons ────────────────────────────────────────── */
    nav.navbar {margin-bottom: 10px;}
    nav.navbar a.menu-btn {display: flex;justify-content: center;align-items: center;}
    nav.navbar a {align-items: center;display: flex;}
    .right-sidebar li a svg{margin-right: 10px}
    .nav-menu svg {width: 20px;height: 20px; stroke: var(--pos-dark);vertical-align: middle}
    .btn svg {vertical-align: middle; width: 16px}
    button.close svg {vertical-align: middle; width: 26px}
    .bootstrap-select.btn-group > .dropdown-toggle{height: 38px}
    .dropdown-toggle-no-arrow::after{display:none!important}

    /* ── Calculator ────────────────────────────────────────────── */
    .calculator{background-color:var(--pos-surface);border-radius:var(--pos-radius);box-shadow:0 0 10px rgba(0,0,0,.2);width:240px}
    .calculator .display{width:100%;height:50px;background-color:#f5f5f5;border:2px solid var(--pos-dark);font-size:1.5em;text-align:right;padding:0 10px;margin-bottom:10px;border-radius:5px}
    .calculator .buttons{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
    .calculator .btn{height:40px;font-size:1em;border:none;border-radius:5px;cursor:pointer;transition:background-color .2s}
    .calculator .btn.number{background-color:#fff;color:#000;border:1px solid #ddd}
    .calculator .btn.operator{background-color:#f0f0f0;color:#000}
    .calculator .btn.action.ac{background-color:#d63031;color:#fff}
    .calculator .btn.action.ce{background-color:#e28d02;color:#fff}
    .calculator .btn.equals{background-color:var(--pos-primary);color:#fff;grid-column:span 2}

    /* ── Product live-search results dropdown ──────────────────── */
    #product-results-container{background:var(--pos-surface);position: absolute;overflow: hidden;max-height: 300px;overflow-y: auto;top:50px;width:100%;z-index:999999;border:1px solid var(--pos-line);border-radius:var(--pos-radius-sm);box-shadow:var(--pos-shadow-hover)}
    #product-results-container .product-img{border-radius: 3px; color: var(--pos-ink);font-size:13px;padding-top:7px;padding-bottom:7px;text-align:left}
    #product-results-container .product-img:hover{background-color: var(--pos-primary);color: #FFF}
    #shortcut-list .dropdown-item {font-size: 13px;padding: 6px 10px;}
    #shortcut-list .badge {font-size: 11px;background:var(--pos-primary)}

    /* ── Customer display indicator ────────────────────────────── */
    #customer-display.inactive svg{stroke:#9ca3af;}
    #customer-display.active svg{stroke:#22c55e;filter: drop-shadow(0 0 4px #22c55e);}

    /* ── Platform theme accents (from superadmin settings) ─────── */
    .pos-page .top-fields label{color:var(--pos-primary)}
    .pos-page .sub-total, .pos-page .totals strong{color:var(--pos-dark)}
    .pos-page .load-more{background-color:var(--pos-primary)!important;border-color:var(--pos-primary)!important;color:#fff!important}
    .pos-page .load-more:hover{background-color:var(--pos-primary-2)!important;border-color:var(--pos-primary-2)!important}
    .pos-page nav.navbar .nav-item .btn-pos, .pos-page .btn-pos{background-color:var(--pos-primary)!important;color:#fff!important;border-color:var(--pos-primary)!important}
    .pos-page nav.navbar .nav-item .btn-pos:hover, .pos-page .btn-pos:hover{background-color:var(--pos-primary-2)!important;border-color:var(--pos-primary-2)!important}
</style>
