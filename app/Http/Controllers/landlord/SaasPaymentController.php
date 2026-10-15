<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Package;
use App\Models\landlord\PlanPromotion;
use App\Models\landlord\Tenant;
use App\Models\landlord\TenantPayment;
use App\Traits\TenantInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook;

class SaasPaymentController extends Controller
{
    use TenantInfo;

    // ─── Public Checkout Flow ─────────────────────────────────────────────

    /** Show checkout page — called after register form for paid plans */
    public function checkout(Request $request)
    {
        $pending = session('pending_checkout');

        // Guard: if no session data, send back to register
        if (empty($pending)) {
            return redirect()->route('register')
                ->with('error', 'Please complete the registration form first.');
        }

        $packages = Package::active()->orderBy('sort_order')->get();
        $selected = Package::find($pending['package_id']);
        $type     = $pending['subscription_type'];

        return view('landlord.checkout', compact('packages', 'selected', 'type', 'pending'));
    }

    // ─── Pending signup (set by PublicController::registerSubmit) ─────────

    /**
     * The signup the customer submitted on /register, validated there and kept
     * server-side. Gateways read the account, package and plan only from here, so
     * nothing posted from the checkout page can change them. Null if it is missing
     * or malformed (e.g. a session from before the password was hashed).
     */
    private function pendingCheckout(): ?array
    {
        $pending = session('pending_checkout');

        if (empty($pending['token']) || empty($pending['password_hash'])
            || ! in_array($pending['subscription_type'] ?? null, ['monthly', 'yearly', 'one_time'], true)
            || ! Package::whereKey($pending['package_id'] ?? null)->exists()) {
            return null;
        }

        return $pending;
    }

    /** Redirect for a checkout request that has no usable pending signup. */
    private function missingCheckout()
    {
        return redirect()->route('register')
            ->with('error', 'Your registration session has expired. Please fill in the registration form again.');
    }

    private function storeNameTaken(array $pending): bool
    {
        return Tenant::whereKey($pending['tenant'])->exists();
    }

    private function storeNameTakenResponse()
    {
        return redirect()->route('register')
            ->with('error', 'That store name was taken before checkout finished. Please register again with another name.');
    }

    /** Price for the pending plan, after any live season-wide promotion (matches the landing page). */
    private function checkoutPrice(Package $package, string $subscriptionType): float
    {
        $price = match ($subscriptionType) {
            'yearly'   => $package->price_yearly,
            'one_time' => $package->price_onetime ?? $package->price_monthly,
            default    => $package->price_monthly,
        };

        return PlanPromotion::discount((float) $price);
    }

    /**
     * Build the request createTenant() expects from the pending signup. The password
     * travels only as its hash, in the attributes bag (never input), which
     * createTenant stores as-is.
     */
    private function tenantRequest(array $pending, array $extra = []): Request
    {
        $request = new Request(array_merge([
            'tenant'            => $pending['tenant'],
            'name'              => $pending['name'],
            'email'             => $pending['email'],
            'company_name'      => $pending['company_name'] ?? '',
            'phone_number'      => $pending['phone_number'] ?? '',
            'package_id'        => $pending['package_id'],
            'subscription_type' => $pending['subscription_type'],
            'logo_filename'     => $pending['logo_filename'] ?? '',
        ], $extra));
        $request->attributes->set('password_hash', $pending['password_hash']);

        return $request;
    }

    /**
     * Pending signup for a gateway callback, only if this browser's session is the
     * one that started the payment (its token went to the gateway as metadata).
     */
    private function pendingForCallback(?string $token): ?array
    {
        $pending = $this->pendingCheckout();

        return ($pending && $token && hash_equals($pending['token'], $token)) ? $pending : null;
    }

    /**
     * Create the tenant and, if that worked, record the payment and clear the
     * pending signup. createTenant() returns a redirect on success and on failure
     * alike; only on success does the tenant row survive, so that is the signal.
     */
    private function createTenantAndRecordPayment(array $pending, array $payment, array $extra = [])
    {
        $result = $this->createTenant($this->tenantRequest($pending, $extra));
        if (! $this->storeNameTaken($pending)) {
            return $result;
        }

        TenantPayment::create(array_merge([
            'tenant_id'         => $pending['tenant'],
            'package_id'        => $pending['package_id'],
            'subscription_type' => $pending['subscription_type'],
        ], $payment));
        session()->forget('pending_checkout');

        return $result;
    }

    // ─── Stripe ───────────────────────────────────────────────────────────

    /** Start a Stripe Checkout session */
    public function stripeCheckout(Request $request)
    {
        $pending = $this->pendingCheckout();
        if (! $pending) return $this->missingCheckout();
        if ($this->storeNameTaken($pending)) return $this->storeNameTakenResponse();

        $package = Package::findOrFail($pending['package_id']);
        $price   = $this->checkoutPrice($package, $pending['subscription_type']);

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => (int)($price * 100),
                    'product_data' => ['name' => $package->name . ' — ' . ucfirst($pending['subscription_type'])],
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => route('saas.stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('saas.checkout'),
            // Account details stay in our session; the gateway gets only the token
            // that ties the payment back to it, plus ids for reconciliation.
            'metadata'    => [
                'checkout_token'    => $pending['token'],
                'tenant'            => $pending['tenant'],
                'package_id'        => $package->id,
                'subscription_type' => $pending['subscription_type'],
            ],
        ]);

        return redirect($session->url);
    }

    /** Stripe redirect callback */
    public function stripeCallback(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $session = StripeSession::retrieve($request->session_id);

        if ($session->payment_status !== 'paid') {
            return redirect(route('saas.checkout'))->with('error', 'Payment was not completed.');
        }

        $pending = $this->pendingForCallback($session->metadata['checkout_token'] ?? null);
        if (! $pending) {
            Log::error('Stripe payment has no matching pending signup', ['session' => $session->id]);
            return redirect()->route('register')->with('error',
                'We received your payment but could not find your registration details. '
                . 'Please contact support and quote reference ' . $session->id . '.');
        }
        if ($this->storeNameTaken($pending)) return $this->storeNameTakenResponse();

        return $this->createTenantAndRecordPayment($pending, [
            'amount'           => $session->amount_total / 100,
            'currency'         => strtoupper($session->currency),
            'transaction_ref'  => $session->payment_intent,
            'status'           => 'completed',
            'paid_by'          => 'stripe',
            'gateway_response' => ['session_id' => $session->id], // array cast encodes it
        ]);
    }

    /** Stripe webhook handler */
    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sig, config('services.stripe.webhook_secret'));
        } catch (\Exception $e) {
            Log::error('Stripe webhook signature mismatch: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            Log::info('Stripe payment completed', ['session' => $session->id]);
        }

        return response()->json(['status' => 'ok']);
    }

    // ─── Free Trial ───────────────────────────────────────────────────────

    public function startFreeTrial(Request $request)
    {
        $request->validate([
            'tenant'     => 'required|alpha_num|min:3|max:30|unique:saleprosaas_landlord.tenants,id',
            'name'       => 'required|string|max:100',
            'email'      => 'required|email',
            'password'   => 'required|min:6',
            'package_id' => 'required|exists:saleprosaas_landlord.packages,id',
        ]);

        $package = Package::findOrFail($request->package_id);

        if (!$package->is_free_trial) {
            return back()->with('error', 'This package does not support free trial.');
        }

        $request->merge(['subscription_type' => 'free_trial']);
        $result = $this->createTenant($request);
        if ($result instanceof \Illuminate\Http\RedirectResponse) return $result;

        return redirect('http://' . $request->tenant . '.' . env('CENTRAL_DOMAIN'))
            ->with('success', 'Your ' . $package->trial_days . '-day trial has started!');
    }

    // ─── PayStack Initiation ─────────────────────────────────────────────

    public function paystackCheckout(Request $request)
    {
        $pending = $this->pendingCheckout();
        if (! $pending) return $this->missingCheckout();
        if ($this->storeNameTaken($pending)) return $this->storeNameTakenResponse();

        $package = Package::findOrFail($pending['package_id']);
        $price   = $this->checkoutPrice($package, $pending['subscription_type']);

        $paystackKey    = \App\Models\landlord\SaasSettings::get('paystack_secret_key') ?? config('services.paystack.secret');
        $reference      = 'PSK-' . strtoupper(Str::random(12));
        $amountKobo     = (int)($price * 100);

        $response = \Illuminate\Support\Facades\Http::withToken($paystackKey)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email'        => $pending['email'],
                'amount'       => $amountKobo,
                'reference'    => $reference,
                'callback_url' => route('saas.paystack.callback'),
                // As with Stripe: only the session token and ids, never account details.
                'metadata'     => [
                    'checkout_token'    => $pending['token'],
                    'tenant'            => $pending['tenant'],
                    'package_id'        => $package->id,
                    'subscription_type' => $pending['subscription_type'],
                ],
            ]);

        $data = $response->json();

        if (!($data['status'] ?? false)) {
            return back()->with('error', 'Paystack initialisation failed: ' . ($data['message'] ?? 'Unknown error'));
        }

        return redirect($data['data']['authorization_url']);
    }

    // ─── PayStack Callback ────────────────────────────────────────────────

    public function paystackCallback(Request $request)
    {
        $reference = $request->reference;

        // Same key as initialisation (the app has no global `Http` alias, so use the facade).
        $paystackKey = \App\Models\landlord\SaasSettings::get('paystack_secret_key') ?? config('services.paystack.secret');
        $response = \Illuminate\Support\Facades\Http::withToken($paystackKey)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        $data = $response->json();

        if (!$data['status'] || $data['data']['status'] !== 'success') {
            return redirect(route('saas.checkout'))->with('error', 'Payment verification failed.');
        }

        $pending = $this->pendingForCallback($data['data']['metadata']['checkout_token'] ?? null);
        if (! $pending) {
            Log::error('Paystack payment has no matching pending signup', ['reference' => $reference]);
            return redirect()->route('register')->with('error',
                'We received your payment but could not find your registration details. '
                . 'Please contact support and quote reference ' . $reference . '.');
        }
        if ($this->storeNameTaken($pending)) return $this->storeNameTakenResponse();

        return $this->createTenantAndRecordPayment($pending, [
            'amount'           => $data['data']['amount'] / 100,
            'currency'         => strtoupper($data['data']['currency']),
            'transaction_ref'  => $reference,
            'status'           => 'completed',
            'paid_by'          => 'paystack',
            'gateway_response' => $data['data'],
        ]);
    }

    // ─── Manual payments (tenant created as status=0 until verified) ──────

    /** Bank transfer — a superadmin must verify the payment and activate. */
    public function bankCheckout(Request $request)
    {
        return $this->manualCheckout($request, 'bank_transfer',
            'Thank you! Your account has been created and is awaiting payment verification. We will activate it within 1 business day.');
    }

    /** MTN Mobile Money */
    public function mtnCheckout(Request $request)
    {
        return $this->manualCheckout($request, 'mtn_mobile_money',
            'Thank you! Your account has been created and is awaiting MTN Mobile Money payment verification. We will activate it within 1 business day.');
    }

    /** Orange Money */
    public function orangeCheckout(Request $request)
    {
        return $this->manualCheckout($request, 'orange_money',
            'Thank you! Your account has been created and is awaiting Orange Money payment verification. We will activate it within 1 business day.');
    }

    /**
     * Only the payment reference and proof come from the form; the account,
     * package and plan come from the pending signup.
     */
    private function manualCheckout(Request $request, string $paidBy, string $successMessage)
    {
        $pending = $this->pendingCheckout();
        if (! $pending) return $this->missingCheckout();
        if ($this->storeNameTaken($pending)) return $this->storeNameTakenResponse();

        $request->validate([
            'transaction_ref' => 'required|string|max:200',
            'payment_proof'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $proofFilename = null;
        if ($request->hasFile('payment_proof')) {
            $file = $request->file('payment_proof');
            $proofFilename = date('YmdHis') . '_proof_' . $pending['tenant'] . '.' . $file->getClientOriginalExtension();
            $file->move(storage_path('app/payment-proofs'), $proofFilename);
        }

        $result = $this->createTenantAndRecordPayment($pending, [
            'amount'          => 0, // unknown until verified
            'status'          => 'pending',
            'paid_by'         => $paidBy,
            'transaction_ref' => $request->transaction_ref,
            'payment_proof'   => $proofFilename,
        ], ['pending_manual_payment' => true]);

        // Still pending means createTenant failed; pass its error redirect through.
        if (session()->has('pending_checkout')) {
            return $result;
        }

        return redirect(route('landing'))->with('success', $successMessage);
    }
}
