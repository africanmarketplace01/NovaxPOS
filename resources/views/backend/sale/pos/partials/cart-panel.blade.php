{{-- ============================================================ --}}
{{-- CART PANEL (Right Column) --}}
{{-- Contains: order form, cart table, payment sidebar, totals --}}
{{-- ============================================================ --}}

            <div class="col-md-7 pos-form">
                <form action="{{ route('sales.store') }}" method="POST" enctype="multipart/form-data" class="payment-form">
                    @csrf

                @php
                if($lims_pos_setting_data)
                $keybord_active = $lims_pos_setting_data->keybord_active;
                else
                $keybord_active = 0;

                $customer_active = DB::table('permissions')
                ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where([
                ['permissions.name', 'customers-add'],
                ['role_id', \Auth::user()->role_id] ])->first();
                @endphp
                <div class="card mb-2 px-2">
                    <div class="d-flex align-items-center flex-wrap">

                        <button type="button" class="btn btn-light btn-md mr-2 date-choice dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-calendar-week"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M7 14h.013" /><path d="M10.01 14h.005" /><path d="M13.01 14h.005" /><path d="M16.015 14h.005" /><path d="M13.015 17h.005" /><path d="M7.01 17h.005" /><path d="M10.01 17h.005" /></svg>
                        </button>
                        <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                            <div class="input-group">
                                @can('change_sale_date')
                                    <input type="text"
                                        name="created_at"
                                        class="form-control date"
                                        placeholder="{{ __('db.Choose date') }}"
                                        value="{{date($general_setting->date_format,strtotime('now'))}}" />
                                @else
                                    <input type="text"
                                        name="created_at"
                                        class="form-control date"
                                        placeholder="{{ __('db.Choose date') }}"
                                        value="{{date($general_setting->date_format,strtotime('now'))}}"
                                        readonly/>
                                @endcan
                            </div>
                        </div>

                        @if(isset(auth()->user()->warehouse_id))
                        <input type="hidden" id="warehouse_id" name="warehouse_id" value="{{auth()->user()->warehouse_id}}" />
                        @else
                        <div data-toggle="tooltip" title="" data-original-title="{{__('db.Warehouse')}}">
                            <button type="button" class="btn btn-light btn-md mr-2" data-toggle="collapse" data-target="#warehousePanel" aria-expanded="false">
                                <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                            </button>
                        </div>
                        @endif

                        @if(isset(auth()->user()->biller_id))
                        <input type="hidden" id="biller_id" name="biller_id" value="{{auth()->user()->biller_id}}" />
                        @else
                        <div data-toggle="tooltip" title="" data-original-title="{{__('db.Biller')}}">
                            <button type="button" class="btn btn-light btn-md mr-2" data-toggle="collapse" data-target="#billerPanel" aria-expanded="false">
                                <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </button>
                        </div>
                        @endif

                        <div data-toggle="tooltip" title="" data-original-title="{{__('db.Currency')}}">
                            <button type="button" class="btn btn-light btn-md mr-2" data-toggle="collapse" data-target="#currencyPanel" aria-expanded="false">
                                <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </button>
                        </div>

                        <div class="form-group top-fields mr-2" style="margin: 7px 0">
                            <label>{{__('db.customer')}}</label>
                            <div class="input-group pos">
                                @php
                                $deposit = [];
                                $points = [];
                                if(isset($lims_sale_data) && !empty($lims_sale_data) && $lims_sale_data->customer_id) {
                                    $customer_id = $lims_sale_data->customer_id;
                                }
                                elseif($lims_pos_setting_data) {
                                    $customer_id = $lims_pos_setting_data->customer_id;
                                }
                                else{
                                $customer_id = $lims_customer_list[0]->id;
                                }
                                @endphp
                                <select required name="customer_id" id="customer_id" class="selectpicker form-control" data-live-search="true" title="Select..." style="width: 100px">
                                    @foreach($lims_customer_list as $customer)
                                        <option
                                            data-points="{{ $customer->points }}"
                                            data-deposit="{{ $customer->deposit }}"
                                            data-credit-limit="{{ $customer->credit_limit }}"
                                            data-pay_term_no="{{ $customer->pay_term_no }}"
                                            data-pay_term_period="{{ $customer->pay_term_period }}"
                                            data-type="{{ $customer->type }}"
                                            value="{{ $customer->id }}"
                                            @if($customer->id == $customer_id) selected @endif>
                                            {{ $customer->name }} <span>({{ $customer->wa_number ?? $customer->phone_number }})</span>
                                        </option>
                                    @endforeach
                                </select>
                                @if($customer_active)
                                <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#addCustomer"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg></button>
                                @endif
                                <x-validation-error fieldName="customer_id" />
                            </div>
                        </div>

                        @if(!in_array('restaurant',explode(',',$general_setting->modules)))
                        <!-- Price type -->
                        <div class="form-group top-fields mr-2" style="margin: 7px 0">
                            <label>{{__('db.Price Option')}}</label>
                            <select id="price_type" class="form-control selectpicker"  style="width: 100px">
                                <option value="retail" {{ $price_type == 'retail' ? 'selected' : '' }}>Retail</option>
                                <option value="wholesale" {{ $price_type == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                            </select>
                        </div>
                        @endif

                        @if(in_array('restaurant',explode(',',$general_setting->modules)))
                            <div class="form-group top-fields mr-2">
                                <label>{{__('db.Service')}}</label>
                                @php
                                if(isset($lims_sale_data) && !empty($lims_sale_data) && $lims_sale_data->service_id) {
                                    $service_id = $lims_sale_data->service_id;
                                }
                                @endphp
                                @if(!empty($service_id))
                                <div class="input-group pos">
                                    <select required id="service_id" name="service_id" class="selectpicker form-control" title="Select service...">
                                        <option value="1" @if($service_id == 1) selected @endif>{{__('db.Dine In')}}</option>
                                        <option value="2" @if($service_id == 2) selected @endif>{{__('db.Take Away')}}</option>
                                        <option value="3" @if($service_id == 3) selected @endif>{{__('db.Delivery')}}</option>
                                    </select>
                                </div>
                                @else
                                <div class="input-group pos">
                                    <select required id="service_id" name="service_id" class="selectpicker form-control" title="Select service...">
                                        <option value="1" selected>{{__('db.Dine In')}}</option>
                                        <option value="2">{{__('db.Take Away')}}</option>
                                        <option value="3">{{__('db.Delivery')}}</option>
                                    </select>
                                </div>
                                @endif
                            </div>

                            <div class="form-group top-fields mr-2">
                                <label>{{__('db.Table')}}</label>
                                <div class="input-group pos">
                                    @php
                                    if(isset($lims_sale_data) && !empty($lims_sale_data) && !empty($lims_sale_data->table_id)) {
                                        $table_id = $lims_sale_data->table_id;
                                    }
                                    @endphp
                                    <select required id="table_id" name="table_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select table...">
                                        @foreach($lims_table_list as $table)
                                        <option value="{{$table->id}}" @if(!empty($table_id) && $table->id == $table_id) selected @endif>
                                            {{$table->name}} at {{$table->floor}} ( ðŸ‘¤ {{$table->number_of_person}})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group top-fields mr-2">
                                <label>{{__('db.Waiter')}}</label>
                                <div class="input-group pos">
                                    @php
                                    if(isset($lims_sale_data) && !empty($lims_sale_data) && !empty($lims_sale_data->waiter_id)) {
                                        $waiter_id = $lims_sale_data->waiter_id;
                                    }
                                    @endphp
                                    <select required id="waiter_id" name="waiter_id" class="selectpicker form-control" title="Select waiter...">
                                        @if(auth()->user()->service_staff == 1)
                                        <option value="{{auth()->user()->id}}" selected >{{auth()->user()->name}}</option>
                                        @else
                                            @foreach($waiter_list as $waiter)
                                            <option value="{{$waiter->id}}" @if(!empty($waiter_id) && $waiter->id == $waiter_id) selected @endif>
                                                {{$waiter->name}}
                                            </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        @endif

                        <a class="btn btn-primary btn-md more-options" data-toggle="collapse" href="#moreOptions" role="button" aria-expanded="false" aria-controls="moreOptions"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM12.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0ZM18.75 12a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg></a>
                    </div>
                    <div id="pos-collapse">
                        <div id="warehousePanel" class="collapse pos-panel" data-parent="#pos-collapse">
                            <div class="card card-body shadow-sm">

                                <div class="input-group">
                                    @php
                                        if(isset($lims_sale_data) && !empty($lims_sale_data) && $lims_sale_data->warehouse_id) {
                                            $warehouse_id = $lims_sale_data->warehouse_id;
                                        }
                                        elseif($lims_pos_setting_data) {
                                            $warehouse_id = $lims_pos_setting_data->warehouse_id;
                                        }
                                        else{
                                            $warehouse_id = $lims_warehouse_list[0]->id;
                                        }

                                        if(auth()->user()->role_id > 2) {
                                            $warehouse_id = auth()->user()->warehouse_id;
                                        }
                                    @endphp
                                    <select required id="warehouse_id" name="warehouse_id"
                                            class="selectpicker form-control"
                                            data-live-search="true"
                                            data-live-search-style="begins"
                                            title="Select warehouse...">

                                        @foreach($lims_warehouse_list as $warehouse)
                                            <option value="{{$warehouse->id}}"
                                                @if($warehouse->id == $warehouse_id) selected @endif>
                                                {{$warehouse->name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                        </div>
                        <div id="billerPanel" class="collapse pos-panel" data-parent="#pos-collapse">
                            <div class="card card-body shadow-sm">

                                <div class="input-group">
                                    @php
                                        if(isset($lims_sale_data) && !empty($lims_sale_data) && $lims_sale_data->biller_id) {
                                            $biller_id = $lims_sale_data->biller_id;
                                        }
                                        elseif($lims_pos_setting_data) {
                                            $biller_id = $lims_pos_setting_data->biller_id;
                                        }
                                        else{
                                            $biller_id = $lims_biller_list[0]->id;
                                        }
                                        if(auth()->user()->role_id > 2) {
                                            $warehouse_id = auth()->user()->warehouse_id;
                                        }
                                    @endphp
                                    <select required id="biller_id" name="biller_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Biller...">
                                        @foreach($lims_biller_list as $biller)
                                        <option value="{{$biller->id}}" @if($biller->id == $biller_id) selected @endif>{{$biller->name . ' (' . $biller->company_name . ')'}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div id="currencyPanel" class="collapse pos-panel" data-parent="#pos-collapse">
                            <div class="card card-body shadow-sm">
                                <div class="form-group d-flex">
                                    <div class="input-group-prepend">
                                        <select name="currency_id" id="currency" class="form-control selectpicker" data-toggle="tooltip" title="" data-original-title="Sale currency">
                                            @foreach($currency_list as $currency_data)
                                            <option value="{{$currency_data->id}}" data-rate="{{$currency_data->exchange_rate}}">{{$currency_data->code}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <input class="form-control" type="text" id="exchange_rate" name="exchange_rate" value="{{$currency->exchange_rate}}">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><x-info title="currency exchange rate" type="info" /></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="collapse" id="moreOptions" data-parent="#pos-collapse">
                            <div class="card card-body">
                                <div class="row">
                                    <?php
                                        $accountSelection = $role_has_permissions_list->where('name', 'account-selection')->first();
                                    ?>
                                    @if ($accountSelection)
                                        <!-- New Account Selection Field -->
                                        <div class="col-md-3 col-6">
                                            <div class="form-group">
                                                <label>{{__('db.Account')}}</label>
                                                <select required name="account_id" id="account_id" class="selectpicker form-control" data-live-search="true">
                                                    <option value="0" style="color: #A7B49E;">Select an Account</option>
                                                    @foreach($lims_account_list as $account)
                                                        <option value="{{ $account->id }}"
                                                            @if(auth()->user()->account_id == $account->id)
                                                                selected
                                                            @elseif($account->is_default == 1)
                                                                selected
                                                            @endif
                                                        >
                                                            {{ $account->name }}
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="col-md-3">
                                        <label>{{__('db.Sale Reference No')}} <x-info title="Sale reference is auto-generated if not inserted manually" type="info" /></label>
                                        <div class="form-group">
                                            <input type="text" id="reference-no" name="reference_no" class="form-control" placeholder="Type reference number"  />
                                        </div>
                                        <x-validation-error fieldName="reference_no" />
                                    </div>
                                    @foreach($custom_fields as $field)
                                    @if(!$field->is_admin || \Auth::user()->role_id == 1)
                                    <div class="{{'col-md-'.$field->grid_value}}">
                                        <div class="form-group">
                                            <label>{{$field->name}}</label>
                                            @if($field->type == 'text')
                                            <input type="text" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                            @elseif($field->type == 'number')
                                            <input type="number" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                            @elseif($field->type == 'textarea')
                                            <textarea rows="5" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif></textarea>
                                            @elseif($field->type == 'checkbox')
                                            <br>
                                            <?php $option_values = explode(",", $field->option_value); ?>
                                            @foreach($option_values as $value)
                                            <label>
                                                <input type="checkbox" name="{{str_replace(' ', '_', strtolower($field->name))}}[]" value="{{$value}}" @if($value==$field->default_value){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                            </label>
                                            &nbsp;
                                            @endforeach
                                            @elseif($field->type == 'radio_button')
                                            <br>
                                            <?php $option_values = explode(",", $field->option_value); ?>
                                            @foreach($option_values as $value)
                                            <label class="radio-inline">
                                                <input type="radio" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$value}}" @if($value==$field->default_value){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                            </label>
                                            &nbsp;
                                            @endforeach
                                            @elseif($field->type == 'select')
                                            <?php $option_values = explode(",", $field->option_value); ?>
                                            <select class="form-control" name="{{str_replace(' ', '_', strtolower($field->name))}}" @if($field->is_required){{'required'}}@endif>
                                                @foreach($option_values as $value)
                                                <option value="{{$value}}" @if($value==$field->default_value){{'selected'}}@endif>{{$value}}</option>
                                                @endforeach
                                            </select>
                                            @elseif($field->type == 'multi_select')
                                            <?php $option_values = explode(",", $field->option_value); ?>
                                            <select class="form-control" name="{{str_replace(' ', '_', strtolower($field->name))}}[]" @if($field->is_required){{'required'}}@endif multiple>
                                                @foreach($option_values as $value)
                                                <option value="{{$value}}" @if($value==$field->default_value){{'selected'}}@endif>{{$value}}</option>
                                                @endforeach
                                            </select>
                                            @elseif($field->type == 'date_picker')
                                            <input type="text" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control date" @if($field->is_required){{'required'}}@endif>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($lims_pos_setting_data->is_table && !in_array('restaurant',explode(',',$general_setting->modules)))
                    <div class="col-12 pl-0 pr-0">
                        <div class="form-group">
                            <select required id="table_id" name="table_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select table...">
                                @foreach($lims_table_list as $table)
                                <option value="{{$table->id}}">{{$table->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="search-box form-group mb-2" style="border: 1px solid #ddd;border-radius: 10px;box-shadow: rgba(37, 83, 185, 0.1) 0px 2px 6px 0px;position:relative">
                    <div class="input-group pos align-items-center px-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tabler-icon tabler-icon-search icon-search-icon"><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path><path d="M21 21l-6 -6"></path></svg>
                        <input style="border:none;height: 46px;" type="text" name="product_code_name" id="product-search-input" placeholder="Scan/Search product by name/code/IMEI" class="form-control" autofocus />
                        <button type="button" class="btn btn-primary" onclick="barcode()"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-upc" viewBox="0 0 16 16"><path d="M3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0z"/></svg></button>
                    </div>
                    <div id="product-results-container">

                    </div>
                    <div id="no-results-message" style="background-color: #f5f6f7;color: #666; margin-top: 5px;padding: 3px 5px; display: none;">No results found</div>
                </div>
                <div class="table-responsive transaction-list" style="background:#FFF; border-radius: 10px; box-shadow: rgba(37, 83, 185, 0.1) 0px 2px 6px 0px;">
                    <table id="myTable" class="table table-hover table-striped order-list table-fixed">
                        <thead class="d-none d-md-block">
                            <tr>
                                <th class="col-sm-5 col-6">{{__('db.product')}}</th>
                                <th class="col-sm-2">{{__('db.Price')}}</th>
                                <th class="col-sm-3 text-center">{{__('db.Quantity')}}</th>
                                <th class="col-sm-2">{{__('db.Subtotal')}}</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-id">

                        </tbody>
                    </table>
                </div>
                <div class="row" style="display: none;">
                    <div class="col-md-2">
                        <div class="form-group">
                            <input type="hidden" name="total_qty" value="0" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <input type="hidden" name="total_discount" value="{{number_format(0, $general_setting->decimal, '.', '')}}" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <input type="hidden" name="total_tax" value="{{number_format(0, $general_setting->decimal, '.', '')}}" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <input type="hidden" name="total_price" value="{{number_format(0, $general_setting->decimal, '.', '')}}" />
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <input type="hidden" name="item" value="0" />
                            <input type="hidden" name="order_tax" value="{{number_format(0, $general_setting->decimal, '.', '')}}" />
                        </div>
                        <x-validation-error fieldName="item" />
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <input type="hidden" name="grand_total" value="{{number_format(0, $general_setting->decimal, '.', '')}}"/>
                            <input type="hidden" name="used_points" />

                            @if(in_array('restaurant',explode(',',$general_setting->modules)))
                            <input type="hidden" name="sale_status" value="5" />
                            @else
                            <input type="hidden" name="sale_status" value="1" />
                            @endif
                            <x-validation-error fieldName="sale_status" />

                            <input type="hidden" name="coupon_active">
                            <input type="hidden" name="coupon_id" value="">
                            <input type="hidden" name="coupon_discount" value="0"/>

                            <input type="hidden" name="pos" value="1" />

                            @if(isset($lims_sale_data) && !empty($lims_sale_data))
                            <input type="hidden" name="sale_id" value="{{$lims_sale_data->id}}" />
                            <input type="hidden" name="draft" value="1" />
                            @else
                            <input type="hidden" name="draft" value="0" />
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12 totals" style="background-color:#f5f6f7;border-top: 2px solid #ebe9f1;padding-bottom: 7px;padding-top: 7px;">
                    <div class="row">
                        <div class="col-sm-6 col-6"></div>
                        <div class="col-sm-3 col-6">
                            <strong class="totals-title">{{__('db.Items')}}</strong><strong id="item">0 (0)</strong>
                        </div>
                        <div class="col-sm-3 col-6">
                            <strong class="totals-title">{{__('db.Total')}}</strong><strong id="subtotal">{{number_format(0, $general_setting->decimal, '.', '')}}</strong>
                        </div>
                        @if ($handle_discount_active)
                            <div class="col-sm-3 col-6">
                                <strong class="totals-title">{{__('db.Discount')}} <button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#order-discount-modal"> <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></button></strong><strong id="discount">{{number_format(0, $general_setting->decimal, '.', '')}}</strong>
                            </div>
                        @endif
                        <div class="col-sm-3 col-6">
                            <strong class="totals-title">{{__('db.Coupon')}} <button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#coupon-modal"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></button></strong><strong id="coupon-text">{{number_format(0, $general_setting->decimal, '.', '')}}</strong>
                        </div>
                        <div class="col-sm-3 col-6">
                            <strong class="totals-title">{{__('db.Tax')}} <button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#order-tax"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></button></strong><strong id="tax">{{number_format(0, $general_setting->decimal, '.', '')}}</strong>
                        </div>
                        <div class="col-sm-3 col-6">
                            <strong class="totals-title">{{__('db.Shipping')}} <button type="button" class="btn btn-link btn-sm" data-toggle="modal" data-target="#shipping-cost-modal"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg></button></strong><strong id="shipping-cost">{{number_format(0, $general_setting->decimal, '.', '')}}</strong>
                        </div>
                    </div>
                </div>

                <div class="payment-amount d-none d-md-block">
                    <h2>{{__('db.grand total')}} <span id="grand-total">{{number_format(0, $general_setting->decimal, '.', '')}}</span></h2>
                </div>
                <div class="payment-options">
                    <div class="column-5 more-payment-options">
                        <div class="btn-group dropup">
                            <button type="button" class="btn btn-primary btn-custom  dropdown-toggle d-md-none" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg> Pay <span id="grand-total-m"></span>
                            </button>
                            <div class="">
                                @if(in_array("card",$options))
                                <div class="column-5">
                                    <button style="background: #0984e3" type="button" class="btn btn-sm btn-custom payment-btn" data-toggle="modal" data-target="#add-payment" id="credit-card-btn" disabled="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" /></svg> {{__('db.Card')}}</button>
                                </div>
                                @endif
                                @if(in_array("cash",$options))
                                <div class="column-5">
                                    <button style="background: #00cec9" type="button" class="btn btn-sm btn-custom payment-btn" data-toggle="modal" data-target="#add-payment" id="cash-btn" disabled="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg> {{__('db.Cash')}}</button>
                                </div>
                                @endif
                                @if(in_array("razorpay",$options))
                                <div class="column-5">
                                    <button style="background: #2d2d2d" type="button"
                                        class="btn btn-sm btn-custom payment-btn"
                                        data-toggle="modal" data-target="#add-payment"
                                        id="razorpay-btn" disabled="true">
                                        Razorpay
                                    </button>
                                </div>
                                @endif
                                @if(in_array("credit",$options))
                                <div class="column-5">
                                    <button style="background: #f05969" type="button" class="btn btn-sm btn-custom payment-btn" data-toggle="modal" data-target="#add-payment" id="credit-sale-btn" disabled="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg> {{__('db.Credit Sale')}}</button>
                                </div>
                                @endif

                                <div class="column-5">
                                    <button style="background: #010429" type="button" class="btn btn-sm btn-custom payment-btn" data-toggle="modal" data-target="#add-payment" id="multiple-payment-btn" disabled="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg> {{__('db.Multiple Payment')}}</button>
                                </div>
                                @if(in_array("installment",$options))
                                <div class="column-5">
                                    <button type="button" class="btn btn-sm btn-warning" disabled="true" id="installmentPlanBtn">
                                        <i class="bi bi-credit-card"></i> {{__('db.Instalment')}}
                                    </button>
                                </div>
                                @endif
                                @if(in_array("cheque",$options))
                                <div class="column-5">
                                    <button style="background-color: #fd7272" type="button" class="btn btn-sm btn-block btn-custom payment-btn" data-toggle="modal" data-target="#add-payment" id="cheque-btn" disabled="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg> {{__('db.Cheque')}}</button>
                                </div>
                                @endif
                                @if(in_array("gift_card",$options))
                                <div class="column-5">
                                    <button style="background-color: #5f27cd" type="button" class="btn btn-sm btn-block btn-custom payment-btn" data-toggle="modal" data-target="#add-payment" id="gift-card-btn" disabled="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg> {{__('db.Gift Card')}}</button>
                                </div>
                                @endif
                                @if(in_array("deposit",$options))
                                <div class="column-5">
                                    <button style="background-color: #b33771" type="button" class="btn btn-sm btn-block btn-custom payment-btn" data-toggle="modal" data-target="#add-payment" id="deposit-btn" disabled="true"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bank" viewBox="0 0 16 16"><path d="m8 0 6.61 3h.89a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H15v7a.5.5 0 0 1 .485.38l.5 2a.498.498 0 0 1-.485.62H.5a.498.498 0 0 1-.485-.62l.5-2A.5.5 0 0 1 1 13V6H.5a.5.5 0 0 1-.5-.5v-2A.5.5 0 0 1 .5 3h.89zM3.777 3h8.447L8 1zM2 6v7h1V6zm2 0v7h2.5V6zm3.5 0v7h1V6zm2 0v7H12V6zM13 6v7h1V6zm2-1V4H1v1zm-.39 9H1.39l-.25 1h13.72z"/></svg> {{__('db.Deposit')}}</button>
                                </div>
                                @endif
                                @if(in_array("points",$options))
                                    @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                    <div class="column-5">
                                        <button style="background-color: #319398" type="button" class="btn btn-sm btn-block btn-custom payment-btn" data-toggle="modal" data-target="#add-payment" id="point-btn" disabled="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" /></svg> {{__('db.Points')}}</button>
                                    </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    <?php
                        $fixed_methods = ['cash', 'card', 'cheque', 'gift_card', 'deposit', 'pesapal'];
                        $payment_methods = explode(',', $lims_pos_setting_data->payment_options);

                        $payment_methods = array_diff($payment_methods, $fixed_methods);
                        $payment_methods = array_values($payment_methods);
                    ?>
                    @if (count($payment_methods))
                    <div class="column-5">
                        <div class="btn-group" role="group">
                            <button id="btn-more" type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                More
                            </button>
                            <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                @foreach ($payment_methods as $method)
                                    <button id="pay-method" class="dropdown-item pay-options payment-btn" type="button"  data-toggle="modal" data-target="#add-payment" value="{{ $method }}" disabled="true">{{ ucfirst($method) }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="column-5">
                        <button style="background-color: #e28d02" type="button" class="btn btn-sm btn-custom" id="draft-btn"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 21v-6m0 0 2.77-.693a9 9 0 0 1 6.208.682l.108.054a9 9 0 0 0 6.086.71l3.114-.732a48.524 48.524 0 0 1-.005-10.499l-3.11.732a9 9 0 0 1-6.085-.711l-.108-.054a9 9 0 0 0-6.208-.682L3 4.5M3 15V4.5" /></svg> {{__('db.Draft')}}</button>
                    </div>
                    <div class="column-5">
                        <button style="background-color: #d63031;" type="button" class="btn btn-sm btn-custom" id="cancel-btn" onclick="return confirmCancel()"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg> {{__('db.Cancel')}}</button>
                    </div>
                    <div class="column-5">
                        <button style="background-color: #ffc107;" type="button" class="btn btn-sm btn-custom" data-toggle="modal" data-target="#recentTransaction"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg> {{__('db.Recent Transaction')}}</button>
                    </div>
                </div>

