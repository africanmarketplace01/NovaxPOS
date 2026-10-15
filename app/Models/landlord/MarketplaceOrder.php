<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class MarketplaceOrder extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'marketplace_orders';

    protected $fillable = [
        'order_number', 'customer_id', 'customer_name', 'customer_email', 'customer_phone', 'customer_address',
        'items', 'currency', 'subtotal', 'platform_fee', 'delivery_fee', 'total',
        // platform_fee is what the customer paid on top; seller_commission is what
        // marketplace-only sellers owe. fee_breakdown is the per-vendor split (FeeSplit).
        'seller_commission', 'fee_breakdown',
        'delivery_agency_id', 'delivery_agency_name', 'delivery_agent_id', 'estimated_delivery',
        'payment_method', 'payment_status', 'payment_reference',
        'status', 'note',
    ];

    protected $casts = [
        'items'        => 'array',
        'subtotal'     => 'float',
        'platform_fee' => 'float',
        'delivery_fee' => 'float',
        'total'        => 'float',
        'seller_commission' => 'float',
        'fee_breakdown' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function deliveryAgency()
    {
        return $this->belongsTo(DeliveryAgency::class, 'delivery_agency_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public static function generateOrderNumber(): string
    {
        return 'MKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Status badge colour helper for views.
     */
    public function statusBadge(): string
    {
        return match ($this->status) {
            'pending'    => 'warning',
            'processing' => 'info',
            'shipped'    => 'primary',
            'delivered'  => 'success',
            'cancelled'  => 'danger',
            default      => 'secondary',
        };
    }

    public function paymentBadge(): string
    {
        return match ($this->payment_status) {
            'paid'     => 'success',
            'pending'  => 'warning',
            'failed'   => 'danger',
            'refunded' => 'secondary',
            default    => 'secondary',
        };
    }

    /**
     * Group items by tenant for fulfilment splitting.
     */
    public function itemsByTenant(): array
    {
        $groups = [];
        foreach ($this->items ?? [] as $item) {
            $tid = $item['tenant_id'] ?? 'unknown';
            $groups[$tid][] = $item;
        }
        return $groups;
    }

    // ── Order tracking ─────────────────────────────────────────────────────────

    public function events()
    {
        return $this->hasMany(MarketplaceOrderEvent::class, 'order_id')->orderBy('created_at');
    }

    /** Append a timeline event. Safe if the events table isn't migrated yet. */
    public function recordEvent(string $status, string $title, ?string $note = null, string $actor = 'system'): void
    {
        try {
            MarketplaceOrderEvent::create([
                'order_id' => $this->id,
                'status'   => $status,
                'title'    => $title,
                'note'     => $note,
                'actor'    => $actor,
            ]);
        } catch (\Throwable $e) {
            // events table missing — tracking is best-effort, never block the order
        }
    }

    /**
     * Canonical tracking steps with completed / current / upcoming states,
     * derived from the current status (+ any recorded events).
     *
     * @return array<int,array{key:string,label:string,icon:string,state:string,at:?string}>
     */
    public function trackingSteps(): array
    {
        $flow = [
            'pending'    => ['label' => 'Order placed',  'icon' => 'fa-receipt'],
            'processing' => ['label' => 'Processing',    'icon' => 'fa-box'],
            'shipped'    => ['label' => 'Shipped',       'icon' => 'fa-truck'],
            'delivered'  => ['label' => 'Delivered',     'icon' => 'fa-circle-check'],
        ];
        $order = array_keys($flow);

        if ($this->status === 'cancelled') {
            return [[
                'key' => 'cancelled', 'label' => 'Order cancelled',
                'icon' => 'fa-circle-xmark', 'state' => 'cancelled',
                'at' => optional($this->updated_at)->format('M j, Y g:i A'),
            ]];
        }

        // When each status was reached (first matching event)
        $eventTimes = [];
        try {
            foreach ($this->events as $e) {
                if (!isset($eventTimes[$e->status])) {
                    $eventTimes[$e->status] = optional($e->created_at)->format('M j, Y g:i A');
                }
            }
        } catch (\Throwable $e) {
            // no events relation loaded/available
        }

        $currentIndex = array_search($this->status, $order);
        if ($currentIndex === false) $currentIndex = 0;

        $steps = [];
        foreach ($order as $i => $key) {
            $state = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'current' : 'upcoming');
            $steps[] = [
                'key'   => $key,
                'label' => $flow[$key]['label'],
                'icon'  => $flow[$key]['icon'],
                'state' => $state,
                'at'    => $eventTimes[$key]
                            ?? ($key === 'pending' ? optional($this->created_at)->format('M j, Y g:i A') : null),
            ];
        }
        return $steps;
    }
}
