<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\landlord\MarketplaceCustomer;
use App\Models\landlord\MarketplaceCustomerToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthApiController extends Controller
{
    private function customerPayload(MarketplaceCustomer $c): array
    {
        return [
            'id'      => $c->id,
            'name'    => $c->name,
            'email'   => $c->email,
            'phone'   => $c->phone,
            'address' => $c->address,
            'city'    => $c->city,
            'country' => $c->country,
        ];
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:120',
            'email'    => 'required|email|max:150|unique:saleprosaas_landlord.marketplace_customers,email',
            'phone'    => 'nullable|string|max:30',
            'password' => 'required|min:6',
            'device'   => 'nullable|string|max:120',
        ]);

        $c = MarketplaceCustomer::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'password'  => $data['password'],
            'is_active' => true,
        ]);

        $token = MarketplaceCustomerToken::issue($c->id, $data['device'] ?? null);

        return response()->json([
            'token'    => $token,
            'customer' => $this->customerPayload($c),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'device'   => 'nullable|string|max:120',
        ]);

        $c = MarketplaceCustomer::where('email', $data['email'])->where('is_active', true)->first();
        if (!$c || !Hash::check($data['password'], $c->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $token = MarketplaceCustomerToken::issue($c->id, $data['device'] ?? null);

        return response()->json([
            'token'    => $token,
            'customer' => $this->customerPayload($c),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['customer' => $this->customerPayload($request->user())]);
    }

    public function updateProfile(Request $request)
    {
        $c = $request->user();
        $data = $request->validate([
            'name'    => 'required|string|max:120',
            'phone'   => 'nullable|string|max:30',
            'address' => 'nullable|string|max:600',
            'city'    => 'nullable|string|max:120',
            'country' => 'nullable|string|max:120',
        ]);
        $c->update($data);
        return response()->json(['customer' => $this->customerPayload($c)]);
    }

    public function logout(Request $request)
    {
        $tokenId = $request->attributes->get('token_id');
        if ($tokenId) {
            MarketplaceCustomerToken::where('id', $tokenId)->delete();
        }
        return response()->json(['message' => 'Signed out.']);
    }

    /** Acknowledge a push device (OneSignal external-id targeting is set app-side). */
    public function pushRegister(Request $request)
    {
        return response()->json(['ok' => true]);
    }
}
