<?php

namespace App\Http\Controllers\Api\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\landlord\SaasSettings;
use App\Models\landlord\SourcingRequest;
use App\Models\landlord\SourcingResponse;
use App\Models\landlord\SourcingMessage;
use App\Services\Marketplace\SourcingNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SourcingApiController extends Controller
{
    public function index(Request $request)
    {
        $c = $request->user();
        $reqs = SourcingRequest::withCount('responses')
            ->where('customer_id', $c->id)->latest()->paginate(20);

        return response()->json([
            'data' => collect($reqs->items())->map(fn ($r) => [
                'id' => $r->id, 'title' => $r->title, 'status' => $r->status,
                'category' => $r->category, 'responses' => $r->responses_count,
                'image_url' => $r->imageUrl(), 'has_video' => (bool) $r->video_path,
                'created_at' => optional($r->created_at)->toIso8601String(),
            ]),
            'meta' => ['current_page' => $reqs->currentPage(), 'last_page' => $reqs->lastPage(), 'total' => $reqs->total()],
        ]);
    }

    public function store(Request $request)
    {
        $c = $request->user();
        $data = $request->validate([
            'title'       => 'required|string|max:150',
            'description' => 'required|string|max:3000',
            'category'    => 'nullable|string|max:100',
            'brand'       => 'nullable|string|max:100',
            'quantity'    => 'nullable|integer|min:1|max:100000',
            'budget_min'  => 'nullable|numeric|min:0',
            'budget_max'  => 'nullable|numeric|min:0',
            'location'    => 'nullable|string|max:150',
            'image'       => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
            'video'       => 'nullable|mimetypes:video/mp4,video/webm,video/quicktime|max:81920',
        ]);

        $imagePath = $request->hasFile('image') ? $request->file('image')->store('sourcing/images', 'public') : null;
        $videoPath = $request->hasFile('video') ? $request->file('video')->store('sourcing/videos', 'public') : null;

        $req = SourcingRequest::create([
            'customer_id' => $c->id,
            'title'       => $data['title'],
            'description' => $data['description'],
            'category'    => $data['category'] ?? null,
            'brand'       => $data['brand'] ?? null,
            'quantity'    => $data['quantity'] ?? 1,
            'budget_min'  => $data['budget_min'] ?? null,
            'budget_max'  => $data['budget_max'] ?? null,
            'currency'    => SaasSettings::get('currency_symbol') ?? SaasSettings::get('currency') ?? null,
            'location'    => $data['location'] ?? null,
            'image_path'  => $imagePath,
            'video_path'  => $videoPath,
            'status'      => 'open',
        ]);

        try { app(SourcingNotifier::class)->notifyNewRequest($req); }
        catch (\Throwable $e) { Log::error('API SourcingNotifier: ' . $e->getMessage()); }

        return response()->json(['id' => $req->id, 'message' => 'Request sent to all vendors.'], 201);
    }

    public function show(Request $request, $id)
    {
        $c = $request->user();
        $req = SourcingRequest::where('customer_id', $c->id)->findOrFail($id);
        $threads = $req->responses()->with('listing')->latest()->get();

        return response()->json([
            'request' => [
                'id' => $req->id, 'title' => $req->title, 'description' => $req->description,
                'status' => $req->status, 'image_url' => $req->imageUrl(), 'video_url' => $req->videoUrl(),
                'budget' => $req->budgetLabel(), 'quantity' => $req->quantity,
            ],
            'threads' => $threads->map(fn ($t) => [
                'id' => $t->id, 'vendor' => $t->tenant_name, 'message' => $t->message,
                'price' => $t->price, 'currency' => $t->currency,
                'listing' => $t->listing ? [
                    'id' => $t->listing->id, 'product_id' => $t->listing->product_id, 'name' => $t->listing->name,
                    'price' => ($t->listing->currency ? $t->listing->currency . ' ' : '') . number_format($t->listing->effectivePrice(), 2),
                    'image_url' => $t->listing->image_url, 'slug' => $t->listing->slug,
                ] : null,
            ]),
        ]);
    }

    public function messages(Request $request, $responseId)
    {
        $c = $request->user();
        $thread = $this->ownedThread($c->id, $responseId);

        SourcingMessage::where('response_id', $thread->id)->where('sender_type', 'vendor')
            ->whereNull('read_at')->update(['read_at' => now()]);

        $messages = $thread->messages()->with('listing')->get()->map(fn ($m) => [
            'id' => $m->id, 'mine' => $m->sender_type === 'customer',
            'sender' => $m->sender_name, 'body' => $m->body,
            'time' => optional($m->created_at)->toIso8601String(),
            'listing' => $m->listing ? [
                'id' => $m->listing->id, 'product_id' => $m->listing->product_id, 'name' => $m->listing->name,
                'price' => ($m->listing->currency ? $m->listing->currency . ' ' : '') . number_format($m->listing->effectivePrice(), 2),
                'image_url' => $m->listing->image_url, 'slug' => $m->listing->slug,
            ] : null,
        ]);

        return response()->json(['messages' => $messages]);
    }

    public function send(Request $request, $responseId)
    {
        $c = $request->user();
        $thread = $this->ownedThread($c->id, $responseId);
        $data = $request->validate(['body' => 'required|string|max:2000']);

        $msg = SourcingMessage::create([
            'response_id' => $thread->id, 'request_id' => $thread->request_id,
            'sender_type' => 'customer', 'customer_id' => $c->id,
            'sender_name' => $c->name, 'body' => $data['body'],
        ]);

        try { app(SourcingNotifier::class)->notifyNewMessage($thread, $msg); }
        catch (\Throwable $e) { Log::error('API sourcing send: ' . $e->getMessage()); }

        return response()->json(['ok' => true, 'id' => $msg->id], 201);
    }

    private function ownedThread(int $customerId, $responseId): SourcingResponse
    {
        $thread = SourcingResponse::findOrFail($responseId);
        SourcingRequest::where('id', $thread->request_id)->where('customer_id', $customerId)->firstOrFail();
        return $thread;
    }
}
