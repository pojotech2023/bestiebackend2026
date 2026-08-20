<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroceryImage extends Model
{
    use HasFactory;

    protected $table = 'grocery_images';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'grocery_list_id',
        'original_name',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the grocery list that owns this image.
     */
    public function groceryList()
    {
        return $this->belongsTo(GroceryList::class);
    }
}
