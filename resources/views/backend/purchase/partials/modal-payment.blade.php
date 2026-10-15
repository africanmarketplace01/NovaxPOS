{{-- ── View Payments Modal ──────────────────────────────────────────────────── --}}
<div id="view-payment" tabindex="-1" role="dialog"
     aria-labelledby="viewPaymentLabel" aria-hidden="true"
     class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="viewPaymentLabel" class="modal-title">{{ __('db.All Payment') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                    <span aria-hidden="true"><i class="dripicons-cross"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-hover payment-list">
                    <thead>
                        <tr>
                            <th>{{ __('db.date') }}</th>
                            <th>{{ __('db.Reference No') }}</th>
                            <th>{{ __('db.Account') }}</th>
                            <th>{{ __('db.Amount') }}</th>
                            <th>{{ __('db.Paid By') }}</th>
                            <th>{{ __('db.Payment Date') }}</th>
                            <th>{{ __('db.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Add Payment Modal ────────────────────────────────────────────────────── --}}
<div id="add-payment" tabindex="-1" role="dialog"
     aria-labelledby="addPaymentLabel" aria-hidden="true"
     class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="addPaymentLabel" class="modal-title">{{ __('db.Add Payment') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                    <span aria-hidden="true"><i class="dripicons-cross"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('purchase.add-payment') }}" method="POST"
                      class="payment-form" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <input type="hidden" name="balance">
                        <div class="col-md-6">
                            <label>{{ __('db.Recieved Amount') }} *</label>
                            <input type="text" name="paying_amount" class="form-control numkey" step="any" required>
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('db.Paying Amount') }} *</label>
                            <input type="text" id="amount" name="amount" class="form-control" step="any" required>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{ __('db.Change') }} : </label>
                            <p class="change ml-2">{{ number_format(0, $general_setting->decimal, '.', '') }}</p>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{ __('db.Paid By') }}</label>
                            <select name="paid_by_id" class="form-control">
                                <option value="1">{{ __('db.Cash') }}</option>
                                <option value="3">{{ __('db.Credit Card') }}</option>
                                <option value="4">{{ __('db.Cheque') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mt-2">
                        <div class="card-element" class="form-control"></div>
                        <div class="card-errors" role="alert"></div>
                    </div>

                    <div id="cheque">
                        <div class="form-group">
                            <label>{{ __('db.Cheque Number') }} *</label>
                            <input type="text" name="cheque_no" class="form-control">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6">
                            <label>{{ __('db.Account') }}</label>
                            <select class="form-control selectpicker" name="account_id">
                                @foreach($lims_account_list as $account)
                                    <option value="{{ $account->id }}" {{ $account->is_default ? 'selected' : '' }}>
                                        {{ $account->name }} [{{ $account->account_no }}]
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('db.Payment Date') }}</label>
                            <input type="text" name="payment_at" id="payment_at"
                                   class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label>{{ __('db.Currency') }} &amp; {{ __('db.Exchange Rate') }}</label>
                            <div class="form-group d-flex align-items-center">
                                <p id="currency_display"      class="form-control-plaintext mb-0 font-weight-bold mr-3"></p>
                                <p id="exchange_rate_display" class="form-control-plaintext mb-0 font-weight-bold"></p>
                            </div>
                            <input type="hidden" name="currency_id"    id="currency_id">
                            <input type="hidden" name="exchange_rate"   id="exchange_rate">
                        </div>
                        <div class="col-md-4 mt-1">
                            <div class="form-group">
                                <label>{{ __('db.Attach Document') }}</label>
                                <x-info title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported" type="info" />
                                <input type="file" name="document" class="form-control" />
                                @if($errors->has('extension'))
                                    <span><strong>{{ $errors->first('extension') }}</strong></span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Payment Note') }}</label>
                        <textarea rows="3" class="form-control" name="payment_note"></textarea>
                    </div>

                    <input type="hidden" name="purchase_id">
                    <button type="submit" class="btn btn-primary">{{ __('db.submit') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ── Edit Payment Modal ───────────────────────────────────────────────────── --}}
<div id="edit-payment" tabindex="-1" role="dialog"
     aria-labelledby="editPaymentLabel" aria-hidden="true"
     class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="editPaymentLabel" class="modal-title">{{ __('db.Update Payment') }}</h5>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close">
                    <span aria-hidden="true"><i class="dripicons-cross"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('purchase.update-payment') }}" method="POST"
                      class="payment-form" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <label>{{ __('db.Recieved Amount') }} *</label>
                            <input type="text" name="edit_paying_amount" class="form-control numkey" step="any" required>
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('db.Paying Amount') }} *</label>
                            <input type="text" name="edit_amount" class="form-control" step="any" required>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{ __('db.Change') }} : </label>
                            <p class="change ml-2">{{ number_format(0, $general_setting->decimal, '.', '') }}</p>
                        </div>
                        <div class="col-md-6 mt-1">
                            <label>{{ __('db.Paid By') }}</label>
                            <select name="edit_paid_by_id" class="form-control selectpicker">
                                <option value="1">{{ __('db.Cash') }}</option>
                                <option value="3">{{ __('db.Credit Card') }}</option>
                                <option value="4">{{ __('db.Cheque') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group mt-2">
                        <div class="card-element" class="form-control"></div>
                        <div class="card-errors" role="alert"></div>
                    </div>

                    <div id="edit-cheque">
                        <div class="form-group">
                            <label>{{ __('db.Cheque Number') }} *</label>
                            <input type="text" name="edit_cheque_no" class="form-control">
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6">
                            <label>{{ __('db.Account') }}</label>
                            <select class="form-control selectpicker" name="account_id">
                                @foreach($lims_account_list as $account)
                                    <option value="{{ $account->id }}">
                                        {{ $account->name }} [{{ $account->account_no }}]
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mt-1">
                            <div class="form-group">
                                <label>{{ __('db.Attach Document') }}</label>
                                <x-info title="Only jpg, jpeg, png, gif, pdf, csv, docx, xlsx and txt file is supported" type="info" />
                                <input type="file" name="edit_document" class="form-control" />
                                <small id="current_edit_document_link" class="text-muted"></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('db.Payment Date') }}</label>
                            <input type="text" name="payment_at" id="edit_payment_at"
                                   class="form-control" value="" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ __('db.Payment Note') }}</label>
                        <textarea rows="3" class="form-control" name="edit_payment_note"></textarea>
                    </div>

                    <input type="hidden" name="payment_id">
                    <button type="submit" class="btn btn-primary">{{ __('db.update') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
