{{-- ── Import product modal ──────────────────────────────────────────── --}}
<div id="importProduct" tabindex="-1" role="dialog" aria-labelledby="importProductLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('product.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 id="importProductLabel" class="modal-title">
                        {{ __('db.import_product') }} / {{ __('db.bulk_update') }}
                    </h5>
                    <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                        <span aria-hidden="true"><i class="dripicons-cross"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="italic"><small>{{ __('db.The field labels marked with are required input fields') }}.</small></p>
                    <p>{{ __('db.The correct column order is') }} (image, name*, code*, type*, brand, category*, unit_code*, cost*, profit_margin(%), price, wholesale price, product_details, variant_name, item_code, additional_price) {{ __('db.and you must follow this') }}.</p>
                    <p>If you provide profit_margin, then price will be calculated based on profit_margin: <strong>price = cost * (1 + profit_margin / 100)</strong></p>
                    <p>{{ __('db.To display Image it must be stored in') }} images/product {{ __('db.directory') }}. {{ __('db.Image name must be same as product name') }}</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.Upload CSV File') }} *</label>
                                <input type="file" name="file" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('db.Sample File') }}</label>
                                <a href="sample_file/sample_products.csv" class="btn btn-info btn-block btn-md">
                                    <i class="dripicons-download"></i> {{ __('db.Download') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('db.Submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
