<?php

namespace App\CentralLogics;

use App\Models\GroceryList;
use App\Models\DeliveryMan;
use App\Models\Log;
use App\CentralLogics\Helpers;

class BestieOrderLogic
{
    // =========================================================================
    //  Assign a delivery person to the grocery list order
    // =========================================================================
    public static function assignDeliveryPerson(GroceryList $groceryList): array
    {
        try {
            $deliveryMan = DeliveryMan::where('status', 1)
            ->where('zone_id', $groceryList->zone_id)
                ->where('active', 1)
                ->orderBy('current_orders', 'ASC')
                ->first();

            if (!$deliveryMan) {
                $groceryList->update(['status' => 'processing']);
                self::createLog($groceryList->id, 'processing', 'No delivery person available.');

                return [
                    'success'      => true,
                    'message'      => 'No delivery person available at the moment.',
                    'delivery_man' => null,
                    'status'       => 'processing',
                ];
            }

            $groceryList->update([
                'delivery_man_id' => $deliveryMan->id,
                'status'          => 'assigned',
                'assigned_at'     => now(),
            ]);

            $deliveryMan->increment('current_orders');

            self::createLog($groceryList->id, 'assigned', "Assigned to: {$deliveryMan->f_name}");

            self::notifyDeliveryPerson($groceryList, $deliveryMan);

            return [
                'success'      => true,
                'message'      => 'Order assigned to delivery person successfully.',
                'delivery_man' => $deliveryMan,
                'status'       => 'assigned',
            ];

        } catch (\Exception $e) {
            return [
                'success'      => false,
                'message'      => 'Error: ' . $e->getMessage(),
                'delivery_man' => null,
                'status'       => null,
            ];
        }
    }

    // =========================================================================
    //  Notify customer when DM confirms the price
    // =========================================================================
    public static function notifyCustomerPriceConfirmed(GroceryList $groceryList): void
    {
        try {
            // Load the customer
            $customer = $groceryList->user;
            if (!$customer || !$customer->cm_firebase_token) {
                return;
            }

            $data = [
                'title'       => 'Order Price Confirmed!',
                'description' => 'Your Bestie Buddy order #' . $groceryList->id
                                 . ' price has been confirmed. Total: ₹'
                                 . number_format((float) $groceryList->total_amount, 2),
                'order_id'    => $groceryList->id,
                'image'       => '',
                'type'        => 'order_price_confirmed',
                'order_type'  => 'bestie_buddy',
                // Send full pricing so app can show breakdown
                'items_cost'      => (string) $groceryList->items_cost,
                'delivery_charge' => (string) $groceryList->delivery_charge,
                'service_charge'  => (string) $groceryList->service_charge,
                'gst_amount'      => (string) $groceryList->gst_amount,
                'total_amount'    => (string) $groceryList->total_amount,
            ];

            $response = Helpers::send_push_notif_to_device($customer->cm_firebase_token, $data);

            // Clear stale FCM token
            if (isset($response['error']['details'])) {
                foreach ($response['error']['details'] as $detail) {
                    if (($detail['errorCode'] ?? '') === 'UNREGISTERED') {
                        $customer->update(['cm_firebase_token' => null]);
                        info('FCM token cleared for customer id: ' . $customer->id);
                    }
                }
            }

        } catch (\Exception $e) {
            info('Customer price-confirmed notification failed: ' . $e->getMessage());
        }
    }

    // =========================================================================
    //  Update order status
    // =========================================================================
    public static function updateOrderStatus(GroceryList $groceryList, string $newStatus): bool
    {
        $validStatuses = ['processing', 'assigned', 'out_for_delivery', 'completed', 'cancelled'];

        if (!in_array($newStatus, $validStatuses)) {
            return false;
        }

        try {
            $oldStatus = $groceryList->status;
            $groceryList->update(['status' => $newStatus]);
            self::createLog($groceryList->id, $newStatus, "Status changed from {$oldStatus} to {$newStatus}");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // =========================================================================
    //  Complete an order (mark as completed/delivered)
    // =========================================================================
    public static function completeOrder(GroceryList $groceryList): bool
    {
        try {
            $groceryList->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            if ($groceryList->delivery_man_id) {
                $dm = DeliveryMan::find($groceryList->delivery_man_id);
                if ($dm && $dm->current_orders > 0) {
                    $dm->decrement('current_orders');
                }
            }

            self::createLog($groceryList->id, 'completed', 'Order completed successfully');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // =========================================================================
    //  Cancel an order
    // =========================================================================
    public static function cancelOrder(GroceryList $groceryList, ?string $reason = null): bool
    {
        try {
            $groceryList->update(['status' => 'cancelled']);

            if ($groceryList->delivery_man_id) {
                $dm = DeliveryMan::find($groceryList->delivery_man_id);
                if ($dm && $dm->current_orders > 0) {
                    $dm->decrement('current_orders');
                }
            }

            $message = $reason ? 'Order cancelled: ' . $reason : 'Order cancelled';
            self::createLog($groceryList->id, 'cancelled', $message);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // =========================================================================
    //  Helper utilities
    // =========================================================================
    public static function getAvailableDeliveryPersonCount(): int
    {
        return DeliveryMan::where('status', 1)->where('active', 1)->count();
    }

    public static function getBestAvailableDeliveryPerson(): ?DeliveryMan
    {
        return DeliveryMan::where('status', 1)
            ->where('active', 1)
            ->orderBy('current_orders', 'ASC')
            ->first();
    }

    // =========================================================================
    //  Private helpers
    // =========================================================================
    private static function createLog(int $groceryListId, string $status, string $message): void
    {
        try {
            Log::create([
                'model'    => 'BestieOrder',
                'model_id' => $groceryListId,
                'type'     => $status,
                'message'  => $message,
                'data'     => json_encode(['status' => $status]),
            ]);
        } catch (\Exception $e) {
            // Silently fail — log is non-critical
        }
    }

    private static function notifyDeliveryPerson(GroceryList $groceryList, DeliveryMan $deliveryMan): void
    {
        try {
            if (false) {
                return;
            }

            // Build a short address string for the DM notification
            $addressHint = $groceryList->address
                ? ' | Deliver to: ' . \Str::limit($groceryList->address, 60)
                : '';

            $data = [
                'title'       => 'New Bestie Buddy Request',
                'description' => 'You have a new Bestie Buddy order request',
                'order_id'    => $groceryList->id,
                'image'       => '',
                'type'        => 'new_order',
                'order_type'  => 'bestie_buddy',
            ];

            \DB::table('user_notifications')->insert([
                'data'            => json_encode($data),
                'delivery_man_id' => $deliveryMan->id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $response = Helpers::send_push_notif_to_device($deliveryMan->fcm_token, $data);

            if (isset($response['error']['details'])) {
                foreach ($response['error']['details'] as $detail) {
                    if (($detail['errorCode'] ?? '') === 'UNREGISTERED') {
                        $deliveryMan->update(['fcm_token' => null]);
                        info('FCM token cleared for DM id: ' . $deliveryMan->id);
                    }
                }
            }

        } catch (\Exception $e) {
            info('Grocery DM notification failed: ' . $e->getMessage());
        }
    }
}