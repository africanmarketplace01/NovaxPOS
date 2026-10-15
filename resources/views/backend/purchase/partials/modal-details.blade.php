{{-- ── Purchase Details Modal ───────────────────────────────────────────────── --}}
<div id="purchase-details" tabindex="-1" role="dialog"
     aria-labelledby="purchaseModalLabel" aria-hidden="true"
     class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">

            <div class="container mt-3 pb-2 border-bottom">
                <div class="row">
                    <div class="col-md-6 d-print-none">
                        <button id="print-btn" type="button" class="btn btn-default btn-sm">
                            <i class="dripicons-print"></i> {{ __('db.Print') }}
                        </button>
                    </div>
                    <div class="col-md-6 d-print-none">
                        <button type="button" id="close-btn" data-dismiss="modal"
                                aria-label="Close" class="close">
                            <span aria-hidden="true"><i class="dripicons-cross"></i></span>
                        </button>
                    </div>
                    <div class="col-md-12">
                        <h3 id="purchaseModalLabel" class="modal-title text-center container-fluid">
                            {{ $general_setting->site_title }}
                        </h3>
                    </div>
                    <div class="col-md-12 text-center">
                        <i style="font-size: 15px;">{{ __('db.Purchase Details') }}</i>
                    </div>
                </div>
            </div>

            <div id="purchase-content" class="modal-body"></div>
            <br>

            <table class="table table-bordered product-purchase-list">
                <thead>
                    <th>#</th>
                    <th>{{ __('db.product') }}</th>
                    <th>{{ __('db.Batch No') }}</th>
                    <th>Qty</th>
                    <th>{{ __('db.Returned') }}</th>
                    <th>{{ __('db.Unit Cost') }}</th>
                    <th>{{ __('db.Tax') }}</th>
                    <th>{{ __('db.Discount') }}</th>
                    <th>{{ __('db.Subtotal') }}</th>
                </thead>
                <tbody></tbody>
            </table>

            <div id="purchase-footer" class="modal-body"></div>
        </div>
    </div>
</div>
