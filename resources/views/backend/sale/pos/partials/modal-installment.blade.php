{{-- ============================================================ --}}
{{-- INSTALLMENT PLAN MODAL + QR SCANNER OVERLAY --}}
{{-- Installment payment configuration; QR code reader overlay div --}}
{{-- ============================================================ --}}

    <!-- ✅ Instalment Plan Modal -->
    <div class="modal fade" id="installmentPlanModal" tabindex="-1" aria-labelledby="installmentPlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Instalment Plan</h5>
                    <button type="button" id="close-installment-modal-x" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                </div>

                <div class="modal-body">
                    <input type="checkbox" class="form-check-input" id="enable_installment" name="enable_installment" style="display:none;">
                    <div id="installmentFields">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{__('db.Plan Name')}}</label>
                                    <input type="text" class="form-control" name="installment_plan[name]" value="12 Months" placeholder="e.g., 6 Month Plan">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{__('db.Price')}}</label>
                                    <input type="number" step="0.01" class="form-control" name="installment_plan[price]" id="installment_price" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{__('db.Additional Amount')}}</label>
                                    <input id="additional_amount" type="number" step="0.01" class="form-control" name="installment_plan[additional_amount]" value="0">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{__('db.Total Amount')}}</label>
                                    <input type="number" step="0.01" class="form-control" name="installment_plan[total_amount]" id="installment_total" readonly>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">{{__('db.Down Payment')}}</label>
                                    <input type="number" step="0.01" class="form-control" id="down_payment_id" name="installment_plan[down_payment]" value="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">{{__('db.Months')}}</label>
                                    <input type="number" step="1" class="form-control" name="installment_plan[months]" id="installment_months" min="1" value="12">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div id="paymentFields" style="display: none;">
                                    <div class="mb-3">
                                        <label class="form-label">{{__('db.Payment Method')}}</label>
                                        <select name="installment_plan[paid_by_id]" class="form-control selectpicker">
                                            @if(in_array("cash",$options))
                                            <option value="1">{{ __('db.Cash') }}</option>
                                            @endif
                                            @if(in_array("gift_card",$options))
                                            <option value="2">{{ __('db.Gift Card') }}</option>
                                            @endif
                                            @if(in_array("card",$options))
                                            <option value="3">{{ __('db.Credit Card') }}</option>
                                            @endif
                                            @if(in_array("cheque",$options))
                                            <option value="4">{{ __('db.Cheque') }}</option>
                                            @endif
                                            @if(in_array("deposit",$options))
                                            <option value="6">{{ __('db.Deposit') }}</option>
                                            @endif
                                            @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                            <option value="7">{{ __('db.Points') }}</option>
                                            @endif
                                            @foreach($options as $option)
                                                @if($option !== 'cash' && $option !== 'card' && $option !== 'cheque' && $option !== 'gift_card' && $option !== 'deposit' && $option !== 'paypal' && $option !== 'pesapal')
                                                    <option value="{{$option}}">{{ucfirst($option)}}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{__('db.Account')}}</label>
                                        <select name="installment_plan[account_id]" class="form-control selectpicker">
                                            @foreach($lims_account_list as $account)
                                            <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{__('db.Payment Note')}}</label>
                                        <textarea name="installment_plan[payment_note]" class="form-control"></textarea>
                                    </div>
                                </div>
                                <div id="schedulePreview" class="mt-2">
                                    <h6>Schedule Preview</h6>
                                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered" id="installmentScheduleTable">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>{{__('db.date')}}</th>
                                                    <th>{{__('db.Amount')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="installment_plan[reference_type]" value="sale">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="close-installment-modal" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="done-installment-modal">Create Instalment</button>
                </div>
            </div>
        </div>
    </div>
</section>
<section id="print-layout" class="">
</section>
<div style="width:100%;max-width:350px;position:fixed;top:5%;left:50%;transform:translateX(-50%);z-index:999">
    <button type="button" class="btn btn-danger" id="closeScannerBtn" style="display:none"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></button>
    <div id="reader" style="width:100%;"></div>
</div>
