<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class DeliveryZonePrice extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'delivery_zone_prices';

    protected $fillable = [
        'agency_id', 'from_zone_id', 'to_zone_id', 'price', 'est_days_min', 'est_days_max',
    ];

    protected $casts = ['price' => 'float'];

    public function agency()
    {
        return $this->belongsTo(DeliveryAgency::class, 'agency_id');
    }
}
