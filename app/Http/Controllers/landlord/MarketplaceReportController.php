<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Mail\PlatformFeeRequestMail;
use App\Models\landlord\MailSetting;
use App\Models\landlord\MarketplaceOrder;
use App\Models\landlord\SaasSettings;
use App\Models\landlord\Tenant;
use App\Services\Marketplace\FeeHolds;
use App\Services\Marketplace\Payouts;
use App\Traits\MailInfo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MarketplaceReportController extends Controller
{
    use MailInfo;

    // ── Reports dashboard ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        [$from, $to, $preset] = $this->resolvePeriod($request);

        $orders = MarketplaceOrder::whereBetween('created_at', [$from, $to->copy()->endOfDay()])
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at')
            ->get();

        // ── Summary cards ──
        // Platform income = customer-paid fees plus marketplace sellers' commission.
        $fee = fn ($o) => (float) $o->platform_fee + (float) ($o->seller_commission ?? 0);
        $summary = [
            'orders'        => $orders->count(),
            'sales'         => $orders->sum('total'),
            'platform_fees' => $orders->sum($fee),
            'delivery_fees' => $orders->sum('delivery_fee'),
            'paid_fees'     => $orders->where('payment_status', 'paid')->sum($fee),
            'unpaid_fees'   => $orders->where('payment_status', '!=', 'paid')->sum($fee),
            'currency'      => $orders->first()->currency ?? (SaasSettings::get('currency') ?? 'USD'),
        ];

        // ── Time series (per day) ──
        $series = $orders->groupBy(fn ($o) => $o->created_at->format('Y-m-d'))
            ->map(fn ($day) => [
                'orders' => $day->count(),
                'sales'  => round($day->sum('total'), 2),
                'fees'   => round($day->sum('platform_fee'), 2),
            ]);

        // Fill missing days for a smooth chart
        $chart = ['labels' => [], 'sales' => [], 'fees' => [], 'orders' => []];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $chart['labels'][] = $d->format('d M');
            $chart['sales'][]  = $series[$key]['sales'] ?? 0;
            $chart['fees'][]   = $series[$key]['fees'] ?? 0;
            $chart['orders'][] = $series[$key]['orders'] ?? 0;
        }

        // ── Payment method breakdown ──
        $byPayment = $orders->groupBy('payment_method')->map(fn ($g) => round($g->sum('total'), 2));

        // ── Vendor breakdown (fee allocated by subtotal share within each order) ──
        $vendors = $this->vendorBreakdown($orders);

        // ── Fee requests list ──
        $feeRequests = DB::connection('saleprosaas_landlord')->table('platform_fee_requests')
            ->orderByDesc('id')->limit(50)->get();

        $tenants = Tenant::active()->whereNotNull('company_name')->orderBy('company_name')->get(['id', 'company_name']);

        // ── Vendor payouts (online-paid orders the platform owes vendors for) ──
        $payouts = Payouts::ready()
            ? DB::connection('saleprosaas_landlord')->table('vendor_payouts')->orderByDesc('id')->limit(50)->get()
            : collect();

        return view('superadmin.marketplace.reports', compact(
            'summary', 'chart', 'byPayment', 'vendors', 'feeRequests', 'payouts', 'tenants', 'from', 'to', 'preset'
        ));
    }

    // ── Vendor payouts ──────────────────────────────────────────────────────

    public function generatePayouts(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'tenant_id'    => 'nullable|string',
        ]);
        if (! Payouts::ready()) {
            return back()->with('error', 'Run the latest landlord migrations to enable payouts.');
        }

        $created = Payouts::generate(
            Carbon::parse($request->period_start)->startOfDay(),
            Carbon::parse($request->period_end)->endOfDay(),
            $request->filled('tenant_id') ? $request->tenant_id : null
        );

        return back()->with($created ? 'message' : 'error', $created
            ? "{$created} payout draft(s) created — send the money, then mark each one paid."
            : 'Nothing to pay out: no new online-paid orders for that period/vendor.');
    }

    public function updatePayout(Request $request, int $id)
    {
        $data = $request->validate([
            'action'           => 'required|in:paid,cancel',
            'payout_method'    => 'required_if:action,paid|nullable|string|max:40',
            'payout_reference' => 'nullable|string|max:120',
            'note'             => 'nullable|string|max:500',
        ]);

        if ($data['action'] === 'paid') {
            Payouts::markPaid($id, $data['payout_method'], $data['payout_reference'] ?? null, $data['note'] ?? null);
            return back()->with('message', 'Payout marked as paid and the vendor has been notified.');
        }

        Payouts::cancel($id);
        return back()->with('message', 'Payout cancelled; its orders can be included in a new payout.');
    }

    // ── Generate fee requests for a period ─────────────────────────────────

    public function generateFeeRequests(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'tenant_id'    => 'nullable|string',
            'unpaid_only'  => 'nullable|boolean',
        ]);

        $from = Carbon::parse($request->period_start)->startOfDay();
        $to   = Carbon::parse($request->period_end)->endOfDay();

        // Only orders where the vendor (or courier) collected the money: the vendor
        // owes the platform its fee. Orders the platform collected online are
        // settled the other way, through vendor payouts with commission deducted,
        // so billing them here would charge the vendor twice.
        $orders = MarketplaceOrder::whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->whereIn('payment_method', Payouts::VENDOR_COLLECTED_METHODS)
            ->get();
        // Never bill the same vendor share of an order twice: skip orders already
        // on a draft, sent or paid request (cancelled ones free their orders).
        $alreadyBilled = [];
        DB::connection('saleprosaas_landlord')->table('platform_fee_requests')
            ->where('status', '!=', 'cancelled')
            ->get(['tenant_id', 'breakdown'])
            ->each(function ($req) use (&$alreadyBilled) {
                foreach (json_decode($req->breakdown ?? '[]', true) ?: [] as $line) {
                    if (!empty($line['order_number'])) {
                        $alreadyBilled[$req->tenant_id . '|' . $line['order_number']] = true;
                    }
                }
            });

        $vendors = $this->vendorBreakdown($orders, true, $alreadyBilled);

        if ($request->filled('tenant_id')) {
            $vendors = array_filter($vendors, fn ($v) => $v['tenant_id'] === $request->tenant_id);
        }

        $created = 0;
        foreach ($vendors as $v) {
            if ($v['fee_share'] <= 0) continue;

            DB::connection('saleprosaas_landlord')->table('platform_fee_requests')->insert([
                'reference'      => 'PFR-' . strtoupper(Str::random(8)),
                'tenant_id'      => $v['tenant_id'],
                'tenant_name'    => $v['tenant_name'],
                'period_start'   => $from->toDateString(),
                'period_end'     => $to->toDateString(),
                'orders_count'   => $v['orders'],
                'sales_subtotal' => $v['subtotal'],
                'fee_amount'     => round($v['fee_share'], 2),
                'currency'       => $v['currency'],
                'breakdown'      => json_encode($v['lines']),
                'status'         => 'draft',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $created++;
        }

        return back()->with($created ? 'message' : 'error',
            $created ? "{$created} fee request(s) generated as drafts — review and send below." : 'No billable activity found for that period/vendor.');
    }

    // ── Send a fee request to the vendor ────────────────────────────────────

    public function sendFeeRequest(int $id)
    {
        $req = DB::connection('saleprosaas_landlord')->table('platform_fee_requests')->find($id);
        if (!$req) return back()->with('error', 'Fee request not found.');

        $tenant   = Tenant::find($req->tenant_id);
        $settings = SaasSettings::allCached();
        $sender   = $settings['site_name'] ?? 'NovaxSuites POS';
        $senderEmail = $settings['mail_from_address'] ?? config('mail.from.address') ?? 'noreply@example.com';

        // Email
        if ($tenant && $tenant->email) {
            try {
                // Use the SaaS SMTP settings (same source as Settings → Test Email).
                // Fall back to the legacy mail_settings table only if unconfigured.
                if (!$this->setMailInfoFromSaasSettings()) {
                    $mailSetting = MailSetting::latest()->first();
                    if ($mailSetting) $this->setMailInfo($mailSetting);
                }
                Mail::to($tenant->email)->send(new PlatformFeeRequestMail($req, $sender, $senderEmail));
            } catch (\Throwable $e) {
                Log::error('Fee request email failed: ' . $e->getMessage());
                return back()->with('error', 'Email failed: ' . $e->getMessage());
            }
        }

        // In-app: tenant admin notification
        try {
            if ($tenant) {
                $reference = $req->reference; $amount = $req->currency . ' ' . number_format((float) $req->fee_amount, 2);
                $tenant->run(function () use ($reference, $amount) {
                    $adminIds = DB::table('users')->whereIn('role_id', [1, 2])
                        ->where('is_active', 1)->where('is_deleted', 0)->pluck('id');
                    foreach ($adminIds as $uid) {
                        DB::table('notifications')->insert([
                            'id' => (string) Str::uuid(),
                            'type' => 'App\\Notifications\\PlatformFeeRequest',
                            'notifiable_type' => 'App\\Models\\User',
                            'notifiable_id' => $uid,
                            'data' => json_encode([
                                'kind'    => 'marketplace_order',
                                'message' => "Platform fee invoice {$reference} — {$amount} payable",
                            ]),
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                });
            }
        } catch (\Throwable $e) {
            Log::error('Fee request in-app failed: ' . $e->getMessage());
        }

        $update = ['status' => 'sent', 'sent_at' => now(), 'updated_at' => now()];
        // Due date drives the marketplace-seller hold (FeeHolds). Resending keeps
        // the original due date so a reminder can't reset an overdue invoice.
        if (\Illuminate\Support\Facades\Schema::connection('saleprosaas_landlord')->hasColumn('platform_fee_requests', 'due_date')) {
            $update['due_date'] = $req->due_date ?? FeeHolds::dueDate();
        }
        DB::connection('saleprosaas_landlord')->table('platform_fee_requests')->where('id', $id)->update($update);

        return back()->with('message', "Fee request {$req->reference} sent to {$req->tenant_name}.");
    }

    // ── Mark paid / cancel ──────────────────────────────────────────────────

    public function updateFeeRequestStatus(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:paid,cancelled,draft']);

        $feeRequest = DB::connection('saleprosaas_landlord')->table('platform_fee_requests')->where('id', $id);
        $feeRequest->update([
            'status'     => $request->status,
            'paid_at'    => $request->status === 'paid' ? now() : null,
            'updated_at' => now(),
        ]);

        // Paying or cancelling an overdue invoice puts a held seller's
        // listings straight back on the marketplace.
        if ($tenantId = $feeRequest->value('tenant_id')) {
            FeeHolds::sync($tenantId);
        }

        return back()->with('message', 'Fee request updated.');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Allocate each order's platform fee to vendors proportionally to their
     * subtotal share of the order.
     */
    /** @param array<string, true> $skip  "tenant_id|order_number" keys to leave out */
    private function vendorBreakdown($orders, bool $withLines = false, array $skip = []): array
    {
        $vendors = [];
        $tenantNames = Tenant::pluck('company_name', 'id');

        foreach ($orders as $order) {
            $orderSubtotal = max((float) $order->subtotal, 0.01);
            $byTenant = collect($order->items)->groupBy('tenant_id');

            foreach ($byTenant as $tenantId => $items) {
                if (isset($skip[$tenantId . '|' . $order->order_number])) {
                    continue;
                }
                $tenantSubtotal = $items->sum('subtotal');
                // Orders since the seller-commission change carry the exact
                // per-vendor split: the customer fee share plus any commission a
                // marketplace seller owes. Older orders split the fee by subtotal.
                $split = $order->fee_breakdown[$tenantId] ?? null;
                $feeShare = $split
                    ? (float) $split['customer_fee'] + (float) $split['seller_commission']
                    : (float) $order->platform_fee * ($tenantSubtotal / $orderSubtotal);

                if (!isset($vendors[$tenantId])) {
                    $vendors[$tenantId] = [
                        'tenant_id'   => $tenantId,
                        'tenant_name' => $tenantNames[$tenantId] ?? $tenantId,
                        'orders'      => 0,
                        'subtotal'    => 0.0,
                        'fee_share'   => 0.0,
                        'currency'    => $order->currency,
                        'lines'       => [],
                    ];
                }

                $vendors[$tenantId]['orders']++;
                $vendors[$tenantId]['subtotal']  += $tenantSubtotal;
                $vendors[$tenantId]['fee_share'] += $feeShare;

                if ($withLines) {
                    $vendors[$tenantId]['lines'][] = [
                        'order_number'   => $order->order_number,
                        'date'           => $order->created_at->format('Y-m-d'),
                        'payment_method' => $order->payment_method,
                        'payment_status' => $order->payment_status,
                        'subtotal'       => round($tenantSubtotal, 2),
                        'fee_share'      => round($feeShare, 2),
                    ];
                }
            }
        }

        // Round + sort by revenue desc
        foreach ($vendors as &$v) {
            $v['subtotal']  = round($v['subtotal'], 2);
            $v['fee_share'] = round($v['fee_share'], 2);
        }
        usort($vendors, fn ($a, $b) => $b['subtotal'] <=> $a['subtotal']);

        return $vendors;
    }

    private function resolvePeriod(Request $request): array
    {
        $preset = $request->get('period', 'month');

        [$from, $to] = match ($preset) {
            'today' => [now()->startOfDay(), now()],
            'week'  => [now()->startOfWeek(), now()],
            'year'  => [now()->startOfYear(), now()],
            'range' => [
                Carbon::parse($request->get('from', now()->subDays(30)->toDateString()))->startOfDay(),
                Carbon::parse($request->get('to', now()->toDateString()))->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()], // month
        };

        return [$from, $to, $preset];
    }
}
