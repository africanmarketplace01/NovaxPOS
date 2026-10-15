<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LandlordInvoice extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'landlord_invoices';

    protected $fillable = [
        'invoice_number', 'tenant_id', 'tenant_name', 'tenant_email', 'tenant_company',
        'subtotal', 'tax_rate', 'tax_amount', 'discount', 'total', 'currency',
        'status', 'notes', 'line_items', 'payment_method',
        'invoice_date', 'due_date', 'paid_date', 'amount_paid', 'reference',
    ];

    protected $casts = [
        'line_items'   => 'array',
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'paid_date'    => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────
    public function scopeUnpaid(Builder $q): Builder
    {
        return $q->whereIn('status', ['unpaid', 'partial']);
    }

    public function scopePaid(Builder $q): Builder
    {
        return $q->where('status', 'paid');
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->whereIn('status', ['unpaid', 'partial'])
                 ->where('due_date', '<', now()->toDateString());
    }

    // ── Helpers ────────────────────────────────────────────────────────────────
    public static function generateNumber(): string
    {
        $prefix = 'INV-' . date('Ym') . '-';
        $last = static::where('invoice_number', 'like', $prefix . '%')
                      ->orderByDesc('id')->value('invoice_number');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float)$this->total - (float)$this->amount_paid);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'paid'      => '<span class="badge bg-success">Paid</span>',
            'partial'   => '<span class="badge bg-warning text-dark">Partial</span>',
            'cancelled' => '<span class="badge bg-secondary">Cancelled</span>',
            default     => '<span class="badge bg-danger">Unpaid</span>',
        };
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['unpaid', 'partial'])
            && $this->due_date
            && $this->due_date->isPast();
    }
}
