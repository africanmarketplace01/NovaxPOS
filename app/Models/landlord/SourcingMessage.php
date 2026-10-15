<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourcingMessage extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'sourcing_messages';

    protected $fillable = [
        'response_id', 'request_id', 'sender_type', 'customer_id', 'tenant_id',
        'sender_name', 'body', 'listing_id', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function isFromCustomer(): bool
    {
        return $this->sender_type === 'customer';
    }
}
