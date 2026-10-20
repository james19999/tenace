<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerCountryDeliverySetting extends Model
{
    protected $fillable = ['country_code', 'delivery_mode', 'order_delivery_fee'];

    protected $casts = ['order_delivery_fee' => 'integer'];
}
