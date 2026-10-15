<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeliveryAgencyToken extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'delivery_agency_tokens';

    protected $fillable = ['agency_id', 'name', 'token', 'device', 'last_used_at'];
    protected $casts    = ['last_used_at' => 'datetime'];

    public static function issue(int $agencyId, ?string $device = null, string $name = 'mobile'): string
    {
        $plain = Str::random(60);
        static::create([
            'agency_id' => $agencyId,
            'name'      => $name,
            'device'    => $device,
            'token'     => hash('sha256', $plain),
        ]);
        return $plain;
    }

    public static function findByPlain(string $plain): ?self
    {
        return static::where('token', hash('sha256', $plain))->first();
    }
}
