<?php

namespace App\Http\Controllers\Admin;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PushNotificationExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class NotificationController extends Controller
{
   function index(Request $request)
{
    $key = explode(' ', $request['search']);
    
    $notifications = Notification::with('zone')
        ->latest()
        ->when(isset($key), function ($q) use ($key) {
            $q->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('title', 'like', "%{$value}%");
                }
            });
        })
        ->get()
        ->groupBy(function ($n) {
            return $n->campaign_key ?: 'solo_' . $n->id;
        })
        ->map(function ($group) {
            $first = $group->first();
            $first->date_from     = $group->min('scheduled_at');
            $first->date_to       = $group->max('scheduled_at');
            $first->total_days    = $group->count();
            $first->sent_count    = $group->where('is_sent', 1)->count();
            $first->pending_count = $group->where('is_sent', 0)->count();
            $first->all_ids       = $group->pluck('id')->toArray();
            return $first;
        })
        ->values(); 
    return view('admin-views.notification.index', compact('notifications'));
}
   public function store(Request $request)
{
    if (env('APP_MODE') == 'demo') {
        return response()->json(['errors' => Helpers::error_formater('feature-disable', 'This option is disabled for demo!')]);
    }

    $validator = Validator::make($request->all(), [
        'notification_title' => 'required|max:191',
        'description'        => 'required|max:1000',
        'tergat'             => 'required|in:customer,deliveryman,restaurant',
        'zone'               => 'required',
        'start_date'         => 'required|date',
        'end_date'           => 'required|date|after_or_equal:start_date',
        'notification_time'  => 'required',
        'image'              => 'nullable|mimes:jpg,jpeg,png,gif,bmp|max:2048',
        'duplicate_image'    => 'nullable|string',
    ], [
        'notification_title.required' => 'Title is required!',
        'tergat.in'                   => 'Invalid target selected!',
        'start_date.required'         => 'Start date is required!',
        'end_date.required'           => 'End date is required!',
        'end_date.after_or_equal'     => 'End date must be after or equal to start date!',
        'notification_time.required'  => 'Time is required!',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => Helpers::error_processor($validator)]);
    }

    $startDate = Carbon::parse($request->start_date, config('app.timezone'))->startOfDay();
    $endDate   = Carbon::parse($request->end_date, config('app.timezone'))->startOfDay();

    if ($startDate->diffInDays($endDate) > 90) {
        return response()->json(['errors' => Helpers::error_formater('date-range', 'Date range cannot be more than 90 days!')]);
    }

    // Normalize time - "HH:MM" or "HH:MM:SS" both handle
    $time = substr($request->notification_time, 0, 5);

    // Image handling
    if ($request->hasFile('image')) {
        $image_name = Helpers::upload(dir: 'notification/', format: 'png', image: $request->file('image'));
    } elseif ($request->filled('duplicate_image') && Storage::disk('public')->exists('notification/' . $request->duplicate_image)) {
        $extension  = pathinfo($request->duplicate_image, PATHINFO_EXTENSION) ?: 'png';
        $image_name = \Carbon\Carbon::now()->toDateString() . '-' . uniqid() . '.' . $extension;
        Storage::disk('public')->copy('notification/' . $request->duplicate_image, 'notification/' . $image_name);
    } else {
        $image_name = null;
    }

    $period       = CarbonPeriod::create($startDate, $endDate);
    $campaign_key = \Illuminate\Support\Str::uuid()->toString(); // ✅ one UUID for entire campaign

    foreach ($period as $date) {
        $scheduledAt = Carbon::createFromFormat(
            'Y-m-d H:i',
            $date->format('Y-m-d') . ' ' . $time,
            config('app.timezone')
        );

        $notification               = new Notification;
        $notification->title        = $request->notification_title;
        $notification->description  = $request->description;
        $notification->image        = $image_name;
        $notification->tergat       = $request->tergat;
        $notification->status       = 1;
        $notification->zone_id      = $request->zone == 'all' ? null : $request->zone;
        $notification->scheduled_at = $scheduledAt;
        $notification->is_sent      = 0;
        $notification->campaign_key = $campaign_key; // ✅ loop inside, before save
        $notification->save();

        // Past/current time = send immediately
        if ($scheduledAt->lte(Carbon::now(config('app.timezone')))) {
            $this->sendNotification($notification);
            $notification->is_sent = 1;
            $notification->save();
        }
    }

    return response()->json([], 200);
}
    public function edit($id)
    {
        $notification = Notification::findOrFail($id);
        return view('admin-views.notification.edit', compact('notification'));
    }

    public function update(Request $request, $id)
    {
        if (env('APP_MODE') == 'demo') {
            Toastr::info(translate('messages.update_option_is_disable_for_demo'));
            return back();
        }

        $request->validate([
            'notification_title' => 'required|max:191',
            'description'        => 'required|max:1000',
            'tergat'             => 'required|in:customer,deliveryman,restaurant',
            'scheduled_at'       => 'required|date',
            'image'              => 'nullable|mimes:jpg,jpeg,png,gif,bmp|max:2048',
        ]);

        $notification = Notification::findOrFail($id);

       

        // FIX: Parse with app timezone
        $scheduledAt = Carbon::createFromFormat('Y-m-d\TH:i', $request->scheduled_at, config('app.timezone'));

        if ($request->hasFile('image')) {
            $image_name = Helpers::update(dir: 'notification/', old_image: $notification->image, format: 'png', image: $request->file('image'));
        } else {
            $image_name = $notification['image'];
        }

        $notification->title        = $request->notification_title;
        $notification->description  = $request->description;
        $notification->image        = $image_name;
        $notification->tergat       = $request->tergat;
        $notification->zone_id      = $request->zone == 'all' ? null : $request->zone;
        $notification->scheduled_at = $scheduledAt;
        $notification->is_sent      = 0; // Reset so scheduler can re-send if time changed
        $notification->updated_at   = now();
        $notification->save();

        // If scheduled time is now or in the past, send immediately
        if ($scheduledAt->lte(Carbon::now(config('app.timezone')))) {
            $this->sendNotification($notification);
            $notification->is_sent = 1;
            $notification->save();
        }

        Toastr::success(translate('messages.notification_updated_successfully'));
        return back();
    }

    public function status(Request $request)
    {
        $notification = Notification::findOrFail($request->id);
        $notification->status = $request->status;
        $notification->save();
        Toastr::success(translate('messages.notification_status_updated'));
        return back();
    }

   public function delete(Request $request)
{
    $notification = Notification::findOrFail($request->id);
    
    // Campaign_key irundha entire campaign delete
    if ($notification->campaign_key) {
        $all = Notification::where('campaign_key', $notification->campaign_key)->get();
        foreach ($all as $n) {
            if ($n->image && Storage::disk('public')->exists('notification/' . $n->image)) {
                Storage::disk('public')->delete('notification/' . $n->image);
            }
            $n->delete();
        }
        // Image once delete panniduvom (same image all rows la)
    } else {
        if ($notification->image && Storage::disk('public')->exists('notification/' . $notification->image)) {
            Storage::disk('public')->delete('notification/' . $notification->image);
        }
        $notification->delete();
    }
    
    Toastr::success(translate('messages.notification_deleted_successfully'));
    return back();
}

    public function export(Request $request)
    {
        try {
            $key = explode(' ', $request['search']);
            $Notification = Notification::when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('title', 'like', "%{$value}%");
                    }
                });
            })->latest()->get();

            $data = [
                'data'   => $Notification,
                'search' => $request['search'] ?? null
            ];

            if ($request->type == 'csv') {
                return Excel::download(new PushNotificationExport($data), 'PushNotification.csv');
            }
            return Excel::download(new PushNotificationExport($data), 'PushNotification.xlsx');
        } catch (\Exception $e) {
            Toastr::error("line___{$e->getLine()}", $e->getMessage());
            info(["line___{$e->getLine()}", $e->getMessage()]);
            return back();
        }
    }

    /**
     * Send push notification to correct topic based on tergat + zone
     */
    private function sendNotification($notification)
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

        // Guard: if tergat is invalid, skip
        if (!array_key_exists($notification->tergat, $topic_all_zone)) {
            \Log::error("Invalid tergat value: {$notification->tergat} for notification ID: {$notification->id}");
            return;
        }

        $topic = $notification->zone_id == null
            ? $topic_all_zone[$notification->tergat]
            : $topic_zone_wise[$notification->tergat];

        $notificationData = clone $notification;

        if ($notification->image) {
            $notificationData->image = url('/') . '/storage/notification/' . $notification->image;
        }

        try {
            Helpers::send_push_notif_to_topic($notificationData, $topic, 'general');
        } catch (\Exception $e) {
            info($e->getMessage());
            \Log::error("Push notification failed for ID {$notification->id}: " . $e->getMessage());
        }
    }
}