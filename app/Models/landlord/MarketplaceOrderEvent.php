<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Model;

class MarketplaceOrderEvent extends Model
{
    protected $connection = 'saleprosaas_landlord';
    protected $table      = 'marketplace_order_events';

    protected $fillable = ['order_id', 'status', 'title', 'note', 'actor'];
}
