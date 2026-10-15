<?php

namespace App\Models\landlord;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceCustomer extends Authenticatable
{
    use Notifiable;

    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'marketplace_customers';

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'avatar',
        'address', 'city', 'country', 'is_active', 'email_verified_at',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(SourcingRequest::class, 'customer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'customer_id');
    }

    public function getInitialsAttribute(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
        return mb_strtoupper($first . $last) ?: 'U';
    }
}
