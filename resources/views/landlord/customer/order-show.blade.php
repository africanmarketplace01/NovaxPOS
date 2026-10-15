@extends('landlord.marketplace.layout')
@section('title', 'Order ' . $order->order_number)

@section('extra-css')
<style>
.oi { display:flex; gap:.8rem; padding:.85rem 0; border-bottom:1px solid var(--mp-border); }
.oi:last-child { border-bottom:none; }
.oi-img { width:56px; height:56px; border-radius:9px; object-fit:cover; background:#f1f5f9; flex-shrink:0; }
.oi-ph { width:56px; height:56px; border-radius:9px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#cbd5e1; }
.star-input { display:inline-flex; flex-direction:row-reverse; }
.star-input input { display:none; }
.star-input label { font-size:1.25rem; color:#e2e8f0; cursor:pointer; padding:0 1px; }
.star-input input:checked ~ label, .star-input label:hover, .star-input label:hover ~ label { color:#f59e0b; }
.rev-toggle { font-size:var(--text-xs); font-weight:600; color: var(--mp-link); cursor:pointer; }
</style>
@endsection

@section('content')
<div class="container py-4">
  <div class="acct-shell">
    @include('landlord.customer._nav', ['active' => 'orders'])
    <div class="acct-main">
      <a href="{{ route('customer.orders') }}" class="text-decoration-none" style="font-size:var(--text-sm)"><i class="fas fa-arrow-left me-1"></i> Back to orders</a>

      @if(session('success'))<div class="mp-flash mp-flash-success mt-2"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>@endif
      @if(session('error'))<div class="mp-flash mp-flash-error mt-2"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif

      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mt-2 mb-3">
        <div>
          <h4 class="fw-bold mb-1">Order {{ $order->order_number }}</h4>
          <div class="text-muted" style="font-size:var(--text-xs)">Placed {{ $order->created_at?->format('M j, Y g:i A') }}</div>
        </div>
        <div class="text-end">
          <span class="badge bg-{{ $order->statusBadge() }} text-capitalize">{{ $order->status }}</span>
          <span class="badge bg-{{ $order->paymentBadge() }} text-capitalize">{{ $order->payment_status }}</span>
        </div>
      </div>

      <div class="row g-3">
        {{-- Tracking --}}
        <div class="col-lg-5">
          <div class="bg-white rounded-3 p-4" style="border:1px solid var(--mp-border)">
            <h6 class="fw-bold mb-3"><i class="fas fa-truck-fast me-2" style="color: var(--mp-link)"></i>Order Tracking</h6>
            @include('landlord.marketplace._tracking', ['order' => $order])
          </div>

          <div class="bg-white rounded-3 p-4 mt-3" style="border:1px solid var(--mp-border)">
            <h6 class="fw-bold mb-2">Delivery</h6>
            <div style="font-size:var(--text-sm);color:#475569">
              <div class="fw-semibold">{{ $order->customer_name }}</div>
              <div>{{ $order->customer_phone }}</div>
              <div>{{ $order->customer_address }}</div>
              @if($order->delivery_agency_name)<div class="mt-1"><i class="fas fa-motorcycle me-1"></i>{{ $order->delivery_agency_name }}
                @if($order->estimated_delivery)· ETA {{ $order->estimated_delivery }}@endif</div>@endif
            </div>
          </div>
        </div>

        {{-- Items + review --}}
        <div class="col-lg-7">
          <div class="bg-white rounded-3 p-4" style="border:1px solid var(--mp-border)">
            <h6 class="fw-bold mb-2">Items</h6>
            @foreach($order->items ?? [] as $item)
              @php $lid = $item['listing_id'] ?? null; $isReviewed = $lid && in_array($lid, $reviewed); @endphp
              <div class="oi">
                @if(!empty($item['image_url']))
                  <img src="{{ $item['image_url'] }}" class="oi-img" alt="">
                @else
                  <div class="oi-ph"><i class="fas fa-box"></i></div>
                @endif
                <div class="flex-grow-1" style="min-width:0">
                  <div class="fw-semibold" style="font-size:var(--text-sm)">{{ $item['name'] ?? 'Item' }}</div>
                  <div class="text-muted" style="font-size:var(--text-xs)">
                    {{ $item['tenant_name'] ?? '' }} · Qty {{ $item['qty'] ?? 1 }} · {{ $order->currency }} {{ number_format($item['price'] ?? 0, 2) }}
                  </div>

                  {{-- Review area (delivered orders only) --}}
                  @if($lid)
                    @if($isReviewed)
                      <div class="mt-1" style="font-size:var(--text-xs);color:#16a34a"><i class="fas fa-check-circle"></i> You reviewed this product</div>
                    @elseif($order->status === 'delivered')
                      <div class="mt-1">
                        <span class="rev-toggle" onclick="document.getElementById('rev{{ $lid }}').classList.toggle('d-none')">
                          <i class="fas fa-star"></i> Rate &amp; review
                        </span>
                        <form action="{{ route('customer.orders.review', $order->id) }}" method="POST" id="rev{{ $lid }}" class="d-none mt-2">
                          @csrf
                          <input type="hidden" name="listing_id" value="{{ $lid }}">
                          <div class="star-input mb-1">
                            @for($s=5;$s>=1;$s--)
                              <input type="radio" name="rating" id="s{{ $lid }}_{{ $s }}" value="{{ $s }}" {{ $s===5?'required':'' }}>
                              <label for="s{{ $lid }}_{{ $s }}">&#9733;</label>
                            @endfor
                          </div>
                          <input type="text" name="title" class="form-control form-control-sm mb-1" placeholder="Title (optional)" maxlength="120">
                          <textarea name="comment" class="form-control form-control-sm mb-1" rows="2" placeholder="Share your experience…" maxlength="2000" required></textarea>
                          <button class="btn btn-sm btn-primary">Submit review</button>
                        </form>
                      </div>
                    @else
                      <div class="mt-1 text-muted" style="font-size:var(--text-xs)">You can review this once the order is delivered.</div>
                    @endif
                  @endif
                </div>
                <div class="text-end fw-semibold" style="font-size:var(--text-sm);white-space:nowrap">
                  {{ $order->currency }} {{ number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1), 2) }}
                </div>
              </div>
            @endforeach

            <hr>
            <div class="d-flex justify-content-between small mb-1"><span>Subtotal</span><span>{{ $order->currency }} {{ number_format($order->subtotal,2) }}</span></div>
            <div class="d-flex justify-content-between small mb-1"><span>Platform fee</span><span>{{ $order->currency }} {{ number_format($order->platform_fee,2) }}</span></div>
            <div class="d-flex justify-content-between small mb-1"><span>Delivery</span><span>{{ $order->currency }} {{ number_format($order->delivery_fee,2) }}</span></div>
            <div class="d-flex justify-content-between fw-bold mt-2" style="font-size:var(--text-lg)"><span>Total</span><span style="color: var(--mp-link)">{{ $order->currency }} {{ number_format($order->total,2) }}</span></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
