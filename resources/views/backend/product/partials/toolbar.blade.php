{{-- ── Action buttons + filter toggle ──────────────────────────────── --}}
@can('products-add')
    <a href="{{ route('products.create') }}" class="btn btn-info add-product-btn btn-icon">
        <i class="dripicons-plus"></i> {{ __('db.add_product') }}
    </a>
@endcan

@can('products-import')
    <a href="#" data-toggle="modal" data-target="#importProduct" class="btn btn-primary add-product-btn btn-icon">
        <i class="dripicons-copy"></i> {{ __('db.import_product') }}
    </a>
@endcan

@can('products-edit')
    @if(in_array('ecommerce', explode(',', $general_setting->modules)))
        <a href="{{ route('product.allProductInStock') }}" class="btn btn-dark add-product-btn btn-icon">
            <i class="dripicons-stack"></i> {{ __('db.All Product In Stock') }}
        </a>
        <a href="{{ route('product.showAllProductOnline') }}" class="btn btn-dark add-product-btn btn-icon">
            <i class="dripicons-wifi"></i> {{ __('db.Show All Product Online') }}
        </a>
    @endif
@endcan

<button type="button" class="btn btn-warning btn-icon" id="toggle-filter">
    <i class="dripicons-experiment"></i> {{ __('db.Filter Products') }}
</button>
