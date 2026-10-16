@extends('landlord.layouts.public')

@section('title', 'Delivery Agency Registration — ' . $siteN)
@section('description', 'Join the ' . $siteN . ' marketplace network and deliver for local businesses.')

@section('content')
    @include('landlord.partials.page-hero', [
        'eyebrow' => 'Delivery partners',
        'title' => 'Become a delivery partner.',
        'lead' => 'Join the ' . $siteN . ' marketplace network and start delivering for local businesses.',
    ])

    <section class="section">
        <div class="container" style="max-width:52rem">
            @if ($errors->any())
                <div class="alert alert--error" role="alert">
                    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form action="{{ route('delivery.register.submit') }}" method="POST" enctype="multipart/form-data" class="card card--pad agency-form">
                @csrf

                <h2 class="agency-form__section">Business information</h2>
                <div class="form-grid form-grid--2">
                    <div class="field">
                        <label for="agency-name">Agency name <span class="req">*</span></label>
                        <input id="agency-name" type="text" name="name" class="input" value="{{ old('name') }}" autocomplete="organization" required>
                    </div>
                    <div class="field">
                        <label for="contact-name">Contact person name <span class="req">*</span></label>
                        <input id="contact-name" type="text" name="contact_name" class="input" value="{{ old('contact_name') }}" autocomplete="name" required>
                    </div>
                    <div class="field">
                        <label for="email">Email address <span class="req">*</span></label>
                        <input id="email" type="email" name="email" class="input" value="{{ old('email') }}" autocomplete="email" required>
                    </div>
                    <div class="field">
                        <label for="phone">Phone number <span class="req">*</span></label>
                        <input id="phone" type="tel" name="phone" class="input" value="{{ old('phone') }}" autocomplete="tel" required>
                    </div>
                    <div class="field span-2">
                        <label for="address">Business address</label>
                        <input id="address" type="text" name="address" class="input" value="{{ old('address') }}" autocomplete="street-address" placeholder="Street, city, country">
                    </div>
                    <div class="field span-2">
                        <label for="logo">Agency logo <span class="opt">(optional · PNG, JPG or WebP, max 5 MB)</span></label>
                        <input id="logo" type="file" name="logo" class="input" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>

                <h2 class="agency-form__section">Service details</h2>
                <div class="form-grid form-grid--2">
                    <div class="field span-2">
                        <label for="coverage-areas">Coverage areas <span class="req">*</span></label>
                        <input id="coverage-areas" type="text" name="coverage_areas" class="input" value="{{ old('coverage_areas') }}" placeholder="e.g. Monrovia, Paynesville, Margibi County" required>
                        <p class="hint">Enter the cities or regions you cover, separated by commas.</p>
                    </div>
                    <div class="field span-2">
                        <label for="description">Description / About</label>
                        <textarea id="description" name="description" class="input" rows="4" placeholder="Tell customers about your delivery service…">{{ old('description') }}</textarea>
                    </div>
                    <div class="field">
                        <label for="base-rate">Base delivery rate <span class="req">*</span></label>
                        <div class="input-group">
                            <input id="base-rate" type="number" name="base_rate" class="input" step="0.01" min="0" value="{{ old('base_rate', 0) }}" required>
                            <select name="currency" class="input" aria-label="Currency">
                                @foreach (['USD', 'LRD', 'NGN', 'GHS'] as $currency)
                                    <option value="{{ $currency }}" @selected(old('currency', 'USD') === $currency)>{{ $currency }}</option>
                                @endforeach
                            </select>
                        </div>
                        <p class="hint">Base fee per delivery.</p>
                    </div>
                    <div class="field">
                        <label for="per-km-rate">Per-km rate <span class="opt">(optional)</span></label>
                        <input id="per-km-rate" type="number" name="per_km_rate" class="input" step="0.01" min="0" value="{{ old('per_km_rate', 0) }}">
                    </div>
                    <div class="field">
                        <label for="estimated-days-min">Estimated days (minimum) <span class="req">*</span></label>
                        <input id="estimated-days-min" type="number" name="estimated_days_min" class="input" min="1" max="60" value="{{ old('estimated_days_min', 1) }}" required>
                    </div>
                    <div class="field">
                        <label for="estimated-days-max">Estimated days (maximum) <span class="req">*</span></label>
                        <input id="estimated-days-max" type="number" name="estimated_days_max" class="input" min="1" max="60" value="{{ old('estimated_days_max', 3) }}" required>
                    </div>
                </div>

                <h2 class="agency-form__section">Account password</h2>
                <div class="form-grid form-grid--2">
                    <div class="field">
                        <label for="password">Password <span class="req">*</span></label>
                        <input id="password" type="password" name="password" class="input" minlength="8" autocomplete="new-password" required>
                        <p class="hint">Use at least 8 characters.</p>
                    </div>
                    <div class="field">
                        <label for="password-confirmation">Confirm password <span class="req">*</span></label>
                        <input id="password-confirmation" type="password" name="password_confirmation" class="input" minlength="8" autocomplete="new-password" required>
                    </div>
                    <div class="span-2">
                        <button type="submit" class="btn btn--primary btn--lg">Submit registration <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                        <p class="hint" style="margin-top:.9rem">Our team will review your application within 24–48 hours.</p>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('head')
    <style>
        .agency-form { display: grid; gap: 1.5rem; }
        .agency-form__section {
            margin: 0;
            padding-bottom: .65rem;
            border-bottom: 1px solid var(--line);
            color: var(--np-navy);
            font-size: var(--text-lg);
        }
        .agency-form textarea.input { min-height: 7rem; resize: vertical; }
        .agency-form .input-group select.input {
            width: auto; flex: 0 0 6.5rem;
            border-radius: 0 var(--radius-control) var(--radius-control) 0;
        }
    </style>
@endpush
