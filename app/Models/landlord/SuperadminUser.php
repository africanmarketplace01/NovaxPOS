<?php

namespace App\Models\landlord;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SuperadminUser extends Authenticatable
{
    use Notifiable;

    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'superadmin_users';

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];
}
