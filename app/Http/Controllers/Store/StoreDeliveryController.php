<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\DeliveryZone;
use App\Models\landlord\DeliveryZonePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Vendor-owned delivery management on the web (tenant backend).
 * Operates on the landlord delivery_* tables scoped to owner_tenant_id.
 * Mirrors Api\Vendor\VendorDeliveryController but renders Blade views.
 */
class StoreDeliveryController extends Controller
{
    private function tenantId(): ?string
    {
        return tenant()?->getTenantKey() ?? (tenancy()->tenant?->id ?? null);
    }

    private function ownAgency($id): DeliveryAgency
    {
        return DeliveryAgency::where('owner_tenant_id', $this->tenantId())->findOrFail($id);
    }

    public function index()
    {
        $tid = $this->tenantId();
        $agencies = DeliveryAgency::where('owner_tenant_id', $tid)->orderBy('name')->get();
        $zonesByAgency  = DeliveryZone::whereIn('agency_id', $agencies->pluck('id'))->orderBy('name')->get()->groupBy('agency_id');
        $pricesByAgency = DeliveryZonePrice::whereIn('agency_id', $agencies->pluck('id'))->get()->groupBy('agency_id');

        return view('backend.store.delivery', compact('agencies', 'zonesByAgency', 'pricesByAgency'));
    }

    public function storeAgency(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:120',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:150',
            'address'   => 'nullable|string|max:300',
            'base_rate' => 'nullable|numeric|min:0',
            'currency'  => 'nullable|string|max:10',
        ]);

        $email = $data['email'] ?? (Str::slug($data['name']) . '-' . $this->tenantId() . '@vendor.local');
        if (DeliveryAgency::where('email', $email)->exists()) {
            $email = Str::slug($data['name']) . '-' . uniqid() . '@vendor.local';
        }

        DeliveryAgency::create([
            'owner_tenant_id'    => $this->tenantId(),
            'name'               => $data['name'],
            'slug'               => DeliveryAgency::generateSlug($data['name']),
            'phone'              => $data['phone'] ?? null,
            'email'              => $email,
            'address'            => $data['address'] ?? null,
            'base_rate'          => $data['base_rate'] ?? 0,
            'currency'           => $data['currency'] ?? 'USD',
            'estimated_days_min' => 1,
            'estimated_days_max' => 3,
            'status'             => 'approved',
            'is_active'          => true,
        ]);
        return back()->with('message', 'Delivery agency added.');
    }

    public function deleteAgency($id)
    {
        $this->ownAgency($id)->update(['is_active' => false]);
        return back()->with('message', 'Agency deactivated.');
    }

    public function storeZone(Request $request, $agencyId)
    {
        $agency = $this->ownAgency($agencyId);
        $data = $request->validate(['name' => 'required|string|max:120', 'note' => 'nullable|string|max:255']);
        DeliveryZone::create(['agency_id' => $agency->id, 'name' => $data['name'], 'note' => $data['note'] ?? null, 'is_active' => true]);
        return back()->with('message', 'Zone added.');
    }

    public function deleteZone($zoneId)
    {
        $zone = DeliveryZone::findOrFail($zoneId);
        $this->ownAgency($zone->agency_id);
        DeliveryZonePrice::where('agency_id', $zone->agency_id)
            ->where(fn ($q) => $q->where('from_zone_id', $zone->id)->orWhere('to_zone_id', $zone->id))->delete();
        $zone->delete();
        return back()->with('message', 'Zone removed.');
    }

    public function setPrice(Request $request, $agencyId)
    {
        $agency = $this->ownAgency($agencyId);
        $data = $request->validate([
            'from_zone_id' => 'required|integer',
            'to_zone_id'   => 'required|integer',
            'price'        => 'required|numeric|min:0',
        ]);
        $count = DeliveryZone::where('agency_id', $agency->id)->whereIn('id', [$data['from_zone_id'], $data['to_zone_id']])->count();
        if ($count < ($data['from_zone_id'] == $data['to_zone_id'] ? 1 : 2)) {
            return back()->with('not_permitted', 'Zones do not belong to this agency.');
        }
        DeliveryZonePrice::updateOrCreate(
            ['agency_id' => $agency->id, 'from_zone_id' => $data['from_zone_id'], 'to_zone_id' => $data['to_zone_id']],
            ['price' => $data['price']],
        );
        return back()->with('message', 'Price saved.');
    }
}
