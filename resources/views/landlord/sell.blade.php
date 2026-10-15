@extends('landlord.layouts.public')

@section('title', 'Sell on the marketplace for free — ' . $siteN)
@section('description', 'List your products on the ' . $siteN . ' marketplace with no subscription. Pay only ' . $commission . ' when you make a sale.')

@php $domain = env('CENTRAL_DOMAIN', 'novasuitespos.com'); @endphp

@section('content')
    @include('landlord.partials.page-hero', [
        'eyebrow' => 'Sell on the marketplace',
        'title' => 'Start selling for free. Pay only when you sell.',
        'lead' => 'No subscription and no monthly bill. List your products on the ' . $siteN . ' marketplace and pay ' . $commission . ' when a customer buys.',
    ])

    <section class="section" style="padding-top:3rem;background-image:url('{{ asset('images/start-selling-bg-image.jpg') }}');background-position:center;background-size:cover">
        <div class="container">
            <div class="grid grid--3" style="margin-bottom:3rem">
                <div class="card card--pad">
                    <span class="icon-tile"><i class="fas fa-hand-holding-dollar" aria-hidden="true"></i></span>
                    <h2 style="margin-top:1rem;font-size:var(--text-xl)">$0 to start</h2>
                    <p class="muted" style="margin-top:.4rem">No package, no trial that runs out. Your store stays open as long as you sell.</p>
                </div>
                <div class="card card--pad">
                    <span class="icon-tile"><i class="fas fa-percent" aria-hidden="true"></i></span>
                    <h2 style="margin-top:1rem;font-size:var(--text-xl)">{{ $commission }}</h2>
                    <p class="muted" style="margin-top:.4rem">Customers pay your listed price. The commission is billed to you with your marketplace orders.</p>
                </div>
                <div class="card card--pad">
                    <span class="icon-tile"><i class="fas fa-arrow-up-right-dots" aria-hidden="true"></i></span>
                    <h2 style="margin-top:1rem;font-size:var(--text-xl)">Upgrade anytime</h2>
                    <p class="muted" style="margin-top:.4rem">Need a POS till, stock across branches or reports? Move to a full plan and keep your store and products.</p>
                </div>
            </div>

            @if (! $package)
                <div class="empty">
                    <i class="fas fa-store" aria-hidden="true"></i>
                    <p>Marketplace selling is opening soon. In the meantime you can <a href="{{ route('register') }}" class="link-arrow">start a free trial</a>.</p>
                </div>
            @else
                <div class="card split">
                    <aside class="split__aside">
                        <p class="aside-label">What you get</p>
                        <h2 style="margin-top:.5rem;font-size:var(--text-2xl)">Your own marketplace store</h2>
                        <ul class="perks">
                            <li><i class="fas fa-circle-check" aria-hidden="true"></i> Store at <strong style="color:#fff">yourstore.{{ $domain }}</strong></li>
                            <li><i class="fas fa-circle-check" aria-hidden="true"></i> Up to {{ $package->product_limit ? number_format($package->product_limit) : 'unlimited' }} products with photos and prices</li>
                            <li><i class="fas fa-circle-check" aria-hidden="true"></i> Listed on the {{ $siteN }} marketplace</li>
                            <li><i class="fas fa-circle-check" aria-hidden="true"></i> Online orders with delivery partners</li>
                            <li><i class="fas fa-circle-check" aria-hidden="true"></i> Vendor mobile app</li>
                        </ul>
                        <p class="aside-label" style="margin-top:2rem">How billing works</p>
                        <p style="margin-top:.5rem;color:rgb(255 255 255 / .82);font-size:var(--text-sm)">
                            We send a fee invoice for your marketplace orders. If an invoice stays unpaid past its due date, your listings are hidden until it is paid. Your store and data are never deleted.
                        </p>
                    </aside>

                    <div class="split__main">
                        <h2 style="font-size:var(--text-3xl)">Open your free store</h2>
                        <p class="muted" style="margin-top:.35rem">Takes about a minute.</p>

                        <div style="margin-top:1.5rem">
                            @if ($errors->any())
                                <div class="alert alert--error" role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                            @endif
                            @if (session('error'))
                                <div class="alert alert--error" role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><span>{{ session('error') }}</span></div>
                            @endif
                        </div>

                        <form action="{{ route('sell.submit') }}" method="POST" enctype="multipart/form-data" style="margin-top:1.25rem">
                            @csrf
                            <div class="form-grid form-grid--2">
                                <div class="field span-2">
                                    <label for="company_name">Business name <span class="req">*</span></label>
                                    <input type="text" id="company_name" name="company_name" class="input" value="{{ old('company_name') }}" autocomplete="organization" required>
                                    <p class="hint">This is the store name shoppers see on the marketplace.</p>
                                </div>
                                <div class="field">
                                    <label for="name">Your name <span class="req">*</span></label>
                                    <input type="text" id="name" name="name" class="input" value="{{ old('name') }}" autocomplete="name" required>
                                </div>
                                <div class="field">
                                    <label for="phone_number">Phone number <span class="req">*</span></label>
                                    <input type="tel" id="phone_number" name="phone_number" class="input" value="{{ old('phone_number') }}" autocomplete="tel" required>
                                </div>
                                <div class="field span-2">
                                    <label for="email">Email address <span class="req">*</span></label>
                                    <input type="email" id="email" name="email" class="input" value="{{ old('email') }}" autocomplete="email" required>
                                </div>
                                <div class="field span-2">
                                    <label for="tenantInput">Store address <span class="req">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="tenant" id="tenantInput" value="{{ old('tenant') }}" placeholder="yourstore"
                                               pattern="[a-z0-9]{3,30}" autocomplete="off" required @class(['input', 'is-invalid' => $errors->has('tenant')])>
                                        <span class="input-group__addon">.{{ $domain }}</span>
                                    </div>
                                    <p class="hint">Lowercase letters and numbers only, 3–30 characters.</p>
                                </div>
                                <div class="field">
                                    <label for="password">Password <span class="req">*</span></label>
                                    <input type="password" id="password" name="password" class="input" minlength="6" autocomplete="new-password" required>
                                </div>
                                <div class="field">
                                    <label for="password_confirmation">Confirm password <span class="req">*</span></label>
                                    <input type="password" id="password_confirmation" name="password_confirmation" class="input" minlength="6" autocomplete="new-password" required>
                                </div>
                                <div class="field span-2">
                                    <label for="logo">Store logo <span class="opt">(optional · PNG, JPG or SVG, max 2 MB)</span></label>
                                    <input type="file" id="logo" name="logo" class="input" accept="image/*">
                                </div>
                                <div class="span-2">
                                    <label style="display:flex;gap:.75rem;align-items:flex-start;font-size:var(--text-sm);color:var(--ink)">
                                        <input type="checkbox" name="accept_terms" value="1" @checked(old('accept_terms')) required style="margin-top:.3rem;width:1.1rem;height:1.1rem;accent-color:var(--np-primary)">
                                        <span>I agree to pay <strong>{{ $commission }}</strong> on marketplace orders, and understand my listings are hidden while a fee invoice is overdue.</span>
                                    </label>
                                </div>
                                <div class="span-2">
                                    <button type="submit" class="btn btn--primary btn--lg btn--block">Open my free store <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                                    <p class="hint text-center" style="margin-top:.9rem">Want the full POS instead? <a href="{{ route('pricing') }}" class="link-arrow">See plans</a></p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('tenantInput');
            if (input) input.addEventListener('input', function () {
                this.value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '');
            });
        })();
    </script>
@endpush
