<?php

use App\Http\Controllers\landlord\AuthController;
use App\Http\Controllers\landlord\DashboardController;
use App\Http\Controllers\landlord\ClientController;
use App\Http\Controllers\landlord\ContractController;
use App\Http\Controllers\landlord\PackageController;
use App\Http\Controllers\landlord\PromotionController;
use App\Http\Controllers\landlord\PaymentController;
use App\Http\Controllers\landlord\TicketController;
use App\Http\Controllers\landlord\SaasPaymentController;
use App\Http\Controllers\landlord\PublicController;
use App\Http\Controllers\landlord\Customer\AuthController as CustomerAuthController;
use App\Http\Controllers\landlord\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\landlord\Customer\SourcingRequestController;
use App\Http\Controllers\landlord\Customer\ChatController as CustomerChatController;
use App\Http\Controllers\landlord\SettingsController;
use App\Http\Controllers\landlord\InvoicesController;
use App\Http\Controllers\landlord\MarketplaceController;
use App\Http\Controllers\landlord\MarketplaceCheckoutController;
use App\Http\Controllers\landlord\MarketplaceAdminController;
use App\Http\Controllers\landlord\MarketplaceReportController;
use App\Http\Controllers\landlord\DeliveryAgencyController;
use App\Http\Controllers\landlord\VideoTutorialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landlord / Superadmin Routes
|--------------------------------------------------------------------------
| These routes are only loaded when the landlord DB connection is active.
| They handle the superadmin portal at the central domain.
*/

// ── Site logo (served from storage, no auth required) ─────────────────────
Route::get('/landlord-logo/{filename}', [SettingsController::class, 'serveLogo'])->name('landlord.logo');

// ── Global Marketplace — Public ────────────────────────────────────────────
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/',                              [MarketplaceController::class, 'index'])->name('index');
    Route::get('/shop/{tenant}',                 [MarketplaceController::class, 'shop'])->name('shop');
    Route::get('/product/{id}/{slug?}',          [MarketplaceController::class, 'product'])->name('product');
    Route::post('/product/{id}/review',          [MarketplaceController::class, 'storeReview'])->name('product.review');

    // Cart
    Route::get('/cart',                          [MarketplaceCheckoutController::class, 'cart'])->name('cart');
    Route::post('/cart/add',                     [MarketplaceCheckoutController::class, 'addToCart'])->name('cart.add');
    Route::post('/cart/update',                  [MarketplaceCheckoutController::class, 'updateCart'])->name('cart.update');

    // Checkout
    Route::get('/checkout',                      [MarketplaceCheckoutController::class, 'checkout'])->name('checkout');
    Route::post('/order/place',                  [MarketplaceCheckoutController::class, 'placeOrder'])->name('order.place');
    Route::get('/order/success/{number}',        [MarketplaceCheckoutController::class, 'orderSuccess'])->name('order.success');

    // Payment callbacks (Stripe / Paystack)
    // Specific routes BEFORE the wildcard to avoid capture
    Route::get('/payment/callback',              [MarketplaceCheckoutController::class, 'paymentCallback'])->name('payment.callback');
    Route::get('/payment/{method}',              [MarketplaceCheckoutController::class, 'payment'])->name('payment');
});

// ── Delivery Agency Registration — Public ──────────────────────────────────
Route::prefix('delivery-agency')->name('delivery.')->group(function () {
    Route::get('/register',                      [DeliveryAgencyController::class, 'registerForm'])->name('register');
    Route::post('/register',                     [DeliveryAgencyController::class, 'register'])->name('register.submit');
    Route::get('/register/success',              [DeliveryAgencyController::class, 'registerSuccess'])->name('register.success');
});

// ── Marketplace Customer Portal ────────────────────────────────────────────
Route::prefix('account')->name('customer.')->group(function () {

    // Guest auth (controllers redirect if already signed in)
    Route::get('/login',     [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login',    [CustomerAuthController::class, 'login'])->name('login.post');
    Route::get('/register',  [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->name('register.post');

    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');

    // Authenticated customer
    Route::middleware('marketplace.customer')->group(function () {
        Route::get('/',        [CustomerDashboardController::class, 'index'])->name('dashboard');
        Route::get('/orders',  [CustomerDashboardController::class, 'orders'])->name('orders');
        Route::get('/orders/{id}',        [CustomerDashboardController::class, 'orderShow'])->name('orders.show');
        Route::post('/orders/{id}/review', [CustomerDashboardController::class, 'submitReview'])->name('orders.review');
        Route::get('/profile', [CustomerDashboardController::class, 'profile'])->name('profile');
        Route::put('/profile', [CustomerDashboardController::class, 'updateProfile'])->name('profile.update');

        // Sourcing requests
        Route::get('/requests',              [SourcingRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/create',       [SourcingRequestController::class, 'create'])->name('requests.create');
        Route::post('/requests',             [SourcingRequestController::class, 'store'])->name('requests.store');
        Route::get('/requests/{id}',         [SourcingRequestController::class, 'show'])->name('requests.show');
        Route::post('/requests/{id}/close',  [SourcingRequestController::class, 'close'])->name('requests.close');

        // Chat (customer side)
        Route::get('/threads/{response}/messages', [CustomerChatController::class, 'messages'])->name('chat.messages');
        Route::post('/threads/{response}/send',    [CustomerChatController::class, 'send'])->name('chat.send');
    });
});

// ── Public / Landing ───────────────────────────────────────────────────────
Route::get('/',                        [PublicController::class, 'landing'])->name('landing');
Route::get('/pricing',                 [PublicController::class, 'pricing'])->name('pricing');
Route::get('/contact-for-renewal',     [PublicController::class, 'contactForRenewal'])->name('contact-for-renewal');
Route::get('/tutorials',               [PublicController::class, 'tutorials'])->name('tutorials');
Route::get('/businesses',              [PublicController::class, 'businesses'])->name('businesses');
Route::get('/register',                [PublicController::class, 'register'])->name('register');
Route::post('/register',               [PublicController::class, 'registerSubmit'])->name('register.submit')->middleware('throttle:saas-register');
Route::get('/registration-success',   [PublicController::class, 'registrationSuccess'])->name('registration.success');
// Free marketplace-only sellers: no package, commission per order.
Route::get('/sell',                    [\App\Http\Controllers\landlord\SellerSignupController::class, 'show'])->name('sell');
Route::post('/sell',                   [\App\Http\Controllers\landlord\SellerSignupController::class, 'store'])->name('sell.submit')->middleware('throttle:saas-register');

// ── SaaS Checkout / Billing ────────────────────────────────────────────────
Route::prefix('checkout')->name('saas.')->group(function () {
    Route::get('/',                   [SaasPaymentController::class, 'checkout'])->name('checkout');
    Route::post('/stripe',            [SaasPaymentController::class, 'stripeCheckout'])->name('stripe.checkout')->middleware('throttle:saas-checkout');
    Route::get('/stripe/callback',    [SaasPaymentController::class, 'stripeCallback'])->name('stripe.callback');
    Route::post('/stripe/webhook',    [SaasPaymentController::class, 'stripeWebhook'])->name('stripe.webhook')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
    Route::post('/paystack',          [SaasPaymentController::class, 'paystackCheckout'])->name('paystack.checkout')->middleware('throttle:saas-checkout');
    Route::get('/paystack/callback',  [SaasPaymentController::class, 'paystackCallback'])->name('paystack.callback');
    Route::post('/free-trial',        [SaasPaymentController::class, 'startFreeTrial'])->name('free-trial')->middleware('throttle:saas-checkout');
    Route::post('/bank',              [SaasPaymentController::class, 'bankCheckout'])->name('bank.checkout')->middleware('throttle:saas-checkout');
    Route::post('/mtn',               [SaasPaymentController::class, 'mtnCheckout'])->name('mtn.checkout')->middleware('throttle:saas-checkout');
    Route::post('/orange',            [SaasPaymentController::class, 'orangeCheckout'])->name('orange.checkout')->middleware('throttle:saas-checkout');
});

// ── Authentication ─────────────────────────────────────────────────────────
Route::prefix('superadmin')->name('superadmin.')->group(function () {

    // Guest-only routes
    Route::middleware('guest:superadmin')->group(function () {
        Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:superadmin-login')->name('login.post');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ── Protected routes ────────────────────────────────────────────────────
    Route::middleware('superadminauth')->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Clients / Tenants
        Route::prefix('clients')->name('clients.')->group(function () {
            Route::get('/',                      [ClientController::class, 'index'])->name('index');
            Route::get('/create',                [ClientController::class, 'create'])->name('create');
            Route::post('/',                     [ClientController::class, 'store'])->name('store');
            Route::get('/{id}',                  [ClientController::class, 'show'])->name('show');
            Route::get('/{id}/edit',             [ClientController::class, 'edit'])->name('edit');
            Route::put('/{id}',                  [ClientController::class, 'update'])->name('update');
            Route::delete('/{id}',               [ClientController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status',    [ClientController::class, 'toggleStatus'])->name('toggle-status');
            Route::post('/{id}/extend-expiry',    [ClientController::class, 'extendExpiry'])->name('extend-expiry');
            Route::post('/{id}/set-subscription', [ClientController::class, 'setSubscription'])->name('set-subscription');
            Route::post('/{id}/send-welcome',           [ClientController::class, 'sendWelcomeEmail'])->name('send-welcome');
            Route::post('/{id}/support',                [ClientController::class, 'setSupportPlan'])->name('support.store');
            Route::patch('/{id}/support/{supportId}',   [ClientController::class, 'updateSupportPlan'])->name('support.update');
            Route::delete('/{id}/support/{supportId}',  [ClientController::class, 'deleteSupportPlan'])->name('support.destroy');
            Route::get('/{id}/impersonate',       [ClientController::class, 'impersonate'])->name('impersonate');
            Route::post('/{id}/toggle-ecommerce',[ClientController::class, 'toggleEcommerce'])->name('toggle-ecommerce');
        });

        // Packages
        Route::prefix('packages')->name('packages.')->group(function () {
            Route::get('/',                      [PackageController::class, 'index'])->name('index');
            Route::get('/create',                [PackageController::class, 'create'])->name('create');
            Route::post('/',                     [PackageController::class, 'store'])->name('store');
            Route::get('/{package}/edit',        [PackageController::class, 'edit'])->name('edit');
            Route::put('/{package}',             [PackageController::class, 'update'])->name('update');
            Route::delete('/{package}',          [PackageController::class, 'destroy'])->name('destroy');
            Route::get('/{package}/data',        [PackageController::class, 'fetchData'])->name('fetchData');
        });

        // Plan promotions (season / limited-time discounts on all plans)
        Route::prefix('promotions')->name('promotions.')->group(function () {
            Route::get('/',              [PromotionController::class, 'index'])->name('index');
            Route::post('/',             [PromotionController::class, 'store'])->name('store');
            Route::put('/{id}',          [PromotionController::class, 'update'])->name('update');
            Route::post('/{id}/toggle',  [PromotionController::class, 'toggle'])->name('toggle');
            Route::delete('/{id}',       [PromotionController::class, 'destroy'])->name('destroy');
        });

        // Payments
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/',                          [PaymentController::class, 'index'])->name('index');
            Route::post('/',                         [PaymentController::class, 'store'])->name('store');
            Route::patch('/{payment}/status',        [PaymentController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{payment}',              [PaymentController::class, 'destroy'])->name('destroy');
        });

        // Settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/',            [SettingsController::class, 'index'])->name('index');
            Route::put('/',            [SettingsController::class, 'update'])->name('update');
            Route::get('/theme-json',  [SettingsController::class, 'themeJson'])->name('theme-json');
            Route::post('/test-email', [SettingsController::class, 'testEmail'])->name('test-email');
            Route::delete('/logo',     [SettingsController::class, 'removeLogo'])->name('logo.remove');
        });

        // Profile (name / email / password for the logged-in superadmin)
        Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/',                        [InvoicesController::class, 'index'])->name('index');
            Route::get('/create',                  [InvoicesController::class, 'create'])->name('create');
            Route::post('/',                       [InvoicesController::class, 'store'])->name('store');
            Route::get('/{invoice}',               [InvoicesController::class, 'show'])->name('show');
            Route::get('/{invoice}/edit',          [InvoicesController::class, 'edit'])->name('edit');
            Route::put('/{invoice}',               [InvoicesController::class, 'update'])->name('update');
            Route::delete('/{invoice}',            [InvoicesController::class, 'destroy'])->name('destroy');
            Route::get('/{invoice}/print',         [InvoicesController::class, 'printInvoice'])->name('print');
            Route::post('/{invoice}/payment',      [InvoicesController::class, 'recordPayment'])->name('payment');
            Route::patch('/{invoice}/status',      [InvoicesController::class, 'updateStatus'])->name('status');
        });

        // Contracts / SLA
        Route::prefix('contracts')->name('contracts.')->group(function () {
            Route::get('/',                           [ContractController::class, 'index'])->name('index');
            Route::get('/create',                     [ContractController::class, 'create'])->name('create');
            Route::post('/',                          [ContractController::class, 'store'])->name('store');
            Route::get('/{contract}',                 [ContractController::class, 'show'])->name('show');
            Route::delete('/{contract}',              [ContractController::class, 'destroy'])->name('destroy');
            Route::post('/{contract}/generate-pdf',   [ContractController::class, 'generatePdfAction'])->name('generate-pdf');
            Route::get('/{contract}/download',        [ContractController::class, 'download'])->name('download');
            Route::post('/{contract}/email',          [ContractController::class, 'email'])->name('email');
        });

        // Support Tickets
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',                          [TicketController::class, 'index'])->name('index');
            Route::get('/{id}',                      [TicketController::class, 'show'])->name('show');
            Route::post('/{id}/reply',               [TicketController::class, 'reply'])->name('reply');
            Route::patch('/{id}/status',             [TicketController::class, 'updateStatus'])->name('update-status');
            Route::delete('/{id}',                   [TicketController::class, 'destroy'])->name('destroy');
        });

        // Video Tutorials
        Route::prefix('video-tutorials')->name('video-tutorials.')->group(function () {
            Route::get('/',                              [VideoTutorialController::class, 'index'])->name('index');
            Route::get('/create',                        [VideoTutorialController::class, 'create'])->name('create');
            Route::post('/',                             [VideoTutorialController::class, 'store'])->name('store');
            Route::get('/{videoTutorial}/edit',          [VideoTutorialController::class, 'edit'])->name('edit');
            Route::put('/{videoTutorial}',               [VideoTutorialController::class, 'update'])->name('update');
            Route::delete('/{videoTutorial}',            [VideoTutorialController::class, 'destroy'])->name('destroy');
            Route::patch('/{videoTutorial}/toggle',      [VideoTutorialController::class, 'togglePublish'])->name('toggle');
            Route::post('/reorder',                      [VideoTutorialController::class, 'reorder'])->name('reorder');
        });

        // Marketplace Admin
        Route::prefix('marketplace')->name('marketplace.')->group(function () {
            Route::get('/',                          [MarketplaceAdminController::class, 'index'])->name('index');

            // Fees
            Route::get('/fees',                      [MarketplaceAdminController::class, 'fees'])->name('fees');
            Route::post('/fees/global',              [MarketplaceAdminController::class, 'updateFees'])->name('fees.update');
            Route::post('/fees/tenant',              [MarketplaceAdminController::class, 'storeTenantFee'])->name('fees.tenant');
            Route::post('/fees/seller',              [MarketplaceAdminController::class, 'updateSellerCommission'])->name('fees.seller');
            Route::delete('/fees/{id}',              [MarketplaceAdminController::class, 'deleteFee'])->name('fees.delete');

            // Delivery Agencies
            Route::get('/agencies',                  [MarketplaceAdminController::class, 'agencies'])->name('agencies');
            Route::post('/agencies/{id}/approve',    [MarketplaceAdminController::class, 'approveAgency'])->name('agency.approve');
            Route::post('/agencies/{id}/reject',     [MarketplaceAdminController::class, 'rejectAgency'])->name('agency.reject');
            Route::post('/agencies/{id}/suspend',    [MarketplaceAdminController::class, 'suspendAgency'])->name('agency.suspend');

            // Listings
            Route::get('/listings',                  [MarketplaceAdminController::class, 'listings'])->name('listings');
            Route::post('/listings/{id}/toggle',     [MarketplaceAdminController::class, 'toggleListing'])->name('listings.toggle');
            Route::delete('/listings/{id}',          [MarketplaceAdminController::class, 'deleteListing'])->name('listings.delete');

            // Orders
            Route::get('/orders',                    [MarketplaceAdminController::class, 'orders'])->name('orders');
            Route::get('/orders/{id}',               [MarketplaceAdminController::class, 'showOrder'])->name('order.show');
            Route::post('/orders/{id}/status',       [MarketplaceAdminController::class, 'updateOrderStatus'])->name('orders.status');

            // Notifications
            Route::post('/notifications/{id}/read',  [MarketplaceAdminController::class, 'markNotificationRead'])->name('notifications.read');

            // Reports & platform fee requests
            Route::get('/reports',                   [MarketplaceReportController::class, 'index'])->name('reports');
            Route::post('/fee-requests/generate',    [MarketplaceReportController::class, 'generateFeeRequests'])->name('fee-requests.generate');
            Route::post('/fee-requests/{id}/send',   [MarketplaceReportController::class, 'sendFeeRequest'])->name('fee-requests.send');
            Route::post('/fee-requests/{id}/status', [MarketplaceReportController::class, 'updateFeeRequestStatus'])->name('fee-requests.status');
            Route::post('/payouts/generate',         [MarketplaceReportController::class, 'generatePayouts'])->name('payouts.generate');
            Route::post('/payouts/{id}',             [MarketplaceReportController::class, 'updatePayout'])->name('payouts.update');
        });

    });
});
