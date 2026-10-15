@extends('backend.layout.main')

@push('css')
<style>
/* ── Product Help Modal fix ──────────────────────────────────────────
   Theme sets .modal{overflow:scroll} which scrolls the dialog off-screen
   during Bootstrap's fade-in translate(0,-25%) start state.
   The modal is moved to <body> via JS (appendTo) at show time to escape
   all parent stacking/overflow contexts. These rules keep it clean. ── */
#productHelpModal {
    overflow-y: auto !important;
}
#productHelpModal .modal-dialog {
    margin: 1.75rem auto !important;
}
</style>
@endpush

@if(in_array('ecommerce',explode(',',$general_setting->modules)) || in_array('restaurant',explode(',',$general_setting->modules)))
@push('css')
<style>
.search_result, .search_result_addon {border:1px solid #e4e6fc;border-radius:5px;overflow-y: scroll;}
.search_result > div, .search_result_addon > div, .selected_items > div, .selected_addons > div {border-top:1px solid #e4e6fc;cursor:pointer;display:flex;align-items:center;padding: 10px;position: relative;}
.search_result > div > img, .search_result_addon > div > img, .selected_items > div > img, .selected_addons > div > img {margin-right: 10px;max-width: 40px;}
.search_result > div h4, .search_result_addon > div h4, .selected_items > div h4, .selected_addons > div h4 {font-size: 0.9rem;}
.search_result > div i,  .search_result_addon > div i, {color:#54b948;position:absolute;right:5px;top:30%}
.search_result div:first-child, .search_result_addon div:first-child, {border-top:none}
.selected_items .remove_item, .selected_addons .remove_item {position: absolute;right: 20px;top:20px};
.delVarOption{display: flex;flex-direction: column;align-items: center;}
</style>
@endpush
@endif

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4>{{__('db.add_product')}}</h4>
                        <button type="button" class="btn btn-outline-info btn-sm" id="openProductHelp">
                            <i class="dripicons-question"></i> Field Help
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <div id="alert-container" style="display:none;" class="alert alert-dismissible fade show" role="alert">
                            <span id="alert-message"></span>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="product-form">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Product Type')}} *</strong> </label>
                                        <div class="input-group">
                                            <select name="type" required class="form-control selectpicker" id="type">
                                                <option value="standard">Standard</option>
                                                <option value="combo">Combo</option>
                                                <option value="digital">Digital</option>
                                                <option value="service">Service</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Product Name')}} *</strong> </label>
                                        <input type="text" name="name" class="form-control" id="name" aria-describedby="name" required>
                                        <span class="validation-msg" id="name-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Product Code')}} *</strong> </label>
                                        <div class="input-group">
                                            <input type="text" name="code" class="form-control" id="code" aria-describedby="code" required>
                                            <div class="input-group-append">
                                                <button id="genbutton" type="button" class="btn btn-sm btn-default" title="{{__('db.Generate')}}"><i class="fa fa-refresh"></i></button>
                                            </div>
                                        </div>
                                        <span class="validation-msg" id="code-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Barcode Symbology')}} *</strong> </label>
                                        <div class="input-group">
                                            <select name="barcode_symbology" required class="form-control selectpicker">
                                                <option value="C128">Code 128</option>
                                                <option value="C39">Code 39</option>
                                                <option value="UPCA">UPC-A</option>
                                                <option value="UPCE">UPC-E</option>
                                                <option value="EAN8">EAN-8</option>
                                                <option value="EAN13">EAN-13</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="digital" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Attach File')}} *</strong> </label>
                                        <div class="input-group">
                                            <input type="file" id="file" name="file" class="form-control">
                                        </div>
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div id="combo" class="col-md-12 mb-1">
                                    <label>{{__('db.add_product')}}</label>
                                    <div class="search-box input-group mb-3">
                                        <button class="btn btn-secondary"><i class="fa fa-barcode"></i></button>
                                        <input type="text" name="product_code_name" id="lims_productcodeSearch" placeholder="{{ __('db.Please type product code and select') }}" class="form-control" />
                                    </div>
                                    <label>{{__('db.Combo Products')}}</label>
                                    <div class="table-responsive">
                                        <table id="myTable" class="table table-hover order-list">
                                            <thead>
                                                <tr>
                                                    <th>{{__('db.product')}}</th>
                                                    <th>{{__('db.Wastage Percent')}}</th>
                                                    <th>{{__('db.Quantity')}}</th>
                                                    <th>{{__('db.Unit Cost')}}</th>
                                                    <th>{{__('db.Unit Price')}}</th>
                                                    <th>{{__('db.Sub total')}}</th>
                                                    <th><i class="dripicons-trash"></i></th>
                                                </tr>

                                            </thead>
                                            <tbody class="combo_product_list_table">

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                {{-- <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Production Cost')}}</strong> </label>
                                        <div class="input-group pos">
                                            <input type="text" name="production_cost" class="form-control" placeholder="Enter production">
                                      </div>
                                    </div>
                                </div> --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Brand')}}</strong> </label>
                                        <div class="input-group pos">
                                          <select name="brand_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Brand...">
                                            @foreach($lims_brand_list as $brand)
                                                <option value="{{$brand->id}}">{{$brand->title}}</option>
                                            @endforeach
                                          </select>
                                          <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#addBrand"><i class="dripicons-plus"></i></button>
                                      </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.category')}} *</strong> </label>
                                        <div class="input-group pos">
                                          <select name="category_id" required class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select Category...">
                                            @foreach($lims_category_list as $category)
                                                <option value="{{$category->id}}">{{$category->name}}</option>
                                            @endforeach
                                          </select>
                                          <div class="input-group-append">
                                            <button type="button" class="btn btn-default btn-sm category-model" data-toggle="modal" data-target="#category-modal"><i class="dripicons-plus"></i></button>
                                          </div>
                                        </div>
                                      <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div id="unit" class="col-md-12">
                                    <div class="row ">
                                        <div class="col-md-4 form-group">
                                                <label>{{__('db.Product Unit')}} *
                                                    <i class="dripicons-information" style="font-size:.8rem;color:#6c757d;cursor:help"
                                                       data-toggle="tooltip" data-placement="top"
                                                       title="Only base units appear here (units with no parent unit). Derived units such as Dozen or Box will appear in Sale &amp; Purchase Unit once you select a base unit."></i>
                                                </strong> </label>
                                                <div class="input-group pos">
                                                  <select required class="form-control selectpicker" name="unit_id">
                                                    <option value="" disabled selected>Select Product Unit...</option>
                                                    @foreach($lims_unit_list as $unit)
                                                        @if($unit->base_unit==null)
                                                            <option value="{{$unit->id}}">{{$unit->unit_name}}</option>
                                                        @endif
                                                    @endforeach
                                                  </select>
                                                  <div class="input-group-append">
                                                    <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#createUnitModal"><i class="dripicons-plus"></i></button>
                                                  </div>
                                              </div>
                                              <span class="validation-msg"></span>
                                        </div>
                                        <div class="col-md-4">
                                                <label>{{__('db.Sale Unit')}}</strong> </label>
                                                <div class="input-group pos">
                                                  <select class="form-control selectpicker" name="sale_unit_id">
                                                  </select>
                                              </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{__('db.Purchase Unit')}}</strong> </label>
                                                <div class="input-group pos">
                                                    <select class="form-control selectpicker" name="purchase_unit_id">
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="cost" class="col-md-4">
                                     <div class="form-group">
                                        <label>{{__('db.Product Cost')}} *</strong> </label>
                                        <input type="number" name="cost" required class="form-control" step="any">
                                        <div class="alert alert-warning very-small-text d-none p-2 position-absolute" id="product-cost-warning">
                                            Cost must be higher than 0!
                                        </div>
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.profit_margin_type') }}</label>
                                        <select name="profit_margin_type" class="form-control" required>

                                            <option value="percentage"
                                                {{ $general_setting->margin_type == 2 ? 'selected' : '' }}>
                                                Percentage (%)
                                            </option>

                                            <option value="flat"
                                                {{ $general_setting->margin_type == 1 ? 'selected' : '' }}>
                                                Flat
                                            </option>
                                        </select>
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                <div id="profit_margin" class="col-md-4">
                                     <div class="form-group">
                                        <label>{{__('db.Profit Margin')}}</strong> </label>
                                        <input type="number" name="profit_margin" value="0" required class="form-control" step="0.01">
                                        <span class="validation-msg"></span>
                                    </div>
                                </div>
                                {{-- 3.4 Item-based commission --}}
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Commission % <small class="text-muted">(per item sold)</small></label>
                                        <input type="number" name="commission_percent" value=""
                                            class="form-control" step="0.01" min="0" max="100"
                                            placeholder="e.g. 2.5">
                                        <small class="text-muted">Applied during payroll commission calculation.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Product Price')}} *</strong> </label>
                                        <input type="number" name="price" required class="form-control" step="any">
                                        <div class="alert alert-warning very-small-text d-none p-2 position-absolute" id="product-price-warning">
                                            Price must be higher than Cost to make Profit!
                                        </div>
                                        <span class="validation-msg"></span>
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" name="qty" value="{{number_format(0, $general_setting->decimal, '.', '')}}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Wholesale Price')}}</strong> </label>
                                        <input type="number" name="wholesale_price" class="form-control" step="any">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Daily Sale Objective')}}</strong></label> <i class="dripicons-question" data-toggle="tooltip" title="{{__('db.Minimum qty which must be sold in a day If not, you will be notified on dashboard But you have to set up the cron job properly for that Follow the documentation in that regard')}}"></i>
                                        <input type="number" name="daily_sale_objective" class="form-control" step="any">
                                    </div>
                                </div>
                                <div id="alert-qty" class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Alert Quantity')}}</strong> </label>
                                        <input type="number" name="alert_quantity" class="form-control" step="any">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Product Tax')}}</label>
                                        <div class="input-group pos">

                                        <select name="tax_id" class="selectpicker form-control" style="width: 100px">
                                            <option value="">No Tax</option>
                                            @foreach($lims_tax_list as $tax)
                                                <option value="{{$tax->id}}">{{$tax->name}}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#addTax"><i class="dripicons-plus"></i></button>
                                    </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{__('db.Tax Method')}}</strong> </label> <i class="dripicons-question" data-toggle="tooltip" title="{{__('db.Exclusive: Poduct price = Actual product price + Tax Inclusive: Actual product price = Product price - Tax')}}"></i>
                                        <select name="tax_method" class="form-control selectpicker">
                                            <option value="1">{{__('db.Exclusive')}}</option>
                                            <option value="2">{{__('db.Inclusive')}}</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Warranty and Guarantee [20-01-2025] -->
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Warranty') }}</label>
                                        <div class="d-flex justify-content-between">
                                            <input type="number" name="warranty" min="1" class="form-control" style="width: 48%;" placeholder="{{ __('db.eg: 1') }}">
                                            <select name="warranty_type" class="form-control selectpicker" style="width: 48%;">
                                                <option value="days">{{ __('db.Days') }}</option>
                                                <option value="months" selected>{{ __('db.Months') }}</option>
                                                <option value="years">{{ __('db.Years') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>{{ __('db.Guarantee') }}</label>
                                        <div class="d-flex justify-content-between">
                                            <input type="number" name="guarantee" min="1" class="form-control" style="width: 48%;" placeholder="{{ __('db.eg: 1') }}">
                                            <select name="guarantee_type" class="form-control selectpicker" style="width: 48%;">
                                                <option value="days">{{ __('db.Days') }}</option>
                                                <option value="months" selected>{{ __('db.Months') }}</option>
                                                <option value="years">{{ __('db.Years') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Warranty and Guarantee end -->

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
                                                        <input type="checkbox" name="{{str_replace(' ', '_', strtolower($field->name))}}[]" value="{{$value}}" @if($value == $field->default_value){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                    </label>
                                                    &nbsp;
                                                @endforeach
                                            @elseif($field->type == 'radio_button')
                                                <br>
                                                <?php $option_values = explode(",", $field->option_value); ?>
                                                @foreach($option_values as $value)
                                                    <label class="radio-inline">
                                                        <input type="radio" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$value}}" @if($value == $field->default_value){{'checked'}}@endif @if($field->is_required){{'required'}}@endif> {{$value}}
                                                    </label>
                                                    &nbsp;
                                                @endforeach
                                            @elseif($field->type == 'select')
                                                <?php $option_values = explode(",", $field->option_value); ?>
                                                <select class="form-control" name="{{str_replace(' ', '_', strtolower($field->name))}}" @if($field->is_required){{'required'}}@endif>
                                                    @foreach($option_values as $value)
                                                        <option value="{{$value}}" @if($value == $field->default_value){{'selected'}}@endif>{{$value}}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($field->type == 'multi_select')
                                                <?php $option_values = explode(",", $field->option_value); ?>
                                                <select class="form-control" name="{{str_replace(' ', '_', strtolower($field->name))}}[]" @if($field->is_required){{'required'}}@endif multiple>
                                                    @foreach($option_values as $value)
                                                        <option value="{{$value}}" @if($value == $field->default_value){{'selected'}}@endif>{{$value}}</option>
                                                    @endforeach
                                                </select>
                                            @elseif($field->type == 'date_picker')
                                                <input type="date" name="{{str_replace(' ', '_', strtolower($field->name))}}" value="{{$field->default_value}}" class="form-control" @if($field->is_required){{'required'}}@endif>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                                <div id="featured" class="col-md-4">
                                    <div class="form-group mt-3">
                                        <input type="checkbox" name="featured" value="1">&nbsp;
                                        <label>{{__('db.Featured')}}</label>
                                        <p class="italic">{{__('db.Featured product will be displayed in POS')}}</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group mt-3">
                                        <input type="checkbox" name="is_embeded" value="1">&nbsp;
                                        <label>{{__('db.Embedded Barcode')}}</label>
                                        <p class="italic">{{__('db.Check this if this product will be used in weight scale machine')}}</p>
                                    </div>
                                </div>
                                <div id="stock-section" class="col-md-12">
                                    <div class="form-group mt-3">
                                        <input type="checkbox" name="is_initial_stock" value="1">&nbsp;
                                        <label>{{__('db.Initial Stock')}}</label>
                                        <p class="italic">{{__('db.This feature will not work for product with variants and batches')}}</p>
                                    </div>
                                </div>
                                <div class="col-md-12" id="initial-stock-section">
                                    <div class="table-responsive ml-2">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{__('db.Warehouse')}}</th>
                                                    <th>{{__('db.qty')}}</th>
                                                    <th>Location Barcode <small class="text-muted">(3.8)</small></th>
                                                </tr>
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="stock_warehouse_id[]" value="{{$warehouse->id}}">
                                                        {{$warehouse->name}}
                                                    </td>
                                                    <td><input type="number" name="stock[]" min="0" class="form-control"></td>
                                                    <td><input type="text" name="location_barcode[]" class="form-control" placeholder="Optional per-location barcode"></td>
                                                </tr>
                                                @endforeach
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.Product Image')}}</strong> </label> <i class="dripicons-question" data-toggle="tooltip" title="{{__('db.You can upload multiple image Only jpeg, jpg, png, gif file can be uploaded First image will be base image')}}"></i>
                                        <div id="imageUpload" class="dropzone"></div>
                                        <span class="validation-msg" id="image-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>{{__('db.Product Details')}}</label>
                                        <textarea name="product_details" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3" id="variant-option">
                                    <h5><input name="is_variant" type="checkbox" id="is-variant" value="1">&nbsp; {{__('db.This product has variant')}}</h5>
                                </div>
                                <div class="col-md-12" id="variant-section">
                                    <div id="variant-input-section">
                                        <div class="row">
                                            <div class="col-md-4 form-group mt-2">
                                                <label>{{__('db.Option')}} *</label>
                                                <input type="text" name="variant_option[]" class="form-control variant-field" placeholder="{{ __('db.Size, Color etc') }}">
                                            </div>
                                            <div class="col-md-7 form-group mt-2">
                                                <label>{{__('db.Value')}} *</label>
                                                <input type="text" name="variant_value[]" class="type-variant form-control variant-field">
                                            </div>
                                            <div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;">
                                                <button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="dripicons-cross"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 form-group">
                                        <button type="button" class="btn btn-info add-more-variant"><i class="dripicons-plus"></i> {{__('db.Add More Variant')}}</button>
                                    </div>
                                    <div class="table-responsive ml-2">
                                        <table id="variant-table" class="table table-hover variant-list">
                                            <thead>
                                                <tr>
                                                    <th>{{__('db.name')}}</th>
                                                    <th>{{__('db.Item Code')}}</th>
                                                    <th>{{__('db.Additional Cost')}}</th>
                                                    <th>{{__('db.Additional Price')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-2" id="diffPrice-option">
                                    <h5><input name="is_diffPrice" type="checkbox" id="is-diffPrice" value="1">&nbsp; {{__('db.This product has different price for different warehouse')}}</h5>
                                </div>
                                <div class="col-md-6" id="diffPrice-section">
                                    <div class="table-responsive ml-2">
                                        <table id="diffPrice-table" class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>{{__('db.Warehouse')}}</th>
                                                    <th>{{__('db.Price')}}</th>
                                                </tr>
                                                @foreach($lims_warehouse_list as $warehouse)
                                                <tr>
                                                    <td>
                                                        <input type="hidden" name="warehouse_id[]" value="{{$warehouse->id}}">
                                                        {{$warehouse->name}}
                                                    </td>
                                                    <td><input type="number" name="diff_price[]" class="form-control"></td>
                                                </tr>
                                                @endforeach
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-12 mt-3" id="batch-option">
                                    <h5><input name="is_batch" type="checkbox" id="is-batch" value="1">&nbsp; {{__('db.This product has batch and expired date')}}</h5>
                                </div>
                                <div class="col-md-12 mt-3" id="imei-option">
                                    <h5><input name="is_imei" type="checkbox" id="is-imei" value="1">&nbsp; {{__('db.This product has IMEI or Serial numbers')}}</h5>
                                </div>
                                <div class="col-md-12 mt-3">
                                    <h5><input name="promotion" type="checkbox" id="promotion" value="1">&nbsp; {{__('db.Add Promotional Price')}}</h5>
                                </div>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-md-4" id="promotion_price">
                                            <label>{{__('db.Promotional Price')}}</label>
                                            <input type="number" name="promotion_price" class="form-control" step="any" />
                                        </div>
                                        <div class="col-md-4" id="start_date">
                                            <div class="form-group">
                                                <label>{{__('db.Promotion Starts')}}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text"><i class="dripicons-calendar"></i></div>
                                                    </div>
                                                    <input type="text" name="starting_date" id="starting_date" class="form-control" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4" id="last_date">
                                            <div class="form-group">
                                                <label>{{__('db.Promotion Ends')}}</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <div class="input-group-text"><i class="dripicons-calendar"></i></div>
                                                    </div>
                                                    <input type="text" name="last_date" id="ending_date" class="form-control" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (\Schema::hasColumn('products', 'woocommerce_product_id'))
                                <div class="col-md-12 mt-3">
                                    <h5><input name="is_sync_disable" type="checkbox" id="is_sync_disable" value="1">&nbsp; {{__('db.Disable Woocommerce Sync')}}</h5>
                                </div>
                                @endif

                                @if(in_array('ecommerce',explode(',',$general_setting->modules)) || in_array('restaurant',explode(',',$general_setting->modules)))
                                <div class="col-md-12 mt-3">
                                    <h5><input name="is_online" type="checkbox" id="is_online" value="1" checked>&nbsp; {{__('db.Sell Online')}}</h5>
                                </div>
                                @endif

                                @if(in_array('restaurant',explode(',',$general_setting->modules)))
                                <div class="col-md-12 mt-3">
                                    <h5><input name="is_addon" type="checkbox" id="is_addon" value="1">&nbsp; {{__('db.This is topping')}} <i class="dripicons-question" data-toggle="tooltip" title="{{__('db.Check this if the item is a topping or extra or add-on only to be served with a main course')}}"></i></h5>
                                </div>
                                @endif

                                @if(in_array('ecommerce',explode(',',$general_setting->modules)))
                                <div class="col-md-12 mt-3">
                                    <h5><input name="in_stock" type="checkbox" id="in_stock" value="1" checked>&nbsp; {{__('db.In Stock')}}</h5>
                                </div>
                                <!-- <div class="col-md-12 mt-3 track_inventory" style="display:none">
                                    <h5><input name="track_inventory" type="checkbox" id="track_inventory" value="0">&nbsp; {{__('db.Track Inventory')}}</h5>
                                </div> -->
                                @endif
                            </div>
                            @if(in_array('ecommerce',explode(',',$general_setting->modules)) || in_array('restaurant',explode(',',$general_setting->modules)))
                            <div class="row">
                                <div class="col-12 mt-3">
                                    <div class="form-group">
                                        <label>{{__('db.Product Tags')}}</strong> </label>
                                        <input type="text" name="tags" class="form-control" value="">
                                        <span class="validation-msg" id="tags-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <br/>
                                    <h6>For SEO</h6>
                                    <br>
                                </div>
                                <div class="col-md-12 form-group">
                                    <label>{{ __('Meta Title') }} *</label>
                                    <input type="text" name="meta_title" class="form-control" value="">
                                </div>
                                <div class="col-md-12 form-group">
                                    <label>{{ __('Meta Description') }} *</label>
                                    <input type="text" name="meta_description" class="form-control" value="">
                                </div>
                                <div class="col-md-12 form-group related-section">
                                    <label>{{__('db.Related Products')}}</label>
                                    <input type="text" id="search_products" class="form-control">
                                    <div class="search_result"></div>
                                    <h4 class="mt-5 mb-3">Selected Items</h4>
                                    <div class="selected_items"></div>
                                    <textarea class="selected_ids hidden no-tiny" name="products"></textarea>
                                </div>
                            </div>
                            @endif

                            @if(in_array('restaurant',explode(',',$general_setting->modules)))
                            <div class="row">
                                <div class="col-md-12 form-group extra-section">
                                    <label>{{__('db.Extras')}}</label>
                                    <input type="text" id="search_addons" class="form-control">
                                    <div class="search_result_addon"></div>
                                    <h4 class="mt-5 mb-3">Selected Extras</h4>
                                    <div class="selected_addons"></div>
                                    <textarea class="selected_addon_ids hidden no-tiny" name="extras"></textarea>
                                </div>
                                <div class="col-md-4 col-6">
                                    <div class="form-group top-fields">
                                        <label>{{__('db.Kitchen')}}</label>
                                        <div class="input-group pos">
                                            <select id="kitchen_id" name="kitchen_id" class="selectpicker form-control" title="Select kitchen...">
                                                @foreach($kitchen_list as $kitchen)
                                                <option value="{{$kitchen->id}}">{{$kitchen->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-6">
                                    <div class="form-group top-fields">
                                        <label>{{__('db.Menu Type')}}</label>
                                        <div class="input-group pos">
                                            <select required id="menu_type" name="menu_type[]" class="selectpicker form-control" multiple>
                                                @foreach($menu_type_list as $menu_type)
                                                <option value="{{$menu_type->id}}">{{$menu_type->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            <div class="form-group mt-3">
                                <button type="submit" id="submit-btn" class="btn btn-primary">{{__('db.add_product')}}</button>
                                <button type="submit" id="submit-and-insert-btn" class="btn btn-info">Save and Insert Another</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Brand Create Modal Start -->
    <div id="addBrand" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
          <div class="modal-content">
            <form id="brand-form">
            <div class="modal-header">
              <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Brand')}}</h5>
              <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
              <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <div class="form-group">
                    <label>{{__('db.Title')}} *</label>
                    <input type="text" name="title" class="form-control" placeholder="{{ __('db.Type brand title') }}" required>
                </div>
                <div class="form-group">
                    <label>{{__('db.Image')}}</label>
                    <input type="file" name="image" class="form-control">
                </div>
                @if(in_array('ecommerce',explode(',',$general_setting->modules)) || in_array('restaurant',explode(',',$general_setting->modules)))
                <div class="row">
                    <div class="col-md-12 mt-3">
                        <h6><strong>{{ __('For SEO') }}</strong></h6>
                        <hr>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ __('Meta Title') }}</label>
                        <input type="text" name="page_title" class="form-control" placeholder="{{ __('db.Meta Title') }}">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>{{ __('Meta Description') }}</label>
                        <input type="text" name="short_description" class="form-control" placeholder="{{ __('db.Meta Description') }}">
                    </div>
                </div>
                @endif
                <div class="form-group">
                    <input type="hidden" name="ajax" value="1">
                    <button type="button" class="btn btn-primary brand-submit-btn">{{__('db.submit')}}</button>
                </div>
            </div>
            </form>
          </div>
        </div>
    </div>
    <!-- Brand Create Modal End -->
    <!-- Tax Create Modal Start -->
    <div id="addTax" tabindex="-1" role="dialog" aria-labelledby="addTaxLabel" aria-hidden="true" class="modal fade text-left">
        <div role="document" class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('tax.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 id="addTaxLabel" class="modal-title">{{__('db.Add Tax')}}</h5>
                        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
                    </div>
                    <div class="modal-body">
                        <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                        <div class="form-group">
                            <label>{{__('db.Tax Name')}} *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>{{__('db.Rate')}}(%) *</label>
                            <input type="number" name="rate" class="form-control" required step="any">
                        </div>
                        <input type="hidden" name="ajax" value="1">
                        <button type="button" class="btn btn-primary tax-submit-btn">{{__('db.submit')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Tax Create Modal End -->

    <!-- Unit Create Modal Start -->
    @include('backend.unit.add_unit_modal')

    <!-- ══════════════════════════════════════════════
         PRODUCT FIELD HELP MODAL
    ══════════════════════════════════════════════ -->
    <div class="modal fade" id="productHelpModal" tabindex="-1" role="dialog" aria-labelledby="productHelpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document" style="max-width:92%;width:960px">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="productHelpModalLabel">
                        <i class="dripicons-question"></i> &nbsp;Product Form — Field Reference Guide
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="max-height:78vh;overflow-y:auto;font-size:.92rem">

                    {{-- Nav tabs --}}
                    <ul class="nav nav-tabs mb-3" id="helpTabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#help-basics">Basics</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#help-pricing">Pricing & Tax</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#help-stock">Stock & Units</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#help-variants">Variants & Batches</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#help-advanced">Advanced</a></li>
                    </ul>

                    <div class="tab-content">

                        {{-- ── BASICS ──────────────────────────────────────────── --}}
                        <div class="tab-pane fade show active" id="help-basics">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr><th style="width:20%">Field</th><th style="width:40%">What it does</th><th>Example</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Product Type</strong> <span class="badge badge-danger">Required</span></td>
                                            <td>
                                                Controls which fields appear and how the product behaves.<br>
                                                <b>Standard</b> – physical item tracked in stock (most common).<br>
                                                <b>Combo</b> – bundle of other existing products sold together; cost/price auto-calculated from components.<br>
                                                <b>Digital</b> – downloadable file (software, e-book); no stock tracking.<br>
                                                <b>Service</b> – labour or service (haircut, repair); no stock tracking.
                                            </td>
                                            <td>
                                                A bottle of water → <code>Standard</code><br>
                                                A "Gift Basket" containing 3 items → <code>Combo</code><br>
                                                A PDF manual → <code>Digital</code><br>
                                                Installation service → <code>Service</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Product Name</strong> <span class="badge badge-danger">Required</span></td>
                                            <td>The customer-facing name shown on receipts, POS, and the online store.</td>
                                            <td><code>Coca-Cola 500ml</code> &nbsp;|&nbsp; <code>Men's Running Shoes – Size 42</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Product Code</strong> <span class="badge badge-danger">Required</span></td>
                                            <td>
                                                Unique SKU used for searching and barcode printing. Click <i class="fa fa-refresh"></i> to auto-generate one.<br>
                                                Must be <em>numeric only</em> if barcode type is UPC-A, UPC-E, EAN-8, or EAN-13.
                                            </td>
                                            <td><code>CC500</code> &nbsp;|&nbsp; <code>12345678</code> (for EAN-8)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Barcode Symbology</strong> <span class="badge badge-danger">Required</span></td>
                                            <td>
                                                Format used when printing barcodes for this product.<br>
                                                <b>Code 128</b> – alphanumeric, any length (best default).<br>
                                                <b>Code 39</b> – alphanumeric, older scanners.<br>
                                                <b>UPC-A</b> – 11-digit numeric (US retail).<br>
                                                <b>EAN-13</b> – 12-digit numeric (international retail).<br>
                                                <b>EAN-8 / UPC-E</b> – compact numeric formats.
                                            </td>
                                            <td>Most products → <code>Code 128</code><br>Packaged retail goods → <code>EAN-13</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Brand</strong></td>
                                            <td>Optional manufacturer or brand. Used for filtering in reports and POS. Click <i class="dripicons-plus"></i> to add a new brand inline.</td>
                                            <td><code>Nike</code> &nbsp;|&nbsp; <code>Samsung</code> &nbsp;|&nbsp; <code>House Brand</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Category</strong> <span class="badge badge-danger">Required</span></td>
                                            <td>Groups products for reports, the POS category filter, and the online store. Click <i class="dripicons-plus"></i> to add a new category inline.</td>
                                            <td><code>Beverages</code> &nbsp;|&nbsp; <code>Electronics</code> &nbsp;|&nbsp; <code>Men's Footwear</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Product Image</strong></td>
                                            <td>Drag and drop multiple images (JPEG/PNG/GIF, max 12 MB each). The <em>first</em> image becomes the main/thumbnail image. Drag to reorder.</td>
                                            <td>Upload front-view, side-view, and label photos of the product.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Product Details</strong></td>
                                            <td>Rich-text description shown on the product detail page in the online store. Not shown on POS or receipts.</td>
                                            <td><em>"Premium 500 ml sparkling water. Sourced from the Liberian highlands. Chilled best at 4°C."</em></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ── PRICING & TAX ───────────────────────────────────── --}}
                        <div class="tab-pane fade" id="help-pricing">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr><th style="width:20%">Field</th><th style="width:40%">What it does</th><th>Example</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Product Cost</strong> <span class="badge badge-danger">Required</span></td>
                                            <td>What you paid to acquire or produce this item (purchase price / cost of goods). Used to calculate profit margin and gross profit reports. Does <em>not</em> appear on customer receipts.</td>
                                            <td>You buy a bottle for <code>2.50</code> → enter <code>2.50</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Profit Margin Type</strong></td>
                                            <td>
                                                Determines how the selling price is computed from cost.<br>
                                                <b>Percentage (%)</b> – price = cost + (cost × margin%). Automatically updates price when cost changes.<br>
                                                <b>Flat</b> – price = cost + flat amount.
                                            </td>
                                            <td>
                                                Cost = 2.50, Margin 20% → Price = <code>3.00</code><br>
                                                Cost = 2.50, Flat 1.00 → Price = <code>3.50</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Profit Margin</strong></td>
                                            <td>The margin value (percent or flat amount) used to auto-calculate selling price. Changing the selling price manually will back-calculate this field.</td>
                                            <td><code>20</code> (for 20% margin) &nbsp;|&nbsp; <code>0.50</code> (flat LRD 0.50 markup)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Product Price</strong> <span class="badge badge-danger">Required</span></td>
                                            <td>The default retail selling price. Shown on receipts and POS. Auto-calculated from cost + margin, but can be edited manually. A warning appears if price ≤ cost.</td>
                                            <td>Cost = 2.50, desired price = <code>3.00</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Wholesale Price</strong></td>
                                            <td>An alternate price for wholesale/bulk customers. Only applied when a sale is created for a customer whose group has "Wholesale" pricing enabled.</td>
                                            <td>Retail price = 3.00 → Wholesale = <code>2.70</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Commission %</strong></td>
                                            <td>Per-item commission earned by the selling staff member. Used during payroll commission calculation. Leave blank to use the global commission rate.</td>
                                            <td>Enter <code>2.5</code> to pay 2.5% of the sale price as commission on each unit sold.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Product Tax</strong></td>
                                            <td>Tax rate applied to this product (e.g. VAT, GST). Select "No Tax" if the product is tax-exempt. Click <i class="dripicons-plus"></i> to create a new tax rate inline.</td>
                                            <td><code>VAT 10%</code> &nbsp;|&nbsp; <code>GST 18%</code> &nbsp;|&nbsp; <code>No Tax</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tax Method</strong></td>
                                            <td>
                                                <b>Exclusive</b> – tax is added on top of the price at checkout. Customer pays Price + Tax.<br>
                                                <b>Inclusive</b> – tax is already included in the price. The displayed price already contains tax.
                                            </td>
                                            <td>
                                                Price = 10.00, Tax 10% Exclusive → customer pays <code>11.00</code><br>
                                                Price = 11.00, Tax 10% Inclusive → tax portion = <code>1.00</code>, net = <code>10.00</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Promotional Price</strong></td>
                                            <td>A temporary discounted price active between the start and end dates. When active, the promo price overrides the regular price at checkout. Check "Add Promotional Price" to reveal these fields.</td>
                                            <td>
                                                Regular price = 3.00<br>
                                                Promo price = <code>2.50</code><br>
                                                Starts: <code>01-07-2026</code> &nbsp; Ends: <code>31-07-2026</code>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Warranty</strong></td>
                                            <td>Duration of the manufacturer/seller warranty, shown on receipts if configured. Select the unit (Days / Months / Years) from the dropdown.</td>
                                            <td><code>12 Months</code> &nbsp;|&nbsp; <code>2 Years</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Guarantee</strong></td>
                                            <td>Satisfaction or functional guarantee period (distinct from warranty). Displayed on receipt and product page.</td>
                                            <td><code>30 Days</code> (30-day money-back guarantee)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ── STOCK & UNITS ───────────────────────────────────── --}}
                        <div class="tab-pane fade" id="help-stock">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr><th style="width:20%">Field</th><th style="width:40%">What it does</th><th>Example</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Product Unit</strong> <span class="badge badge-danger">Required</span></td>
                                            <td>The base unit this product is counted in. Only base (parent) units appear here. Derived units (e.g. Dozen = 12 Pieces) appear after selection in Sale/Purchase Unit fields. Click <i class="dripicons-plus"></i> to create a new unit inline.</td>
                                            <td>A bottle of water → <code>Piece</code><br>Fabric → <code>Metre</code><br>Cooking oil → <code>Litre</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Sale Unit</strong></td>
                                            <td>The unit displayed to customers on POS and invoices. Can be the base unit or a derived unit (e.g. sell by the Dozen while tracking by Piece). Populated automatically once a Product Unit is selected.</td>
                                            <td>Product unit = Piece; Sale unit = <code>Dozen</code> (auto-converts × 12)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Purchase Unit</strong></td>
                                            <td>The unit used when creating purchase orders from suppliers. Often a larger unit like Carton or Box. Stock is converted to the base unit automatically.</td>
                                            <td>Buy in <code>Carton</code> (24 bottles); stock records individual bottles.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Daily Sale Objective</strong></td>
                                            <td>Minimum quantity that should be sold per day. If actual sales fall below this, a dashboard notification is triggered (requires cron job setup). Leave blank to disable.</td>
                                            <td>Enter <code>50</code> to be notified if fewer than 50 units are sold in a day.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Alert Quantity</strong></td>
                                            <td>Stock reorder point. When inventory drops to or below this number, a low-stock alert appears on the dashboard.</td>
                                            <td>Enter <code>10</code> → alert fires when stock = 10 units or fewer.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Initial Stock</strong></td>
                                            <td>Check this to set opening stock quantities per warehouse when creating the product. Not available for products with variants or batches. Enter the quantity for each warehouse in the table that appears.</td>
                                            <td>Warehouse A = <code>100</code>, Warehouse B = <code>50</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Location Barcode</strong></td>
                                            <td>An optional per-warehouse shelf/bin barcode for this product. Printed on location labels to direct staff to the exact shelf. One per warehouse row in the Initial Stock table.</td>
                                            <td><code>A1-SH3-BIN5</code> (Aisle 1, Shelf 3, Bin 5)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Different Price per Warehouse</strong></td>
                                            <td>Check "This product has different price for different warehouse" to enter a separate selling price for each warehouse location. Useful when the same product is priced differently by branch.</td>
                                            <td>Main Store = <code>3.00</code>, Airport Branch = <code>3.50</code></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ── VARIANTS & BATCHES ──────────────────────────────── --}}
                        <div class="tab-pane fade" id="help-variants">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr><th style="width:20%">Field</th><th style="width:40%">What it does</th><th>Example</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>This product has variants</strong></td>
                                            <td>
                                                Enables product variants (e.g. Size, Color). Each combination of option values becomes a separate traceable variant with its own item code and optional cost/price adjustment.<br>
                                                <strong>Option</strong> – the variant dimension (e.g. "Size").<br>
                                                <strong>Value</strong> – comma-separated values for that option (e.g. "S, M, L, XL").<br>
                                                The system auto-generates all combinations in the table below and lets you set an additional cost and price per variant.
                                            </td>
                                            <td>
                                                Option 1: <code>Size</code> → Values: <code>S, M, L</code><br>
                                                Option 2: <code>Color</code> → Values: <code>Red, Blue</code><br>
                                                → 6 combinations generated (S-Red, S-Blue, M-Red …)
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Item Code</strong> <small>(variant table)</small></td>
                                            <td>Auto-generated code for each variant (variant name + product code). Can be edited manually. Must be unique.</td>
                                            <td><code>S-Red-CC500</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Additional Cost / Price</strong> <small>(variant table)</small></td>
                                            <td>Amount added to the base product cost/price for this variant. Leave 0 if the variant is the same price as the base product.</td>
                                            <td>XL size costs 0.50 more to produce → Additional Cost = <code>0.50</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Batch & Expiry Date</strong></td>
                                            <td>Check "This product has batch and expired date" to enable lot/batch tracking with expiry dates. Stock is received and sold in batches. Cannot be combined with variants. Ideal for food, medicine, and perishables.</td>
                                            <td>Milk Batch <code>LOT-2026-07</code> expires <code>15-08-2026</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>IMEI / Serial Numbers</strong></td>
                                            <td>Check "This product has IMEI or Serial numbers" to require a unique serial/IMEI number for each individual unit sold. Each sale prompts for the serial number. Ideal for phones, laptops, and electronics.</td>
                                            <td>Samsung Galaxy S24 – IMEI <code>352099001761481</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Combo Products</strong> <small>(Combo type only)</small></td>
                                            <td>Search and add the individual products that make up this bundle. Set the quantity of each component. Cost and price are auto-summed from the component prices. Set Wastage % to account for preparation loss.</td>
                                            <td>
                                                Gift Basket = <code>1× Chocolate Box</code> + <code>2× Orange Juice</code> + <code>1× Wine Glass</code><br>
                                                Wastage: <code>0%</code>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- ── ADVANCED ─────────────────────────────────────────── --}}
                        <div class="tab-pane fade" id="help-advanced">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr><th style="width:20%">Field</th><th style="width:40%">What it does</th><th>Example</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Featured</strong></td>
                                            <td>Marks the product as "featured" so it appears in the featured products grid on the POS screen and in the online store's featured section. Use sparingly for your top sellers.</td>
                                            <td>Check for your 5–10 best-selling or promoted products.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Embedded Barcode</strong></td>
                                            <td>Check if this product is weighed by a scale machine that prints embedded barcodes (the barcode encodes both the product code and the actual weight/price). The POS will decode the embedded weight when scanning.</td>
                                            <td>Deli meats, fresh fish, or any product sold by weight with a price-embedded barcode.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Sell Online</strong> <small>(ecommerce/restaurant)</small></td>
                                            <td>When checked the product is published to your public online store. Unchecking hides it from customers without deleting it.</td>
                                            <td>Uncheck for internal-use items (e.g. packaging materials) that should never appear in the store.</td>
                                        </tr>
                                        <tr>
                                            <td><strong>In Stock</strong> <small>(ecommerce)</small></td>
                                            <td>Controls the "In Stock" / "Out of Stock" badge on the online store. Independent of the actual quantity — useful when you want to manually mark a product unavailable.</td>
                                            <td>Uncheck to show "Out of Stock" even if physical quantity > 0 (e.g. item is reserved).</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Attach File</strong> <small>(Digital type only)</small></td>
                                            <td>The downloadable file customers receive after purchase. Supported: any file type. Ensure the file is the final deliverable.</td>
                                            <td><code>NovaxPOS_User_Manual_v2.pdf</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Product Tags</strong> <small>(ecommerce/restaurant)</small></td>
                                            <td>Comma-separated keywords that improve search discoverability on the online store and help customers find related products.</td>
                                            <td><code>cold drink, refreshing, diet, sugar-free</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Meta Title</strong> <small>(ecommerce/restaurant)</small></td>
                                            <td>The HTML &lt;title&gt; tag for this product's page — shown in browser tabs and Google search results. Ideally 50–60 characters.</td>
                                            <td><code>Buy Coca-Cola 500ml Online | NovaxPOS Store</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Meta Description</strong> <small>(ecommerce/restaurant)</small></td>
                                            <td>A short summary (150–160 chars) shown in Google search snippets below the page title. Write it to encourage clicks.</td>
                                            <td><code>Order ice-cold Coca-Cola 500ml online. Fast delivery across Liberia.</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Related Products</strong> <small>(ecommerce)</small></td>
                                            <td>Products shown as "You may also like" on this product's detail page. Search by name or code and select. Improves cross-selling.</td>
                                            <td>For Coca-Cola 500ml → relate <code>Fanta 500ml</code>, <code>Sprite 500ml</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Save and Insert Another</strong></td>
                                            <td>Saves the current product and immediately resets the form so you can enter the next product without navigating away. Useful for bulk product entry sessions.</td>
                                            <td>Use when adding 10+ new products in one sitting.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-light border mt-3" style="font-size:.85rem">
                                <strong><i class="dripicons-information"></i> Tips:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>Fields marked <span class="badge badge-danger">Required</span> must be filled before saving.</li>
                                    <li>Use the <i class="fa fa-refresh"></i> button next to Product Code to auto-generate a unique code instantly.</li>
                                    <li>Profit Margin and Product Price are linked — editing one auto-updates the other.</li>
                                    <li>Variants, Batches, and IMEI cannot be combined on the same product.</li>
                                    <li>Initial Stock entry only works on products <em>without</em> variants or batches; add stock for those types via a Purchase or Stock Adjustment after creation.</li>
                                </ul>
                            </div>
                        </div>

                    </div>{{-- end tab-content --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- ── END PRODUCT HELP MODAL ── -->

</section>
@endsection
@push('scripts')
<script type="text/javascript">

    // Help modal — move to <body> to escape theme's .modal{overflow:scroll}
    // which scrolls the dialog off-screen during Bootstrap's fade-in animation.
    $('#openProductHelp').on('click', function () {
        var $modal = $('#productHelpModal');
        if (!$modal.parent().is('body')) {
            $modal.appendTo('body');
        }
        $modal.modal('show');
    });

    var submit_type = 'add';

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function showAlert(message, type = 'success') {
        const container = $('#alert-container');
        container.removeClass('alert-success alert-danger alert-warning alert-info')
                 .addClass('alert-' + type)
                 .show();
        $('#alert-message').text(message);
        
        // Auto-scroll to top
        $('html, body').animate({ scrollTop: 0 }, 'slow');

        // Auto-fade after 5 seconds
        setTimeout(() => {
            container.fadeOut('slow');
        }, 8000);
    }

    $("#is_addon").on('click', function(){
        if($("#is_addon").prop('checked') == false){
            $('.extra-section,.related-section').css('display','block');
        }else{
            $('.extra-section,.related-section').css('display','none');
        }
    })

    @if(in_array('ecommerce',explode(',',$general_setting->modules)) || in_array('restaurant',explode(',',$general_setting->modules)))
    $('#search_products').on('input', function() {
        var item = $(this).val();
        $('.search_result').html('<div class="d-block text-center"><div class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>');

        if(item.length >= 3){
            $.ajax({
                type: "get",
                url: "{{url('search')}}/" + item,
                success: function(data) {
                    $('.search_result').html('').css('height','200px');
                    $.each(data,function(key, value){
                        var image = value.image.split(',');
                        $('.search_result').append('<div data-id="'+value.id+'"><img src="{{asset("images/product/small/")}}/'+image[0]+'"><h4>'+value.name+'</h4><i class="dripicons-checkmark d-none"></i></div>')
                    })
                }
            })
        } else if (item.length < 3) {
            $('.search_result').html('');
        }
    });

    $(document).on('click','.search_result div',function(){
        $(this).find('i').removeClass('d-none');
        var selected_item = '<div data-id="'+$(this).data('id')+'">'+$(this).html()+'<span class="remove_item"><i class="dripicons-cross"></i></span></div>';
        if ($('.selected_ids').html().indexOf($(this).data('id')) === -1){
            $('.selected_items').prepend(selected_item);
            $('.selected_ids').append($(this).data('id')+',');
            $('.selected_items .dripicons-checkmark').addClass('d-none');
        }
    });

    $(document).on('click','.remove_item',function(){
        var item = $(this).parent().remove();
        var remove_id = $(this).parent().data('id');
        var selected_ids = $('.selected_ids').html().replace(remove_id+',','');
        $('.selected_ids').html(selected_ids);
    });
    @endif

    @if(in_array('restaurant',explode(',',$general_setting->modules)))
    $('#search_addons').on('input', function() {
        var item = $(this).val();
        $('.search_result_addon').html('<div class="d-block text-center"><div class="spinner-border text-secondary" role="status"><span class="sr-only">Loading...</span></div></div>');

        if(item.length >= 3){
            $.ajax({
                type: "get",
                url: "{{url('search')}}/" + item,
                success: function(data) {
                    $('.search_result_addon').html('').css('height','200px');
                    $.each(data,function(key, value){
                        var image = value.image.split(',');
                        $('.search_result_addon').append('<div data-id="'+value.id+'"><img src="{{asset("images/product/small/")}}/'+image[0]+'"><h4>'+value.name+'</h4><i class="dripicons-checkmark d-none"></i></div>')
                    })
                }
            })
        } else if (item.length < 3) {
            $('.search_result_addon').html('');
        }
    });

    $(document).on('click','.search_result_addon div',function(){
        $(this).find('i').removeClass('d-none');
        var selected_addon = '<div data-id="'+$(this).data('id')+'">'+$(this).html()+'<span class="remove_item"><i class="dripicons-cross"></i></span></div>';
        if ($('.selected_addon_ids').html().indexOf($(this).data('id')) === -1){
            $('.selected_addons').prepend(selected_addon);
            $('.selected_addon_ids').append($(this).data('id')+',');
            $('.selected_addons .dripicons-checkmark').addClass('d-none');
        }
    });

    $(document).on('click','.remove_item',function(){
        var item = $(this).parent().remove();
        var remove_addon_id = $(this).parent().data('id');
        var selected_addon_ids = $('.selected_addon_ids').html().replace(remove_addon_id +',','');
        $('.selected_addon_ids').html(selected_addon_ids);

    });
    @endif

    $("ul#product").siblings('a').attr('aria-expanded','true');
    $("ul#product").addClass("show");
    $("ul#product #product-create-menu").addClass("active");

    @if(config('database.connections.saleprosaas_landlord'))
        numberOfProduct = <?php echo json_encode($numberOfProduct)?>;
        $.ajax({
            type: 'GET',
            async: false,
            url: '{{route("package.fetchData", $general_setting->package_id)}}',
            success: function(data) {
                if(data['number_of_product'] > 0 && data['number_of_product'] <= numberOfProduct) {
                    localStorage.setItem("message", "You don't have permission to create another product as you already exceed the limit! Subscribe to another package if you wants more!");
                    location.href = "{{route('products.index')}}";
                }
            }
        });
    @endif

    $("#digital").hide();
    $("#combo").hide();
    $("#variant-section").hide();
    $("#initial-stock-section").hide();
    $("#diffPrice-section").hide();
    $("#promotion_price").hide();
    $("#start_date").hide();
    $("#last_date").hide();
    var variantPlaceholder = <?php echo json_encode(__('db.Enter variant value seperated by comma')); ?>;
    var variantIds = [];
    var combinations = [];
    var oldCombinations = [];
    var oldAdditionalCost = [];
    var oldAdditionalPrice = [];
    var step;
    var numberOfWarehouse = <?php echo json_encode(count($lims_warehouse_list)) ?>;

    $('[data-toggle="tooltip"]').tooltip();

    $('#genbutton').on("click", function(){
      $.get('gencode', function(data){
        $("input[name='code']").val(data);
      });
    });

    $('.add-more-variant').on("click", function() {
        var htmlText = '<div class="row"><div class="col-md-4 form-group mt-2"><label>Option *</label><input type="text" name="variant_option[]" class="form-control variant-field" placeholder="Size, Color etc..."></div><div class="col-md-7 form-group mt-2"><label>Value *</label><input type="text" name="variant_value[]" class="type-variant form-control variant-field"></div><div class="col-sm-1 form-group mt-2" style="display:flex;flex-direction:column;align-items:center;justify-content:end;"><button type="button" class="delVarOption btn btn-danger btn-sm mr-3"><i class="dripicons-cross"></i></button></div></div>';
        $("#variant-input-section").append(htmlText);
        $('.type-variant').tagsInput();
    });

    $(document).on("click", '.delVarOption', function() {
        $(this).parent().parent().remove();
        $('.type-variant').tagsInput();
    });

    //start variant related js
    $(function() {
        $('.type-variant').tagsInput();
    });

    (function($) {
        var delimiter = [];
        var inputSettings = [];
        var callbacks = [];

        $.fn.addTag = function(value, options) {
            options = jQuery.extend({
                focus: false,
                callback: true
            }, options);
            this.each(function() {
                var id = $(this).attr('id');
                var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
                if (tagslist[0] === '') tagslist = [];

                value = jQuery.trim(value);

                if ((inputSettings[id].unique && $(this).tagExist(value)) || !_validateTag(value, inputSettings[id], tagslist, delimiter[id])) {
                    $('#' + id + '_tag').addClass('error');
                    return false;
                }

                $('<span>', {class: 'tag'}).append(
                    $('<span>', {class: 'tag-text'}).text(value),
                    $('<button>', {class: 'tag-remove'}).click(function() {
                        return $('#' + id).removeTag(encodeURI(value));
                    })
                ).insertBefore('#' + id + '_addTag');
                tagslist.push(value);

                $('#' + id + '_tag').val('');
                if (options.focus) {
                    $('#' + id + '_tag').focus();
                } else {
                    $('#' + id + '_tag').blur();
                }

                $.fn.tagsInput.updateTagsField(this, tagslist);

                if (options.callback && callbacks[id] && callbacks[id]['onAddTag']) {
                    var f = callbacks[id]['onAddTag'];
                    f.call(this, this, value);
                }

                if (callbacks[id] && callbacks[id]['onChange']) {
                    var i = tagslist.length;
                    var f = callbacks[id]['onChange'];
                    f.call(this, this, value);
                }

                $(".type-variant").each(function(index) {
                    variantIds.splice(index, 1, $(this).attr('id'));
                });

                //start custom code
                first_variant_values = $('#'+variantIds[0]).val().split(_getDelimiter(delimiter[variantIds[0] ]));
                combinations = first_variant_values;
                step = 1;
                while(step < variantIds.length) {
                    var newCombinations = [];
                    for (var i = 0; i < combinations.length; i++) {
                        new_variant_values = $('#'+variantIds[step]).val().split(_getDelimiter(delimiter[variantIds[step] ]));
                        for (var j = 0; j < new_variant_values.length; j++) {
                            newCombinations.push(combinations[i]+'/'+new_variant_values[j]);
                        }
                    }
                    combinations = newCombinations;
                    step++;
                }
                var rownumber = $('table.variant-list tbody tr:last').index();
                if(rownumber > -1) {
                    oldCombinations = [];
                    oldAdditionalCost = [];
                    oldAdditionalPrice = [];
                    $(".variant-name").each(function(i) {
                        oldCombinations.push($(this).text());
                        oldAdditionalCost.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-cost').val());
                        oldAdditionalPrice.push($('table.variant-list tbody tr:nth-child(' + (i + 1) + ')').find('.additional-price').val());
                    });
                }
                $("table.variant-list tbody").remove();
                var newBody = $("<tbody>");
                for(i = 0; i < combinations.length; i++) {
                    var variant_name = combinations[i];
                    var item_code = variant_name+'-'+$("#code").val();
                    var newRow = $("<tr>");
                    var cols = '';
                    cols += '<td class="variant-name">'+variant_name+'<input type="hidden" name="variant_name[]" value="' + variant_name + '" /></td>';
                    cols += '<td><input type="text" class="form-control item-code" name="item_code[]" value="'+item_code+'" /></td>';
                    //checking if this variant already exist in the variant table
                    oldIndex = oldCombinations.indexOf(combinations[i]);
                    if(oldIndex >= 0) {
                        cols += '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="'+oldAdditionalCost[oldIndex]+'" step="any" /></td>';
                        cols += '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="'+oldAdditionalPrice[oldIndex]+'" step="any" /></td>';
                    }
                    else {
                        cols += '<td><input type="number" class="form-control additional-cost" name="additional_cost[]" value="" step="any" /></td>';
                        cols += '<td><input type="number" class="form-control additional-price" name="additional_price[]" value="" step="any" /></td>';
                    }
                    newRow.append(cols);
                    newBody.append(newRow);
                }
                $("table.variant-list").append(newBody);
                //end custom code
            });
            return false;
        };

        $.fn.removeTag = function(value) {
            value = decodeURI(value);

            this.each(function() {
                var id = $(this).attr('id');

                var old = $(this).val().split(_getDelimiter(delimiter[id]));

                $('#' + id + '_tagsinput .tag').remove();

                var str = '';
                for (i = 0; i < old.length; ++i) {
                    if (old[i] != value) {
                        str = str + _getDelimiter(delimiter[id]) + old[i];
                    }
                }

                $.fn.tagsInput.importTags(this, str);

                if (callbacks[id] && callbacks[id]['onRemoveTag']) {
                    var f = callbacks[id]['onRemoveTag'];
                    f.call(this, this, value);
                }
            });

            return false;
        };

        $.fn.tagExist = function(val) {
            var id = $(this).attr('id');
            var tagslist = $(this).val().split(_getDelimiter(delimiter[id]));
            return (jQuery.inArray(val, tagslist) >= 0);
        };

        $.fn.importTags = function(str) {
            var id = $(this).attr('id');
            $('#' + id + '_tagsinput .tag').remove();
            $.fn.tagsInput.importTags(this, str);
        };

        $.fn.tagsInput = function(options) {
            var settings = jQuery.extend({
                interactive: true,
                placeholder: variantPlaceholder,
                minChars: 0,
                maxChars: null,
                limit: null,
                validationPattern: null,
                width: 'auto',
                height: 'auto',
                autocomplete: null,
                hide: true,
                delimiter: ',',
                unique: true,
                removeWithBackspace: true
            }, options);

            var uniqueIdCounter = 0;

            this.each(function() {
                if (typeof $(this).data('tagsinput-init') !== 'undefined') return;

                $(this).data('tagsinput-init', true);

                if (settings.hide) $(this).hide();

                var id = $(this).attr('id');
                if (!id || _getDelimiter(delimiter[$(this).attr('id')])) {
                    id = $(this).attr('id', 'tags' + new Date().getTime() + (++uniqueIdCounter)).attr('id');
                }

                var data = jQuery.extend({
                    pid: id,
                    real_input: '#' + id,
                    holder: '#' + id + '_tagsinput',
                    input_wrapper: '#' + id + '_addTag',
                    fake_input: '#' + id + '_tag'
                }, settings);

                delimiter[id] = data.delimiter;
                inputSettings[id] = {
                    minChars: settings.minChars,
                    maxChars: settings.maxChars,
                    limit: settings.limit,
                    validationPattern: settings.validationPattern,
                    unique: settings.unique
                };

                if (settings.onAddTag || settings.onRemoveTag || settings.onChange) {
                    callbacks[id] = [];
                    callbacks[id]['onAddTag'] = settings.onAddTag;
                    callbacks[id]['onRemoveTag'] = settings.onRemoveTag;
                    callbacks[id]['onChange'] = settings.onChange;
                }

                var markup = $('<div>', {id: id + '_tagsinput', class: 'tagsinput'}).append(
                    $('<div>', {id: id + '_addTag'}).append(
                        settings.interactive ? $('<input>', {id: id + '_tag', class: 'tag-input', value: '', placeholder: settings.placeholder}) : null
                    )
                );

                $(markup).insertAfter(this);

                $(data.holder).css('width', settings.width);
                $(data.holder).css('min-height', settings.height);
                $(data.holder).css('height', settings.height);

                if ($(data.real_input).val() !== '') {
                    $.fn.tagsInput.importTags($(data.real_input), $(data.real_input).val());
                }

                // Stop here if interactive option is not chosen
                if (!settings.interactive) return;

                $(data.fake_input).val('');
                $(data.fake_input).data('pasted', false);

                $(data.fake_input).on('focus', data, function(event) {
                    $(data.holder).addClass('focus');

                    if ($(this).val() === '') {
                        $(this).removeClass('error');
                    }
                });

                $(data.fake_input).on('blur', data, function(event) {
                    $(data.holder).removeClass('focus');
                });

                if (settings.autocomplete !== null && jQuery.ui.autocomplete !== undefined) {
                    $(data.fake_input).autocomplete(settings.autocomplete);
                    $(data.fake_input).on('autocompleteselect', data, function(event, ui) {
                        $(event.data.real_input).addTag(ui.item.value, {
                            focus: true,
                            unique: settings.unique
                        });

                        return false;
                    });

                    $(data.fake_input).on('keypress', data, function(event) {
                        if (_checkDelimiter(event)) {
                            $(this).autocomplete("close");
                        }
                    });
                } else {
                    $(data.fake_input).on('blur', data, function(event) {
                        $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                            focus: true,
                            unique: settings.unique
                        });

                        return false;
                    });
                }

                // If a user types a delimiter create a new tag
                $(data.fake_input).on('keypress', data, function(event) {
                    if (_checkDelimiter(event)) {
                        event.preventDefault();

                        $(event.data.real_input).addTag($(event.data.fake_input).val(), {
                            focus: true,
                            unique: settings.unique
                        });

                        return false;
                    }
                });

                $(data.fake_input).on('paste', function () {
                    $(this).data('pasted', true);
                });

                // If a user pastes the text check if it shouldn't be splitted into tags
                $(data.fake_input).on('input', data, function(event) {
                    if (!$(this).data('pasted')) return;

                    $(this).data('pasted', false);

                    var value = $(event.data.fake_input).val();

                    value = value.replace(/\n/g, '');
                    value = value.replace(/\s/g, '');

                    var tags = _splitIntoTags(event.data.delimiter, value);

                    if (tags.length > 1) {
                        for (var i = 0; i < tags.length; ++i) {
                            $(event.data.real_input).addTag(tags[i], {
                                focus: true,
                                unique: settings.unique
                            });
                        }

                        return false;
                    }
                });

                // Deletes last tag on backspace
                data.removeWithBackspace && $(data.fake_input).on('keydown', function(event) {
                    if (event.keyCode == 8 && $(this).val() === '') {
                         event.preventDefault();
                         var lastTag = $(this).closest('.tagsinput').find('.tag:last > span').text();
                         var id = $(this).attr('id').replace(/_tag$/, '');
                         $('#' + id).removeTag(encodeURI(lastTag));
                         $(this).trigger('focus');
                    }
                });

                // Removes the error class when user changes the value of the fake input
                $(data.fake_input).keydown(function(event) {
                    // enter, alt, shift, esc, ctrl and arrows keys are ignored
                    if (jQuery.inArray(event.keyCode, [13, 37, 38, 39, 40, 27, 16, 17, 18, 225]) === -1) {
                        $(this).removeClass('error');
                    }
                });
            });

            return this;
        };

        $.fn.tagsInput.updateTagsField = function(obj, tagslist) {
            var id = $(obj).attr('id');
            $(obj).val(tagslist.join(_getDelimiter(delimiter[id])));
        };

        $.fn.tagsInput.importTags = function(obj, val) {
            $(obj).val('');

            var id = $(obj).attr('id');
            var tags = _splitIntoTags(delimiter[id], val);

            for (i = 0; i < tags.length; ++i) {
                $(obj).addTag(tags[i], {
                    focus: false,
                    callback: false
                });
            }

            if (callbacks[id] && callbacks[id]['onChange']) {
                var f = callbacks[id]['onChange'];
                f.call(obj, obj, tags);
            }
        };

        var _getDelimiter = function(delimiter) {
            if (typeof delimiter === 'undefined') {
                return delimiter;
            } else if (typeof delimiter === 'string') {
                return delimiter;
            } else {
                return delimiter[0];
            }
        };

        var _validateTag = function(value, inputSettings, tagslist, delimiter) {
            var result = true;

            if (value === '') result = false;
            if (value.length < inputSettings.minChars) result = false;
            if (inputSettings.maxChars !== null && value.length > inputSettings.maxChars) result = false;
            if (inputSettings.limit !== null && tagslist.length >= inputSettings.limit) result = false;
            if (inputSettings.validationPattern !== null && !inputSettings.validationPattern.test(value)) result = false;

            if (typeof delimiter === 'string') {
                if (value.indexOf(delimiter) > -1) result = false;
            } else {
                $.each(delimiter, function(index, _delimiter) {
                    if (value.indexOf(_delimiter) > -1) result = false;
                    return false;
                });
            }

            return result;
        };

        var _checkDelimiter = function(event) {
            var found = false;

            if (event.which === 13) {
                return true;
            }

            if (typeof event.data.delimiter === 'string') {
                if (event.which === event.data.delimiter.charCodeAt(0)) {
                    found = true;
                }
            } else {
                $.each(event.data.delimiter, function(index, delimiter) {
                    if (event.which === delimiter.charCodeAt(0)) {
                        found = true;
                    }
                });
            }

            return found;
         };

         var _splitIntoTags = function(delimiter, value) {
             if (value === '') return [];

             if (typeof delimiter === 'string') {
                 return value.split(delimiter);
             } else {
                 var tmpDelimiter = '∞';
                 var text = value;

                 $.each(delimiter, function(index, _delimiter) {
                     text = text.split(_delimiter).join(tmpDelimiter);
                 });

                 return text.split(tmpDelimiter);
             }

             return [];
         };
    })(jQuery);
    //end of variant related js

    tinymce.init({
      selector: 'textarea:not(.no-tiny)',
      height: 130,
      plugins: [
        'advlist autolink lists link image charmap print preview anchor textcolor',
        'searchreplace visualblocks code fullscreen',
        'insertdatetime media table contextmenu paste code wordcount'
      ],
      toolbar: 'insert | undo redo |  formatselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat',
      branding:false
    });
    $('select[name="type"]').on('change', function() {
        if($(this).val() == 'combo'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            hide();
            $("#profit_margin").show(300);
            $("#stock-section").hide(300);
            $("#cost").show(300);
            $("#unit").show(300);
            $("#combo").show(300);
            //$("input[name='price']").prop('disabled',true);
            $("#is-variant").prop("checked", false);
            $("#is-diffPrice").prop("checked", false);
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section").hide(300);
        }
        else if($(this).val() == 'digital'){
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',true);
            hide();
            $("#profit_margin").hide(300);
            $("#digital").show(300);
            $("#combo").hide(300);
            $("#stock-section").show(300);
            $("input[name='price']").prop('disabled',false);
            $("#is-variant").prop("checked", false);
            $("#is-diffPrice").prop("checked", false);
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section, #batch-option").hide(300);
        }
        else if($(this).val() == 'service') {
            $("input[name='cost']").prop('required',false);
            $("select[name='unit_id']").prop('required',false);
            $("input[name='file']").prop('required',true);
            hide();
            $("#profit_margin").hide(300);
            $("#stock-section").show(300);
            $("#combo").hide(300);
            $("#digital").hide(300);
            $("input[name='price']").prop('disabled',false);
            $("#is-variant").prop("checked", false);
            $("#is-diffPrice").prop("checked", false);
            $("#variant-section, #variant-option, #diffPrice-option, #diffPrice-section, #batch-option, #imei-option").hide(300);
        }
        else if($(this).val() == 'standard') {
            $("input[name='cost']").prop('required',true);
            $("select[name='unit_id']").prop('required',true);
            $("input[name='file']").prop('required',false);
            $("#stock-section").show(300);
            $("#cost").show(300);
            $("#profit_margin").show(300);
            $("#unit").show(300);
            $("#alert-qty").show(300);
            $("#variant-option, #diffPrice-option, #batch-option, #imei-option").show(300);
            $("#digital").hide(300);
            $("#combo").hide(300);
            $("input[name='price']").prop('disabled',false);
        }
    });

    $('select[name="unit_id"]').on('change', function() {

        unitID = $(this).val();
        if(unitID) {
            populate_category(unitID);
        }else{
            $('select[name="sale_unit_id"]').empty();
            $('select[name="purchase_unit_id"]').empty();
        }
    });
    <?php $productArray = []; ?>
    var lims_product_code = [
        @foreach($lims_product_list_without_variant as $product)
            <?php
                $productArray[] = htmlspecialchars($product->code) . ' (' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name)) . ')';
            ?>
        @endforeach
        @foreach($lims_product_list_with_variant as $product)
            <?php
                $productArray[] = htmlspecialchars($product->item_code) . ' (' . preg_replace('/[\n\r]/', "<br>", htmlspecialchars($product->name)) . ')';
            ?>
        @endforeach
            <?php
                echo  '"'.implode('","', $productArray).'"';
            ?> ];

    var lims_productcodeSearch = $('#lims_productcodeSearch');

    lims_productcodeSearch.autocomplete({
        source: function(request, response) {
            var matcher = new RegExp(".?" + $.ui.autocomplete.escapeRegex(request.term), "i");
            response($.grep(lims_product_code, function(item) {
                return matcher.test(item);
            }));
        },
        select: function(event, ui) {
            var data = ui.item.value;
            $.ajax({
                type: 'GET',
                url: 'lims_product_search',
                data: {
                    data: data
                },
                success: function(responseData) {
                    data = responseData[0];
                    console.log(data)
                    //console.log(data);
                    var flag = 1;
                    $(".varient_id").each(function() {
                        if ($(this).val() == data[1]) {
                            alert('Duplicate input is not allowed!')
                            flag = 0;
                        }
                    });
                    $("input[name='product_code_name']").val('');
                    if(flag){
                        var newRow = $("<tr>");
                        var cols = '';
                        cols += '<td>' + data[0] +' [' + data[1] + ']</td>';
                        cols += `<td>
                                    <div class="input-group">
                                        <input type="number" name="wastage_percent[]" class="form-control wastage_percent" value="0"/>
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </td>`;
                        cols += `<td>
                                    <div class="input-group" style="max-width: unset">
                                        <input type="number"
                                            class="form-control qty"
                                            min="0.000001"
                                            name="product_qty[]"
                                            value="1"
                                            step="any"
                                            placeholder="Qty"
                                            aria-label="Quantity">
                                        <div class="input-group-append">
                                            `+data[13]+`
                                        </div>
                                    </div>
                                </td>`;
                        cols += '<td><input type="number" class="form-control unit_cost" name="product_unit_cost[]" value="' + data[10] + '"/></td>';
                        cols += '<td><input type="number" class="form-control unit_price" name="unit_price[]" value="' + data[2] + '" step="any"/></td>';
                        cols += '<td><input type="number" class="form-control subtotal" name="subtotal[]" value="' + data[2] + '" step="any"/></td>';
                        cols += '<td><button type="button" class="ibtnDel btn btn-sm btn-danger">X</button></td>';
                        cols += '<input type="hidden" class="product-id" name="product_id[]" value="' + data[8] + '"/>';
                        cols += '<input type="hidden" class="" name="variant_id[]" value="' + data[9] + '"/>';
                        cols += '<input type="hidden" class="product_unit_price" name="" value="' + data[2] + '"/>';
                        cols += '<input type="hidden" class="product_unit_cost" name="" value="' + data[10] + '"/>';
                        cols += '<input type="hidden" class="varient_id" name="" value="' + data[1] + '"/>';

                        newRow.append(cols);
                        $(".combo_product_list_table").append(newRow);
                        calculate_price();
                    }
                }
            });
        }
    });

    //Change quantity or unit price
    $("#myTable").on('input', '.qty , .unit_cost, .unit_price', function() {
        calculate_price();
    });

    //Delete product
    $("table.order-list tbody").on("click", ".ibtnDel", function(event) {
        $(this).closest("tr").remove();
        calculate_price();
    });

    function hide() {
        $("#cost").hide(300);
        $("#unit").hide(300);
        $("#alert-qty").hide(300);
    }

    function calculate_price() {
        var price = 0;
        var cost = 0
        $(".qty").each(function() {
            rowindex = $(this).closest('tr').index();
            quantity =  $(this).val();
            unit_price = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product_unit_price').val();
            product_unit_price = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .product_unit_price').val();
            product_unit_cost = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ')  .product_unit_cost').val();

            // price += quantity * unit_price;
            unit_cost = $('table.order-list tbody tr:nth-child(' + (rowindex + 1) + ') .unit_cost').val();
            cost += quantity * unit_cost;

            // subtotal calculation
            let $row = $(this).closest('tr');
            let qty = parseFloat($(this).val()) || 0;

            // Get selected option and its data attributes
            let $selectedOption = $row.find('.combo_unit_id option:selected');
            let operator = $selectedOption.data('operator');
            let operationValue = parseFloat($selectedOption.data('operation_value')) || 1;

            // Convert quantity based on operator
            let convertedQty = quantity;
            if (operator === '*') {
                convertedQty = quantity * operationValue;
            } else if (operator === '/') {
                convertedQty = quantity / operationValue;
            }

            console.log(
                'subtotal :' + unit_price,
                'product_unit_price :' + product_unit_price,
                'product_unit_cost :' + product_unit_cost,
                'convertedQty : ' + convertedQty
            )

            // Calculate subtotal using convertedQty
            let subtotal = convertedQty * unit_price;
            let total_unit_cost = convertedQty * product_unit_cost;
            let total_unit_price = convertedQty * product_unit_price;
            cost += convertedQty * unit_cost;
            price += subtotal;
            // Update subtotal field
            $row.find('.unit_cost').val(total_unit_cost.toFixed(2));
            $row.find('.unit_price').val(total_unit_price.toFixed(2));
            $row.find('.subtotal').val(subtotal.toFixed(2));
        });
        $('input[name="price"]').val(price.toFixed(2));

        let total_cost = 0;
        $('input[name="product_unit_cost[]"]').each(function() {
            let value = parseFloat($(this).val()) || 0;
            total_cost += value;
        });
        $('input[name="cost"]').val(total_cost.toFixed(2));

    }

    function populate_category(unitID){
        $.ajax({
            url: 'saleunit/'+unitID,
            type: "GET",
            dataType: "json",
            success:function(data) {
                  $('select[name="sale_unit_id"]').empty();
                  $('select[name="purchase_unit_id"]').empty();
                  $.each(data, function(key, value) {
                      $('select[name="sale_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                      $('select[name="purchase_unit_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                  });
                  $('.selectpicker').selectpicker('refresh');
            },
        });
    }

    $('input[name="profit_margin"]').val("{{ $general_setting->default_margin_value }}");

    let marginType = $('select[name="profit_margin_type"]').val();

    $('input[name="profit_margin"]').on("input", function () {
        if (marginType === "flat" && parseFloat($(this).val()) < 0) {
            $(this).val(0);
        }
    });

    // Profit type change
    $('select[name="profit_margin_type"]').on("change", function () {
        marginType = $(this).val();

        // update placeholder
        $('input[name="profit_margin"]').attr(
            "placeholder",
            marginType === "percentage" ? "% value" : "Flat amount"
        );

        recalcPrice();
    }).trigger("change");

    // Recalc price function
    function recalcPrice() {
        let cost   = parseFloat($('input[name="cost"]').val()) || 0;
        let margin = parseFloat($('input[name="profit_margin"]').val()) || 0;

        let price =
            marginType === "percentage"
                ? cost + (cost * margin / 100)
                : cost + margin;

        $('input[name="price"]').val(price.toFixed(2)).trigger("change");
    }

    // Recalc margin when price edited manually
    function recalcMargin() {
        let price = parseFloat($('input[name="price"]').val()) || 0;
        let cost  = parseFloat($('input[name="cost"]').val()) || 0;

        let margin =
            marginType === "percentage"
                ? ((price - cost) / cost * 100).toFixed(2)
                : (price - cost).toFixed(2);

        $('input[name="profit_margin"]').val(margin);
    }

    // Live calculations
    $('input[name="cost"], input[name="profit_margin"]').on("input", function () {
        recalcPrice();
    });

    $('input[name="price"]').on("input", function () {
        recalcMargin();
    });

    // Warning UI
    $('input[name="price"], input[name="cost"]').on("change keyup", function () {

        let curCost  = parseFloat($('input[name="cost"]').val()) || 0;
        let curPrice = parseFloat($('input[name="price"]').val()) || 0;

        if (isNaN(curPrice) || curPrice === 0) {
            $('#product-price-warning').addClass('d-none');
            return;
        }

        if (curCost <= 0) {
            $('#product-cost-warning').removeClass('d-none');
        } else {
            $('#product-cost-warning').addClass('d-none');
        }

        if (curPrice <= curCost) {
            $('#product-price-warning').removeClass('d-none');
        } else {
            $('#product-price-warning').addClass('d-none');
        }
    });

    $("input[name='is_initial_stock']").on("change", function () {
        if ($(this).is(':checked')) {
            if(numberOfWarehouse > 0)
                $("#initial-stock-section").show(300);
            else {
                showAlert('Please create warehouse first before adding stock!', 'danger');
                $(this).prop("checked", false);
            }
        }
        else {
            $("#initial-stock-section").hide(300);
        }
    });

    function initialStockUncheck(){
        $("input[name='is_initial_stock']").prop("checked", false);
        $("input[name='featured']").prop("checked", false);
        $("#initial-stock-section").hide(300);
    }

    $("input[name='is_batch']").on("change", function () {
        if ($(this).is(':checked')) {
            initialStockUncheck()
            $("#variant-option").hide(300);
            $("#stock-section").hide(300);
            $("#featured").hide(300);

        }
        else {
            $("#variant-option").show(300);
            $("#stock-section").show(300);
            $("#featured").show(300);
        }

    });

    $("input[name='is_imei']").on("change", function () {
        if ($(this).is(':checked')) {
            initialStockUncheck()
            $("#stock-section").hide(300);
            $("#featured").hide(300);
        }
        else {
            $("#stock-section").show(300);
            $("#featured").show(300);
        }

    });

    $("input[name='is_variant']").on("change", function () {
        if ($(this).is(':checked')) {
            initialStockUncheck()
            $("#variant-section").show(300);
            $("#batch-option").hide(300);
            $(".variant-field").prop("required", true);
            $("#stock-section").hide(300);
        }
        else {
            $("#variant-section").hide(300);
            $("#batch-option").show(300);
            $(".variant-field").prop("required", false);
            $("#stock-section").show(300);
        }
    });

    $("input[name='is_diffPrice']").on("change", function () {
        if ($(this).is(':checked')) {
            $("#diffPrice-section").show(300);
        }
        else
            $("#diffPrice-section").hide(300);
    });

    $( "#promotion" ).on( "change", function() {
        if ($(this).is(':checked')) {
            $("#starting_date").val($.datepicker.formatDate('dd-mm-yy', new Date()));
            $("#promotion_price").show(300);
            $("#start_date").show(300);
            $("#last_date").show(300);
        }
        else {
            $("#promotion_price").hide(300);
            $("#start_date").hide(300);
            $("#last_date").hide(300);
        }
    });

    var starting_date = $('#starting_date');
    starting_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    var ending_date = $('#ending_date');
    ending_date.datepicker({
     format: "dd-mm-yyyy",
     startDate: "<?php echo date('d-m-Y'); ?>",
     autoclose: true,
     todayHighlight: true
     });

    $(window).keydown(function(e){
        if (e.which == 13) {
            var $targ = $(e.target);

            if (!$targ.is("textarea") && !$targ.is(":button,:submit")) {
                var focusNext = false;
                $(this).find(":input:visible:not([disabled],[readonly]), a").each(function(){
                    if (this === e.target) {
                        focusNext = true;
                    }
                    else if (focusNext){
                        $(this).focus();
                        return false;
                    }
                });

                return false;
            }
        }
    });
    //dropzone portion
    Dropzone.autoDiscover = false;

    jQuery.validator.setDefaults({
        errorPlacement: function (error, element) {
            if(error.html() == 'Select Category...')
                error.html('This field is required.');
            $(element).closest('div.form-group').find('.validation-msg').html(error.html());
        },
        highlight: function (element) {
            $(element).closest('div.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).closest('div.form-group').removeClass('has-error').addClass('has-success');
            $(element).closest('div.form-group').find('.validation-msg').html('');
        }
    });

    function validate() {
        // console.log('validate function: ' + 1516);
        var product_code = $("input[name='code']").val();
        var barcode_symbology = $('select[name="barcode_symbology"]').val();
        var exp = /^\d+$/;

        if(!(product_code.match(exp)) && (barcode_symbology == 'UPCA' || barcode_symbology == 'UPCE' || barcode_symbology == 'EAN8' || barcode_symbology == 'EAN13') ) {
            showAlert('Product code must be numeric.', 'danger');
            return false;
        }
        else if(product_code.match(exp)) {
            if(barcode_symbology == 'UPCA' && product_code.length > 11){
                showAlert('Product code length must be less than 12', 'danger');
                return false;
            }
            else if(barcode_symbology == 'EAN8' && product_code.length > 7){
                showAlert('Product code length must be less than 8', 'danger');
                return false;
            }
            /*else if(barcode_symbology == 'EAN13' && product_code.length > 12){
                alert('Product code length must be less than 13');
                return false;
            }*/
        }

        if( $("#type").val() == 'combo' ) {
            var rownumber = $('table.order-list tbody tr:last').index();
            if (rownumber < 0) {
                showAlert("Please insert product to table!", 'danger')
                return false;
            }
        }
        if($("#is-variant").is(":checked")) {
            rowindex = $("table#variant-table tbody tr:last").index();
            if (rowindex < 0) {
                showAlert('This product has variant. Please insert variant to table', 'danger');
                return false;
            }
        }
        $("input[name='price']").prop('disabled',false);
        return true;
    }

    /*$('#submit-btn').on("click", function (e) {
        $('#submit-btn').attr('disabled','true').html('<span class="spinner-border text-light" role="status"></span> {{__("db.Saving")}}...');
    })*/

    $(".dropzone").sortable({
        items:'.dz-preview',
        cursor: 'grab',
        opacity: 0.5,
        containment: '.dropzone',
        distance: 20,
        tolerance: 'pointer',
        stop: function () {
          var queue = myDropzone.getAcceptedFiles();
          newQueue = [];
          $('#imageUpload .dz-preview .dz-filename [data-dz-name]').each(function (count, el) {
                var name = el.innerHTML;
                queue.forEach(function(file) {
                    if (file.name === name) {
                        newQueue.push(file);
                    }
                });
          });
          myDropzone.files = newQueue;
        }
    });

    myDropzone = new Dropzone('div#imageUpload', {
        addRemoveLinks: true,
        autoProcessQueue: false,
        uploadMultiple: true,
        parallelUploads: 100,
        maxFilesize: 12,
        paramName: 'image',
        clickable: true,
        method: 'POST',
        url: "{{route('products.store')}}",
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        renameFile: function(file) {
            var dt = new Date();
            var time = dt.getTime();
            return time + file.name;
        },
        acceptedFiles: ".jpeg,.jpg,.png,.gif,.webp",
        init: function () {
            var myDropzone = this;
            $('#submit-btn, #submit-and-insert-btn').on("click", function (e) {
                e.preventDefault();
                submit_type = $(this).attr('id') == 'submit-btn' ? 'add' : 'insert';
                if ( $("#product-form").valid() && validate() ) {
                    $(this).attr('disabled','true').html('<span class="spinner-border text-light" role="status"></span> {{__("db.Saving")}}...');
                    tinyMCE.triggerSave();
                    if(myDropzone.getAcceptedFiles().length) {
                        myDropzone.processQueue();
                    }
                    else {
                        var formData = new FormData();
                        var data = $("#product-form").serializeArray();
                        $.each(data, function (key, el) {
                            formData.append(el.name, el.value);
                        });
                        var file = $('#file')[0].files;
                        if(file.length > 0)
                            formData.append('file',file[0]);

                        $.ajax({
                            type:'POST',
                            url:"{{route('products.store')}}",
                            data: formData,
                            contentType: false,
                            processData: false,
                            success:function(response) {
                                if(submit_type == 'add')
                                    location.href = '../products';
                                else {
                                    showAlert(response.message, 'success');
                                    resetForm();
                                }
                            },
                            error:function(response) {
                                // console.log(response);
                              if(response.responseJSON.errors.name) {
                                  $("#name-error").text(response.responseJSON.errors.name);
                              }
                              else if(response.responseJSON.errors.code) {
                                  $("#code-error").text(response.responseJSON.errors.code);
                              }
                            },
                        });
                    }
                }
            });

            this.on('sending', function (file, xhr, formData) {
                // Append all form inputs to the formData Dropzone will POST
                var data = $("#product-form").serializeArray();
                $.each(data, function (key, el) {
                    formData.append(el.name, el.value);
                });
                var file = $('#file')[0].files;
                if(file.length > 0)
                    formData.append('file',file[0]);
                // console.log(formData);
            });
        },
        error: function (file, response) {
            console.log(response.message, 'hi');
            if(response.message) {
              $("#name-error").text(response.message);
              this.removeAllFiles(true);
            }
            else if(response.code) {
              $("#code-error").text(response.code);
              this.removeAllFiles(true);
            }
            else {
              try {
                  var res = JSON.parse(response);
                  if (typeof res.message !== 'undefined' && !$modal.hasClass('in')) {
                      $("#success-icon").attr("class", "fas fa-thumbs-down");
                      $("#success-text").html(res.message);
                      $modal.modal("show");
                  } else {
                      if ($.type(response) === "string")
                          var message = response; //dropzone sends it's own error messages in string
                      else
                          var message = response.message;
                      file.previewElement.classList.add("dz-error");
                      _ref = file.previewElement.querySelectorAll("[data-dz-errormessage]");
                      _results = [];
                      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                          node = _ref[_i];
                          _results.push(node.textContent = message);
                      }
                      return _results;
                  }
              } catch (error) {
                  console.log(error, 'hi');
              }
            }
        },
        successmultiple: function (file, response) {
            if(submit_type == 'add')
                location.href = '../products';
            else {
                showAlert(response.message, 'success');
                resetForm();
            }
        },
        completemultiple: function (file, response) {
            console.log(file, response, "completemultiple");
        },
        reset: function () {
            console.log("resetFiles");
            this.removeAllFiles(true);
        }
    });
    // brand create ajax start
    $('.brand-submit-btn').on("click", function() {
        $.ajax({
            type:'POST',
            url:'{{route('brand.store')}}',
            data: $("#brand-form").serialize(),
            success:function(response) {
                key = response['id'];
                value = response['title'];
                $('select[name="brand_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                $('select[name="brand_id"]').val(key);
                $('.selectpicker').selectpicker('refresh');
                $("#addBrand").modal('hide');
            }
        });
    });
    $('.category-model').on("click", function() {
        $('.category-submit-btn').prop('type', 'button');
        $('.category-ajax-check').val(1);
    });
    // category create ajax start
    $('.category-submit-btn').on("click", function() {
        $.ajax({
            type:'POST',
            url:'{{route('category.store')}}',
            data: $("#category-form").serialize(),
            success:function(response) {
                key = response['id'];
                value = response['name'];
                $('select[name="category_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                $('select[name="category_id"]').val(key);
                $('.selectpicker').selectpicker('refresh');
                $("#category-modal").modal('hide');
            }
        });
    });
    // Tax Ajax Create
        // category create ajax start
        $('.tax-submit-btn').on("click", function() {
        $.ajax({
            type:'POST',
            url:'{{route('tax.store')}}',
            data: $("#tax-form").serialize(),
            success:function(response) {
                key = response['id'];
                value = response['name'];
                $('select[name="tax_id"]').append('<option value="'+ key +'">'+ value +'</option>');
                $('select[name="tax_id"]').val(key);
                $('.selectpicker').selectpicker('refresh');
                $("#addTax").modal('hide');
            }
        });
    });

    function resetForm() {
        $("#product-form")[0].reset();
        $('#submit-btn, #submit-and-insert-btn').removeAttr('disabled');
        $('#submit-btn').html('{{__("db.add_product")}}');
        $('#submit-and-insert-btn').html('Save and Insert Another');
        
        // Core Reset logic
        if(typeof myDropzone !== 'undefined') {
            myDropzone.removeAllFiles(true);
        }
        $('.combo_product_list_table').empty();
        $('.selectpicker').selectpicker('refresh');
        
        // Sync Profit Margin & Type
        $('select[name="profit_margin_type"]').trigger('change');
        $('input[name="profit_margin"]').val("{{ $general_setting->default_margin_value }}");
        
        // Clear dynamic sections
        if(typeof $('.type-variant').importTags === 'function') {
            $('.type-variant').importTags('');
        }
        $('table.variant-list tbody').empty();
        
        // Reset TinyMCE
        if(tinyMCE.get(0)) {
            tinyMCE.get(0).setContent('');
        }
        
        // Hide conditional sections
        $("#digital, #combo, #variant-section, #initial-stock-section, #diffPrice-section, #promotion_price, #start_date, #last_date").hide();
        
        // Reset numerical defaults
        $("input[name='qty']").val("{{number_format(0, $general_setting->decimal, '.', '')}}");
        $("input[name='code']").val(""); // Cleared for unique entry
    }

</script>

<script>
$(document).on('click', '#create_unit', function (e) {
    e.preventDefault();

    let form = $(this).closest('form');
    let formData = form.serialize();

    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                // close modal
                $('#createUnitModal').modal('hide');

                // clear form
                form.trigger('reset');

                // append new unit to dropdown (example)
                if (response.unit) {
                    $("select[name='unit_id']").append(
                        `<option value="${response.unit.id}">${response.unit.unit_name}</option>`
                    ).selectpicker('refresh');
                }

            } else {
            }
        }
    });
});
</script>

@endpush
