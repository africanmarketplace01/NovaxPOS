{{-- ============================================================ --}}
{{-- PAYMENT MODAL (#add-payment) --}}
{{-- Finalize sale: payment rows, amounts, currency --}}
{{-- ============================================================ --}}

                <!-- payment modal -->
                <div id="add-payment" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Finalize Sale')}}</h5>
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-10" id="payment-select-row">
                                        <div class="row">
                                            <div class="col-md-3 col-6 mt-1 paying-amount-container">
                                                <label>{{__('db.Paying Amount')}} *</label>
                                                <input type="text" name="paid_amount[]" value="0" class="form-control paid_amount numkey" step="any">
                                            </div>
                                            <div class="col-md-3 col-6 mt-1">
                                                <input type="hidden" name="paid_by_id[]">
                                                <label>{{__('db.Paid By')}}</label>
                                                <select name="paid_by_id_select[]" class="form-control selectpicker">
                                                    @if(in_array("cash",$options))
                                                    <option value="1">Cash</option>
                                                    @endif
                                                    @if(in_array("gift_card",$options))
                                                    <option value="2">Gift Card</option>
                                                    @endif
                                                    @if(in_array("card",$options))
                                                    <option value="3">Credit Card</option>
                                                    @endif
                                                    @if(in_array("cheque",$options))
                                                    <option value="4">Cheque</option>
                                                    @endif
                                                    @if(in_array("deposit",$options))
                                                    <option value="6">Deposit</option>
                                                    @endif
                                                    @if($lims_reward_point_setting_data && $lims_reward_point_setting_data->is_active)
                                                    <option value="7">Points</option>
                                                    @endif
                                                    @if(in_array("credit",$options))   {{-- ✅ এটা যোগ করুন --}}
                                                    <option value="credit_sale">Credit Sale</option>
                                                    @endif
                                                    @if(in_array("razorpay",$options))
                                                    <option value="razorpay">Razorpay</option>
                                                    @endif
                                                    @foreach($options as $option)
                                                        @if(!in_array($option, ['cash','card','cheque','gift_card','deposit','paypal','pesapal','points','credit']))
                                                            <option value="{{$option}}">{{ucfirst($option)}}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3 col-6 mt-1 cash-received-container">
                                                <label id="received-paying">{{__('db.Cash Received')}} <x-info title="Cash handed over to you. example: sale amount is 300. customer gives you 500. cash received: 500 " type="info" /> *</label>
                                                <input type="text" name="paying_amount[]" class="form-control paying_amount numkey" required step="any">
                                            </div>
                                        </div>
                                        <div class="row add-more-row mt-2">
                                            <div class="col-md-12 text-center"><button class="btn btn-info add-more">+ {{__('db.Add More Payment')}}</button></div>
                                        </div>
                                        <div id="payment_receiver_id" class="row">
                                            <div class="col-md-12 mt-1">
                                                <label>{{__('db.Payment Receiver')}}</label>
                                                <input type="text" name="payment_receiver" class="form-control">
                                            </div>
                                            <div class="form-group col-md-12">
                                                <label>{{__('db.Payment Note')}}</label>
                                                <textarea id="payment_note" rows="2" class="form-control" name="payment_note"></textarea>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>{{__('db.Sale Note')}}</label>
                                                <textarea rows="3" class="form-control" name="sale_note"></textarea>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <label>{{__('db.Staff Note')}}</label>
                                                <textarea rows="3" class="form-control" name="staff_note"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 p-2 bg-info text-light pt-4 pb-4 payment-info">
                                        <div class="mt-4">
                                            <h2>{{__('db.Total Payable')}}</h2>
                                            <p class="total_payable text-light"></p>
                                        </div>
                                        <div class="mt-4">
                                            <h2>{{__('db.Total Paying')}}</h2>
                                            <p class="total_paying text-light">0.00</p>
                                        </div>
                                        <div class="mt-4">
                                            <h2>{{__('db.Change')}}</h2>
                                            <p class="change text-light">0.00</p>
                                        </div>
                                        <div class="mt-4">
                                            <h2>{{__('db.Due')}}</h2>
                                            <p class="due text-light">0.00</p>
                                        </div>
                                    </div>
                                    {{-- points info here --}}
                                    <div class="points-info col-md-2 bg-info text-light p-2  pt-4 pb-4 ">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mt-3">
                                            <button id="submit-btn" type="button" class="btn btn-primary">{{__('db.submit')}}</button>
                                            @if ($lims_pos_setting_data && $lims_pos_setting_data->show_print_invoice)
                                                <div class="form-check d-inline-block ml-3">
                                                    <input class="form-check-input" type="checkbox" name="print_invoice" id="print_invoice" checked>
                                                    <label style="color:rgb(136, 136, 136);" class="form-check-label" for="print_invoice">
                                                        {{ __('db.print_invoice') }}
                                                    </label>
                                                </div>
                                            @endif
                                            <div class="form-check d-inline-block ml-3">
                                                <input class="form-check-input" type="checkbox" name="send_whatsapp" id="send_whatsapp" checked>
                                                <label style="color:rgb(136, 136, 136);" class="form-check-label" for="send_whatsapp">
                                                    {{ __('db.send_whatsapp_message') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

