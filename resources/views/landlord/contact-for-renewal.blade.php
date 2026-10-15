@extends('landlord.layouts.public')

@section('title', 'Renew your subscription — ' . $siteName)
@section('solid-header', '1')

@section('content')
    <div class="container status-wrap">
        <div class="card status-card">
            <div class="status-card__head">
                <div class="status-card__icon"><i class="fas fa-arrows-rotate" aria-hidden="true"></i></div>
                <h1>Renew your subscription</h1>
                <p>Contact our support team to renew your {{ $siteName }} access.</p>
            </div>
            <div class="status-card__body">
                @if ($tenantId)
                    <div class="url-box" style="margin-bottom:1.5rem">
                        <span>Account reference</span>
                        <strong>{{ $tenant?->company_name ?: $tenantId }}</strong>
                        @if ($tenant && $tenant->company_name)
                            <div class="hint" style="margin-top:0">{{ $tenantId }}</div>
                        @endif
                    </div>
                @endif

                <div style="display:grid;gap:.75rem">
                    @if ($supportPhone)
                        <a class="btn btn--primary btn--lg btn--block" href="tel:{{ preg_replace('/[^+0-9]/', '', $supportPhone) }}">
                            <i class="fas fa-phone" aria-hidden="true"></i> {{ $supportPhone }}
                        </a>
                    @endif
                    @if ($supportEmail)
                        <a class="btn btn--outline btn--lg btn--block" href="mailto:{{ $supportEmail }}?subject={{ rawurlencode('Subscription renewal' . ($tenantId ? ' - ' . $tenantId : '')) }}">
                            <i class="fas fa-envelope" aria-hidden="true"></i> {{ $supportEmail }}
                        </a>
                    @endif
                    <a class="link-arrow" href="{{ route('pricing') }}" style="justify-content:center;margin-top:.5rem">View available plans <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </div>
@endsection
