<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Notification;
use App\CentralLogics\Helpers;
use Illuminate\Console\Command;

class SendScheduledNotifications extends Command
{
    protected $signature   = 'notifications:send-scheduled';
    protected $description = 'Send all pending scheduled notifications whose scheduled_at time has arrived';

    public function handle(): int
    {
       $now = Carbon::now(config('app.timezone'));

$notifications = Notification::where('is_sent', 0)
    ->where('status', 1)
    ->whereNotNull('scheduled_at')
    ->whereRaw('scheduled_at <= ?', [$now->format('Y-m-d H:i:s')])
    ->get();

        if ($notifications->isEmpty()) {
            $this->info('No pending notifications to send.');
            return Command::SUCCESS;
        }

        $this->info("Found {$notifications->count()} notification(s) to send.");

        foreach ($notifications as $notification) {
            try {
                $this->sendNotification($notification);

                $notification->is_sent = 1;
                $notification->save();

                $this->info("✓ Sent notification ID {$notification->id}: {$notification->title}");
            } catch (\Exception $e) {
                $this->error("✗ Failed notification ID {$notification->id}: " . $e->getMessage());
                \Log::error("Scheduled notification ID {$notification->id} failed: " . $e->getMessage());
            }
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }

    /**
     * Determine the correct FCM topic and dispatch the push notification.
     */
    private function sendNotification(Notification $notification): void
    {
        $topic_all_zone = [
            'customer'    => 'all_zone_customer',
            'deliveryman' => 'all_zone_delivery_man',
            'restaurant'  => 'all_zone_restaurant',
        ];

        $topic_zone_wise = [
            'customer'    => 'zone_' . $notification->zone_id . '_customer',
            'deliveryman' => 'zone_' . $notification->zone_id . '_delivery_man',
            'restaurant'  => 'zone_' . $notification->zone_id . '_restaurant',
        ];

        if (!array_key_exists($notification->tergat, $topic_all_zone)) {
            \Log::error("Invalid tergat '{$notification->tergat}' for notification ID {$notification->id}");
            return;
        }

        $topic = $notification->zone_id === null
            ? $topic_all_zone[$notification->tergat]
            : $topic_zone_wise[$notification->tergat];

        // Build a plain-object copy so we can rewrite the image URL safely
        $notificationData = clone $notification;
        if ($notification->image) {
            $notificationData->image = url('/') . '/storage/notification/' . $notification->image;
        }

        Helpers::send_push_notif_to_topic($notificationData, $topic, 'general');
    }
}