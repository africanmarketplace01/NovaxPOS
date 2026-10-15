<?php

namespace App\Services\Marketplace;

use App\Models\landlord\DeliveryAgency;
use App\Models\landlord\MarketplaceOrder;
use App\Models\landlord\Tenant;
use App\Models\StoreOrder;
use Illuminate\Support\Facades\Log;

/**
 * Fans a marketplace order out into a store_order row inside each relevant
 * tenant's database so vendors see it in their POS. Shared by the web checkout
 * and the mobile API.
 */
class OrderFanout
{
    public static function run(MarketplaceOrder $order, array $items, ?DeliveryAgency $agency = null): void
    {
        $byTenant = [];
        foreach ($items as $item) {
            $byTenant[$item['tenant_id']][] = $item;
        }

        foreach ($byTenant as $tenantId => $tenantItems) {
            try {
                $tenant = Tenant::find($tenantId);
                if (!$tenant) {
                    Log::error("Marketplace fan-out: tenant {$tenantId} not found for order {$order->order_number}");
                    continue;
                }

                $tenantSubtotal = collect($tenantItems)->sum('subtotal');
                // Marketplace sellers see the commission they owe on this order;
                // for subscribed merchants the customer paid the fee, so it stays 0.
                $commission = (float) ($order->fee_breakdown[$tenantId]['seller_commission'] ?? 0);

                $tenant->run(function () use ($order, $tenantItems, $tenantSubtotal, $commission) {
                    StoreOrder::create([
                        'order_number'             => StoreOrder::generateOrderNumber(),
                        'customer_name'            => $order->customer_name,
                        'customer_email'           => $order->customer_email,
                        'customer_phone'           => $order->customer_phone,
                        'customer_address'         => $order->customer_address,
                        'items'                    => $tenantItems,
                        'subtotal'                 => $tenantSubtotal,
                        'tax_amount'               => 0,
                        'total'                    => $tenantSubtotal,
                        'currency'                 => $order->currency,
                        'note'                     => $order->note,
                        'payment_method'           => $order->payment_method,
                        'payment_status'           => $order->payment_status,
                        'delivery_agency_id'       => $order->delivery_agency_id,
                        'delivery_agency_name'     => $order->delivery_agency_name,
                        'delivery_fee'             => 0,
                        'platform_fee'             => $commission,
                        'source'                   => 'marketplace',
                        'marketplace_order_number' => $order->order_number,
                        'status'                   => 'pending',
                    ]);
                });
            } catch (\Throwable $e) {
                Log::error("Marketplace fan-out failed for tenant {$tenantId}: " . $e->getMessage(), [
                    'order' => $order->order_number,
                ]);
            }
        }
    }
}
