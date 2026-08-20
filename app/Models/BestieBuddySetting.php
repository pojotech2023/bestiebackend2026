<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BestieBuddySetting extends Model
{
    protected $table = 'bestie_buddy_settings';

    protected $fillable = [
        'delivery_charge_type',        // 'percentage' | 'fixed'
        'delivery_charge_percentage',  // used when type = percentage
        'delivery_charge_amount',      // used when type = fixed (flat ₹ amount)
        'service_charge_percentage',
        'gst_percentage',
        'is_active',
    ];

    protected $casts = [
        'delivery_charge_type'         => 'string',
        'delivery_charge_percentage'   => 'decimal:2',
        'delivery_charge_amount'       => 'decimal:2',
        'service_charge_percentage'    => 'decimal:2',
        'gst_percentage'               => 'decimal:2',
        'is_active'                    => 'boolean',
    ];

    /**
     * Always return the single settings row (create if missing).
     */
    public static function current(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'delivery_charge_type'       => 'percentage',
                'delivery_charge_percentage' => 5.00,
                'delivery_charge_amount'     => 0.00,
                'service_charge_percentage'  => 2.00,
                'gst_percentage'             => 18.00,
                'is_active'                  => true,
            ]
        );
    }

    /**
     * Calculate all charges from a base items_cost.
     *
     * Delivery charge logic (priority order):
     *   1. $customDeliveryCharge passed (per-order admin/DM override) → use that flat amount
     *   2. settings type = 'fixed'      → use delivery_charge_amount (global flat)
     *   3. settings type = 'percentage' → use delivery_charge_percentage % of items_cost
     *
     * The custom per-order override NEVER adds on top — it fully replaces the global setting.
     *
     * @param  float       $itemsCost
     * @param  float|null  $customDeliveryCharge  Per-order flat override. null = use global setting.
     * @return array{deliveryCharge: float, serviceCharge: float, gstAmount: float, totalAmount: float}
     */
  public function calculateCharges(float $itemsCost, ?float $customDeliveryCharge = null): array
{
    // ── Delivery Charge ───────────────────────────────────────────────────
    if ($customDeliveryCharge !== null) {
        // DM or admin set a flat override — use it directly
        $deliveryCharge = $customDeliveryCharge;
    } elseif ($this->delivery_charge_type === 'fixed') {
        // Global setting is a flat fixed amount (e.g. ₹30)
        $deliveryCharge = (float) $this->delivery_charge_amount;
    } else {
        // Global setting is a percentage of items cost
        $deliveryCharge = ($itemsCost * (float) $this->delivery_charge_percentage) / 100;
    }

    // ── Service Charge ────────────────────────────────────────────────────
    $serviceCharge = ($itemsCost * (float) $this->service_charge_percentage) / 100;

    // ── GST ───────────────────────────────────────────────────────────────
    $gstAmount = ($itemsCost * (float) $this->gst_percentage) / 100;

    // ── Total ─────────────────────────────────────────────────────────────
    $totalAmount = $itemsCost + $deliveryCharge + $serviceCharge + $gstAmount;

    return [
        'deliveryCharge' => round($deliveryCharge, 2),
        'serviceCharge'  => round($serviceCharge, 2),
        'gstAmount'      => round($gstAmount, 2),
        'totalAmount'    => round($totalAmount, 2),
    ];
}
    /**
     * Helper: returns the effective global delivery charge for a given items_cost.
     * Useful for live preview in Blade / API.
     */
    public function getEffectiveDeliveryCharge(float $itemsCost): float
    {
        if ($this->delivery_charge_type === 'fixed') {
            return (float) round($this->delivery_charge_amount, 0, PHP_ROUND_HALF_UP);
        }
        return (float) round($itemsCost * ($this->delivery_charge_percentage / 100), 0, PHP_ROUND_HALF_UP);
    }

    /**
     * NEW HELPER: Returns a human-readable label for the delivery charge.
     * Used in Blade views to show correct label instead of always showing %.
     * Examples:
     *   fixed       → "Fixed ₹30"
     *   percentage  → "5%"
     */
    public function getDeliveryChargeLabel(): string
    {
        if ($this->delivery_charge_type === 'fixed') {
            return 'Fixed ₹' . number_format((float) $this->delivery_charge_amount, 0);
        }
        return number_format((float) $this->delivery_charge_percentage, 2) . '%';
    }
}