{{-- ── Product details modal ─────────────────────────────────────────── --}}
<div id="product-details" tabindex="-1" role="dialog" aria-labelledby="productDetailsLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="productDetailsLabel" class="modal-title">{{ __('db.Product Details') }}</h5>
                <button id="print-btn" type="button" class="btn btn-default btn-sm ml-3">
                    <i class="dripicons-print"></i> {{ __('db.Print') }}
                </button>
                <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close">
                    <span aria-hidden="true"><i class="dripicons-cross"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5" id="slider-content"></div>
                    <div class="col-md-5 offset-1" id="product-content"></div>

                    @if($role_id <= 2)
                    <div class="col-md-12 mt-2" id="product-warehouse-section">
                        <h5>{{ __('db.Warehouse Quantity') }}</h5>
                        <table class="table table-bordered table-hover product-warehouse-list">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    @endif

                    <div class="col-md-7 mt-2" id="product-variant-section">
                        <h5>{{ __('db.Product Variant Information') }}</h5>
                        <table class="table table-bordered table-hover product-variant-list">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    @if($role_id <= 2)
                    <div class="col-md-5 mt-2" id="product-variant-warehouse-section">
                        <h5>{{ __('db.Warehouse quantity of product variants') }}</h5>
                        <table class="table table-bordered table-hover product-variant-warehouse-list">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    @endif
                </div>

                <h5 id="combo-header"></h5>
                <table class="table table-bordered table-hover item-list">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
