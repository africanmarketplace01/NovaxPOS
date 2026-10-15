<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'delivery_zones';

    protected $fillable = ['agency_id', 'name', 'note', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function agency()
    {
        return $this->belongsTo(DeliveryAgency::class, 'agency_id');
    }
}
