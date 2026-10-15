<?php

namespace App\Http\Controllers\landlord\Customer;

use App\Http\Controllers\Controller;
use App\Models\landlord\SourcingResponse;
use App\Models\landlord\SourcingMessage;
use App\Models\landlord\SourcingRequest;
use App\Services\Marketplace\SourcingNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /** Ensure the thread belongs to the signed-in customer. */
    private function thread($responseId): SourcingResponse
    {
        $c = Auth::guard('customer')->user();
        $response = SourcingResponse::findOrFail($responseId);
        $req = SourcingRequest::where('id', $response->request_id)
            ->where('customer_id', $c->id)
            ->firstOrFail();
        $response->setRelation('request', $req);
        return $response;
    }

    public function messages($responseId)
    {
        $thread = $this->thread($responseId);

        // Mark vendor→customer messages as read
        SourcingMessage::where('response_id', $thread->id)
            ->where('sender_type', 'vendor')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $thread->update(['customer_last_read_at' => now()]);

        $messages = $thread->messages()->with('listing')->get()->map(function ($m) {
            return [
                'id'      => $m->id,
                'mine'    => $m->sender_type === 'customer',
                'sender'  => $m->sender_name,
                'body'    => $m->body,
                'time'    => optional($m->created_at)->format('M j, g:i A'),
                'listing' => $m->listing ? [
                    'id'    => $m->listing->id,
                    'name'  => $m->listing->name,
                    'price' => $m->listing->currency . ' ' . number_format($m->listing->effectivePrice(), 2),
                    'url'   => route('marketplace.product', ['id' => $m->listing->id, 'slug' => $m->listing->slug]),
                    'image' => $m->listing->image_url,
                ] : null,
            ];
        });

        return response()->json(['messages' => $messages]);
    }

    public function send(Request $request, $responseId)
    {
        $thread = $this->thread($responseId);
        $c = Auth::guard('customer')->user();

        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $msg = SourcingMessage::create([
            'response_id' => $thread->id,
            'request_id'  => $thread->request_id,
            'sender_type' => 'customer',
            'customer_id' => $c->id,
            'sender_name' => $c->name,
            'body'        => $data['body'],
        ]);

        try {
            app(SourcingNotifier::class)->notifyNewMessage($thread, $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('SourcingNotifier message failed: ' . $e->getMessage());
        }

        return response()->json(['ok' => true, 'id' => $msg->id]);
    }
}
