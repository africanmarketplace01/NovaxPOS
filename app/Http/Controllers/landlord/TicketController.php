<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Models\landlord\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    private function ticketsTable()
    {
        return DB::connection('saleprosaas_landlord')->table('support_tickets');
    }

    private function repliesTable()
    {
        return DB::connection('saleprosaas_landlord')->table('ticket_replies');
    }

    public function index(Request $request)
    {
        $query = $this->ticketsTable()->orderBy('created_at', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->tenant_id) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $tickets = $query->paginate(20);
        $tenantIds = collect($tickets->items())->pluck('tenant_id')->unique()->filter();
        $tenants = Tenant::whereIn('id', $tenantIds)->get(['id', 'company_name'])->keyBy('id');

        $openCount   = $this->ticketsTable()->where('status', 'open')->count();
        $allTenants  = Tenant::orderBy('company_name')->get(['id', 'company_name']);

        return view('superadmin.tickets.index', compact('tickets', 'tenants', 'openCount', 'allTenants'));
    }

    public function show(int $id)
    {
        $ticket  = $this->ticketsTable()->find($id);
        if (!$ticket) abort(404);

        $replies = $this->repliesTable()->where('ticket_id', $id)->orderBy('created_at')->get();
        $tenant  = Tenant::find($ticket->tenant_id);

        return view('superadmin.tickets.show', compact('ticket', 'replies', 'tenant'));
    }

    public function reply(Request $request, int $id)
    {
        $request->validate(['reply' => 'required|string']);

        $this->repliesTable()->insert([
            'ticket_id'   => $id,
            'sender_type' => 'admin',
            'message'     => $request->reply,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->ticketsTable()->where('id', $id)->update([
            'status'     => 'in_progress',
            'updated_at' => now(),
        ]);

        return back()->with('message', 'Reply sent.');
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:open,in_progress,closed']);
        $this->ticketsTable()->where('id', $id)->update([
            'status'     => $request->status,
            'updated_at' => now(),
        ]);
        return back()->with('message', 'Ticket status updated.');
    }

    public function destroy(int $id)
    {
        $this->repliesTable()->where('ticket_id', $id)->delete();
        $this->ticketsTable()->where('id', $id)->delete();
        return redirect()->route('superadmin.tickets.index')->with('message', 'Ticket deleted.');
    }
}
