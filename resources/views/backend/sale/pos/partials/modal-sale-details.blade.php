{{-- ============================================================ --}}
{{-- SALE DETAILS / RECENT TRANSACTIONS MODALS --}}
{{-- Contains: sale details, recent sales, recent drafts, today-sale, today-profit modals --}}
{{-- ============================================================ --}}

                <!-- Sale detaisl -->
                <div id="get-sale-details" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="container mt-3 pb-2 border-bottom">
                                <div class="row">
                                    <div class="col-md-6 d-print-none">
                                        <button id="print-btn" type="button" class="btn btn-light btn-sm"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" /></svg> {{__('db.Print')}}</button>

                                        <form action="{{ route('sale.sendmail') }}" method="POST" class="sendmail-form">
                                            @csrf
                                            <input type="hidden" name="sale_id">
                                            <button class="btn btn-light btn-sm d-print-none"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg> {{__('db.Email')}}</button>
                                        </form>
                                    </div>
                                    <div class="col-md-6 d-print-none">
                                        <button type="button" id="close-btn" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                                    </div>
                                    <div class="col-md-4 text-left">
                                        <img src="{{url('logo', $general_setting->site_logo)}}" width="90px;">
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h3 id="exampleModalLabel" class="modal-title container-fluid">{{$general_setting->site_title}}</h3>
                                    </div>
                                    <div class="col-md-4 text-right">
                                        <i style="font-size: 15px;">{{__('db.Sale Details')}}</i>
                                    </div>
                                </div>
                            </div>
                            <div id="sale-content" class="modal-body">
                            </div>
                            <br>
                            <table class="table table-bordered product-sale-list">
                                <thead>
                                    <th>#</th>
                                    <th>{{__('db.product')}}</th>
                                    <th>{{__('db.Batch No')}}</th>
                                    <th>{{__('db.qty')}}</th>
                                    <th>{{__('db.Returned')}}</th>
                                    <th>{{__('db.Unit Price')}}</th>
                                    <th>{{__('db.Tax')}}</th>
                                    <th>{{__('db.Discount')}}</th>
                                    <th>{{__('db.Subtotal')}}</th>
                                    <th>{{__('db.Delivered')}}</th>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                            <div id="sale-footer" class="modal-body"></div>
                        </div>
                    </div>
                </div>

                <!-- today sale modal -->
                <div id="today-sale-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Today Sale')}}</h5>
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            <div class="modal-body">
                                <small>{{__('db.Please review the transaction and payments')}}</small>
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-hover">
                                            <tbody>
                                                <tr>
                                                    <td>{{__('db.Total Sale Amount')}}:</td>
                                                    <td class="total_sale_amount text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Cash Payment')}}:</td>
                                                    <td class="cash_payment text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Credit Card Payment')}}:</td>
                                                    <td class="credit_card_payment text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Cheque Payment')}}:</td>
                                                    <td class="cheque_payment text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Gift Card Payment')}}:</td>
                                                    <td class="gift_card_payment text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Deposit Payment')}}:</td>
                                                    <td class="deposit_payment text-right"></td>
                                                </tr>
                                                @if(in_array("paypal",$options) && (strlen(env('PAYPAL_LIVE_API_USERNAME'))>0) && (strlen(env('PAYPAL_LIVE_API_PASSWORD'))>0) && (strlen(env('PAYPAL_LIVE_API_SECRET'))>0))
                                                <tr>
                                                    <td>{{__('db.Paypal Payment')}}:</td>
                                                    <td class="paypal_payment text-right"></td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <td>{{__('db.Total Payment')}}:</td>
                                                    <td class="total_payment text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Total Sale Return')}}:</td>
                                                    <td class="total_sale_return text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Total Expense')}}:</td>
                                                    <td class="total_expense text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>{{__('db.Total Cash')}}:</strong></td>
                                                    <td class="total_cash text-right"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- today profit modal -->
                <div id="today-profit-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Today Profit')}}</h5>
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <select required name="warehouseId" class="form-control">
                                            <option value="0">{{__('db.All Warehouse')}}</option>
                                            @foreach($lims_warehouse_list as $warehouse)
                                            <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-12 mt-2">
                                        <table class="table table-hover">
                                            <tbody>
                                                <tr>
                                                    <td>{{__('db.Product Revenue')}}:</td>
                                                    <td class="product_revenue text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Product Cost')}}:</td>
                                                    <td class="product_cost text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td>{{__('db.Expense')}}:</td>
                                                    <td class="expense_amount text-right"></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>{{__('db.profit')}}  <x-info title="Revenue - Product Cost - Expense" type="info" />:</strong></td>
                                                    <td class="profit text-right"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
