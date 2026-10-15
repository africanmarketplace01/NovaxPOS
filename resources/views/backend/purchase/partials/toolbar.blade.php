@can('purchases-add')
    <a href="{{ route('purchases.create') }}" class="btn btn-info btn-icon">
        <i class="dripicons-plus"></i> {{ __('db.Add Purchase') }}
    </a>&nbsp;
@endcan

@can('purchases-import')
    <a href="{{ url('purchases/purchase_by_csv') }}" class="btn btn-primary btn-icon">
        <i class="dripicons-copy"></i> {{ __('db.Import Purchase') }}
    </a>
@endcan

@if(auth()->user()->role_id <= 2)
    <a href="{{ url('purchases/deleted_data') }}" class="btn btn-secondary btn-icon">
        <i class="dripicons-trash"></i> {{ __('Deleted Purchases') }}
    </a>
@endif

<button type="button" class="btn btn-warning btn-icon" id="toggle-filter">
    <i class="dripicons-experiment"></i> {{ __('db.Filter Purchases') }}
</button>
