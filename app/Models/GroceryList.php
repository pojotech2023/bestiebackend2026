<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroceryList extends Model
{
    protected $fillable = [
        'user_id',
        'delivery_man_id',
        'title',
        'description',
        'status',
        'images',
        // Address
        'address_id',
        'latitude',
        'longitude',
        'address',
        'street_number',
        'house',
        'floor',
        // Pricing
        'items_cost',
        'delivery_charge',
        'service_charge',
        'gst_amount',
        'total_amount',
        'charges_confirmed',
        'charges_confirmed_at',
        'is_price_confirmed',
        'price_confirmed_at',
        // ✅ NEW: flag that admin set a custom delivery charge for THIS order
        'custom_delivery_charge',
        'assigned_at',
        'completed_at',
        'zone_id',
    ];

    protected $casts = [
        'images'                  => 'array',
        'assigned_at'             => 'datetime',
        'completed_at'            => 'datetime',
        'charges_confirmed_at'    => 'datetime',
        'charges_confirmed'       => 'boolean',
        'is_price_confirmed'      => 'boolean',
        'price_confirmed_at'      => 'datetime',
        'items_cost'              => 'decimal:2',
        'delivery_charge'         => 'decimal:2',
        'service_charge'          => 'decimal:2',
        'gst_amount'              => 'decimal:2',
        'total_amount'            => 'decimal:2',
        'custom_delivery_charge'  => 'decimal:2',  // ✅ NEW
    ];

    // ─── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function delivery_man(): BelongsTo
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GroceryListItem::class, 'grocery_list_id')
                    ->orderBy('sort_order');
    }

    public function image_records(): HasMany
    {
        return $this->hasMany(GroceryImage::class, 'grocery_list_id')
                    ->orderBy('sort_order');
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Re-calculate and save charges using BestieBuddySetting percentages.
     *
     * ✅ FIX: If this order has a custom_delivery_charge set, that value is used
     *         instead of the global percentage. Admin can override per-order.
     *
     * @param  float       $itemsCost
     * @param  float|null  $customDeliveryCharge
     *         Pass a value to set/update the override for this order.
     *         Pass null  to keep whatever is already stored (or use global % if none stored).
     *         Pass -1    to CLEAR the override and revert to global %.
     */
    public function recalculateCharges(float $itemsCost, ?float $customDeliveryCharge = null): void
    {
        $settings = BestieBuddySetting::current();

        // Resolve the delivery charge to use
        if ($customDeliveryCharge === -1.0) {
            // -1 is a sentinel → clear any stored custom charge
            $this->custom_delivery_charge = null;
            $overrideForCalc = null;
        } elseif ($customDeliveryCharge !== null) {
            // Store the new custom charge on the model
            $this->custom_delivery_charge = $customDeliveryCharge;
            $overrideForCalc = $customDeliveryCharge;
        } else {
            // null passed → use whatever is already stored (if any)
            $overrideForCalc = isset($this->custom_delivery_charge)
                ? (float) $this->custom_delivery_charge
                : null;
        }

        $charges = $settings->calculateCharges($itemsCost, $overrideForCalc);

        $this->update([
            'items_cost'             => $itemsCost,
            'delivery_charge'        => $charges['deliveryCharge'],
            'service_charge'         => $charges['serviceCharge'],
            'gst_amount'             => $charges['gstAmount'],
            'total_amount'           => $charges['totalAmount'],
            'custom_delivery_charge' => $this->custom_delivery_charge,  // persist override
        ]);
    }
}