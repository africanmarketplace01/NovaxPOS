<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarketplaceFee extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'marketplace_fees';

    protected $fillable = [
        'scope', 'scope_id', 'percent_fee', 'flat_fee', 'label', 'is_active', 'created_by',
    ];

    protected $casts = [
        'percent_fee' => 'float',
        'flat_fee'    => 'float',
        'is_active'   => 'boolean',
    ];

    // ── Fee resolution ─────────────────────────────────────────────────────────

    /**
     * Calculate the platform fee for an order.
     *
     * Precedence: tenant > category > global
     *
     * @param  float       $subtotal
     * @param  string|null $tenantId
     * @param  string|null $category
     * @return array{percent_fee: float, flat_fee: float, fee_amount: float, label: string}
     */
    public static function calculate(float $subtotal, ?string $tenantId = null, ?string $category = null): array
    {
        $fee = null;

        // 1. Per-tenant override
        if ($tenantId) {
            $fee = static::where('scope', 'tenant')
                ->where('scope_id', $tenantId)
                ->where('is_active', true)
                ->first();
        }

        // 2. Per-category override
        if (!$fee && $category) {
            $fee = static::where('scope', 'category')
                ->where('scope_id', $category)
                ->where('is_active', true)
                ->first();
        }

        // 3. Global default
        if (!$fee) {
            $fee = static::where('scope', 'global')
                ->where('is_active', true)
                ->first();
        }

        if (!$fee) {
            return ['percent_fee' => 0, 'flat_fee' => 0, 'fee_amount' => 0, 'label' => 'Platform fee'];
        }

        $feeAmount = round($subtotal * ($fee->percent_fee / 100) + $fee->flat_fee, 2);

        return [
            'percent_fee' => $fee->percent_fee,
            'flat_fee'    => $fee->flat_fee,
            'fee_amount'  => $feeAmount,
            'label'       => $fee->label ?? 'Platform fee',
        ];
    }

    /**
     * Get the global fee row, creating defaults if absent.
     */
    public static function globalFee(): self
    {
        return static::firstOrCreate(
            ['scope' => 'global', 'scope_id' => null],
            ['percent_fee' => 5.00, 'flat_fee' => 0.00, 'label' => 'Platform processing fee', 'is_active' => true]
        );
    }
}
