<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\DeliveryZone;
use App\Models\landlord\DeliveryZonePrice;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\MarketplaceOrder;
use App\Models\landlord\SaasSettings;
use App\Services\Marketplace\OrderFanout;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    /**
     * Delivery agencies available at checkout, each with its zones and the
     * zone-to-zone price matrix. Optionally scoped to the cart's vendor(s) via
     * ?tenant_id= so a vendor's in-house agencies show alongside global ones.
     */
    public function deliveryOptions(Request $request)
    {
        $query = DeliveryAgency::where('status', 'approved')->where('is_active', true);
        if ($request->filled('tenant_id')) {
            $tid = $request->tenant_id;
            $query->where(fn ($q) => $q->whereNull('owner_tenant_id')->orWhere('owner_tenant_id', $tid));
        } else {
            // Only global (registered) agencies when no vendor context is given.
            $query->whereNull('owner_tenant_id');
        }

        $agencies = $query->orderBy('name')->get()->map(function ($a) {
            $zones  = DeliveryZone::where('agency_id', $a->id)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $prices = DeliveryZonePrice::where('agency_id', $a->id)->get()
                ->map(fn ($p) => ['from_zone_id' => $p->from_zone_id, 'to_zone_id' => $p->to_zone_id, 'price' => (float) $p->price]);
            return [
                'id'         => $a->id,
                'name'       => $a->name,
                'base_rate'  => (float) $a->base_rate,
                'currency'   => $a->currency,
                'estimate'   => $a->deliveryEstimate(),
                'vendor_owned' => ! empty($a->owner_tenant_id),
                'zones'      => $zones,
                'prices'     => $prices,
            ];
        });
        return response()->json(['data' => $agencies]);
    }

    /** Assigned delivery agent (name, phone, photo, bike) for the customer view. */
    private function agentInfo($agentId): ?array
    {
        if (!$agentId) return null;
        $a = \App\Models\landlord\DeliveryAgent::find($agentId);
        if (!$a) return null;
        $bike = \App\Models\landlord\DeliveryBike::where('agent_id', $a->id)->first();
        return [
            'name'      => $a->name,
            'phone'     => $a->phone,
            'photo_url' => $a->photoUrl(),
            'bike'      => $bike ? trim($bike->label() . ' · ' . $bike->plate_number, ' ·') : null,
        ];
    }

    private function orderPayload(MarketplaceOrder $o, bool $withTracking = false): array
    {
        $data = [
            'id'             => $o->id,
            'order_number'   => $o->order_number,
            'status'         => $o->status,
            'payment_status' => $o->payment_status,
            'payment_method' => $o->payment_method,
            'currency'       => $o->currency,
            'subtotal'       => (float) $o->subtotal,
            'platform_fee'   => (float) $o->platform_fee,
            'delivery_fee'   => (float) $o->delivery_fee,
            'total'          => (float) $o->total,
            'items'          => $o->items,
            'address'        => $o->customer_address,
            'delivery_agency'=> $o->delivery_agency_name,
            'delivery_agent' => $this->agentInfo($o->delivery_agent_id),
            'tracking_number'=> $o->tracking_number,
            'created_at'     => optional($o->created_at)->toIso8601String(),
        ];
        if ($withTracking) {
            $data['tracking'] = $o->trackingSteps();
        }
        return $data;
    }

    public function index(Request $request)
    {
        $c = $request->user();
        $page = MarketplaceOrder::where(fn ($q) => $q->where('customer_id', $c->id)->orWhere('customer_email', $c->email))
            ->latest()->paginate(20);

        return response()->json([
            'data' => collect($page->items())->map(fn ($o) => $this->orderPayload($o)),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, $id)
    {
        $c = $request->user();
        $o = MarketplaceOrder::where('id', $id)
            ->where(fn ($q) => $q->where('customer_id', $c->id)->orWhere('customer_email', $c->email))
            ->firstOrFail();
        $o->load('events');
        return response()->json(['order' => $this->orderPayload($o, true)]);
    }

    /** Guest tracking by order number + email/phone. */
    public function track(Request $request)
    {
        $request->validate(['order_number' => 'required|string', 'contact' => 'required|string']);
        $o = MarketplaceOrder::where('order_number', $request->order_number)
            ->where(fn ($q) => $q->where('customer_email', $request->contact)->orWhere('customer_phone', $request->contact))
            ->first();
        if (!$o) {
            return response()->json(['message' => 'Order not found. Check the number and email/phone.'], 404);
        }
        $o->load('events');
        return response()->json(['order' => $this->orderPayload($o, true)]);
    }

    public function place(Request $request)
    {
        $c = $request->user();

        $data = $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.listing_id' => 'required|integer',
            'items.*.qty'        => 'required|integer|min:1|max:999',
            'customer_name'      => 'required|string|max:120',
            'customer_phone'     => 'required|string|max:30',
            'customer_address'   => 'required|string|max:600',
            'payment_method'     => 'required|in:stripe,paystack,bank_transfer,mtn,orange,cod',
            'delivery_agency_id' => 'nullable|integer',
            'from_zone_id'       => 'nullable|integer',
            'to_zone_id'         => 'nullable|integer',
            'note'               => 'nullable|string|max:600',
        ]);

        // Build items from live listings (never trust client prices)
        $items = []; $subtotal = 0;
        foreach ($data['items'] as $row) {
            $l = MarketplaceListing::visible()->find($row['listing_id']);
            if (!$l) continue;
            $price = $l->effectivePrice();
            $line  = $price * $row['qty'];
            $subtotal += $line;
            $items[] = [
                'listing_id' => $l->id, 'tenant_id' => $l->tenant_id,
                'tenant_name' => $l->tenant_store_name ?: $l->tenant_name,
                'product_id' => $l->product_id, 'name' => $l->name,
                'price' => $price, 'image_url' => $l->image_url,
                'qty' => $row['qty'], 'subtotal' => $line,
            ];
        }
        if (empty($items)) {
            return response()->json(['message' => 'None of the items are available.'], 422);
        }

        // Customer pays the fee only on subscribed merchants' items; marketplace
        // sellers owe a commission instead (same rules as the web checkout).
        $feeSplit    = \App\Services\Marketplace\FeeSplit::forItems($items);
        $platformFee = $feeSplit['customer_fee'];

        $deliveryFee = 0; $agencyName = null; $agency = null; $deliveryEst = null;
        if (!empty($data['delivery_agency_id'])) {
            $agency = DeliveryAgency::where('is_active', true)->find($data['delivery_agency_id']);
            if ($agency) {
                $agencyName  = $agency->name;
                $deliveryEst = $agency->deliveryEstimate();
                $deliveryFee = $agency->base_rate;
                // Zone-to-zone pricing overrides the base rate when both zones are given.
                if (!empty($data['from_zone_id']) && !empty($data['to_zone_id'])) {
                    $zp = DeliveryZonePrice::where('agency_id', $agency->id)
                        ->where('from_zone_id', $data['from_zone_id'])
                        ->where('to_zone_id', $data['to_zone_id'])->first();
                    if ($zp) {
                        $deliveryFee = $zp->price;
                        if ($zp->est_days_min && $zp->est_days_max) {
                            $deliveryEst = $zp->est_days_min === $zp->est_days_max
                                ? $zp->est_days_min . ' business day' . ($zp->est_days_min > 1 ? 's' : '')
                                : $zp->est_days_min . '–' . $zp->est_days_max . ' business days';
                        }
                    }
                }
            }
        }

        $total = $subtotal + $platformFee + $deliveryFee;

        $order = MarketplaceOrder::create([
            'order_number'         => MarketplaceOrder::generateOrderNumber(),
            'customer_id'          => $c->id,
            'customer_name'        => $data['customer_name'],
            'customer_email'       => $c->email,
            'customer_phone'       => $data['customer_phone'],
            'customer_address'     => $data['customer_address'],
            'items'                => $items,
            'currency'             => SaasSettings::get('currency') ?? 'USD',
            'subtotal'             => $subtotal,
            'platform_fee'         => $platformFee,
            'delivery_fee'         => $deliveryFee,
            'total'                => $total,
            'delivery_agency_id'   => $agency?->id,
            'delivery_agency_name' => $agencyName,
            'estimated_delivery'   => $deliveryEst,
            'payment_method'       => $data['payment_method'],
            'payment_status'       => 'pending',
            'note'                 => $data['note'] ?? null,
            'status'               => 'pending',
        ] + \App\Services\Marketplace\FeeSplit::orderAttributes($feeSplit));

        $order->recordEvent('pending', 'Order placed', 'Order received via the app.', 'customer');
        OrderFanout::run($order, $items, $agency);
        \App\Jobs\SendMarketplaceOrderNotifications::dispatch($order->id);

        return response()->json(['order' => $this->orderPayload($order, true)], 201);
    }
}
