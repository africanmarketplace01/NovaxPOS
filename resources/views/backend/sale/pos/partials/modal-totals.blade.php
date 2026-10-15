{{-- ============================================================ --}}
{{-- TOTALS MODALS --}}
{{-- Contains: order discount, coupon, shipping cost, invoice modals --}}
{{-- ============================================================ --}}

                <!-- order_discount modal -->
                <div id="order-discount-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{__('db.Order Discount')}}</h5>
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            <div class="modal-body">
                                @php
                                    $__generalSetting = DB::table('general_settings')->latest()->first();
                                    $__approvedDiscs = ($__generalSetting->enforce_approved_discounts ?? false)
                                        && !empty($__generalSetting->approved_discount_percentages)
                                        ? json_decode($__generalSetting->approved_discount_percentages, true)
                                        : null;
                                @endphp
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        @php
                                            $selected_discount_type = old('order_discount_type_select')
                                                ?? ($lims_sale_data->order_discount_type ?? 'Flat');
                                        @endphp
                                        <label>{{__('db.Order Discount Type')}}</label>
                                        <select id="order-discount-type" name="order_discount_type_select" class="form-control">
                                            <option value="Flat" {{ (!$__approvedDiscs && $selected_discount_type == 'Flat') ? 'selected' : '' }}>
                                                {{ __('db.Flat') }}
                                            </option>
                                            <option value="Percentage" {{ $selected_discount_type == 'Percentage' ? 'selected' : '' }}>
                                                {{ __('db.Percentage') }}
                                            </option>
                                        </select>
                                        <input type="hidden" name="order_discount_type">
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>{{__('db.Value')}}</label>
                                        @if($__approvedDiscs)
                                            <select name="order_discount_value" class="form-control selectpicker" id="order-discount-val">
                                                <option value="0">0 — No Discount</option>
                                                @foreach($__approvedDiscs as $pct)
                                                    <option value="{{ $pct }}">{{ $pct }}%</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" name="order_discount_value" class="form-control numkey" id="order-discount-val" >
                                        @endif
                                        <input type="hidden" name="order_discount" class="form-control" id="order-discount" >
                                    </div>
                                </div>

                                {{-- 3.15 Manager Override PIN section (shown when user lacks handle_discount) --}}
                                @if(!$handle_discount_active)
                                <div class="alert alert-warning mt-2 mb-2">
                                    <i class="dripicons-lock"></i>
                                    You do not have discount permission. Request a manager override:
                                </div>
                                <div id="override-pin-section" class="mb-2">
                                    <div class="input-group">
                                        <input type="password" id="override-pin-input" class="form-control" maxlength="6" placeholder="Manager PIN (4–6 digits)">
                                        <div class="input-group-append">
                                            <button type="button" id="verify-override-btn" class="btn btn-warning">
                                                <i class="dripicons-lock"></i> Verify
                                            </button>
                                        </div>
                                    </div>
                                    <div id="override-pin-message" class="mt-1 small"></div>
                                    <input type="hidden" name="override_token" id="override-token-field">
                                </div>
                                @endif

                                <button type="button" name="order_discount_btn" class="btn btn-primary" data-dismiss="modal">{{__('db.submit')}}</button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3.15 Override PIN verification script --}}
                @if(!$handle_discount_active)
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    document.getElementById('verify-override-btn').addEventListener('click', function () {
                        const pin = document.getElementById('override-pin-input').value.trim();
                        if (!pin) return;

                        fetch('{{ route("user.verifyOverridePin") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ pin })
                        })
                        .then(r => r.json())
                        .then(data => {
                            const msg = document.getElementById('override-pin-message');
                            if (data.success) {
                                document.getElementById('override-token-field').value = data.override_token;
                                msg.innerHTML = '<span class="text-success"><i class="dripicons-checkmark"></i> Approved by ' + data.manager + '. You may now apply the discount.</span>';
                                document.getElementById('verify-override-btn').disabled = true;
                                document.getElementById('order-discount-val').disabled = false;
                            } else {
                                msg.innerHTML = '<span class="text-danger"><i class="dripicons-cross"></i> ' + (data.message || 'Invalid PIN') + '</span>';
                            }
                        })
                        .catch(() => {
                            document.getElementById('override-pin-message').innerHTML = '<span class="text-danger">Request failed.</span>';
                        });
                    });
                });
                </script>
                @endif
                <!-- coupon modal -->
                <div id="coupon-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{__('db.Coupon Code')}}</h5>
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            @php
                                $coupon_code = '';
                                if (isset($lims_sale_data)) {
                                    $lims_coupon_data = $lims_coupon_list->where('id', $lims_sale_data->coupon_id)->first();
                                    $coupon_code = $lims_coupon_data ? $lims_coupon_data->code : '';
                                }
                            @endphp
                            <div class="modal-body">
                                <div class="form-group">
                                    <input type="text" id="coupon-code" class="form-control" placeholder="Type Coupon Code..." value="{{ $coupon_code }}">
                                </div>
                                <button type="button" class="btn btn-primary coupon-check" data-dismiss="modal">{{__('db.submit')}}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- order_tax modal -->
                <div id="order-tax" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{__('db.Order Tax')}}</h5>
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <input type="hidden" name="order_tax_rate">
                                    <select class="form-control" name="order_tax_rate_select" id="order-tax-rate-select">
                                        <option value="0">No Tax</option>
                                        @foreach($lims_tax_list as $tax)
                                        <option value="{{$tax->rate}}">{{$tax->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" name="order_tax_btn" class="btn btn-primary" data-dismiss="modal">{{__('db.submit')}}</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- shipping_cost modal -->
                <div id="shipping-cost-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">{{__('db.Shipping Cost')}}</h5>
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <input type="text" name="shipping_cost" class="form-control numkey" id="shipping-cost-val" step="any" >
                                </div>
                                <button type="button" name="shipping_cost_btn" class="btn btn-primary" data-dismiss="modal">{{__('db.submit')}}</button>
                            </div>
                        </div>
                    </div>
                </div>

                </form>

                {{-- invoice modal start --}}
                <div id="invoice-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
                    <div role="document" class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg></span></button>
                            </div>
                            <div id="invoice-modal-content" class="modal-body">
                            </div>
                        </div>
                    </div>
                </div>
                {{-- invoice modal end --}}

