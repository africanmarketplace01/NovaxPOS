<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'mail_settings';

    protected $fillable = [
        'name',
        'email',
        'driver',
        'host',
        'port',
        'encryption',
        'user_name',
        'password',
        'sendgrid_api_key',
    ];
}
