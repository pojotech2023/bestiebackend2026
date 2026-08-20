<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroceryListItem extends Model
{
    protected $table = 'grocery_list_items';

    protected $fillable = [
        'grocery_list_id',
        'item_name',
        'count',          // NEW — number of pieces / packs
        'quantity',
        'unit',
        'rate',
        'total_amount',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity'     => 'decimal:2',
        'rate'         => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function grocery_list(): BelongsTo
    {
        return $this->belongsTo(GroceryList::class, 'grocery_list_id');
    }
}