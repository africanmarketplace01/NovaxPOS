<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('superadmin')->check()) {
            return redirect()->route('superadmin.dashboard');
        }
        return view('superadmin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('superadmin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('superadmin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('superadmin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('superadmin.login');
    }

    /**
     * Update the authenticated superadmin's name, email, and/or password.
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\landlord\SuperadminUser $user */
        $user = Auth::guard('superadmin')->user();

        $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'email', 'max:150',
                Rule::unique('saleprosaas_landlord.superadmin_users', 'email')->ignore($user->id),
            ],
            // current_password is required only when the user wants to set a new password
            'current_password' => [
                'nullable',
                'required_with:new_password',
                'string',
            ],
            'new_password' => [
                'nullable',
                'string',
                Password::min(8)->letters()->numbers(),
                'confirmed',
            ],
        ], [
            'current_password.required_with' => 'Please enter your current password to set a new one.',
            'new_password.confirmed'         => 'The new password confirmation does not match.',
        ]);

        // If a new password is requested, verify the current one first
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()
                    ->withInput()
                    ->withErrors(['current_password' => 'Your current password is incorrect.'])
                    ->with('profile_tab', true);
            }
        }

        $user->name  = $request->name;
        $user->email = $request->email;

        if ($request->filled('new_password')) {
            $user->password = Hash::make($request->new_password);
        }

        $user->save();

        return redirect()
            ->route('superadmin.settings.index', ['tab' => 'profile'])
            ->with('message', 'Profile updated successfully.');
    }
}
