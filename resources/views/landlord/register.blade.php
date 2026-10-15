@extends('landlord.layouts.public')

@section('title', 'Create your account — ' . $siteN)
@section('solid-header', '1')

@php
    $domain = env('CENTRAL_DOMAIN', 'novaxpos.com');
    // Respect a billing type the visitor already chose (from a pricing link or
    // a failed submit); only auto-pick one when nothing was chosen.
    $billingChosen = old('subscription_type') !== null || request()->filled('type');
@endphp

@section('content')
    <div class="container status-wrap">
        <div class="card split">
            <aside class="split__aside">
                <p class="aside-label">Get started</p>
                <h2 style="margin-top:.5rem;font-size:var(--text-2xl)">Start your journey today</h2>
                <p style="margin-top:.75rem;color:rgb(255 255 255 / .78)">Everything you need to run your business, in one place.</p>
                <ul class="perks">
                    <li><i class="fas fa-circle-check" aria-hidden="true"></i> {{ $th['trial_days'] ?? 14 }}-day free trial</li>
                    <li><i class="fas fa-circle-check" aria-hidden="true"></i> No credit card required</li>
                    <li><i class="fas fa-circle-check" aria-hidden="true"></i> Your own store subdomain</li>
                    <li><i class="fas fa-circle-check" aria-hidden="true"></i> Online store &amp; marketplace listing</li>
                    <li><i class="fas fa-circle-check" aria-hidden="true"></i> Cancel anytime</li>
                </ul>
                <p style="margin-top:2.5rem;font-size:var(--text-sm);color:rgb(255 255 255 / .65)">Already have an account?</p>
                <a href="http://{{ $domain }}" style="font-weight:600;color:#fff"><i class="fas fa-right-to-bracket" aria-hidden="true"></i> Sign in to your store</a>
            </aside>

            <div class="split__main">
                <h1 style="font-size:var(--text-3xl)">Create your account</h1>
                <p class="muted" style="margin-top:.35rem">Your POS will be ready in seconds.</p>

                <div style="margin-top:1.5rem">
                    @if ($errors->any())
                        <div class="alert alert--error" role="alert">
                            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @foreach (['not_permitted' => 'error', 'error' => 'error', 'success' => 'success'] as $key => $tone)
                        @if (session($key))
                            <div class="alert alert--{{ $tone }}" role="{{ $tone === 'error' ? 'alert' : 'status' }}">
                                <i class="fas {{ $tone === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check' }}" aria-hidden="true"></i>
                                <span>{{ session($key) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>

                <form action="{{ route('register.submit') }}" method="POST" id="regForm" enctype="multipart/form-data" style="margin-top:1.5rem">
                    @csrf

                    <fieldset style="border:0;padding:0;margin:0 0 1.75rem">
                        <legend class="field"><span class="label">Choose a plan</span></legend>
                        <div class="choice-grid">
                            @foreach ($packages as $pkg)
                                <div class="choice">
                                    <input type="radio" name="package_id" id="pkg_{{ $pkg->id }}" value="{{ $pkg->id }}" class="pkg-radio"
                                           data-free="{{ $pkg->is_free_trial ? 1 : 0 }}" data-paid-trial="{{ $pkg->allow_paid_trial ? 1 : 0 }}" data-onetime="{{ $pkg->price_onetime ? 1 : 0 }}"
                                           @checked(old('package_id', $selected?->id) == $pkg->id)>
                                    <label for="pkg_{{ $pkg->id }}">
                                        <span class="choice__title">{{ $pkg->name }}</span><br>
                                        @if ($pkg->is_free_trial)
                                            <span class="choice__price" style="color:var(--success)">Free</span> <span class="choice__meta">&middot; {{ $pkg->trial_days }}-day trial</span>
                                        @elseif ($pkg->price_onetime && ! $pkg->price_monthly)
                                            <span class="choice__price">${{ number_format($pkg->price_onetime, 0) }}</span> <span class="choice__meta">once</span>
                                        @else
                                            <span class="choice__price">${{ number_format($pkg->price_monthly, 0) }}</span><span class="choice__meta">/mo</span>
                                            @if ($pkg->price_onetime)<span class="choice__meta"> &middot; or ${{ number_format($pkg->price_onetime, 0) }} once</span>@endif
                                        @endif
                                        @if ($pkg->allow_paid_trial && ! $pkg->is_free_trial)
                                            <br><span class="choice__meta" style="color:var(--warning)">{{ $pkg->trial_days }}-day trial available</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="form-grid form-grid--2">
                        <div class="field">
                            <label for="name">Your name <span class="req">*</span></label>
                            <input type="text" id="name" name="name" class="input" value="{{ old('name') }}" autocomplete="name" required>
                        </div>
                        <div class="field">
                            <label for="email">Email address <span class="req">*</span></label>
                            <input type="email" id="email" name="email" class="input" value="{{ old('email') }}" autocomplete="email" required>
                        </div>
                        <div class="field">
                            <label for="company_name">Company name <span class="opt">(optional)</span></label>
                            <input type="text" id="company_name" name="company_name" class="input" value="{{ old('company_name') }}" autocomplete="organization">
                        </div>
                        <div class="field">
                            <label for="phone_number">Phone number <span class="opt">(optional)</span></label>
                            <input type="tel" id="phone_number" name="phone_number" class="input" value="{{ old('phone_number') }}" autocomplete="tel">
                        </div>

                        <div class="field span-2">
                            <label for="logoInput">Business logo <span class="opt">(optional · PNG, JPG or SVG, max 2 MB)</span></label>
                            <div style="display:flex;align-items:center;gap:1rem">
                                <img id="logoPreview" src="" alt="" hidden style="width:3.5rem;height:3.5rem;object-fit:contain;border:1px solid var(--line);border-radius:var(--radius-control);padding:.25rem;flex-shrink:0">
                                <input type="file" name="logo" id="logoInput" accept="image/*" @class(['input', 'is-invalid' => $errors->has('logo')])>
                            </div>
                            @error('logo')<p class="error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field span-2">
                            <label for="tenantInput">Store subdomain <span class="req">*</span></label>
                            <div class="input-group">
                                <input type="text" name="tenant" id="tenantInput" value="{{ old('tenant') }}" placeholder="yourstore"
                                       pattern="[a-z0-9]{3,30}" autocomplete="off" required @class(['input', 'is-invalid' => $errors->has('tenant')])>
                                <span class="input-group__addon">.{{ $domain }}</span>
                            </div>
                            <p class="hint">Lowercase letters and numbers only, 3–30 characters.</p>
                            @error('tenant')<p class="error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field">
                            <label for="password">Password <span class="req">*</span></label>
                            <input type="password" id="password" name="password" minlength="6" placeholder="At least 6 characters" autocomplete="new-password" required
                                   @class(['input', 'is-invalid' => $errors->has('password')])>
                            @error('password')<p class="error">{{ $message }}</p>@enderror
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Confirm password <span class="req">*</span></label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="input" minlength="6" placeholder="Re-enter password" autocomplete="new-password" required>
                        </div>

                        <div class="field span-2">
                            <label for="subscriptionType">Billing <span class="req">*</span></label>
                            <select name="subscription_type" id="subscriptionType" required @class(['input', 'is-invalid' => $errors->has('subscription_type')])>
                                @foreach (['free_trial' => 'Free trial', 'paid_trial' => 'Paid trial', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'one_time' => 'One-time (lifetime)'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('subscription_type', $type) == $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="hint">Paid plans continue to secure checkout after this step.</p>
                            @error('subscription_type')<p class="error">{{ $message }}</p>@enderror
                        </div>

                        <div class="span-2">
                            <button type="submit" class="btn btn--primary btn--lg btn--block">Create my account <i class="fas fa-arrow-right" aria-hidden="true"></i></button>
                            <p class="hint text-center" style="margin-top:.9rem">By creating an account you agree to our Terms of Service and Privacy Policy.</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var billing = document.getElementById('subscriptionType');

            // Pick a sensible billing type for the chosen plan.
            function applyPlanBilling(radio) {
                if (!billing || !radio) return;
                if (radio.dataset.free === '1') billing.value = 'free_trial';
                else if (radio.dataset.paidTrial === '1') billing.value = 'paid_trial';
                else if (radio.dataset.onetime === '1') billing.value = 'one_time';
                else billing.value = 'monthly';
            }

            document.querySelectorAll('.pkg-radio').forEach(function (radio) {
                radio.addEventListener('change', function () { if (this.checked) applyPlanBilling(this); });
            });

            // On load, only fill in billing when the visitor hasn't chosen one
            // already (a "Get started yearly" link must stay yearly).
            @unless ($billingChosen)
                applyPlanBilling(document.querySelector('.pkg-radio:checked'));
            @endunless

            // Logo preview.
            var logoInput = document.getElementById('logoInput');
            var logoPreview = document.getElementById('logoPreview');
            logoInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function (e) { logoPreview.src = e.target.result; logoPreview.hidden = false; };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    logoPreview.hidden = true;
                }
            });

            // Keep the subdomain lowercase letters and digits as it is typed.
            document.getElementById('tenantInput').addEventListener('input', function () {
                this.value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '');
            });
        })();
    </script>
@endpush
