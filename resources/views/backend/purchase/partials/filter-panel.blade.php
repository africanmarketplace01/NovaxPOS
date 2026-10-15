<div class="card mt-3 mb-2">
    <div class="card-body" id="filter-card" style="display: none;">
        <div class="row mt-2">

            {{-- Date range --}}
            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.date') }}</label>
                    <input type="text" class="daterangepicker-field form-control"
                           value="{{ $starting_date }} To {{ $ending_date }}" required />
                    <input type="hidden" name="starting_date" value="{{ $starting_date }}" />
                    <input type="hidden" name="ending_date"   value="{{ $ending_date }}"   />
                </div>
            </div>

            {{-- Warehouse (admin only) --}}
            <div class="col-md-3 @if(auth()->user()->role_id > 2) d-none @endif">
                <div class="form-group top-fields">
                    <label>{{ __('db.Warehouse') }}</label>
                    <select id="warehouse_id" name="warehouse_id"
                            class="selectpicker form-control"
                            data-live-search="true" data-live-search-style="begins">
                        <option value="0">{{ __('db.All Warehouse') }}</option>
                        @foreach($lims_warehouse_list as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Purchase status --}}
            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.Purchase Status') }}</label>
                    <select id="purchase-status" class="form-control" name="purchase_status">
                        <option value="0">{{ __('db.All') }}</option>
                        <option value="1">{{ __('db.Recieved') }}</option>
                        <option value="2">{{ __('db.Partial') }}</option>
                        <option value="3">{{ __('db.Pending') }}</option>
                        <option value="4">{{ __('db.Ordered') }}</option>
                    </select>
                </div>
            </div>

            {{-- Payment status --}}
            <div class="col-md-3">
                <div class="form-group top-fields">
                    <label>{{ __('db.Payment Status') }}</label>
                    <select id="payment-status" class="form-control" name="payment_status">
                        <option value="0">{{ __('db.All') }}</option>
                        <option value="1">{{ __('db.Due') }}</option>
                        <option value="2">{{ __('db.Paid') }}</option>
                    </select>
                </div>
            </div>

            {{-- AJAX loading indicator --}}
            <div id="filter-loading" class="col-12 text-center my-2" style="display: none;">
                <span class="spinner-border text-primary spinner-border-sm" role="status"></span>
                <span>Loading results...</span>
            </div>

        </div>
    </div>
</div>
