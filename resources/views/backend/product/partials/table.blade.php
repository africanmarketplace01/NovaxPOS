{{-- ── Product DataTable ─────────────────────────────────────────────── --}}
<div class="table-responsive">
    <table id="product-data-table" class="table pt-0" style="width: 100%">
        <thead>
            <tr>
                <th class="not-exported"></th>
                <th>{{ __('db.product') }}</th>
                <th class="d-none"></th>
                <th>{{ __('db.Code') }}</th>
                <th>{{ __('db.Brand') }}</th>
                <th>{{ __('db.category') }}</th>
                <th>{{ __('db.Quantity') }}</th>
                <th>{{ __('db.Unit') }}</th>
                <th>{{ __('db.Price') }}</th>
                @if($role_id <= 2)
                    <th>{{ __('db.Cost') }}</th>
                    <th>{{ __('db.Stock Worth') . '(' . __('db.Price') . '/' . __('db.Cost') . ')' }}</th>
                @endif
                @foreach($custom_fields as $fieldName)
                    <th>{{ $fieldName }}</th>
                @endforeach
                <th class="not-exported">{{ __('db.action') }}</th>
            </tr>
        </thead>
    </table>
</div>
