<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\DeliveryAgent;
use App\Models\landlord\DeliveryBike;
use App\Models\landlord\DeliveryZone;
use App\Models\landlord\DeliveryZonePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Vendor-owned delivery management (runs in tenant context, operates on the
 * landlord delivery_* tables scoped to owner_tenant_id = current tenant).
 */
class VendorDeliveryController extends Controller
{
    private function tenantId(): ?string
    {
        return tenant()?->getTenantKey() ?? (tenancy()->tenant?->id ?? null);
    }

    /** Fetch an agency the current vendor owns, or fail. */
    private function ownAgency($id): DeliveryAgency
    {
        return DeliveryAgency::where('owner_tenant_id', $this->tenantId())->findOrFail($id);
    }

    // ── Agencies ─────────────────────────────────────────────────────────────
    public function agencies(Request $request)
    {
        $tid = $this->tenantId();
        $agencies = DeliveryAgency::where('owner_tenant_id', $tid)->orderBy('name')->get()
            ->map(fn ($a) => $this->agencyArray($a));
        return response()->json(['data' => $agencies]);
    }

    private function agencyArray(DeliveryAgency $a): array
    {
        return [
            'id'          => $a->id,
            'name'        => $a->name,
            'phone'       => $a->phone,
            'email'       => $a->email,
            'address'     => $a->address,
            'base_rate'   => (float) $a->base_rate,
            'currency'    => $a->currency,
            'is_active'   => (bool) $a->is_active,
            'status'      => $a->status,
            'zones_count' => DeliveryZone::where('agency_id', $a->id)->count(),
        ];
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

        $agency = DeliveryAgency::create([
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
            'status'             => 'approved', // vendor-owned agencies are auto-approved
            'is_active'          => true,
        ]);
        return response()->json(['ok' => true, 'agency' => $this->agencyArray($agency)], 201);
    }

    public function updateAgency(Request $request, $id)
    {
        $agency = $this->ownAgency($id);
        $data = $request->validate([
            'name'      => 'sometimes|string|max:120',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:150',
            'address'   => 'nullable|string|max:300',
            'base_rate' => 'nullable|numeric|min:0',
            'currency'  => 'nullable|string|max:10',
            'is_active' => 'nullable|boolean',
        ]);
        $agency->update($data);
        return response()->json(['ok' => true, 'agency' => $this->agencyArray($agency)]);
    }

    public function deleteAgency(Request $request, $id)
    {
        $agency = $this->ownAgency($id);
        $agency->update(['is_active' => false]);
        return response()->json(['ok' => true]);
    }

    // ── Zones + pricing matrix ───────────────────────────────────────────────
    public function zones(Request $request, $agencyId)
    {
        $agency = $this->ownAgency($agencyId);
        $zones = DeliveryZone::where('agency_id', $agency->id)->orderBy('name')->get(['id', 'name', 'note', 'is_active']);
        $prices = DeliveryZonePrice::where('agency_id', $agency->id)->get()
            ->map(fn ($p) => [
                'from_zone_id' => $p->from_zone_id,
                'to_zone_id'   => $p->to_zone_id,
                'price'        => (float) $p->price,
            ]);
        return response()->json([
            'agency'   => $this->agencyArray($agency),
            'zones'    => $zones,
            'prices'   => $prices,
            'currency' => $agency->currency,
        ]);
    }

    public function storeZone(Request $request, $agencyId)
    {
        $agency = $this->ownAgency($agencyId);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'note' => 'nullable|string|max:255',
        ]);
        $zone = DeliveryZone::create([
            'agency_id' => $agency->id,
            'name'      => $data['name'],
            'note'      => $data['note'] ?? null,
            'is_active' => true,
        ]);
        return response()->json(['ok' => true, 'zone' => ['id' => $zone->id, 'name' => $zone->name, 'note' => $zone->note, 'is_active' => true]], 201);
    }

    public function deleteZone(Request $request, $zoneId)
    {
        $zone = DeliveryZone::findOrFail($zoneId);
        // ownership check via the agency
        $this->ownAgency($zone->agency_id);
        DeliveryZonePrice::where('agency_id', $zone->agency_id)
            ->where(fn ($q) => $q->where('from_zone_id', $zone->id)->orWhere('to_zone_id', $zone->id))->delete();
        $zone->delete();
        return response()->json(['ok' => true]);
    }

    /** Upsert a single zone-to-zone price. */
    public function setPrice(Request $request, $agencyId)
    {
        $agency = $this->ownAgency($agencyId);
        $data = $request->validate([
            'from_zone_id' => 'required|integer',
            'to_zone_id'   => 'required|integer',
            'price'        => 'required|numeric|min:0',
        ]);
        // Ensure both zones belong to this agency
        $count = DeliveryZone::where('agency_id', $agency->id)->whereIn('id', [$data['from_zone_id'], $data['to_zone_id']])->count();
        if ($count < ($data['from_zone_id'] == $data['to_zone_id'] ? 1 : 2)) {
            return response()->json(['message' => 'Zones do not belong to this agency.'], 422);
        }

        DeliveryZonePrice::updateOrCreate(
            ['agency_id' => $agency->id, 'from_zone_id' => $data['from_zone_id'], 'to_zone_id' => $data['to_zone_id']],
            ['price' => $data['price']],
        );
        return response()->json(['ok' => true]);
    }

    // ── Agents (riders) ──────────────────────────────────────────────────────
    private function ownAgent($agentId): DeliveryAgent
    {
        $agent = DeliveryAgent::findOrFail($agentId);
        $this->ownAgency($agent->agency_id); // authorize via agency ownership
        return $agent;
    }

    private function agentArray(DeliveryAgent $a): array
    {
        $bike = DeliveryBike::where('agent_id', $a->id)->first();
        return [
            'id'             => $a->id,
            'agency_id'      => $a->agency_id,
            'name'           => $a->name,
            'phone'          => $a->phone,
            'email'          => $a->email,
            'license_number' => $a->license_number,
            'photo_url'      => $a->photoUrl(),
            'is_active'      => (bool) $a->is_active,
            'bike'           => $bike ? ['id' => $bike->id, 'label' => $bike->label(), 'plate' => $bike->plate_number] : null,
        ];
    }

    public function agents(Request $request, $agencyId)
    {
        $agency = $this->ownAgency($agencyId);
        $agents = DeliveryAgent::where('agency_id', $agency->id)->orderBy('name')->get()->map(fn ($a) => $this->agentArray($a));
        return response()->json(['data' => $agents]);
    }

    private function storeAgentPhoto(Request $request): ?string
    {
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
            $f = $request->file('photo');
            $name = 'agent-' . date('YmdHis') . '-' . uniqid() . '.' . $f->getClientOriginalExtension();
            $f->move(storage_path('app/landlord-logos'), $name);
            return $name;
        }
        return null;
    }

    public function storeAgent(Request $request, $agencyId)
    {
        $agency = $this->ownAgency($agencyId);
        $data = $request->validate([
            'name'           => 'required|string|max:120',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:150',
            'license_number' => 'nullable|string|max:80',
            'national_id'    => 'nullable|string|max:80',
            'photo'          => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
        ]);
        $photo = $this->storeAgentPhoto($request);
        $agent = DeliveryAgent::create(array_merge(
            collect($data)->except('photo')->toArray(),
            ['agency_id' => $agency->id, 'is_active' => true, 'photo' => $photo]
        ));
        return response()->json(['ok' => true, 'agent' => $this->agentArray($agent)], 201);
    }

    public function updateAgent(Request $request, $agentId)
    {
        $agent = $this->ownAgent($agentId);
        $data = $request->validate([
            'name'           => 'sometimes|string|max:120',
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:150',
            'license_number' => 'nullable|string|max:80',
            'national_id'    => 'nullable|string|max:80',
            'is_active'      => 'nullable|boolean',
            'photo'          => 'nullable|image|mimes:jpeg,jpg,png,webp|max:4096',
        ]);
        $photo = $this->storeAgentPhoto($request);
        if ($photo) $data['photo'] = $photo; else unset($data['photo']);
        $agent->update($data);
        return response()->json(['ok' => true, 'agent' => $this->agentArray($agent)]);
    }

    public function deleteAgent(Request $request, $agentId)
    {
        $agent = $this->ownAgent($agentId);
        DeliveryBike::where('agent_id', $agent->id)->update(['agent_id' => null]);
        $agent->update(['is_active' => false]);
        return response()->json(['ok' => true]);
    }

    // ── Bikes ────────────────────────────────────────────────────────────────
    private function bikeArray(DeliveryBike $b): array
    {
        return [
            'id'              => $b->id,
            'agency_id'       => $b->agency_id,
            'agent_id'        => $b->agent_id,
            'make'            => $b->make,
            'model'           => $b->model,
            'plate_number'    => $b->plate_number,
            'color'           => $b->color,
            'year'            => $b->year,
            'registration_no' => $b->registration_no,
            'insurance_no'    => $b->insurance_no,
            'note'            => $b->note,
            'is_active'       => (bool) $b->is_active,
            'agent_name'      => optional(DeliveryAgent::find($b->agent_id))->name,
        ];
    }

    public function bikes(Request $request, $agencyId)
    {
        $agency = $this->ownAgency($agencyId);
        return response()->json([
            'bikes'  => DeliveryBike::where('agency_id', $agency->id)->orderByDesc('id')->get()->map(fn ($b) => $this->bikeArray($b)),
            'agents' => DeliveryAgent::where('agency_id', $agency->id)->where('is_active', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeBike(Request $request, $agencyId)
    {
        $agency = $this->ownAgency($agencyId);
        $data = $request->validate([
            'plate_number'    => 'required|string|max:40',
            'make'            => 'nullable|string|max:80',
            'model'           => 'nullable|string|max:80',
            'color'           => 'nullable|string|max:40',
            'year'            => 'nullable|string|max:8',
            'registration_no' => 'nullable|string|max:80',
            'insurance_no'    => 'nullable|string|max:80',
            'note'            => 'nullable|string|max:500',
            'agent_id'        => 'nullable|integer',
        ]);
        if (!empty($data['agent_id']) && !DeliveryAgent::where('agency_id', $agency->id)->where('id', $data['agent_id'])->exists()) {
            return response()->json(['message' => 'Agent does not belong to this agency.'], 422);
        }
        $bike = DeliveryBike::create(array_merge($data, ['agency_id' => $agency->id, 'is_active' => true]));
        return response()->json(['ok' => true, 'bike' => $this->bikeArray($bike)], 201);
    }

    public function updateBike(Request $request, $bikeId)
    {
        $bike = DeliveryBike::findOrFail($bikeId);
        $agency = $this->ownAgency($bike->agency_id);
        $data = $request->validate([
            'plate_number'    => 'sometimes|string|max:40',
            'make'            => 'nullable|string|max:80',
            'model'           => 'nullable|string|max:80',
            'color'           => 'nullable|string|max:40',
            'year'            => 'nullable|string|max:8',
            'registration_no' => 'nullable|string|max:80',
            'insurance_no'    => 'nullable|string|max:80',
            'note'            => 'nullable|string|max:500',
            'agent_id'        => 'nullable|integer',
            'is_active'       => 'nullable|boolean',
        ]);
        if (!empty($data['agent_id']) && !DeliveryAgent::where('agency_id', $agency->id)->where('id', $data['agent_id'])->exists()) {
            return response()->json(['message' => 'Agent does not belong to this agency.'], 422);
        }
        $bike->update($data);
        return response()->json(['ok' => true, 'bike' => $this->bikeArray($bike)]);
    }

    public function deleteBike(Request $request, $bikeId)
    {
        $bike = DeliveryBike::findOrFail($bikeId);
        $this->ownAgency($bike->agency_id);
        $bike->delete();
        return response()->json(['ok' => true]);
    }
}
