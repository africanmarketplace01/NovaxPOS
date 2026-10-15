<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\landlord\MarketplaceListing;
use App\Models\landlord\SourcingRequest;
use App\Models\landlord\SourcingResponse;
use App\Models\landlord\SourcingMessage;
use App\Services\Marketplace\SourcingNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Vendor-facing (tenant POS) side of the product-sourcing workflow.
 * Runs inside a tenant session; all data lives on the landlord connection.
 */
class VendorSourcingController extends Controller
{
    private function tenantId(): ?string
    {
        return tenant()?->getTenantKey() ?? (tenancy()->tenant?->id ?? null);
    }

    private function tenantName(): string
    {
        $tid = $this->tenantId();
        $listing = MarketplaceListing::where('tenant_id', $tid)->first();
        return $listing->tenant_store_name
            ?? $listing->tenant_name
            ?? (tenant()->company_name ?? 'Vendor');
    }

    /** Sidebar badge — open requests this vendor hasn't answered yet. */
    public static function openCountBadge(): int
    {
        try {
            $tid = tenant()?->getTenantKey();
            if (!$tid) return 0;
            $answered = SourcingResponse::where('tenant_id', $tid)->pluck('request_id');
            return SourcingRequest::where('status', 'open')->whereNotIn('id', $answered)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function index(Request $request)
    {
        $tid = $this->tenantId();
        $answered = SourcingResponse::where('tenant_id', $tid)->pluck('request_id')->all();

        $requests = SourcingRequest::where('status', 'open')
            ->latest()
            ->paginate(12);

        return view('backend.marketplace_requests.index', compact('requests', 'answered'));
    }

    public function show($id)
    {
        $tid = $this->tenantId();
        $req = SourcingRequest::findOrFail($id);

        $thread = SourcingResponse::where('request_id', $id)->where('tenant_id', $tid)->first();

        // Products this vendor can reference in the chat / offer
        $listings = MarketplaceListing::where('tenant_id', $tid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'promo_price', 'currency', 'image_url']);

        return view('backend.marketplace_requests.show', compact('req', 'thread', 'listings'));
    }

    /** Create (or update) this vendor's response thread and post the first message. */
    public function respond(Request $request, $id)
    {
        $tid = $this->tenantId();
        $req = SourcingRequest::findOrFail($id);

        $data = $request->validate([
            'message'    => 'required|string|max:2000',
            'price'      => 'nullable|numeric|min:0',
            'listing_id' => 'nullable|integer',
        ]);

        $listing = null;
        if (!empty($data['listing_id'])) {
            $listing = MarketplaceListing::where('tenant_id', $tid)->find($data['listing_id']);
        }

        $thread = SourcingResponse::updateOrCreate(
            ['request_id' => $req->id, 'tenant_id' => $tid],
            [
                'tenant_name' => $this->tenantName(),
                'listing_id'  => $listing?->id,
                'message'     => $data['message'],
                'price'       => $data['price'] ?? null,
                'currency'    => $listing?->currency,
            ]
        );

        // First message in the thread
        $msg = SourcingMessage::create([
            'response_id' => $thread->id,
            'request_id'  => $req->id,
            'sender_type' => 'vendor',
            'tenant_id'   => $tid,
            'sender_name' => $this->tenantName(),
            'body'        => $data['message'],
            'listing_id'  => $listing?->id,
        ]);

        // Bump response counter (only when this is a new thread)
        SourcingRequest::where('id', $req->id)->update([
            'responses_count' => SourcingResponse::where('request_id', $req->id)->count(),
        ]);

        try {
            app(SourcingNotifier::class)->notifyNewMessage($thread, $msg);
        } catch (\Throwable $e) {
            Log::error('Vendor respond notify failed: ' . $e->getMessage());
        }

        return redirect()->route('vendor.sourcing.show', $req->id)
            ->with('message', 'Your response was sent to the customer.');
    }

    public function messages($responseId)
    {
        $tid = $this->tenantId();
        $thread = SourcingResponse::where('id', $responseId)->where('tenant_id', $tid)->firstOrFail();

        SourcingMessage::where('response_id', $thread->id)
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $thread->update(['vendor_last_read_at' => now()]);

        $messages = $thread->messages()->with('listing')->get()->map(function ($m) {
            return [
                'id'      => $m->id,
                'mine'    => $m->sender_type === 'vendor',
                'sender'  => $m->sender_name,
                'body'    => $m->body,
                'time'    => optional($m->created_at)->format('M j, g:i A'),
                'listing' => $m->listing ? [
                    'name'  => $m->listing->name,
                    'price' => $m->listing->currency . ' ' . number_format($m->listing->effectivePrice(), 2),
                ] : null,
            ];
        });

        return response()->json(['messages' => $messages]);
    }

    public function send(Request $request, $responseId)
    {
        $tid = $this->tenantId();
        $thread = SourcingResponse::where('id', $responseId)->where('tenant_id', $tid)->firstOrFail();

        $data = $request->validate([
            'body'       => 'required|string|max:2000',
            'listing_id' => 'nullable|integer',
        ]);

        $listing = !empty($data['listing_id'])
            ? MarketplaceListing::where('tenant_id', $tid)->find($data['listing_id'])
            : null;

        $msg = SourcingMessage::create([
            'response_id' => $thread->id,
            'request_id'  => $thread->request_id,
            'sender_type' => 'vendor',
            'tenant_id'   => $tid,
            'sender_name' => $thread->tenant_name ?: $this->tenantName(),
            'body'        => $data['body'],
            'listing_id'  => $listing?->id,
        ]);

        try {
            app(SourcingNotifier::class)->notifyNewMessage($thread, $msg);
        } catch (\Throwable $e) {
            Log::error('Vendor send notify failed: ' . $e->getMessage());
        }

        return response()->json(['ok' => true, 'id' => $msg->id]);
    }
}
