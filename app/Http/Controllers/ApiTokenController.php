<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    public function index()
    {
        $tokens = ApiToken::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get();

        return view('backend.api_tokens.index', compact('tokens'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:60',
            'abilities'  => 'nullable|array',
            'abilities.*'=> 'in:read,write,*',
            'expires_in' => 'nullable|integer|min:1|max:365', // days
        ]);

        // Generate a plain random token
        $plain = Str::random(40);

        $record = ApiToken::create([
            'user_id'    => auth()->id(),
            'name'       => $request->name,
            'token'      => Hash::make($plain),
            'abilities'  => $request->abilities ?? ['*'],
            'expires_at' => $request->expires_in
                            ? now()->addDays((int) $request->expires_in)
                            : null,
        ]);

        // Store the plain token temporarily in session for one-time display
        session()->flash('new_token', $record->id . '|' . $plain);
        session()->flash('new_token_name', $record->name);

        return redirect()->route('api-tokens.index')
            ->with('message', 'API token created. Copy it now — it will not be shown again.');
    }

    public function destroy(ApiToken $apiToken)
    {
        // Only allow deleting own tokens (or admin)
        if ($apiToken->user_id !== auth()->id() && !auth()->user()->hasRole('Admin')) {
            abort(403);
        }

        $apiToken->delete();

        return back()->with('message', 'API token revoked.');
    }
}
