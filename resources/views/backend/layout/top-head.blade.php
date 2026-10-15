<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{$general_setting->site_title}}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if(!config('database.connections.saleprosaas_landlord'))
    <link rel="icon" type="image/png" href="{{url('logo', $general_setting->favicon ?? $general_setting->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
    <link rel="preload" href="<?php echo asset('vendor/bootstrap/css/bootstrap-datepicker.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('vendor/bootstrap/css/bootstrap-datepicker.min.css') ?>" rel="stylesheet"></noscript>
    <link rel="preload" href="<?php echo asset('vendor/bootstrap/css/awesome-bootstrap-checkbox.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('vendor/bootstrap/css/awesome-bootstrap-checkbox.css') ?>" rel="stylesheet"></noscript>
    <link rel="preload" href="<?php echo asset('vendor/bootstrap/css/bootstrap-select.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('vendor/bootstrap/css/bootstrap-select.min.css') ?>" rel="stylesheet"></noscript>
    <!-- Font Awesome CSS-->
    <link rel="preload" href="<?php echo asset('vendor/font-awesome/css/font-awesome.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('vendor/font-awesome/css/font-awesome.min.css') ?>" rel="stylesheet"></noscript>
    <!-- Drip icon font-->
    <link rel="preload" href="<?php echo asset('vendor/dripicons/webfont.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('vendor/dripicons/webfont.css') ?>" rel="stylesheet"></noscript>
    <!-- Custom Scrollbar-->
    <link rel="preload" href="<?php echo asset('vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css') ?>" rel="stylesheet"></noscript>
    <!-- virtual keybord stylesheet-->
    <link rel="preload" href="<?php echo asset('vendor/keyboard/css/keyboard.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('vendor/keyboard/css/keyboard.css') ?>" rel="stylesheet"></noscript>
    <link rel="stylesheet" href="<?php echo asset('css/style.default.css') ?>" id="theme-stylesheet" type="text/css">
    <!-- Custom stylesheet - for your changes-->
    <link rel="stylesheet" href="<?php echo asset('css/custom-'.$general_setting->theme) ?>" type="text/css" id="custom-style">
    @if( Config::get('app.locale') == 'ar' || $general_setting->is_rtl)
      <!-- RTL css -->
      <link rel="stylesheet" href="<?php echo asset('vendor/bootstrap/css/bootstrap-rtl.min.css') ?>" type="text/css">
      <link rel="stylesheet" href="<?php echo asset('css/custom-rtl.css') ?>" type="text/css" id="custom-style">
    @endif

    @else
    <link rel="icon" type="image/png" href="{{url('logo', $general_setting->favicon ?? $general_setting->site_logo)}}" />
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo asset('../../vendor/bootstrap/css/bootstrap.min.css') ?>" type="text/css">
    <link rel="preload" href="<?php echo asset('../../vendor/bootstrap/css/bootstrap-datepicker.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('../../vendor/bootstrap/css/bootstrap-datepicker.min.css') ?>" rel="stylesheet"></noscript>
    <link rel="preload" href="<?php echo asset('../../vendor/bootstrap/css/awesome-bootstrap-checkbox.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('../../vendor/bootstrap/css/awesome-bootstrap-checkbox.css') ?>" rel="stylesheet"></noscript>
    <link rel="preload" href="<?php echo asset('../../vendor/bootstrap/css/bootstrap-select.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('../../vendor/bootstrap/css/bootstrap-select.min.css') ?>" rel="stylesheet"></noscript>
    <!-- Font Awesome CSS-->
    <link rel="preload" href="<?php echo asset('../../vendor/font-awesome/css/font-awesome.min.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('../../vendor/font-awesome/css/font-awesome.min.css') ?>" rel="stylesheet"></noscript>
    <!-- Drip icon font-->
    <link rel="preload" href="<?php echo asset('../../vendor/dripicons/webfont.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('../../vendor/dripicons/webfont.css') ?>" rel="stylesheet"></noscript>
    <!-- Custom Scrollbar-->
    <link rel="preload" href="<?php echo asset('../../vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('../../vendor/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css') ?>" rel="stylesheet"></noscript>
    <!-- virtual keybord stylesheet-->
    <link rel="preload" href="<?php echo asset('../../vendor/keyboard/css/keyboard.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="<?php echo asset('../../vendor/keyboard/css/keyboard.css') ?>" rel="stylesheet"></noscript>

    <link rel="stylesheet" href="<?php echo asset('../../css/style.default.css') ?>" id="theme-stylesheet" type="text/css">

    <!-- Custom stylesheet - for your changes-->
    <link rel="stylesheet" href="<?php echo asset('../../css/custom-'.$general_setting->theme) ?>" type="text/css" id="custom-style">
    @if( Config::get('app.locale') == 'ar' || $general_setting->is_rtl)
      <!-- RTL css -->
      <link rel="stylesheet" href="<?php echo asset('../../vendor/bootstrap/css/bootstrap-rtl.min.css') ?>" type="text/css">
      <link rel="stylesheet" href="<?php echo asset('../../css/custom-rtl.css') ?>" type="text/css" id="custom-style">
    @endif
    @endif

    <!-- Google fonts -->
    @if($general_setting->font_css)
      {!! $general_setting->font_css !!}
    @else
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">
    @endif

    @stack('css')

    @if(config('database.connections.saleprosaas_landlord'))
    {{-- ── Platform Brand Theme Variables (same as main layout) ─────────── --}}
    <style>
        :root {
            @php
                try {
                    $__th = \App\Models\landlord\SaasSettings::allCached();
                } catch (\Throwable $__e) { $__th = []; }
            @endphp
            --lp-primary:        {{ $__th['theme_primary']       ?? '#0066CC' }};
            --lp-primary-light:  {{ $__th['theme_primary_light'] ?? '#00A3FF' }};
            --lp-accent:         {{ $__th['theme_accent']        ?? '#66FF33' }};
            --lp-lime:           {{ $__th['theme_lime']          ?? '#8DFF00' }};
            --lp-dark:           {{ $__th['theme_dark']          ?? '#003366' }};
            --lp-cyan:           {{ $__th['theme_cyan']          ?? '#00D4FF' }};
            --lp-font:           "{{ $__th['site_font'] ?? 'Inter' }}";
        }
        /* Apply brand primary to Bootstrap primary elements */
        .btn-primary, .badge-primary, .bg-primary { background-color: var(--lp-primary) !important; border-color: var(--lp-primary) !important; }
        .btn-primary:hover { background-color: var(--lp-primary-light) !important; border-color: var(--lp-primary-light) !important; }
        .text-primary  { color: var(--lp-primary) !important; }
        .border-primary{ border-color: var(--lp-primary) !important; }
    </style>
    @endif

    @if(!empty($__th['site_font']) && $__th['site_font'] !== 'Inter')
    <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $__th['site_font']) }}:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @endif

    {{-- LIBServices type scale and font; the till stays compact (body.pos-page). --}}
    <link rel="stylesheet" href="{{ url('css/lib-type.css') }}?v={{ @filemtime(public_path('css/lib-type.css')) }}">

    <!-- Custom CSS from general settings -->
    {!! $general_setting->pos_css !!}
  </head>
  <body class="pos-page">
      <div id="content">
          @yield('content')
      </div>

    @if(!config('database.connections.saleprosaas_landlord'))
      <script type="text/javascript" src="<?php echo asset('vendor/jquery/jquery.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('vendor/jquery/jquery-ui.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('vendor/jquery/bootstrap-datepicker.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('vendor/popper.js/umd/popper.min.js') ?>">
      </script>
      <script type="text/javascript" src="<?php echo asset('vendor/bootstrap/js/bootstrap.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('vendor/bootstrap-toggle/js/bootstrap-toggle.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('vendor/bootstrap/js/bootstrap-select.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('vendor/keyboard/js/jquery.keyboard.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('vendor/keyboard/js/jquery.keyboard.extension-autocomplete.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('vendor/jquery.cookie/jquery.cookie.js') ?>">
      </script>
      <script type="text/javascript" src="<?php echo asset('vendor/jquery-validation/jquery.validate.min.js') ?>"></script>
      @if( Config::get('app.locale') == 'ar' || $general_setting->is_rtl)
        <script type="text/javascript" src="<?php echo asset('js/front_rtl.js') ?>"></script>
      @else
        <script type="text/javascript" src="<?php echo asset('js/front.js') ?>"></script>
      @endif

    @else
    
      <script type="text/javascript" src="<?php echo asset('../../vendor/jquery/jquery.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/jquery/jquery-ui.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/jquery/bootstrap-datepicker.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/popper.js/umd/popper.min.js') ?>">
      </script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/bootstrap/js/bootstrap.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/bootstrap-toggle/js/bootstrap-toggle.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/bootstrap/js/bootstrap-select.min.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/keyboard/js/jquery.keyboard.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/keyboard/js/jquery.keyboard.extension-autocomplete.js') ?>"></script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/jquery.cookie/jquery.cookie.js') ?>">
      </script>
      <script type="text/javascript" src="<?php echo asset('../../vendor/jquery-validation/jquery.validate.min.js') ?>"></script>
      @if( Config::get('app.locale') == 'ar' || $general_setting->is_rtl)
        <script type="text/javascript" src="<?php echo asset('../../js/front_rtl.js') ?>"></script>
      @else
        <script type="text/javascript" src="<?php echo asset('../../js/front.js') ?>"></script>
      @endif

    @endif

    @stack('scripts')
    <script type="text/javascript">

          function myFunction() {
              setTimeout(showPage, 150);
          }

          function showPage() {
            document.getElementById("loader").style.display = "none";
            document.getElementById("content").style.display = "block";
            $("#lims_productcodeSearch").focus();
          }

          $("div.alert").delay(7000).slideUp(750);
          $('select').selectpicker({
              style: 'btn-link',
          });

        //switch theme code
        var theme = <?php echo json_encode($theme); ?>;
        if(theme == 'dark') {
            $('body').addClass('dark-mode');
            $('#switch-theme i').addClass('dripicons-brightness-low');
        }
        else {
            $('body').removeClass('dark-mode');
            $('#switch-theme i').addClass('dripicons-brightness-max');
        }
        $('#switch-theme').click(function() {
            if(theme == 'light') {
                theme = 'dark';
                var url = <?php echo json_encode(route('switchTheme', 'dark')); ?>;
                $('body').addClass('dark-mode');
                $('#switch-theme i').addClass('dripicons-brightness-low');
            }
            else {
                theme = 'light';
                var url = <?php echo json_encode(route('switchTheme', 'light')); ?>;
                $('body').removeClass('dark-mode');
                $('#switch-theme i').addClass('dripicons-brightness-max');
            }

            $.get(url, function(data) {
                console.log('theme changed to '+theme);
            });
        });

        $("li#notification-icon").on("click", function (argument) {
              $.get('notifications/mark-as-read', function(data) {
                  $("span.notification-number").text(alert_product);
              });
          });

      $("a#add-expense").click(function(e){
        e.preventDefault();
        $('#expense-modal').modal();
      });

      $("a#send-notification").click(function(e){
        e.preventDefault();
        $('#notification-modal').modal();
      });

      $("a#add-account").click(function(e){
        e.preventDefault();
        $('#account-modal').modal();
      });

      $("a#account-statement").click(function(e){
        e.preventDefault();
        $('#account-statement-modal').modal();
      });

      $("a#profitLoss-link").click(function(e){
        e.preventDefault();
        $("#profitLoss-report-form").submit();
      });

      $("a#report-link").click(function(e){
        e.preventDefault();
        $("#product-report-form").submit();
      });

      $("a#purchase-report-link").click(function(e){
        e.preventDefault();
        $("#purchase-report-form").submit();
      });

      $("a#sale-report-link").click(function(e){
        e.preventDefault();
        $("#sale-report-form").submit();
      });

      $("a#payment-report-link").click(function(e){
        e.preventDefault();
        $("#payment-report-form").submit();
      });

      $("a#warehouse-report-link").click(function(e){
        e.preventDefault();
        $('#warehouse-modal').modal();
      });

      $("a#user-report-link").click(function(e){
        e.preventDefault();
        $('#user-modal').modal();
      });

      $("a#customer-report-link").click(function(e){
        e.preventDefault();
        $('#customer-modal').modal();
      });

      $("a#customer-group-report-link").click(function(e){
        e.preventDefault();
        $('#customer-group-modal').modal();
      });

      $("a#supplier-report-link").click(function(e){
        e.preventDefault();
        $('#supplier-modal').modal();
      });

      $("a#due-report-link").click(function(e){
        e.preventDefault();
        $("#customer-due-report-form").submit();
      });

      $("a#supplier-due-report-link").click(function(e){
        e.preventDefault();
        $("#supplier-due-report-form").submit();
      });

      $('.date').datepicker({
         format: "dd-mm-yyyy",
         autoclose: true,
         todayHighlight: true
      });

    </script>
  </body>
</html>
