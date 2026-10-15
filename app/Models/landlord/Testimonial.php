<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'landlord_testimonials';

    protected $fillable = [
        'author_name',
        'author_role',
        'author_avatar',
        'author_initials',
        'stars',
        'body',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Returns the public URL for the author avatar, or null if none uploaded.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->author_avatar && file_exists(public_path('landlord/images/testimonials/' . $this->author_avatar))) {
            return asset('landlord/images/testimonials/' . $this->author_avatar);
        }
        return null;
    }
}
