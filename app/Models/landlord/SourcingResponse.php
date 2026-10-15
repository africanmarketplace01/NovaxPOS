<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A vendor's response to a sourcing request. Each response is a conversation
 * thread between one vendor (tenant) and the requesting customer.
 */
class SourcingResponse extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'sourcing_responses';

    protected $fillable = [
        'request_id', 'tenant_id', 'tenant_name', 'listing_id',
        'message', 'price', 'currency',
        'customer_last_read_at', 'vendor_last_read_at',
    ];

    protected $casts = [
        'price'                 => 'float',
        'customer_last_read_at' => 'datetime',
        'vendor_last_read_at'   => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(SourcingRequest::class, 'request_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SourcingMessage::class, 'response_id')->orderBy('created_at');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }
}
