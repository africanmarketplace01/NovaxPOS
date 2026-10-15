{{-- ============================================================ --}}
{{-- CASH REGISTER MODALS (conditional on cash_register setting) --}}
{{-- Contains: open register modal, register details modal --}}
{{-- ============================================================ --}}

@if($lims_pos_setting_data && $lims_pos_setting_data->cash_register)
<!-- add cash register modal -->
<div id="cash-register-modal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cashRegister.store') }}" method="POST">
                @csrf
            <div class="modal-header">
                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Cash Register')}}</h5>
            </div>
            <div class="modal-body">
                <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                <div class="row">
                    <div class="col-md-6 form-group warehouse-section">
                        <label>{{__('db.Warehouse')}} *</strong> </label>
                        <select required name="warehouse_id" class="selectpicker form-control" data-live-search="true" data-live-search-style="begins" title="Select warehouse...">
                            @foreach($lims_warehouse_list as $warehouse)
                            <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                            @endforeach
                        </select>
                        <x-validation-error fieldName="warehouse_id" />
                    </div>
                    <div class="col-md-6 form-group">
                        <label>{{__('db.Cash in Hand')}} *</strong> </label>
                        <input type="number" step="any" name="cash_in_hand" required class="form-control">
                    </div>
                    <div class="col-md-12 form-group">
                        <button type="submit" class="btn btn-primary">{{__('db.submit')}}</button>
                    </div>
                </div>
            </div>
            </form>
        </div>
    </div>
</div>
<!-- cash register details modal -->
<div id="register-details-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Cash Register Details')}}</h5>
                    <small>{{__('db.Please review the transaction and payments')}}</small>
                </div>
                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
            </div>
            <div class="modal-body pt-0">
                <form action="{{route('cashRegister.close')}}" method="POST">
                @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-hover">
                                <tbody>
                                    <tr>
                                        <td>{{__('db.Cash in Hand')}}:</td>
                                        <td id="cash_in_hand" class="text-right">0</td>
                                    </tr>
                                    <tr>
                                        <td>{{__('db.Total Sale Amount')}}:</td>
                                        <td id="total_sale_amount" class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('db.Total Payment')}}:</td>
                                        <td id="total_payment" class="text-right"></td>
                                    </tr>
                                    @if(in_array("cash",$options))
                                    <tr>
                                        <td>{{__('db.Cash Payment')}}:</td>
                                        <td id="cash_payment" class="text-right"></td>
                                    </tr>
                                    @endif
                                    @if(in_array("card",$options))
                                    <tr>
                                        <td>{{__('db.Credit Card Payment')}}:</td>
                                        <td id="credit_card_payment" class="text-right"></td>
                                    </tr>
                                    @endif
                                    @if(in_array("cheque",$options))
                                    <tr>
                                        <td>{{__('db.Cheque Payment')}}:</td>
                                        <td id="cheque_payment" class="text-right"></td>
                                    </tr>
                                    @endif
                                    @if(in_array("gift_card",$options))
                                    <tr>
                                        <td>{{__('db.Gift Card Payment')}}:</td>
                                        <td id="gift_card_payment" class="text-right"></td>
                                    </tr>
                                    @endif
                                    @if(in_array("deposit",$options))
                                    <tr>
                                        <td>{{__('db.Deposit Payment')}}:</td>
                                        <td id="deposit_payment" class="text-right"></td>
                                    </tr>
                                    @endif
                                    @if(in_array("paypal",$options) && (strlen(env('PAYPAL_LIVE_API_USERNAME'))>0) && (strlen(env('PAYPAL_LIVE_API_PASSWORD'))>0) && (strlen(env('PAYPAL_LIVE_API_SECRET'))>0))
                                    <tr>
                                        <td>{{__('db.Paypal Payment')}}:</td>
                                        <td id="paypal_payment" class="text-right"></td>
                                    </tr>
                                    @endif
                                    <tbody id="custom-methods-container"></tbody>
                                    <tr>
                                        <td>{{__('db.Total Sale Return')}}:</td>
                                        <td id="total_sale_return" class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('db.Total Expense')}}:</td>
                                        <td id="total_expense" class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <td>{{__('db.Total Supplier Payment')}}:</td>
                                        <td id="total_supplier_payment" class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{__('db.Total Cash')}}:</strong></td>
                                        <td id="total_cash" class="text-right"></td>
                                    </tr>
                                    <tr id="closing_row" style="display:none">
                                        <td><strong>{{__('db.Actual Cash')}}:</strong></td>
                                        <td class="text-right">
                                            <input class="form-control" type="text" name="actual_cash" style="max-width:200px; text-align:right;float:right" value="" min="0"/>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-12 text-center" id="closing-section">
                            <button id="close_register" type="button" class="btn btn-primary">{{__('db.Close Register')}}</button>
                            <input type="hidden" name="closing_balance">
                            <input type="hidden" name="cash_register_id">
                            <button type="submit" class="btn btn-primary" id="submit_register">{{__('db.Close Register')}}</button>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
