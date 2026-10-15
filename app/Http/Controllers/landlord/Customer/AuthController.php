<?php

namespace App\Http\Controllers\landlord\Customer;

use App\Http\Controllers\Controller;
use App\Models\landlord\MarketplaceCustomer;
use App\Models\landlord\MarketplaceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }
        return view('landlord.customer.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('customer')->attempt(
            ['email' => $data['email'], 'password' => $data['password'], 'is_active' => 1],
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();
            return redirect()->intended(route('customer.dashboard'))
                ->with('success', 'Welcome back!');
        }

        return back()->withInput($request->only('email'))
            ->with('error', 'Those credentials do not match our records.');
    }

    public function showRegister()
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.dashboard');
        }
        return view('landlord.customer.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|max:150|unique:saleprosaas_landlord.marketplace_customers,email',
            'phone'    => 'nullable|string|max:30',
            'password' => 'required|min:6|confirmed',
        ]);

        $customer = MarketplaceCustomer::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => $data['password'], // hashed by cast
            'is_active' => true,
        ]);

        // Link any prior guest orders placed with this email
        MarketplaceOrder::where('customer_email', $customer->email)
            ->whereNull('customer_id')
            ->update(['customer_id' => $customer->id]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->intended(route('customer.dashboard'))
            ->with('success', 'Your account is ready — welcome to the marketplace!');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('marketplace.index')->with('success', 'You have been signed out.');
    }
}
