{{--
    SaaS Analytics Widget — included in the tenant dashboard only when SaaS mode is active.
    Shows current usage vs package limits.
--}}
@if(config('database.connections.saleprosaas_landlord'))
@php
    use \App\Models\landlord\Tenant;
    $tenantId = null;
    $package  = null;
    try {
        $tenantId = tenant()?->getTenantKey();
        if ($tenantId) {
            $landlordTenant = Tenant::with('package')->find($tenantId);
            $package = $landlordTenant?->package;
        }
    } catch (\Throwable $e) {}

    $productCount   = DB::table('products')->where('is_active', true)->count();
    $userCount      = DB::table('users')->where('is_deleted', false)->count();
    $warehouseCount = DB::table('warehouses')->count();
@endphp

@if($package)
<div class="card mb-4" style="border:none;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08);">
    <div class="card-header" style="background:transparent;border-bottom:1px solid #f1f5f9;font-weight:600;">
        <i class="fas fa-tachometer-alt me-2 text-primary"></i>
        Plan Usage — <span class="text-primary">{{ $package->name }}</span>
        <a href="{{ env('RENEWAL_URL', '#') }}" class="btn btn-sm btn-outline-primary float-end" style="font-size:.75rem;">
            Upgrade Plan
        </a>
    </div>
    <div class="card-body">
        <div class="row g-3">
            {{-- Products --}}
            <div class="col-md-4">
                @php
                    $limit = $package->product_limit;
                    $pct   = $limit ? min(100, round($productCount / $limit * 100)) : 0;
                    $color = $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : 'success');
                @endphp
                <div class="d-flex justify-content-between mb-1" style="font-size:.85rem;">
                    <span>Products</span>
                    <span class="fw-semibold">{{ number_format($productCount) }} / {{ $limit ? number_format($limit) : '∞' }}</span>
                </div>
                @if($limit)
                <div class="progress" style="height:6px;border-radius:999px;">
                    <div class="progress-bar bg-{{ $color }}" style="width:{{ $pct }}%;"></div>
                </div>
                @else
                <div class="progress" style="height:6px;border-radius:999px;">
                    <div class="progress-bar bg-success" style="width:100%;opacity:.4;"></div>
                </div>
                @endif
            </div>

            {{-- Users --}}
            <div class="col-md-4">
                @php
                    $ulimit = $package->user_limit;
                    $upct   = $ulimit ? min(100, round($userCount / $ulimit * 100)) : 0;
                    $ucolor = $upct >= 90 ? 'danger' : ($upct >= 70 ? 'warning' : 'success');
                @endphp
                <div class="d-flex justify-content-between mb-1" style="font-size:.85rem;">
                    <span>Staff Accounts</span>
                    <span class="fw-semibold">{{ number_format($userCount) }} / {{ $ulimit ? number_format($ulimit) : '∞' }}</span>
                </div>
                @if($ulimit)
                <div class="progress" style="height:6px;border-radius:999px;">
                    <div class="progress-bar bg-{{ $ucolor }}" style="width:{{ $upct }}%;"></div>
                </div>
                @else
                <div class="progress" style="height:6px;border-radius:999px;">
                    <div class="progress-bar bg-success" style="width:100%;opacity:.4;"></div>
                </div>
                @endif
            </div>

            {{-- Warehouses --}}
            <div class="col-md-4">
                @php
                    $wlimit = $package->warehouse_limit;
                    $wpct   = $wlimit ? min(100, round($warehouseCount / $wlimit * 100)) : 0;
                    $wcolor = $wpct >= 90 ? 'danger' : ($wpct >= 70 ? 'warning' : 'success');
                @endphp
                <div class="d-flex justify-content-between mb-1" style="font-size:.85rem;">
                    <span>Warehouses</span>
                    <span class="fw-semibold">{{ number_format($warehouseCount) }} / {{ $wlimit ? number_format($wlimit) : '∞' }}</span>
                </div>
                @if($wlimit)
                <div class="progress" style="height:6px;border-radius:999px;">
                    <div class="progress-bar bg-{{ $wcolor }}" style="width:{{ $wpct }}%;"></div>
                </div>
                @else
                <div class="progress" style="height:6px;border-radius:999px;">
                    <div class="progress-bar bg-success" style="width:100%;opacity:.4;"></div>
                </div>
                @endif
            </div>
        </div>

        {{-- Expiry notice --}}
        @if($landlordTenant?->expiry_date)
        @php $exp = \Carbon\Carbon::parse($landlordTenant->expiry_date); @endphp
        <div class="mt-3 p-2 rounded {{ $exp->diffInDays() <= 7 ? 'bg-warning bg-opacity-10' : 'bg-light' }}"
             style="font-size:.8rem;">
            <i class="fas fa-calendar me-2 {{ $exp->isPast() ? 'text-danger' : 'text-muted' }}"></i>
            @if($exp->isPast())
                <span class="text-danger fw-semibold">Subscription expired. <a href="{{ env('RENEWAL_URL','#') }}">Renew now</a></span>
            @elseif($exp->diffInDays() <= 7)
                <span class="text-warning fw-semibold">Expires in {{ $exp->diffForHumans() }}. <a href="{{ env('RENEWAL_URL','#') }}">Renew now</a></span>
            @else
                <span class="text-muted">Subscription valid until {{ $exp->format('d M Y') }}</span>
            @endif
        </div>
        @endif
    </div>
</div>
@endif
@endif
