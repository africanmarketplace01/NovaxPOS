<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Biller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreOrder;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles the fulfilment lifecycle of a store/marketplace order:
 *  - Enforces valid status transitions.
 *  - Completing an order creates a real Sale record (+ product_sales, payment,
 *    stock decrement) so it appears in sales reports.
 *  - Cancelling a previously-completed order reverses that sale (restock).
 */
class StoreOrderCompletionService
{
    /** Statuses an order may move to from its current status. */
    public function allowedNext(string $current): array
    {
        return match ($current) {
            'completed' => ['completed', 'cancelled'],
            'cancelled' => ['cancelled'],
            default     => ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'],
        };
    }

    public function canTransition(string $current, string $next): bool
    {
        return in_array($next, $this->allowedNext($current), true);
    }

    /**
     * Apply a status change with all side effects. Assumes the transition has
     * already been validated with canTransition(). Runs in a transaction.
     */
    public function apply(StoreOrder $order, string $next, ?int $userId): void
    {
        $current = $order->status;
        DB::transaction(function () use ($order, $current, $next, $userId) {
            if ($next === 'completed' && !$order->sale_id) {
                $order->sale_id = $this->createSale($order, $userId);
            }
            if ($next === 'cancelled' && $current === 'completed' && $order->sale_id) {
                $this->reverseSale($order->sale_id);
                $order->sale_id = null;
            }
            $order->status = $next;
            $order->save();
        });
    }

    /** Create a Sale from a completed order. Returns the new sale id. */
    public function createSale(StoreOrder $order, ?int $userId): int
    {
        $warehouse = Warehouse::where('is_active', 1)->first();
        $biller    = Biller::where('is_active', 1)->first();
        $accountId = optional(Account::where('is_default', 1)->first())->id
                     ?? optional(Account::where('is_active', 1)->first())->id;

        $customer = null;
        if ($order->customer_phone) $customer = Customer::where('phone_number', $order->customer_phone)->first();
        if (!$customer && $order->customer_email) $customer = Customer::where('email', $order->customer_email)->first();
        if (!$customer) $customer = Customer::where('is_active', 1)->orderBy('id')->first();

        $items    = is_array($order->items) ? $order->items : [];
        $totalQty = 0.0; $productTotal = 0.0;
        foreach ($items as $it) {
            $totalQty     += (float) ($it['qty'] ?? 0);
            $productTotal += (float) ($it['subtotal'] ?? (($it['price'] ?? 0) * ($it['qty'] ?? 0)));
        }

        $taxAmount = (float) $order->tax_amount;
        $shipping  = (float) $order->delivery_fee;
        $grand     = (float) $order->total;
        $paid      = strtolower((string) $order->payment_status) === 'paid' ? $grand : 0.0;

        $sale = Sale::create([
            'reference_no'   => 'so-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)),
            'user_id'        => $userId,
            'customer_id'    => $customer?->id,
            'warehouse_id'   => $warehouse?->id,
            'biller_id'      => $biller?->id,
            'item'           => count($items),
            'total_qty'      => $totalQty,
            'total_discount' => 0,
            'total_tax'      => $taxAmount,
            'total_price'    => $productTotal,
            'order_tax_rate' => 0,
            'order_tax'      => $taxAmount,
            'order_discount' => 0,
            'shipping_cost'  => $shipping,
            'grand_total'    => $grand,
            'sale_status'    => 1,
            'payment_status' => $paid >= $grand && $grand > 0 ? 4 : ($paid > 0 ? 2 : 1),
            'paid_amount'    => $paid,
            'sale_type'      => $order->source === 'marketplace' ? 'marketplace' : 'online store',
            'sale_note'      => 'Auto-created from order ' . $order->order_number,
            'created_at'     => now(),
        ]);

        $saleUnits = Product::whereIn('id', collect($items)->pluck('product_id')->filter())->pluck('sale_unit_id', 'id');
        foreach ($items as $it) {
            $pid = (int) ($it['product_id'] ?? 0);
            if (!$pid) continue;
            $qty  = (float) ($it['qty'] ?? 0);
            $net  = (float) ($it['price'] ?? 0);
            $line = (float) ($it['subtotal'] ?? ($net * $qty));
            DB::table('product_sales')->insert([
                'sale_id'        => $sale->id,
                'product_id'     => $pid,
                'qty'            => $qty,
                'sale_unit_id'   => (int) ($saleUnits[$pid] ?? 0),
                'net_unit_price' => $net,
                'discount'       => 0,
                'tax_rate'       => 0,
                'tax'            => 0,
                'total'          => $line,
            ]);
            Product::where('id', $pid)->decrement('qty', $qty);
            if ($warehouse) {
                DB::table('product_warehouse')->where('product_id', $pid)->where('warehouse_id', $warehouse->id)->decrement('qty', $qty);
            }
        }

        if ($paid > 0) {
            DB::table('payments')->insert(array_filter([
                'sale_id'           => $sale->id,
                'account_id'        => $accountId,
                'payment_reference' => 'pay-' . $sale->reference_no,
                'amount'            => $paid,
                'change'            => 0,
                'paying_method'     => $order->payment_method ?: 'Online',
                'created_at'        => now(),
                'updated_at'        => now(),
            ], fn ($v) => $v !== null));
        }

        return $sale->id;
    }

    /**
     * Mark an order's payment as received. If the order already produced a Sale
     * (it was completed), the outstanding balance is recorded as a payment so
     * the sale shows as fully paid — for customers who pay later (e.g. COD).
     */
    public function markPaid(StoreOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order->payment_status = 'paid';
            $order->save();

            if ($order->sale_id) {
                $sale = Sale::find($order->sale_id);
                if ($sale) {
                    $due = (float) $sale->grand_total - (float) $sale->paid_amount;
                    if ($due > 0) {
                        $accountId = optional(Account::where('is_default', 1)->first())->id
                                     ?? optional(Account::where('is_active', 1)->first())->id;
                        DB::table('payments')->insert(array_filter([
                            'sale_id'           => $sale->id,
                            'account_id'        => $accountId,
                            'payment_reference' => 'pay-' . $sale->reference_no . '-' . strtoupper(substr(uniqid(), -4)),
                            'amount'            => $due,
                            'change'            => 0,
                            'paying_method'     => $order->payment_method ?: 'Cash',
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ], fn ($v) => $v !== null));
                        $sale->paid_amount    = $sale->grand_total;
                        $sale->payment_status = 4; // paid
                        $sale->save();
                    }
                }
            }
        });
    }

    /** Reverse an auto-created sale: restock and delete its rows. */
    public function reverseSale(int $saleId): void
    {
        $lines = DB::table('product_sales')->where('sale_id', $saleId)->get();
        $sale  = Sale::find($saleId);
        $warehouseId = $sale?->warehouse_id;
        foreach ($lines as $ln) {
            Product::where('id', $ln->product_id)->increment('qty', (float) $ln->qty);
            if ($warehouseId) {
                DB::table('product_warehouse')->where('product_id', $ln->product_id)->where('warehouse_id', $warehouseId)->increment('qty', (float) $ln->qty);
            }
        }
        DB::table('product_sales')->where('sale_id', $saleId)->delete();
        DB::table('payments')->where('sale_id', $saleId)->delete();
        Sale::where('id', $saleId)->delete();
    }
}
