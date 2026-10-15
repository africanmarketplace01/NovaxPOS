<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MarketplaceCustomerToken extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'marketplace_customer_tokens';

    protected $fillable = ['customer_id', 'name', 'token', 'device', 'last_used_at'];

    protected $casts = ['last_used_at' => 'datetime'];

    /**
     * Issue a new token for a customer. Returns the PLAINTEXT token
     * (only shown once) while storing its sha256 hash.
     */
    public static function issue(int $customerId, ?string $device = null, string $name = 'mobile'): string
    {
        $plain = Str::random(60);
        static::create([
            'customer_id' => $customerId,
            'name'        => $name,
            'device'      => $device,
            'token'       => hash('sha256', $plain),
        ]);
        return $plain;
    }

    public static function findByPlain(string $plain): ?self
    {
        return static::where('token', hash('sha256', $plain))->first();
    }
}
