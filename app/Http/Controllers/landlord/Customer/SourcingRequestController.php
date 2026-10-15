<?php

namespace App\Http\Controllers\landlord\Customer;

use App\Http\Controllers\Controller;
use App\Models\landlord\SaasSettings;
use App\Models\landlord\SourcingRequest;
use App\Models\landlord\MarketplaceListing;
use App\Services\Marketplace\SourcingNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SourcingRequestController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    public function index()
    {
        $c = $this->customer();
        $requests = SourcingRequest::withCount('responses')
            ->where('customer_id', $c->id)
            ->latest()
            ->paginate(10);

        return view('landlord.customer.requests.index', compact('c', 'requests'));
    }

    public function create()
    {
        $c = $this->customer();
        $categories = MarketplaceListing::visible()->distinct()->pluck('category')->filter()->sort()->values();
        return view('landlord.customer.requests.create', compact('c', 'categories'));
    }

    public function store(Request $request)
    {
        $c = $this->customer();

        $data = $request->validate([
            'title'       => 'required|string|max:150',
            'description' => 'required|string|max:3000',
            'category'    => 'nullable|string|max:100',
            'brand'       => 'nullable|string|max:100',
            'quantity'    => 'nullable|integer|min:1|max:100000',
            'budget_min'  => 'nullable|numeric|min:0',
            'budget_max'  => 'nullable|numeric|min:0|gte:budget_min',
            'location'    => 'nullable|string|max:150',
            'image'       => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',            // 5 MB
            'video'       => 'nullable|mimetypes:video/mp4,video/webm,video/quicktime|max:81920', // 80 MB (~2 min)
        ], [
            'video.mimetypes' => 'The showcase video must be an MP4, WebM or MOV file.',
            'video.max'       => 'The video may not be larger than 80 MB (roughly a 1–2 minute clip).',
        ]);

        $imagePath = null;
        $videoPath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('sourcing/images', 'public');
        }
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('sourcing/videos', 'public');
        }

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

        // Notify every vendor (email + in-app) — guarded so a mail failure never blocks.
        try {
            app(SourcingNotifier::class)->notifyNewRequest($req);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SourcingNotifier failed: ' . $e->getMessage());
        }

        return redirect()->route('customer.requests.show', $req->id)
            ->with('success', 'Your request has been sent to all vendors. You will be notified when they respond.');
    }

    public function show($id)
    {
        $c = $this->customer();
        $req = SourcingRequest::where('customer_id', $c->id)->findOrFail($id);

        $threads = $req->responses()->with('listing')->latest()->get();

        return view('landlord.customer.requests.show', compact('c', 'req', 'threads'));
    }

    public function close($id)
    {
        $c = $this->customer();
        $req = SourcingRequest::where('customer_id', $c->id)->findOrFail($id);
        $req->update(['status' => 'closed']);
        return back()->with('success', 'Request closed.');
    }
}
