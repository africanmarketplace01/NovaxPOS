<?php

namespace App\Services\Marketplace;

use App\Models\landlord\MarketplaceOrder;
use App\Models\landlord\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Payouts to vendors for marketplace orders the platform collected.
 *
 * Who holds the money decides the direction:
 *  - Online payment (anything but cash on delivery) that admin has marked
 *    paid: the platform holds it and owes each vendor their items' value,
 *    minus the commission a marketplace-only seller owes. → a payout.
 *  - Cash on delivery: the vendor or courier holds it and owes the platform
 *    its fee. → a platform fee request (MarketplaceReportController).
 *
 * The customer-paid platform fee and the delivery fee are never part of a
 * vendor's payout: the platform keeps the first, the agency is paid the second.
 */
class Payouts
{
    /** Payment methods where the vendor (or courier) collects the money. */
    public const VENDOR_COLLECTED_METHODS = ['cod'];

    public static function ready(): bool
    {
        return Schema::connection('saleprosaas_landlord')->hasTable('vendor_payouts');
    }

    /** Orders whose money the platform holds. */
    public static function platformCollectedOrders(Carbon $from, Carbon $to)
    {
        return MarketplaceOrder::whereBetween('created_at', [$from, $to])
            ->whereNotIn('payment_method', self::VENDOR_COLLECTED_METHODS)
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Create draft payouts for a period. Orders already covered by a draft or
     * paid payout are skipped, so overlapping periods never double-pay.
     *
     * @return int number of drafts created
     */
    public static function generate(Carbon $from, Carbon $to, ?string $onlyTenant = null): int
    {
        $db = DB::connection('saleprosaas_landlord');
        $already = $db->table('vendor_payout_orders')
            ->select('tenant_id', 'marketplace_order_id')->get()
            ->map(fn ($r) => $r->tenant_id . '|' . $r->marketplace_order_id)->flip();

        $names = Tenant::pluck('company_name', 'id');
        $vendors = [];

        foreach (self::platformCollectedOrders($from, $to) as $order) {
            foreach (collect($order->items)->groupBy('tenant_id') as $tenantId => $items) {
                $tenantId = (string) $tenantId;
                if ($onlyTenant && $tenantId !== $onlyTenant) {
                    continue;
                }
                if (isset($already[$tenantId . '|' . $order->id])) {
                    continue;
                }

                $gross = round((float) $items->sum('subtotal'), 2);
                $commission = round((float) ($order->fee_breakdown[$tenantId]['seller_commission'] ?? 0), 2);

                $v = &$vendors[$tenantId];
                $v ??= ['gross' => 0.0, 'commission' => 0.0, 'currency' => $order->currency, 'orders' => [], 'lines' => []];
                $v['gross'] += $gross;
                $v['commission'] += $commission;
                $v['orders'][] = $order->id;
                $v['lines'][] = [
                    'order_number' => $order->order_number,
                    'date' => $order->created_at->format('Y-m-d'),
                    'payment_method' => $order->payment_method,
                    'gross' => $gross,
                    'commission' => $commission,
                    'net' => round($gross - $commission, 2),
                ];
                unset($v);
            }
        }

        $created = 0;
        foreach ($vendors as $tenantId => $v) {
            $net = round($v['gross'] - $v['commission'], 2);
            if ($net <= 0) {
                continue;
            }

            $db->transaction(function () use ($db, $tenantId, $v, $net, $names, $from, $to, &$created) {
                $id = $db->table('vendor_payouts')->insertGetId([
                    'reference' => 'VPO-' . strtoupper(Str::random(8)),
                    'tenant_id' => $tenantId,
                    'tenant_name' => $names[$tenantId] ?? $tenantId,
                    'period_start' => $from->toDateString(),
                    'period_end' => $to->toDateString(),
                    'orders_count' => count($v['orders']),
                    'gross_amount' => round($v['gross'], 2),
                    'commission_amount' => round($v['commission'], 2),
                    'net_amount' => $net,
                    'currency' => $v['currency'],
                    'breakdown' => json_encode($v['lines']),
                    'status' => 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $db->table('vendor_payout_orders')->insert(array_map(fn ($orderId) => [
                    'vendor_payout_id' => $id,
                    'tenant_id' => $tenantId,
                    'marketplace_order_id' => $orderId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $v['orders']));
                $created++;
            });
        }

        return $created;
    }

    /** Record that the money was sent, and tell the vendor. */
    public static function markPaid(int $id, string $method, ?string $reference, ?string $note = null): void
    {
        $db = DB::connection('saleprosaas_landlord');
        $payout = $db->table('vendor_payouts')->where('id', $id)->where('status', 'draft')->first();
        if (! $payout) {
            return;
        }

        $db->table('vendor_payouts')->where('id', $id)->update([
            'status' => 'paid',
            'payout_method' => $method,
            'payout_reference' => $reference,
            'note' => $note,
            'paid_at' => now(),
            'updated_at' => now(),
        ]);

        if ($tenant = Tenant::find($payout->tenant_id)) {
            $amount = $payout->currency . ' ' . number_format((float) $payout->net_amount, 2);
            VendorNotifier::send(
                $tenant,
                "Payout {$payout->reference} sent — {$amount}",
                "We have sent you {$amount} for your marketplace sales.",
                ['This covers marketplace orders the platform collected payment for during the period below.'],
                array_filter([
                    'Payout' => $payout->reference,
                    'Period' => Carbon::parse($payout->period_start)->format('d M') . ' – ' . Carbon::parse($payout->period_end)->format('d M Y'),
                    'Your sales' => $payout->currency . ' ' . number_format((float) $payout->gross_amount, 2),
                    'Commission deducted' => (float) $payout->commission_amount > 0 ? $payout->currency . ' ' . number_format((float) $payout->commission_amount, 2) : null,
                    'Amount sent' => $amount,
                    'Method' => strtoupper($method),
                    'Reference' => $reference,
                ])
            );
        }
    }

    /** Cancel a draft; its orders become available for a new payout. */
    public static function cancel(int $id): void
    {
        $db = DB::connection('saleprosaas_landlord');
        $db->transaction(function () use ($db, $id) {
            $updated = $db->table('vendor_payouts')->where('id', $id)->where('status', 'draft')
                ->update(['status' => 'cancelled', 'updated_at' => now()]);
            if ($updated) {
                $db->table('vendor_payout_orders')->where('vendor_payout_id', $id)->delete();
            }
        });
    }
}
