<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreOrder extends Model
{
    protected $table = 'store_orders';

    protected $fillable = [
        'order_number', 'customer_name', 'customer_email', 'customer_phone',
        'customer_address', 'items', 'subtotal', 'tax_amount', 'total',
        'currency', 'note', 'status', 'sale_id',
        // Payment
        'payment_method', 'payment_status', 'payment_reference',
        // Delivery
        'delivery_agency_id', 'delivery_agency_name', 'delivery_fee', 'estimated_delivery',
        // Marketplace
        'platform_fee', 'source', 'marketplace_order_number',
    ];

    protected $casts = [
        'items'        => 'array',
        'subtotal'     => 'float',
        'tax_amount'   => 'float',
        'total'        => 'float',
        'delivery_fee' => 'float',
        'platform_fee' => 'float',
    ];

    /**
     * Generate a unique order number: ORD-YYYYMMDD-XXXXX
     */
    public static function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }
}
