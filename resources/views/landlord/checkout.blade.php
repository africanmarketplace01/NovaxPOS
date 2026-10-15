@extends('landlord.layouts.public')

@section('title', 'Checkout — ' . $siteN)
@section('solid-header', '1')

@php
    $domain = env('CENTRAL_DOMAIN', 'novaxpos.com');

    $gateways = array_filter([
        'stripe' => ! empty($th['stripe_key']) ? ['Stripe', '<i class="fab fa-stripe gw-logo" style="color:#635bff"></i>'] : null,
        'paystack' => ! empty($th['paystack_public_key']) ? ['Paystack', '<i class="fas fa-bolt gw-logo" style="color:#00a8d6"></i>'] : null,
        'bank' => ! empty($th['bank_transfer_enabled']) ? ['Bank transfer', '<i class="fas fa-building-columns gw-logo" style="color:#16a34a"></i>'] : null,
        'mtn' => ! empty($th['mtn_enabled']) ? ['MTN MoMo', '<span class="gw-chip" style="background:#FFCB00;color:#000">MTN</span>'] : null,
        'orange' => ! empty($th['orange_enabled']) ? ['Orange Money', '<span class="gw-chip" style="background:#FF6600;color:#fff">OM</span>'] : null,
    ]);
    $firstGateway = array_key_first($gateways);

    // Manual methods: details the payer needs, then a reference + proof form.
    $manual = [
        'bank' => [
            'title' => 'Bank transfer details',
            'rows' => [
                ['Bank', $th['bank_name'] ?? null, false],
                ['Account name', $th['bank_account_name'] ?? null, false],
                ['Account number', $th['bank_account_number'] ?? null, true],
                ['Routing / sort code', $th['bank_routing_number'] ?? null, true],
                ['SWIFT / BIC', $th['bank_swift_code'] ?? null, true],
            ],
            'note' => $th['bank_payment_instructions'] ?? null,
            'route' => 'saas.bank.checkout',
            'ref_label' => 'Transaction reference',
            'ref_placeholder' => 'Bank reference / transfer ID',
        ],
        'mtn' => [
            'title' => 'MTN Mobile Money details',
            'rows' => [
                ['MoMo number', $th['mtn_number'] ?? null, true],
                ['Account name', $th['mtn_account_name'] ?? null, false],
            ],
            'note' => $th['mtn_instructions'] ?? null,
            'route' => 'saas.mtn.checkout',
            'ref_label' => 'Transaction reference / MoMo ID',
            'ref_placeholder' => 'MoMo transaction ID',
        ],
        'orange' => [
            'title' => 'Orange Money details',
            'rows' => [
                ['Orange number', $th['orange_number'] ?? null, true],
                ['Account name', $th['orange_account_name'] ?? null, false],
            ],
            'note' => $th['orange_instructions'] ?? null,
            'route' => 'saas.orange.checkout',
            'ref_label' => 'Transaction reference / Orange ID',
            'ref_placeholder' => 'Orange Money transaction ID',
        ],
    ];

    if ($selected) {
        $displayPrice = match ($type) {
            'yearly' => $selected->price_yearly,
            'one_time' => $selected->price_onetime ?? $selected->price_monthly,
            default => $selected->price_monthly,
        };
        [$billingLabel, $periodLabel] = match ($type) {
            'yearly' => ['Yearly price', 'per year'],
            'one_time' => ['Lifetime price', 'one-time, lifetime access'],
            default => ['Monthly price', 'per month'],
        };
    }
@endphp


@section('content')
    <div class="container status-wrap">
        <a href="{{ route('pricing') }}" class="link-arrow" style="margin-bottom:1.25rem"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to pricing</a>

        @if (session('not_permitted'))
            {{-- Escaped: tenant-creation failures append exception text, which can echo submitted values. --}}
            <div class="alert alert--error" role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><span>{{ session('not_permitted') }}</span></div>
        @endif
        @if (session('error'))
            <div class="alert alert--error" role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><span>{{ session('error') }}</span></div>
        @endif
        @if (session('success'))
            <div class="alert alert--success" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i><span>{{ session('success') }}</span></div>
        @endif
        @if ($errors->any())
            <div class="alert alert--error" role="alert"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="card split" style="margin-top:1rem">
            <aside class="split__aside">
                <p class="aside-label">Your order</p>
                @if ($selected)
                    <h2 style="margin-top:.4rem;font-size:var(--text-2xl)">{{ $selected->name }}</h2>
                    @if ($selected->description)
                        <p style="margin-top:.4rem;color:rgb(255 255 255 / .72);font-size:var(--text-sm)">{{ $selected->description }}</p>
                    @endif

                    <p class="aside-label" style="margin-top:1.5rem">{{ $billingLabel }}</p>
                    <p style="font-size:var(--text-4xl);font-weight:800;line-height:1.1">${{ number_format($displayPrice, 2) }}</p>
                    <p style="color:rgb(255 255 255 / .65);font-size:var(--text-sm)">{{ $periodLabel }}</p>
                    @if ($type === 'one_time')
                        <p style="margin-top:.75rem;color:var(--np-accent);font-weight:700;font-size:var(--text-sm)"><i class="fas fa-infinity" aria-hidden="true"></i> Pay once, use forever</p>
                    @elseif ($type === 'yearly' && $selected->getYearlySavingPercent() > 0)
                        <p style="margin-top:.75rem;color:var(--np-accent);font-weight:700;font-size:var(--text-sm)">You save {{ $selected->getYearlySavingPercent() }}% vs monthly</p>
                    @endif

                    <p class="aside-label" style="margin-top:1.75rem">What&rsquo;s included</p>
                    <ul class="perks" style="margin-top:.75rem">
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i> {{ $selected->product_limit ? number_format($selected->product_limit) . ' products' : 'Unlimited products' }}</li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i> {{ $selected->user_limit ? number_format($selected->user_limit) . ' staff accounts' : 'Unlimited staff' }}</li>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i> {{ $selected->warehouse_limit ? number_format($selected->warehouse_limit) . ' warehouses' : 'Unlimited warehouses' }}</li>
                        @foreach ($selected->features ?? [] as $feature)
                            <li><i class="fas fa-circle-check" aria-hidden="true"></i> {{ ucwords(str_replace('_', ' ', $feature)) }}</li>
                        @endforeach
                        @if ($selected->is_free_trial)
                            <li><i class="fas fa-gift" aria-hidden="true"></i> {{ $selected->trial_days }}-day free trial included</li>
                        @endif
                    </ul>
                @else
                    <p style="margin-top:1rem;color:rgb(255 255 255 / .7)">No package selected.</p>
                @endif
            </aside>

            <div class="split__main">
                <h1 style="font-size:var(--text-3xl)">Complete your order</h1>
                <p class="muted" style="margin-top:.35rem">Your store will be ready in seconds.</p>

                <div class="account-box" style="margin-top:1.5rem">
                    <div class="account-box__head">
                        <i class="fas fa-circle-user" style="color:var(--np-primary)" aria-hidden="true"></i> Account details
                        <a href="{{ route('register') }}"><i class="fas fa-pen" aria-hidden="true"></i> Change</a>
                    </div>
                    <dl>
                        <div><dt>Store: </dt><dd>{{ $pending['tenant'] }}.{{ $domain }}</dd></div>
                        <div><dt>Name: </dt><dd>{{ $pending['name'] }}</dd></div>
                        @if (! empty($pending['company_name']))
                            <div><dt>Company: </dt><dd>{{ $pending['company_name'] }}</dd></div>
                        @endif
                        <div><dt>Email: </dt><dd>{{ $pending['email'] }}</dd></div>
                    </dl>
                </div>

                @if ($gateways)
                    <p class="field"><span class="label">Choose a payment method</span></p>
                    <div class="gateways" role="tablist" aria-label="Payment method" data-tabs>
                        @foreach ($gateways as $key => [$label, $logo])
                            <button type="button" class="gateway" role="tab" id="gw-tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                                    aria-selected="{{ $key === $firstGateway ? 'true' : 'false' }}" tabindex="{{ $key === $firstGateway ? 0 : -1 }}">
                                {!! $logo !!}<span>{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>

                    @if (isset($gateways['stripe']))
                        <div id="panel-stripe" role="tabpanel" aria-labelledby="gw-tab-stripe" @if ($firstGateway !== 'stripe') hidden @endif>
                            <form action="{{ route('saas.stripe.checkout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn--primary btn--lg btn--block" style="background:#635bff"><i class="fab fa-stripe-s" aria-hidden="true"></i> Continue to Stripe</button>
                            </form>
                        </div>
                    @endif

                    @if (isset($gateways['paystack']))
                        <div id="panel-paystack" role="tabpanel" aria-labelledby="gw-tab-paystack" @if ($firstGateway !== 'paystack') hidden @endif>
                            <form action="{{ route('saas.paystack.checkout') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn--primary btn--lg btn--block" style="background:#00a8d6"><i class="fas fa-bolt" aria-hidden="true"></i> Continue to Paystack</button>
                            </form>
                        </div>
                    @endif

                    @foreach ($manual as $key => $method)
                        @continue(! isset($gateways[$key]))
                        <div id="panel-{{ $key }}" role="tabpanel" aria-labelledby="gw-tab-{{ $key }}" @if ($firstGateway !== $key) hidden @endif>
                            <div class="pay-details">
                                <h3>{{ $method['title'] }}</h3>
                                <dl>
                                    @foreach ($method['rows'] as [$label, $value, $mono])
                                        @continue(empty($value))
                                        <dt>{{ $label }}</dt><dd @class(['mono' => $mono])>{{ $value }}</dd>
                                    @endforeach
                                </dl>
                                @if ($method['note'])
                                    <p class="pay-details__note"><i class="fas fa-circle-info" aria-hidden="true"></i> {{ $method['note'] }}</p>
                                @endif
                            </div>
                            <form action="{{ route($method['route']) }}" method="POST" enctype="multipart/form-data" class="form-grid">
                                @csrf
                                <div class="field">
                                    <label for="ref-{{ $key }}">{{ $method['ref_label'] }} <span class="req">*</span></label>
                                    <input type="text" id="ref-{{ $key }}" name="transaction_ref" class="input" placeholder="{{ $method['ref_placeholder'] }}" required>
                                </div>
                                <div class="field">
                                    <label for="proof-{{ $key }}">Payment proof <span class="opt">(screenshot or receipt, optional)</span></label>
                                    <input type="file" id="proof-{{ $key }}" name="payment_proof" class="input" accept="image/*,.pdf">
                                    <p class="hint">PNG, JPG or PDF · max 2 MB</p>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn--primary btn--lg btn--block"><i class="fas fa-paper-plane" aria-hidden="true"></i> Submit &amp; await activation</button>
                                    <p class="hint" style="margin-top:.6rem"><i class="fas fa-clock" aria-hidden="true"></i> Your account is activated once we verify the payment, usually within one business day.</p>
                                </div>
                            </form>
                        </div>
                    @endforeach
                @else
                    <div class="alert alert--warning"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i><span>No payment method is configured yet. Please contact the administrator.</span></div>
                @endif

                <ul class="trust">
                    <li><i class="fas fa-lock" aria-hidden="true"></i> SSL encrypted</li>
                    <li><i class="fas fa-shield-halved" aria-hidden="true"></i> PCI compliant</li>
                    <li><i class="fas fa-rotate-left" aria-hidden="true"></i> Cancel anytime</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
