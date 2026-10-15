<?php

namespace App\Http\Controllers\landlord\Customer;

use App\Http\Controllers\Controller;
use App\Models\landlord\MarketplaceOrder;
use App\Models\landlord\SourcingRequest;
use App\Models\landlord\SourcingMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    /** All orders belonging to this customer (by id or matching email). */
    private function ordersQuery()
    {
        $c = $this->customer();
        return MarketplaceOrder::where(function ($q) use ($c) {
            $q->where('customer_id', $c->id)
              ->orWhere('customer_email', $c->email);
        });
    }

    public function index()
    {
        $c = $this->customer();

        $orders = (clone $this->ordersQuery())->latest()->limit(5)->get();
        $requests = SourcingRequest::where('customer_id', $c->id)->latest()->limit(5)->get();

        $stats = [
            'orders'        => (clone $this->ordersQuery())->count(),
            'open_requests' => SourcingRequest::where('customer_id', $c->id)->where('status', 'open')->count(),
            'responses'     => \App\Models\landlord\SourcingResponse::whereIn(
                                    'request_id',
                                    SourcingRequest::where('customer_id', $c->id)->pluck('id')
                               )->count(),
        ];

        return view('landlord.customer.dashboard', compact('c', 'orders', 'requests', 'stats'));
    }

    public function orders()
    {
        $c = $this->customer();
        $orders = $this->ordersQuery()->latest()->paginate(15);
        return view('landlord.customer.orders', compact('c', 'orders'));
    }

    public function orderShow($id)
    {
        $c = $this->customer();
        $order = $this->ordersQuery()->where('id', $id)->firstOrFail();
        $order->load('events');

        // Which listings in this order has the customer already reviewed?
        $listingIds = collect($order->items ?? [])->pluck('listing_id')->filter()->unique()->values();
        $reviewed = [];
        try {
            $reviewed = \App\Models\landlord\MarketplaceReview::whereIn('listing_id', $listingIds)
                ->where('reviewer_email', $c->email)
                ->pluck('listing_id')->all();
        } catch (\Throwable $e) {
            // reviews table missing — ignore
        }

        return view('landlord.customer.order-show', compact('c', 'order', 'reviewed'));
    }

    public function submitReview(Request $request, $id)
    {
        $c = $this->customer();
        $order = $this->ordersQuery()->where('id', $id)->firstOrFail();

        $data = $request->validate([
            'listing_id' => 'required|integer',
            'rating'     => 'required|integer|min:1|max:5',
            'title'      => 'nullable|string|max:120',
            'comment'    => 'required|string|max:2000',
        ]);

        // The listing must be part of this order
        $item = collect($order->items ?? [])->firstWhere('listing_id', (int) $data['listing_id']);
        if (!$item) {
            return back()->with('error', 'That product is not part of this order.');
        }
        if ($order->status !== 'delivered') {
            return back()->with('error', 'You can review products once the order is delivered.');
        }

        // One review per customer per product
        $already = \App\Models\landlord\MarketplaceReview::where('listing_id', $data['listing_id'])
            ->where('reviewer_email', $c->email)->exists();
        if ($already) {
            return back()->with('error', 'You have already reviewed this product.');
        }

        \App\Models\landlord\MarketplaceReview::create([
            'listing_id'     => $data['listing_id'],
            'product_id'     => $item['product_id'] ?? null,
            'tenant_id'      => $item['tenant_id'] ?? null,
            'reviewer_name'  => $c->name,
            'reviewer_email' => $c->email,
            'rating'         => $data['rating'],
            'title'          => $data['title'] ?? null,
            'comment'        => $data['comment'],
            'is_verified'    => true,   // verified purchase
            'is_approved'    => true,
        ]);

        return back()->with('success', 'Thanks for your review!');
    }

    public function profile()
    {
        $c = $this->customer();
        return view('landlord.customer.profile', compact('c'));
    }

    public function updateProfile(Request $request)
    {
        $c = $this->customer();

        $data = $request->validate([
            'name'    => 'required|string|max:120',
            'phone'   => 'nullable|string|max:30',
            'address' => 'nullable|string|max:600',
            'city'    => 'nullable|string|max:120',
            'country' => 'nullable|string|max:120',
            'new_password' => 'nullable|min:6|confirmed',
        ]);

        $c->name    = $data['name'];
        $c->phone   = $data['phone'] ?? null;
        $c->address = $data['address'] ?? null;
        $c->city    = $data['city'] ?? null;
        $c->country = $data['country'] ?? null;
        if (!empty($data['new_password'])) {
            // The model's `hashed` cast hashes on save — assign the plain value.
            $c->password = $data['new_password'];
        }
        $c->save();

        return back()->with('success', 'Profile updated.');
    }
}
