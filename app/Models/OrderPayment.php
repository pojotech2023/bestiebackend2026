<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'amount',
        'payment_status',
        'payment_method',
        'transaction_ref',
        'created_at','updated_at'
    ];
    protected $casts = [
        'order_id'=> 'integer',
        'amount' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }
}
