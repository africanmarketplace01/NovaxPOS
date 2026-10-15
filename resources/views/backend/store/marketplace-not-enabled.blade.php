@extends('backend.layout.main')
@section('title', 'Marketplace Listings')

@section('content')
<section class="forms">
    <div class="container-fluid">
        <div class="text-center py-5">
            <i class="fas fa-store fa-4x text-muted mb-4 d-block"></i>
            <h4 class="mb-2" style="font-weight:700;">Marketplace Listing Not Enabled</h4>
            <p class="text-muted mb-4">Enable marketplace listing in your store settings to appear on the global marketplace.</p>
            <a href="{{ route('store.settings') }}" class="btn btn-primary">
                <i class="fas fa-cog" style="margin-right:6px;"></i>Go to Store Settings
            </a>
        </div>
    </div>
</section>
@endsection
