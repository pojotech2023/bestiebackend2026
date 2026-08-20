<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Scopes\ZoneScope;

class Notification extends Model
{
    protected $casts = [
        'status'       => 'integer',
        'is_sent'      => 'integer',
        'scheduled_at' => 'datetime',        // ← cast so Carbon comparisons work everywhere
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function getDataAttribute(): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'order_id'    => '',
            'image'       => $this->image,
            'type'        => 'order_status',
        ];
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /** Scope: notifications that are due but not yet sent */
    public function scopePendingScheduled($query)
    {
        return $query
            ->where('is_sent', 0)
            ->where('status', 1)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now());
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d H:i:s', strtotime($value));
    }

    protected static function booted()
    {
        static::addGlobalScope(new ZoneScope);
        
    }
}