@extends('landlord.layouts.public')

@section('title', 'Pricing — ' . $siteN)
@section('description', 'Simple, transparent pricing for ' . $siteN . '. Start free and upgrade as your business grows.')

@php $yearlySavingsLabel = $th['yearly_savings_label'] ?? ''; @endphp

@section('content')
    @include('landlord.partials.page-hero', [
        'eyebrow' => 'Pricing',
        'title' => 'Simple, transparent pricing',
        'lead' => 'Start free and upgrade as your business grows. No hidden fees, no lock-in contracts.',
        'center' => true,
        'extraView' => 'landlord.partials.billing-toggle',
        'extraData' => ['yearlySavingsLabel' => $yearlySavingsLabel],
    ])

    <section class="section">
        <div class="container">
            @if ($packages->isEmpty())
                <div class="empty"><i class="fas fa-box-open" aria-hidden="true"></i><p>No plans available yet. Check back soon.</p></div>
            @else
                <div @class(['plans', 'plans--4' => $packages->count() >= 4, 'plans--3' => $packages->count() === 3]) data-billing="monthly">
                    @foreach ($packages as $plan)
                        @include('landlord.partials.plan-card', ['plan' => $plan, 'promotion' => $promotion ?? null])
                    @endforeach
                </div>
            @endif

            @include('landlord.partials.seller-callout')

            <ul class="trust" style="margin-top:2.5rem">
                <li><i class="fas fa-lock" aria-hidden="true"></i> SSL on every store</li>
                <li><i class="fas fa-database" aria-hidden="true"></i> Automatic backups</li>
                <li><i class="fas fa-signal" aria-hidden="true"></i> 99.9% uptime</li>
                <li><i class="fas fa-rotate-left" aria-hidden="true"></i> Cancel anytime</li>
            </ul>
        </div>
    </section>

    <section class="section cta-band">
        <div class="container">
            <h2>Not sure which plan fits?</h2>
            <p>Start on a free trial and upgrade whenever you need more products, staff or branches. Your data comes with you.</p>
            <div class="cta-band__actions">
                <a href="{{ route('register') }}" class="btn btn--accent btn--lg"><i class="fas fa-rocket" aria-hidden="true"></i> Start free trial</a>
                <a href="{{ route('landing') }}#faq" class="btn btn--ghost-light btn--lg">Read the FAQ</a>
            </div>
        </div>
    </section>
@endsection
