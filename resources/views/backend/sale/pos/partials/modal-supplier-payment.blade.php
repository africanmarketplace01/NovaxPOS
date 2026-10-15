{{-- ============================================================ --}}
{{-- SUPPLIER PAYMENT MODAL (#add-supplier-payment) --}}
{{-- Quick supplier payment entry from POS --}}
{{-- ============================================================ --}}

<!-- supplier payment modal -->
<div id="add-supplier-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Payment')}}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('supplier.clearDue') }}" method="POST" class="supplier-payment-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mt-1">
                            <label>{{__('db.Supplier')}} *</label>
                            <select name="supplier_id" id="supplier_list" class="form-control" data-live-search="true" data-live-search-style="begins" title="Select Supplier..." required>
                                <option value=""></option>
                            </select>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{__('db.Due')}}</label>
                            <input type="number" class="form-control" readonly name="balance">
                        </div>
                        <div class="col-md-12 mt-1">
                            <label>{{__('db.Amount')}} *</label>
                            <input type="number" id="supplier-amount" name="amount" step="any" class="form-control" required>
                        </div>
                        <div class="col-md-12 mt-1">
                            <label>{{__('db.Note')}}</label>
                            <textarea name="note" rows="4" class="form-control"></textarea>
                        </div>
                    </div>
                    <input type="hidden" name="cash_register" value="" />
                    <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- end supplier payment modal -->
