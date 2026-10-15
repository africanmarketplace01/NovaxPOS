@extends('landlord.layouts.public')

@section('title', 'Your store is ready — ' . $siteN)
@section('solid-header', '1')

@section('content')
    <div class="container status-wrap">
        <div class="card status-card">
            <div class="status-card__head">
                <div class="status-card__icon"><i class="fas fa-check" aria-hidden="true"></i></div>
                <h1>{{ ($isSeller ?? false) ? 'Your marketplace store is ready!' : 'Your store is ready!' }}</h1>
                <p>{{ ($isSeller ?? false) ? 'Sign in to add your products and publish them to the marketplace.' : 'Setup is complete. You can now sign in to your POS.' }}</p>
            </div>
            <div class="status-card__body">
                <div class="url-box">
                    <span>Your store address</span>
                    <a href="{{ $loginUrl }}" target="_blank" rel="noopener">{{ $loginUrl }}</a>
                </div>
                <ul class="info-list">
                    <li><i class="fas fa-envelope" aria-hidden="true"></i><span>A welcome email with your sign-in details has been sent to <strong>{{ $email }}</strong>.</span></li>
                    <li><i class="fas fa-key" aria-hidden="true"></i><span>Sign in with the email and password you registered with.</span></li>
                    <li><i class="fas fa-shield-halved" aria-hidden="true"></i><span>We recommend changing your password after your first sign-in.</span></li>
                    @if ($isSeller ?? false)
                        <li><i class="fas fa-store" aria-hidden="true"></i><span>Add products, then open <strong>Online Store → Marketplace Listings</strong> to publish them. You pay a commission only when you sell.</span></li>
                    @endif
                </ul>
                <a href="{{ $loginUrl }}" class="btn btn--primary btn--lg btn--block" id="gotoBtn" style="margin-top:1.75rem">
                    <i class="fas fa-right-to-bracket" aria-hidden="true"></i> {{ ($isSeller ?? false) ? 'Go to my store' : 'Go to my POS' }}
                </a>
                <p class="hint text-center" id="countdownMsg" style="margin-top:.9rem" aria-live="polite">
                    Redirecting automatically in <span id="countdownSec">10</span>s&hellip;
                    <button type="button" id="stayBtn" style="border:0;background:none;color:var(--np-primary);font-weight:600;text-decoration:underline">Stay here</button>
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var url = @json($loginUrl);
            var seconds = 10;
            var label = document.getElementById('countdownSec');
            var message = document.getElementById('countdownMsg');
            var timer = setInterval(function () {
                seconds--;
                label.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(timer);
                    window.location.href = url;
                }
            }, 1000);

            function cancel() {
                clearInterval(timer);
                message.hidden = true;
            }
            document.getElementById('gotoBtn').addEventListener('click', cancel);
            document.getElementById('stayBtn').addEventListener('click', cancel);
        })();
    </script>
@endpush
