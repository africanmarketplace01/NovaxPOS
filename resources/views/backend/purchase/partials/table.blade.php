<div class="table-responsive">
    <table id="purchase-table" class="table purchase-list mt-0" style="width: 100%">
        <thead>
            <tr>
                <th class="not-exported"></th>
                <th>{{ __('db.date') }}</th>
                <th>{{ __('db.reference') }}</th>
                <th>{{ __('db.Created By') }}</th>
                <th>{{ __('db.Supplier') }}</th>
                @if($general_setting->show_products_details_in_purchase_table)
                    <th>{{ __('db.Products') }}</th>
                    <th>{{ __('db.Quantity') }}</th>
                @endif
                <th>{{ __('db.Purchase Status') }}</th>
                <th>{{ __('db.grand total') }}</th>
                <th>{{ __('db.Returned Amount') }}</th>
                <th>{{ __('db.Paid') }}</th>
                <th>{{ __('db.Due') }}</th>
                <th>{{ __('db.Currency') }}/{{ __('db.Exchange Rate') }}</th>
                <th>{{ __('db.payment_term') }}</th>
                <th>{{ __('db.due_date') }}</th>
                <th>{{ __('db.Payment Status') }}</th>
                @foreach($custom_fields as $fieldName)
                    <th>{{ $fieldName }}</th>
                @endforeach
                <th class="not-exported">{{ __('db.action') }}</th>
            </tr>
        </thead>

        <tfoot class="tfoot active">
            <th></th>
            <th>{{ __('db.Total') }}</th>
            <th></th><th></th><th></th>
            @if($general_setting->show_products_details_in_purchase_table)
                <th></th><th></th>
            @endif
            <th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th>
            @foreach($custom_fields as $fieldName)
                <th></th>
            @endforeach
            <th></th>
        </tfoot>
    </table>
</div>
