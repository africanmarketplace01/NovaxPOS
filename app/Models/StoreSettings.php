<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSettings extends Model
{
    protected $table = 'store_settings';

    protected $fillable = [
        'enabled', 'store_name', 'store_about', 'store_banner', 'store_logo',
        'store_currency', 'show_out_of_stock', 'allow_orders', 'order_note', 'whatsapp_number',
        // Marketplace
        'marketplace_listed', 'marketplace_listed_at',
        // Payment methods
        'accept_stripe', 'accept_paystack', 'accept_bank_transfer',
        'accept_mtn', 'accept_orange', 'accept_cod',
        // Tax & contact
        'tax_rate', 'store_email', 'store_instagram', 'store_facebook',
    ];

    protected $casts = [
        'enabled'            => 'boolean',
        'show_out_of_stock'  => 'boolean',
        'allow_orders'       => 'boolean',
        'marketplace_listed' => 'boolean',
        'accept_stripe'      => 'boolean',
        'accept_paystack'    => 'boolean',
        'accept_bank_transfer' => 'boolean',
        'accept_mtn'         => 'boolean',
        'accept_orange'      => 'boolean',
        'accept_cod'         => 'boolean',
        'tax_rate'           => 'float',
        'marketplace_listed_at' => 'datetime',
    ];

    /**
     * Return enabled payment methods as an array of keys.
     */
    public function enabledPaymentMethods(): array
    {
        $methods = [];
        if ($this->accept_stripe)       $methods[] = 'stripe';
        if ($this->accept_paystack)     $methods[] = 'paystack';
        if ($this->accept_bank_transfer) $methods[] = 'bank_transfer';
        if ($this->accept_mtn)          $methods[] = 'mtn';
        if ($this->accept_orange)       $methods[] = 'orange';
        if ($this->accept_cod)          $methods[] = 'cod';
        return $methods ?: ['cod']; // default to COD if nothing configured
    }

    /**
     * Get the singleton store settings row, creating defaults if not present.
     */
    public static function instance(): self
    {
        return self::firstOrCreate([], [
            'enabled'    => false,
            'store_name' => config('app.name', 'My Store'),
        ]);
    }
}
