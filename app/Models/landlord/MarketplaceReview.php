<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceReview extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'marketplace_product_reviews';

    protected $fillable = [
        'listing_id', 'product_id', 'tenant_id', 'reviewer_name', 'reviewer_email',
        'rating', 'title', 'comment', 'is_verified', 'is_approved',
    ];

    protected $casts = [
        'rating'      => 'integer',
        'is_verified' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopeForListing(Builder $query, int $listingId): Builder
    {
        return $query->where('listing_id', $listingId);
    }
}
