<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantConfig extends Model
{
    use HasFactory;

    protected $casts = [
        'customer_order_date'=>'integer',
        'restaurant_id'=>'integer',
        'customer_date_order_sratus'=>'boolean',
        'instant_order'=>'boolean',
    ];
    protected $guarded = ['id'];

}
