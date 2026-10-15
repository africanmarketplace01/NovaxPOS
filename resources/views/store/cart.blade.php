@extends('store.layout')
@section('title', 'Cart — ' . ($store->store_name ?? 'Store'))

@section('content')
<div class="store-breadcrumb">
    <div class="container"><a href="{{ route('store.index') }}">Home</a> / <span>Cart</span></div>
</div>

<div class="container" style="padding:2.5rem 0 4rem;max-width:860px">
    <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:1.5rem">
        <i class="fas fa-shopping-cart me-2" style="color:var(--store-primary)"></i>Your Cart
    </h1>

    @if(session('error'))
    <div class="flash-error">{{ session('error') }}</div>
    @endif

    @if(empty($cart))
    <div style="text-align:center;padding:4rem 2rem;background:#fff;border-radius:14px;border:1px solid var(--store-border)">
        <i class="fas fa-shopping-cart fa-3x mb-3" style="color:#cbd5e1"></i>
        <h4 style="margin-bottom:.5rem">Your cart is empty</h4>
        <p class="text-muted">Add some products to get started.</p>
        <a href="{{ route('store.products') }}" class="btn btn-store-primary px-4 mt-2">Browse Products</a>
    </div>
    @else

    <div class="row g-4">
        <div class="col-lg-8">
            <div style="background:#fff;border-radius:14px;border:1px solid var(--store-border);overflow:hidden">
                <table class="table table-borderless mb-0" style="font-size:.875rem">
                    <thead style="background:#f8fafc;font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#64748b">
                        <tr>
                            <th style="padding:.75rem 1rem">Product</th>
                            <th style="padding:.75rem;text-align:center">Qty</th>
                            <th style="padding:.75rem;text-align:right">Subtotal</th>
                            <th style="padding:.75rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total = 0; @endphp
                        @foreach($cart as $key => $item)
                        @php $subtotal = $item['price'] * $item['qty']; $total += $subtotal; @endphp
                        <tr style="border-bottom:1px solid #f1f5f9">
                            <td style="padding:.9rem 1rem;vertical-align:middle">
                                <div style="display:flex;align-items:center;gap:.75rem">
                                    @if(!empty($item['image']) && file_exists(public_path($item['image'])))
                                    <img src="{{ asset($item['image']) }}" style="width:50px;height:50px;object-fit:cover;border-radius:8px;border:1px solid var(--store-border)">
                                    @else
                                    <div style="width:50px;height:50px;background:#f1f5f9;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#cbd5e1">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    @endif
                                    <div>
                                        <div style="font-weight:600">{{ $item['name'] }}</div>
                                        <div style="font-size:.75rem;color:#94a3b8">{{ number_format($item['price'], 2) }} each</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:.9rem;text-align:center;vertical-align:middle">
                                <form action="{{ route('store.cart.update') }}" method="POST" style="display:inline-flex;align-items:center;gap:.25rem">
                                    @csrf
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="{{ $key }}">
                                    <input type="number" name="qty" value="{{ $item['qty'] }}" min="1" max="999"
                                           style="width:60px;border:1px solid var(--store-border);border-radius:6px;text-align:center;padding:.3rem;font-size:.875rem"
                                           onchange="this.form.submit()">
                                </form>
                            </td>
                            <td style="padding:.9rem;text-align:right;vertical-align:middle;font-weight:700;color:var(--store-primary)">
                                {{ number_format($subtotal, 2) }}
                            </td>
                            <td style="padding:.9rem;text-align:right;vertical-align:middle">
                                <form action="{{ route('store.cart.update') }}" method="POST" style="display:inline">
                                    @csrf
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="{{ $key }}">
                                    <button type="submit" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:.9rem" title="Remove">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mt-3">
                <a href="{{ route('store.products') }}" class="btn btn-store-outline btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Continue Shopping
                </a>
                <form action="{{ route('store.cart.update') }}" method="POST" style="margin-left:auto">
                    @csrf
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-sm btn-light" style="color:#ef4444">
                        <i class="fas fa-trash me-1"></i> Clear Cart
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div style="background:#fff;border-radius:14px;border:1px solid var(--store-border);padding:1.5rem;position:sticky;top:80px">
                <div style="font-weight:700;font-size:1rem;margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid var(--store-border)">
                    Order Summary
                </div>
                <div style="display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:.5rem">
                    <span>Subtotal</span>
                    <span>{{ number_format($total, 2) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:1rem;color:#64748b">
                    <span>Shipping</span><span>TBD</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800;padding-top:.75rem;border-top:1px solid var(--store-border)">
                    <span>Total</span>
                    <span style="color:var(--store-primary)">{{ number_format($total, 2) }}</span>
                </div>
                @if($store->allow_orders)
                <a href="{{ route('store.checkout') }}" class="btn btn-store-primary w-100 mt-4" style="font-size:.95rem;padding:.65rem">
                    Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>
                </a>
                @else
                <div class="flash-error mt-3">Online ordering is currently unavailable.</div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
