<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantContract extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'tenant_contracts';

    protected $fillable = [
        'tenant_id',
        'document_type',    // full_contract | sla_only | sow_only
        'effective_date',
        'contract_amount',
        'deposit_percent',
        'balance_percent',
        'hosting_months',
        'support_months',
        'warranty_days',
        'timeline_days',
        'include_scope',
        'scope_items',      // JSON array of scope line items
        'notes',
        // Editable section content
        'purpose_text',
        'software_description',
        'system_name',
        // SLA metrics
        'sla_uptime',
        'sla_critical_hours',
        'sla_bugfix_days',
        'sla_maintenance_notice_hours',
        'sla_support_hours',
        'sla_backup_days',
        // Legal
        'termination_notice_days',
        'governing_law',
        // Meta
        'pdf_path',
        'company_logo',
        'sent_at',
        'sent_to_email',
        'created_by',
    ];

    protected $casts = [
        'scope_items'     => 'array',
        'include_scope'   => 'boolean',
        'effective_date'  => 'date',
        'sent_at'         => 'datetime',
        'contract_amount' => 'float',
    ];

    /* ── Relationships ── */

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /* ── Helpers ── */

    public function getDocumentTypeLabel(): string
    {
        return match($this->document_type) {
            'full_contract' => 'Services Contract Agreement',
            'sla_only'      => 'Service Level Agreement (SLA)',
            'sow_only'      => 'Scope of Work (SOW)',
            default         => ucwords(str_replace('_', ' ', $this->document_type)),
        };
    }

    public function getDepositAmount(): float
    {
        return round(($this->contract_amount ?? 0) * ($this->deposit_percent / 100), 2);
    }

    public function getBalanceAmount(): float
    {
        return round(($this->contract_amount ?? 0) * ($this->balance_percent / 100), 2);
    }

    public function wasSent(): bool
    {
        return $this->sent_at !== null;
    }
}
