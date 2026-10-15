<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\DeliveryAgencyToken;
use App\Models\landlord\DeliveryAgent;
use App\Models\landlord\DeliveryBike;
use App\Models\landlord\MarketplaceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Delivery-agency portal for the marketplace app: agencies log in, manage their
 * riders (agents) and bikes, and handle orders assigned to them.
 */
class DeliveryAgencyApiController extends Controller
{
    // ── Auth ─────────────────────────────────────────────────────────────────
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
            'device'   => 'nullable|string|max:191',
        ]);
        $agency = DeliveryAgency::where('email', $data['email'])->first();
        if (!$agency || !$agency->password || !Hash::check($data['password'], $agency->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 422);
        }
        if ($agency->status !== 'approved') {
            return response()->json(['message' => 'Your agency is pending approval by the marketplace admin.'], 403);
        }
        if (!$agency->is_active) {
            return response()->json(['message' => 'Your agency account is inactive.'], 403);
        }
        $token = DeliveryAgencyToken::issue($agency->id, $data['device'] ?? null);
        return response()->json(['token' => $token, 'agency' => $this->agencyProfile($agency)]);
    }

    public function me(Request $request)
    {
        return response()->json(['agency' => $this->agencyProfile($request->user())]);
    }

    public function logout(Request $request)
    {
        $tokenId = $request->attributes->get('token_id');
        if ($tokenId) DeliveryAgencyToken::where('id', $tokenId)->delete();
        return response()->json(['ok' => true]);
    }

    private function agencyProfile(DeliveryAgency $a): array
    {
        return [
            'id'            => $a->id,
            'name'          => $a->name,
            'email'         => $a->email,
            'phone'         => $a->phone,
            'currency'      => $a->currency,
            'agents_count'  => DeliveryAgent::where('agency_id', $a->id)->where('is_active', 1)->count(),
            'bikes_count'   => DeliveryBike::where('agency_id', $a->id)->count(),
            'orders_active' => MarketplaceOrder::where('delivery_agency_id', $a->id)->whereNotIn('status', ['delivered', 'completed', 'cancelled'])->count(),
        ];
    }

    // ── Orders assigned to this agency ───────────────────────────────────────
    public function orders(Request $request)
    {
        $agency = $request->user();
        $q = MarketplaceOrder::where('delivery_agency_id', $agency->id);
        if ($request->filled('status')) $q->where('status', $request->status);
        $page = $q->latest()->paginate(20);
        return response()->json([
            'data' => collect($page->items())->map(fn ($o) => [
                'id'             => $o->id,
                'order_number'   => $o->order_number,
                'status'         => $o->status,
                'payment_status' => $o->payment_status,
                'currency'       => $o->currency,
                'total'          => (float) $o->total,
                'delivery_fee'   => (float) $o->delivery_fee,
                'customer'       => $o->customer_name,
                'phone'          => $o->customer_phone,
                'address'        => $o->customer_address,
                'agent'          => $this->agentInfo($o->delivery_agent_id),
                'created_at'     => optional($o->created_at)->format('M j, Y g:i A'),
            ]),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function assignAgent(Request $request, $orderId)
    {
        $agency = $request->user();
        $data = $request->validate(['agent_id' => 'nullable|integer']);
        $order = MarketplaceOrder::where('id', $orderId)->where('delivery_agency_id', $agency->id)->firstOrFail();
        if (empty($data['agent_id'])) {
            $order->update(['delivery_agent_id' => null]);
            return response()->json(['ok' => true, 'agent' => null]);
        }
        if (!DeliveryAgent::where('agency_id', $agency->id)->where('id', $data['agent_id'])->exists()) {
            return response()->json(['message' => 'That agent is not part of your agency.'], 422);
        }
        $order->update(['delivery_agent_id' => $data['agent_id']]);
        $this->syncStoreOrderAgent($order, $data['agent_id']);
        return response()->json(['ok' => true, 'agent' => $this->agentInfo($data['agent_id'])]);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        $agency = $request->user();
        $data = $request->validate(['status' => 'required|in:processing,shipped,delivered']);
        $order = MarketplaceOrder::where('id', $orderId)->where('delivery_agency_id', $agency->id)->firstOrFail();
        $order->update(['status' => $data['status']]);
        try { $order->recordEvent($data['status'], ucfirst($data['status']), 'Updated by delivery agency.', 'agency'); } catch (\Throwable $e) {}
        return response()->json(['ok' => true, 'status' => $order->status]);
    }

    /** Mirror agent assignment back to the vendor's tenant store_order if linked. */
    private function syncStoreOrderAgent(MarketplaceOrder $order, ?int $agentId): void
    {
        try {
            $tenantId = collect($order->items ?? [])->pluck('tenant_id')->filter()->first();
            if (!$tenantId) return;
            $tenant = \App\Models\landlord\Tenant::find($tenantId);
            $tenant?->run(function () use ($order, $agentId) {
                DB::table('store_orders')->where('marketplace_order_number', $order->order_number)
                    ->update(['delivery_agent_id' => $agentId]);
            });
        } catch (\Throwable $e) {}
    }

    private function agentInfo($agentId): ?array
    {
        if (!$agentId) return null;
        $a = DeliveryAgent::find($agentId);
        if (!$a) return null;
        $bike = DeliveryBike::where('agent_id', $a->id)->first();
        return ['id' => $a->id, 'name' => $a->name, 'phone' => $a->phone, 'photo_url' => $a->photoUrl(),
                'bike' => $bike ? trim($bike->label() . ' · ' . $bike->plate_number, ' ·') : null];
    }

    // ── Agents ───────────────────────────────────────────────────────────────
    private function agentArray(DeliveryAgent $a): array
    {
        $bike = DeliveryBike::where('agent_id', $a->id)->first();
        return ['id' => $a->id, 'name' => $a->name, 'phone' => $a->phone, 'email' => $a->email,
                'license_number' => $a->license_number, 'photo_url' => $a->photoUrl(), 'is_active' => (bool) $a->is_active,
                'bike' => $bike ? ['label' => $bike->label(), 'plate' => $bike->plate_number] : null];
    }

    public function agents(Request $request)
    {
        $agency = $request->user();
        return response()->json(['data' => DeliveryAgent::where('agency_id', $agency->id)->orderBy('name')->get()->map(fn ($a) => $this->agentArray($a))]);
    }

    public function storeAgent(Request $request)
    {
        $agency = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120', 'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150', 'license_number' => 'nullable|string|max:80',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
        ]);
        $photo = null;
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $f = $request->file('photo');
            $photo = 'agent-' . date('YmdHis') . '-' . uniqid() . '.' . $f->getClientOriginalExtension();
            $f->move(storage_path('app/landlord-logos'), $photo);
        }
        $agent = DeliveryAgent::create(array_merge(collect($data)->except('photo')->toArray(),
            ['agency_id' => $agency->id, 'is_active' => true, 'photo' => $photo]));
        return response()->json(['ok' => true, 'agent' => $this->agentArray($agent)], 201);
    }

    public function deleteAgent(Request $request, $id)
    {
        $agency = $request->user();
        $agent = DeliveryAgent::where('agency_id', $agency->id)->findOrFail($id);
        DeliveryBike::where('agent_id', $agent->id)->update(['agent_id' => null]);
        $agent->update(['is_active' => false]);
        return response()->json(['ok' => true]);
    }

    // ── Bikes ─────────────────────────────────────────────────────────────────
    private function bikeArray(DeliveryBike $b): array
    {
        return ['id' => $b->id, 'make' => $b->make, 'model' => $b->model, 'plate_number' => $b->plate_number,
                'color' => $b->color, 'year' => $b->year, 'agent_id' => $b->agent_id,
                'agent_name' => optional(DeliveryAgent::find($b->agent_id))->name];
    }

    public function bikes(Request $request)
    {
        $agency = $request->user();
        return response()->json([
            'bikes'  => DeliveryBike::where('agency_id', $agency->id)->orderByDesc('id')->get()->map(fn ($b) => $this->bikeArray($b)),
            'agents' => DeliveryAgent::where('agency_id', $agency->id)->where('is_active', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeBike(Request $request)
    {
        $agency = $request->user();
        $data = $request->validate([
            'plate_number' => 'required|string|max:40', 'make' => 'nullable|string|max:80', 'model' => 'nullable|string|max:80',
            'color' => 'nullable|string|max:40', 'year' => 'nullable|string|max:8',
            'registration_no' => 'nullable|string|max:80', 'insurance_no' => 'nullable|string|max:80',
            'note' => 'nullable|string|max:500', 'agent_id' => 'nullable|integer',
        ]);
        if (!empty($data['agent_id']) && !DeliveryAgent::where('agency_id', $agency->id)->where('id', $data['agent_id'])->exists()) {
            return response()->json(['message' => 'Agent is not part of your agency.'], 422);
        }
        $bike = DeliveryBike::create(array_merge($data, ['agency_id' => $agency->id, 'is_active' => true]));
        return response()->json(['ok' => true, 'bike' => $this->bikeArray($bike)], 201);
    }

    public function deleteBike(Request $request, $id)
    {
        $agency = $request->user();
        DeliveryBike::where('agency_id', $agency->id)->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
