{{-- ============================================================ --}}
{{-- EXPENSE MODAL (#expense-modal) --}}
{{-- Quick expense entry from POS --}}
{{-- ============================================================ --}}

<!-- expense modal -->
<div id="expense-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" class="modal fade text-left">
    <div role="document" class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
        <h5 id="exampleModalLabel" class="modal-title">{{ __('Add Expense') }}</h5>
        <button type="button" data-dismiss="modal" aria-label="Close" class="close"><span aria-hidden="true"><i class="dripicons-cross"></i></span></button>
        </div>
        <div class="modal-body">
        <p class="italic"><small>{{ __('The field labels marked with are required input fields') }}.</small></p>
        <form action="{{ route('expenses.store') }}" method="POST">
            @csrf
        <?php
        $lims_expense_category_list = DB::table('expense_categories')->where('is_active', true)->get();
        if (Auth::user()->role_id > 2)
            $lims_warehouse_list = DB::table('warehouses')->where([
            ['is_active', true],
            ['id', Auth::user()->warehouse_id]
            ])->get();
        else
            $lims_warehouse_list = DB::table('warehouses')->where('is_active', true)->get();
        $lims_account_list = \App\Models\Account::where('is_active', true)->get();
        ?>
        <div class="row">
            <div class="col-md-6 form-group">
            <label>{{ __('Date') }}</label>
            <input type="text" name="created_at" class="form-control date" placeholder="{{__('db.Choose date')}}" value="{{date($general_setting->date_format,strtotime('now'))}}"/>
            </div>
            <div class="col-md-6 form-group">
            <label>{{ __('Expense Category') }} *</label>
            <select name="expense_category_id" class="selectpicker form-control" required data-live-search="true" data-live-search-style="begins" title="Select Expense Category...">
                @foreach($lims_expense_category_list as $expense_category)
                <option value="{{$expense_category->id}}">{{$expense_category->name . ' (' . $expense_category->code. ')'}}</option>
                @endforeach
            </select>
            </div>
            <div class="col-md-6 form-group">
            <label>{{ __('Warehouse') }} *</label>
            <select name="warehouse_id" class="selectpicker form-control" required data-live-search="true" data-live-search-style="begins" title="Select Warehouse...">
                @foreach($lims_warehouse_list as $warehouse)
                <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                @endforeach
            </select>
            </div>
            <div class="col-md-6 form-group">
            <label>{{ __('Amount') }} *</label>
            <input type="number" id="expense-amount" name="amount" step="any" required class="form-control">
            </div>
            <div class="col-md-6 form-group">
            <label> {{ __('Account') }}</label>
            <select class="form-control selectpicker" name="account_id">
                @foreach($lims_account_list as $account)
                @if($account->is_default)
                <option selected value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                @else
                <option value="{{$account->id}}">{{$account->name}} [{{$account->account_no}}]</option>
                @endif
                @endforeach
            </select>
            </div>
        </div>
        <div class="form-group">
            <label>{{ __('Note') }}</label>
            <textarea name="note" rows="3" class="form-control"></textarea>
        </div>
        <div class="form-group">
            <input type="hidden" name="cash_register" value="" />
            <button type="submit" class="btn btn-primary">{{ __('submit') }}</button>
        </div>
        </form>
        </div>
    </div>
    </div>
</div>
<!-- end expense modal -->
