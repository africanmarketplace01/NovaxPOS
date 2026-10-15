<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class DeliveryBike extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'delivery_bikes';

    protected $fillable = [
        'agency_id', 'agent_id', 'make', 'model', 'plate_number',
        'color', 'year', 'registration_no', 'insurance_no', 'note', 'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function agent()
    {
        return $this->belongsTo(DeliveryAgent::class, 'agent_id');
    }

    public function label(): string
    {
        return trim(($this->make ?? '') . ' ' . ($this->model ?? '')) ?: 'Bike';
    }
}
