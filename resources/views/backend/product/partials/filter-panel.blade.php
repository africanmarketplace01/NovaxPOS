{{-- ── Filter panel (collapsible) ────────────────────────────────────── --}}
<div class="card mt-3 mb-2">
    <div class="card-body" id="filter-card" style="display: none;">
        <div class="row">

            <div class="col-md-3 @if(\Auth::user()->role_id > 2){{'d-none'}}@endif">
                <div class="form-group top-fields">
                    <label>{{ __('db.Warehouse') }}</label>
                    <select id="warehouse_id" name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins">
                        <option value="0">{{ __('db.All Warehouse') }}</option>
                        @foreach($lims_warehouse_list as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.Product Type') }}</label>
                    <select name="product_type" class="form-control selectpicker" id="product_type" data-live-search="true" data-live-search-style="begins">
                        <option value="all" selected>All Types</option>
                        <option value="standard">Standard</option>
                        <option value="combo">Combo</option>
                        <option value="digital">Digital</option>
                        <option value="service">Service</option>
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.Brand') }}</label>
                    <select name="brand_id" class="form-control selectpicker" id="brand_id" data-live-search="true" data-live-search-style="begins">
                        <option value="0" selected>All Brands</option>
                        @foreach($lims_brand_list as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.category') }}</label>
                    <select name="category_id" class="form-control selectpicker" id="category_id" data-live-search="true" data-live-search-style="begins">
                        <option value="0" selected>All Categories</option>
                        @foreach($lims_category_list as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.Unit') }}</label>
                    <select name="unit_id" class="form-control selectpicker" id="unit_id" data-live-search="true" data-live-search-style="begins">
                        <option value="0" selected>All Unit</option>
                        @foreach($lims_unit_list as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->unit_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.Tax') }}</label>
                    <select name="tax_id" class="form-control selectpicker" id="tax_id" data-live-search="true" data-live-search-style="begins">
                        <option value="0" selected>All Tax</option>
                        @foreach($lims_tax_list as $tax)
                            <option value="{{ $tax->id }}">{{ $tax->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.Product with') }}</label>
                    <select name="imeiorvariant" class="form-control selectpicker" id="imeiorvariant">
                        <option value="0" selected>Select IMEI/Variant/Batch</option>
                        <option value="batch">Batch/Expiry</option>
                        <option value="imei">IMEI</option>
                        <option value="variant">Variant</option>
                    </select>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.stock') }}</label>
                    <select name="stock_filter" class="form-control selectpicker" id="stock_filter">
                        <option value="all" selected>All</option>
                        <option value="with">With Stock</option>
                        <option value="without">Without Stock</option>
                    </select>
                </div>
            </div>

            <div id="filter-loading" class="col-12 text-center my-2" style="display:none;">
                <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                <span>Loading results...</span>
            </div>

        </div>
    </div>
</div>
