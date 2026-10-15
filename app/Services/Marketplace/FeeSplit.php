<?php

namespace App\Services\Marketplace;

use App\Models\landlord\MarketplaceFee;
use App\Models\landlord\SaasSettings;
use App\Models\landlord\Tenant;

/**
 * Works out who pays the platform fee on a marketplace order, per vendor.
 *
 *  - Subscribed merchants: the fee is added to the customer's total (the
 *    behaviour the marketplace always had). Tenant overrides in
 *    marketplace_fees now apply, because each vendor is priced separately.
 *  - Marketplace-only sellers: no surcharge. The customer pays the listed
 *    price and the seller owes a commission, billed through the existing
 *    platform-fee requests. Rate: the seller's tenant override if set,
 *    otherwise the seller_commission_* platform settings.
 *
 * Call it with the order's items (each needs tenant_id plus subtotal, or
 * price and qty). The result is frozen onto the order as fee_breakdown so
 * later rate changes never rewrite history.
 */
class FeeSplit
{
    /**
     * @param  array<int, array>  $items
     * @return array{customer_fee: float, seller_commission: float, label: string, vendors: array<string, array>}
     */
    public static function forItems(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $tenantId = (string) ($item['tenant_id'] ?? '');
            $line = isset($item['subtotal'])
                ? (float) $item['subtotal']
                : (float) ($item['price'] ?? 0) * (float) ($item['qty'] ?? 1);
            $groups[$tenantId] = ($groups[$tenantId] ?? 0) + $line;
        }

        $sellerIds = $groups
            ? Tenant::whereIn('id', array_keys($groups))
                ->where('subscription_type', Tenant::MARKETPLACE_SUBSCRIPTION)
                ->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];

        $vendors = [];
        $customerFee = 0.0;
        $commission = 0.0;
        $label = 'Platform fee';

        foreach ($groups as $tenantId => $subtotal) {
            $isSeller = in_array($tenantId, $sellerIds, true);

            if ($isSeller) {
                [$percent, $flat] = self::sellerRate($tenantId);
                $amount = round($subtotal * $percent / 100 + $flat, 2);
                $commission += $amount;
                $vendors[$tenantId] = [
                    'subtotal' => round($subtotal, 2),
                    'is_seller' => true,
                    'percent' => $percent,
                    'flat' => $flat,
                    'customer_fee' => 0.0,
                    'seller_commission' => $amount,
                ];
            } else {
                $fee = MarketplaceFee::calculate($subtotal, $tenantId ?: null);
                $customerFee += $fee['fee_amount'];
                $label = $fee['label'] ?: $label;
                $vendors[$tenantId] = [
                    'subtotal' => round($subtotal, 2),
                    'is_seller' => false,
                    'percent' => (float) $fee['percent_fee'],
                    'flat' => (float) $fee['flat_fee'],
                    'customer_fee' => (float) $fee['fee_amount'],
                    'seller_commission' => 0.0,
                ];
            }
        }

        return [
            'customer_fee' => round($customerFee, 2),
            'seller_commission' => round($commission, 2),
            'label' => $label,
            'vendors' => $vendors,
        ];
    }

    /**
     * The fee line the customer sees at checkout, in the shape the views have
     * always used. The rate is only shown when every surcharged vendor shares
     * it; a cart of marketplace sellers only has no customer fee at all.
     */
    public static function customerFeeInfo(array $split): array
    {
        $rates = collect($split['vendors'])
            ->reject(fn ($v) => $v['is_seller'])
            ->map(fn ($v) => [$v['percent'], $v['flat']])
            ->unique(fn ($r) => implode('|', $r));
        [$percent, $flat] = $rates->count() === 1 ? $rates->first() : [0, 0];

        return [
            'label' => $split['label'],
            'fee_amount' => $split['customer_fee'],
            'percent_fee' => (float) $percent,
            'flat_fee' => (float) $flat,
        ];
    }

    /**
     * Extra marketplace_orders attributes for this split. Empty until the
     * add_marketplace_seller_support migration has added the columns, so a
     * deploy that runs code before migrations can't break checkout.
     */
    public static function orderAttributes(array $split): array
    {
        if (! \Illuminate\Support\Facades\Schema::connection('saleprosaas_landlord')->hasColumn('marketplace_orders', 'seller_commission')) {
            return [];
        }

        return [
            'seller_commission' => $split['seller_commission'],
            'fee_breakdown' => $split['vendors'],
        ];
    }

    /**
     * Commission rate for a marketplace seller: [percent, flat].
     * A per-tenant override in marketplace_fees wins over the platform default.
     */
    public static function sellerRate(string $tenantId): array
    {
        try {
            $override = $tenantId === '' ? null : MarketplaceFee::where('scope', 'tenant')
                ->where('scope_id', $tenantId)
                ->where('is_active', true)
                ->first();
        } catch (\Illuminate\Database\QueryException $e) {
            $override = null; // marketplace tables not migrated yet: use the platform default
        }

        if ($override) {
            return [(float) $override->percent_fee, (float) $override->flat_fee];
        }

        $settings = SaasSettings::allCached();

        return [
            (float) ($settings['seller_commission_percent'] ?? 5),
            (float) ($settings['seller_commission_flat'] ?? 0),
        ];
    }
}
