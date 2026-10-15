{{-- ============================================================ --}}
{{-- PRODUCT PANEL (Left Column) --}}
{{-- Contains: navbar, category/brand/featured filters, product grid, filter sidebar --}}
{{-- ============================================================ --}}

<section id="pos-layout" class="forms pos-section hidden-print">
    <div class="container-fluid">
        <div class="row">
            <!-- product list -->
            <div class="col-md-5 order-first order-md-2">
                <!-- navbar-->
                <header>
                    <nav class="navbar" style="padding: 5px 10px">
                        <div class="dropdown">
                            <a class="btn menu-btn dropdown-toggle-no-arrow" type="button" data-toggle="dropdown" aria-expanded="false" role="button"><svg  style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg></a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" target="_blank"  href="{{url('/dashboard')}}">{{__('db.dashboard')}}</a>
                                <?php
                                $product_permission_active = $role_has_permissions_list->where('name', 'products-index')->first();
                                ?>
                                @if($product_permission_active)
                                <a class="dropdown-item" target="_blank" href="{{route('products.index')}}">{{__('db.product_list')}}</a>
                                @endif

                                <?php
                                $sale_permission_active = $role_has_permissions_list->where('name', 'sales-index')->first();
                                ?>
                                @if($sale_permission_active)
                                <a class="dropdown-item" target="_blank" href="{{route('sales.index')}}">{{__('db.Sale List')}}</a>
                                @endif

                                <?php
                                $purchase_permission_active = $role_has_permissions_list->where('name', 'sales-index')->first();
                                ?>
                                @if($purchase_permission_active)
                                <a class="dropdown-item" target="_blank" href="{{route('purchases.index')}}">{{__('db.Purchase List')}}</a>
                                @endif

                                <?php
                                $transfer_permission_active = $role_has_permissions_list->where('name', 'transfers-index')->first();
                                ?>
                                @if($transfer_permission_active)
                                <a class="dropdown-item" target="_blank" href="{{route('transfers.index')}}">{{__('db.Transfer List')}}</a>
                                @endif
                            </div>
                        </div>
                        <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center">
                            <!-- //keyboard shortcuts -->
                            <li class="nav-item d-none d-lg-block dropdown">
                                <a class="dropdown-toggle-no-arrow" type="button" data-toggle="dropdown" aria-expanded="false" role="button">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-keyboard"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M2 8a2 2 0 0 1 2 -2h16a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2l0 -8" /><path d="M6 10l0 .01" /><path d="M10 10l0 .01" /><path d="M14 10l0 .01" /><path d="M18 10l0 .01" /><path d="M6 14l0 .01" /><path d="M18 14l0 .01" /><path d="M10 14l4 .01" /></svg>
                                </a>

                                <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="shortcutDropdown" style="min-width: 260px; max-height: 350px; overflow-y: auto;">

                                    <div id="shortcut-list"></div>

                                </div>
                            </li>
                            <!-- //mobile collapse -->
                            <li class="nav-item d-md-none">
                                <a  data-toggle="collapse" href="#collapseProducts" role="button" aria-expanded="false" aria-controls="collapseProducts"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m7.875 14.25 1.214 1.942a2.25 2.25 0 0 0 1.908 1.058h2.006c.776 0 1.497-.4 1.908-1.058l1.214-1.942M2.41 9h4.636a2.25 2.25 0 0 1 1.872 1.002l.164.246a2.25 2.25 0 0 0 1.872 1.002h2.092a2.25 2.25 0 0 0 1.872-1.002l.164-.246A2.25 2.25 0 0 1 16.954 9h4.636M2.41 9a2.25 2.25 0 0 0-.16.832V12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 12V9.832c0-.287-.055-.57-.16-.832M2.41 9a2.25 2.25 0 0 1 .382-.632l3.285-3.832a2.25 2.25 0 0 1 1.708-.786h8.43c.657 0 1.281.287 1.709.786l3.284 3.832c.163.19.291.404.382.632M4.5 20.25h15A2.25 2.25 0 0 0 21.75 18v-2.625c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125V18a2.25 2.25 0 0 0 2.25 2.25Z" /></svg></a>
                            </li>
                            <!-- //calculator -->
                            <li class="nav-item d-none d-lg-block dropdown">
                                <a  class="dropdown-toggle-no-arrow" type="button" data-toggle="dropdown" aria-expanded="false" role="button"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V13.5Zm0 2.25h.008v.008H8.25v-.008Zm0 2.25h.008v.008H8.25V18Zm2.498-6.75h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V13.5Zm0 2.25h.007v.008h-.007v-.008Zm0 2.25h.007v.008h-.007V18Zm2.504-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V18Zm2.498-6.75h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V13.5ZM8.25 6h7.5v2.25h-7.5V6ZM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0 0 12 2.25Z" /></svg></a>
                                <div class="dropdown-menu calculator p-3" onclick="event.stopPropagation();">
                                    <input type="text" class="display" readonly>
                                    <div class="buttons">
                                        <button class="btn action ac">AC</button>
                                        <button class="btn action ce">CE</button>
                                        <button class="btn operator">%</button>
                                        <button class="btn operator">÷</button>

                                        <button class="btn number">7</button>
                                        <button class="btn number">8</button>
                                        <button class="btn number">9</button>
                                        <button class="btn operator">x</button>

                                        <button class="btn number">4</button>
                                        <button class="btn number">5</button>
                                        <button class="btn number">6</button>
                                        <button class="btn operator">-</button>

                                        <button class="btn number">1</button>
                                        <button class="btn number">2</button>
                                        <button class="btn number">3</button>
                                        <button class="btn operator">+</button>

                                        <button class="btn number">0</button>
                                        <button class="btn number">.</button>
                                        <button class="btn equals">=</button>
                                    </div>
                                </div>
                            </li>
                            <!-- //Sale return -->
                            <li class="nav-item" data-toggle="tooltip" title="Sale Return">
                                <a type="button" data-toggle="dropdown" aria-expanded="false" role="button"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg></a>
                                <div class="dropdown-menu pl-3 pr-3" style="max-width: 250px;">
                                    <form method="GET" action="{{route('return-sale.create')}}"  target="_blank" accept-charset="UTF-8">
                                        <div class="form-group">
                                            <label>Sale Reference *</label>
                                            <div class="input-group">
                                                <input type="text" name="reference_no" class="form-control">
                                                <button type="submit" class="btn btn-primary btn-sm" data-toggle="tooltip"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" style="stroke:#FFF" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </li>
                            <!-- //fullscreen -->
                            <li class="nav-item d-none d-lg-block">
                                <a id="btnFullscreen" data-toggle="tooltip" title="Full Screen"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg></a>
                            </li>
                            <!-- //Customer Display Screen -->
                            <li class="nav-item d-none d-lg-block">
                                <a id="customer-display"
                                class="inactive"
                                href="{{route('sales.customerDisplay')}}"
                                data-toggle="tooltip"
                                title="{{__('db.enable_customer_display')}}">

                                <svg xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="size-6">

                                        <path stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3.75 4.5h16.5a.75.75 0 01.75.75v10.5a.75.75 0 01-.75.75H3.75a.75.75 0 01-.75-.75V5.25a.75.75 0 01.75-.75zM6 18h12m-6 0v2.25" />
                                </svg>

                                </a>
                            </li>
                            <!-- //print last reciept -->
                            <li class="nav-item">
                                <a id="print-last-receipt" href="{{route('sales.printLastReciept')}}" data-toggle="tooltip" title="{{__('db.Print Last Reciept')}}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg></a>
                            </li>
                            @if($lims_pos_setting_data && $lims_pos_setting_data->cash_register)
                            <!-- //cash register -->
                            <li class="nav-item d-none d-lg-block">
                                <a href="" id="register-details-btn" data-id="" data-toggle="tooltip" title="{{__('db.Cash Register Details')}}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" /></svg></a>
                            </li>
                            @endif
                            @if(($alert_product + count(\Auth::user()->unreadNotifications)) > 0)
                            <li class="nav-item d-none d-lg-block" id="notification-icon">
                                <a rel="nofollow" data-toggle="tooltip" title="{{__('Notifications')}}" class="nav-link dropdown-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg><span class="badge badge-danger notification-number">{{$alert_product + count(\Auth::user()->unreadNotifications)}}</span>
                                    <span class="caret"></span>
                                    <span class="sr-only">Toggle Dropdown</span>
                                </a>
                                <ul class="right-sidebar" user="menu">
                                    <li class="notifications">
                                        <a href="{{route('report.qtyAlert')}}" class="btn btn-link">{{$alert_product}} product exceeds alert quantity</a>
                                    </li>
                                    @foreach(\Auth::user()->unreadNotifications as $key => $notification)
                                    <li class="notifications">
                                        <a href="#" class="btn btn-link">{{ $notification->data['message'] }}</a>
                                    </li>
                                    @endforeach
                                </ul>
                            </li>
                            @endif
                            <li class="nav-item">
                                <a rel="nofollow" data-toggle="tooltip" class="nav-link dropdown-item"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                <!-- <span>{{ucfirst(Auth::user()->name)}}</span>  -->
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                                </a>
                                <ul class="right-sidebar">
                                    <li>
                                        <a target="_blank" href="{{route('user.profile', ['id' => Auth::id()])}}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg> {{__('db.profile')}}</a>
                                    </li>
                                    <?php
                                    $add_expense_permission = $role_has_permissions_list->where('name', 'expenses-add')->first();
                                    ?>
                                    @if($add_expense_permission)
                                    <li>
                                        <a href="" data-toggle="modal" data-target="#expense-modal"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 0 0-2.25-2.25H15a3 3 0 1 1-6 0H5.25A2.25 2.25 0 0 0 3 12m18 0v6a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9m18 0V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v3" /></svg> {{__('db.Add Expense')}}</a>
                                    </li>
                                    @endif
                                    <?php
                                    $add_payment_permission = $role_has_permissions_list->where('name', 'purchase-payment-add')->first();
                                    ?>
                                    @if($add_payment_permission)
                                    <li>
                                        <a href="" class="add-supplier-payment" data-toggle="modal" data-target="#add-supplier-payment"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg> {{__('db.Add Payment')}}</a>
                                    </li>
                                    @endif
                                    <?php
                                    $today_sale_permission = $permission_list->where('name', 'today_sale')->first();
                                    $today_sale_permission_active = DB::table('role_has_permissions')->where([
                                        ['permission_id', $today_sale_permission->id],
                                        ['role_id', Auth::user()->role_id]
                                    ])->first();

                                    $today_profit_permission = $permission_list->where('name', 'today_profit')->first();
                                    $today_profit_permission_active = DB::table('role_has_permissions')->where([
                                        ['permission_id', $today_profit_permission->id],
                                        ['role_id', Auth::user()->role_id]
                                    ])->first();
                                    ?>

                                    @if($today_sale_permission_active)
                                    <li>
                                        <a href="" id="today-sale-btn" title="{{__('db.Today Sale')}}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>{{__('db.Today Sale')}}</a>
                                    </li>
                                    @endif
                                    @if($today_profit_permission_active)
                                    <li>
                                        <a href="" id="today-profit-btn" title="{{__('db.Today Profit')}}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>{{__('db.Today Profit')}}</a>
                                    </li>
                                    @endif
                                    <?php
                                    $general_setting_permission = $permission_list->where('name', 'general_setting')->first();
                                    $general_setting_permission_active = DB::table('role_has_permissions')->where([
                                        ['permission_id', $general_setting_permission->id],
                                        ['role_id', Auth::user()->role_id]
                                    ])->first();

                                    $pos_setting_permission = $permission_list->where('name', 'pos_setting')->first();

                                    $pos_setting_permission_active = DB::table('role_has_permissions')->where([
                                        ['permission_id', $pos_setting_permission->id],
                                        ['role_id', Auth::user()->role_id]
                                    ])->first();

                                    $authUser = Auth::user()->role_id;
                                    ?>
                                    @if($pos_setting_permission_active)
                                    <li><a href="{{route('setting.pos')}}" title="{{__('db.POS Setting')}}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg> {{__('db.POS Setting')}}</a> </li>
                                    @endif
                                    @if($general_setting_permission_active)
                                    <li>
                                        <a href="{{route('setting.general')}}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg> {{__('db.settings')}}</a>
                                    </li>
                                    @endif
                                    <li>
                                        <a href="{{url('my-transactions/'.date('Y').'/'.date('m'))}}"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg> {{__('db.My Transaction')}}</a>
                                    </li>
                                    @if(Auth::user()->role_id != 5)
                                    <li>
                                        <a href="{{url('holidays/my-holiday/'.date('Y').'/'.date('m'))}}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" /></svg> {{__('db.My Holiday')}}</a>
                                    </li>
                                    @endif
                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                        document.getElementById('logout-form').submit();"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" /></svg>

                                            {{__('db.logout')}}
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </header>

                <div class="filter-window">
                    <div class="category mt-3">
                        <div class="row ml-2 mr-2 px-2">
                            <div class="col-7">Choose category</div>
                            <div class="col-5 text-right">
                                <span class="btn btn-light btn-sm btn-close">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </span>
                            </div>
                        </div>
                        <div class="row ml-2 mt-3">
                            <div class="col-12 mb-3">
                                <input type="text" id="categorySearch" class="form-control form-control live-filter" data-target=".category-img" placeholder="Search category...">
                            </div>
                            @foreach($lims_category_list as $category)
                            <div class="col-md-3 col-6 category-img text-center" data-category="{{$category->id}}" data-name="{{ strtolower($category->name) }}">
                                @if($category->image)
                                <img src="{{url('images/category', $category->image)}}" />
                                @else
                                <img src="{{url('/images/product/zummXD2dvAtI.png')}}" />
                                @endif
                                <p class="text-center">{{$category->name}}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="brand mt-3">
                        <div class="row ml-2 mr-2 px-2">
                            <div class="col-7">Choose brand</div>
                            <div class="col-5 text-right">
                                <span class="btn btn-light btn-sm btn-close">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </span>
                            </div>
                        </div>
                        <div class="row ml-2 mt-3">
                            <div class="col-12 mb-3">
                                <input type="text" id="brandSearch" class="form-control form-control live-filter" data-target=".brand-img" placeholder="Search brand...">
                            </div>
                            @foreach($lims_brand_list as $brand)
                            <div class="col-md-3 col-6 brand-img text-center" data-brand="{{$brand->id}}" data-name="{{ strtolower($brand->title) }}">
                                @if($brand->image)
                                <img src="{{url('images/brand',$brand->image)}}" />
                                @else
                                <img src="{{url('/images/product/zummXD2dvAtI.png')}}" />
                                @endif
                                <p class="text-center">{{$brand->title}}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="products-m mt-3">
                        <div class="row ml-2 mr-2 px-2">
                            <div class="col-7"></div>
                            <div class="col-5 text-right">
                                <span class="btn btn-light btn-sm btn-close">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </span>
                            </div>
                        </div>
                        <div class="product_list_mobile table-container row mt-3" data-cat="" data-brand="">

                        </div>
                    </div>
                </div>
                <div id="collapseProducts" class="">
                    <div class="d-flex justify-content-between product-btns">

                        <button class="btn btn-block btn-primary mt-0 ml-1 mr-1" id="category-filter">{{__('db.category')}}</button>

                        <button class="btn btn-block btn-info mt-0 ml-1 mr-1" id="brand-filter">{{__('db.Brand')}}</button>

                        <button class="btn btn-block btn-danger mt-0 ml-1 mr-1" id="featured-filter">{{__('db.Featured')}}</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-primary alert-dismissible fade show mb-0 pt-1 pb-1 loading-message"><span class="small">{{ __('db.Loading products for selected warehouse') }}</span>
                            <button type="button" id="closeButtonUpgrade" class="close pt-1 pb-1" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-12 table-container main mt-2" data-cat="" data-brand="">

                        <div class="product-grid text-center">

                            <div class="loader " title="4" style="border:none">
                                <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve">
                                    <rect x="0" y="0" width="4" height="10" fill="#333">
                                    <animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0" dur="0.6s" repeatCount="indefinite"></animateTransform>
                                    </rect>
                                    <rect x="10" y="0" width="4" height="10" fill="#333">
                                    <animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.2s" dur="0.6s" repeatCount="indefinite"></animateTransform>
                                    </rect>
                                    <rect x="20" y="0" width="4" height="10" fill="#333">
                                    <animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.4s" dur="0.6s" repeatCount="indefinite"></animateTransform>
                                    </rect>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

