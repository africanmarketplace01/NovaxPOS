<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourcingRequest extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'sourcing_requests';

    protected $fillable = [
        'customer_id', 'title', 'description', 'category', 'brand', 'quantity',
        'budget_min', 'budget_max', 'currency', 'location',
        'image_path', 'video_path', 'status', 'responses_count',
    ];

    protected $casts = [
        'budget_min'      => 'float',
        'budget_max'      => 'float',
        'quantity'        => 'integer',
        'responses_count' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCustomer::class, 'customer_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(SourcingResponse::class, 'request_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SourcingMessage::class, 'request_id');
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function videoUrl(): ?string
    {
        return $this->video_path ? asset('storage/' . $this->video_path) : null;
    }

    public function budgetLabel(): ?string
    {
        if (!$this->budget_min && !$this->budget_max) return null;
        $cur = $this->currency ? $this->currency . ' ' : '';
        if ($this->budget_min && $this->budget_max) {
            return $cur . number_format($this->budget_min, 0) . ' – ' . number_format($this->budget_max, 0);
        }
        return $cur . number_format($this->budget_min ?: $this->budget_max, 0);
    }
}
