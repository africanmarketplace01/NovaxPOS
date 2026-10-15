<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPayment extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'tenant_payments';

    protected $fillable = [
        'tenant_id',
        'package_id',
        'amount',
        'currency',
        'paid_by',           // stripe | razorpay | paystack | manual | paydunya | ssl
        'transaction_ref',
        'payment_proof',     // file path for manual bank transfers
        'subscription_type', // monthly | yearly | free_trial
        'status',            // pending | completed | failed | refunded
        'gateway_response',  // JSON raw gateway data
        'notes',
    ];

    protected $casts = [
        'amount'           => 'float',
        'gateway_response' => 'array',
    ];

    /* ── Relationships ─────────────────────────────────────────────────────── */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
