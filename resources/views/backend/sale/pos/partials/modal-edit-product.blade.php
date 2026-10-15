{{-- ============================================================ --}}
{{-- EDIT PRODUCT MODAL (#editModal) + ADD CUSTOMER MODAL --}}
{{-- Edit cart row: qty, price, discount, tax, notes --}}
{{-- ============================================================ --}}

                <!-- product edit modal -->
                <div id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 id="modal_header" class="modal-title"></h5>
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            <div class="modal-body">
                                <form>
                                    <div class="row modal-element">
                                        <div class="col-md-4 form-group">
                                            <label>{{__('db.Quantity')}}</label>
                                            <input type="text" name="edit_qty" class="form-control numkey">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label>{{__('db.Unit Discount')}}</label>
                                            <input type="text" name="edit_discount" class="form-control numkey">
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>{{__('db.Price Option')}}</strong> </label>
                                                <div class="input-group">
                                                    <select class="form-control selectpicker" name="price_option" class="price-option">
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label>{{__('db.Unit Price')}}</label>
                                            @can('price_edit_in_sale')
                                                <input type="text" name="edit_unit_price" class="form-control numkey" step="any">
                                            @else
                                                <input type="text" name="edit_unit_price" class="form-control numkey" step="any" readonly>
                                            @endcan
                                        </div>
                                        <?php
                                        $tax_name_all[] = 'No Tax';
                                        $tax_rate_all[] = 0;
                                        foreach ($lims_tax_list as $tax) {
                                            $tax_name_all[] = $tax->name;
                                            $tax_rate_all[] = $tax->rate;
                                        }
                                        ?>
                                        <div class="col-md-4 form-group">
                                            <label>{{__('db.Tax Rate')}}</label>
                                            <select name="edit_tax_rate" class="form-control selectpicker">
                                                @foreach($tax_name_all as $key => $name)
                                                <option value="{{$key}}">{{$name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div id="edit_unit" class="col-md-4 form-group">
                                            <label>{{__('db.Product Unit')}}</label>
                                            <select name="edit_unit" class="form-control selectpicker">
                                            </select>
                                        </div>
                                        <!-- <div class="col-md-4 form-group">
                                            <label>{{__('db.Cost')}}</label>
                                            <p id="product-cost"></p>
                                        </div> -->
                                    </div>
                                    <button type="button" name="update_btn" class="btn btn-primary">{{__('db.update')}}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- add customer modal -->
                <div id="addCustomer" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="alert-container mb-3"></div>
                        <div class="modal-content">
                            <form action="{{ route('customer.store') }}" method="POST" enctype="multipart/form-data" id="customer-form">
                                @csrf
                                <div class="modal-header">
                                    <h5 id="exampleModalLabel" class="modal-title">{{__('db.Add Customer')}}</h5>
                                    <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                                </div>
                                <div class="modal-body">
                                <p class="italic"><small>{{__('db.The field labels marked with are required input fields')}}.</small></p>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Customer Group')}} *</strong> </label>
                                            <select required class="form-control selectpicker" name="customer_group_id">
                                                @foreach($lims_customer_group_all as $customer_group)
                                                <option value="{{$customer_group->id}}">{{$customer_group->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.name')}} *</strong> </label>
                                            <input type="text" name="customer_name" required class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Email')}}</label>
                                            <input type="text" name="email" placeholder="example@example.com" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Phone Number')}} *</label>
                                            <input type="text" name="phone_number" required class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="country-phone-group form-group">
                                            <label>{{__('db.WhatsApp Number')}}</label>
                                            <div class="d-flex">
                                                <select id="country_code" name="country_code" class="form-control w-auto me-2">
                                                </select>
                                                <input type="tel" id="wa_number" class="form-control">
                                                <input type="hidden" id="full_phone" name="wa_number">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Address')}}</label>
                                            <input type="text" name="address" required class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.City')}}</label>
                                            <input type="text" name="city" required class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Credit Limit')}} <x-info title="Leave it blank for unlimited credit" type="info" /></label>
                                            <input type="number" name="credit_limit" class="form-control" value="0" step="any" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{__('db.Tax Number')}}</label>
                                            <input type="text" name="tax_no" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <input type="hidden" name="pos" value="1">
                                    <button type="button" class="btn btn-primary customer-submit-btn">{{__('db.submit')}}</button>

                                </div>
                            </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- recent transaction modal -->
                <div id="recentTransaction" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 id="exampleModalLabel" class="modal-title">{{__('db.Recent Transaction')}}
                                    <div class="badge badge-primary">{{__('db.latest')}} 10</div>
                                </h5>
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            <div class="modal-body">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#sale-latest" role="tab" data-toggle="tab">{{__('db.Sale')}}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#draft-latest" role="tab" data-toggle="tab">{{__('db.Draft')}}</a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div role="tabpanel" class="tab-pane show active" id="sale-latest">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>{{__('db.date')}}</th>
                                                        <th>{{__('db.reference')}}</th>
                                                        <th>{{__('db.customer')}}</th>
                                                        <th>{{__('db.grand total')}}</th>
                                                        <th>{{__('db.action')}}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div role="tabpanel" class="tab-pane fade" id="draft-latest">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>{{__('db.date')}}</th>
                                                        <th>{{__('db.reference')}}</th>
                                                        <th>{{__('db.customer')}}</th>
                                                        <th>{{__('db.grand total')}}</th>
                                                        <th>{{__('db.action')}}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
