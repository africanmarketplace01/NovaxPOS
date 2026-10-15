<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class DeliveryAgent extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'delivery_agents';

    protected $fillable = [
        'agency_id', 'name', 'phone', 'email', 'photo',
        'license_number', 'national_id', 'is_active', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];
    protected $casts  = ['is_active' => 'boolean'];

    public function agency()
    {
        return $this->belongsTo(DeliveryAgency::class, 'agency_id');
    }

    public function bike()
    {
        return $this->hasOne(DeliveryBike::class, 'agent_id');
    }

    /** Public URL for the agent photo (served from storage/app/landlord-logos). */
    public function photoUrl(): ?string
    {
        if (!$this->photo) return null;
        return url('/landlord-logo/' . $this->photo);
    }
}
