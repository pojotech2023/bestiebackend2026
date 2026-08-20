<?php

namespace App\Http\Controllers\Admin;

use App\Models\GroceryList;
use App\Models\GroceryListItem;
use App\Models\GroceryImage;
use App\Models\BestieBuddySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class GroceryListController extends Controller
{
    // ─── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $groceryLists = GroceryList::query()
            ->with(['user', 'items', 'image_records'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($uq) use ($search) {
                        $uq->where('f_name', 'like', "%{$search}%")
                           ->orWhere('l_name', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%")
                           ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->appends(['search' => $search, 'status' => $status]);

        return view('admin-views.grocery-list.index', compact('groceryLists', 'search', 'status'));
    }

    // ─── Show ──────────────────────────────────────────────────────────────────

    public function show($id)
    {
        $groceryList = GroceryList::with(['user', 'items', 'image_records', 'delivery_man'])
            ->findOrFail($id);

        $settings = BestieBuddySetting::current();

        return view('admin-views.grocery-list.show', compact('groceryList', 'settings'));
    }

    // ─── Edit ──────────────────────────────────────────────────────────────────

    public function edit($id)
    {
        $groceryList = GroceryList::with(['user', 'items', 'image_records', 'delivery_man'])
            ->findOrFail($id);

        return view('admin-views.grocery-list.edit', compact('groceryList'));
    }

    // ─── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $request->validate([
            'status'                    => 'required|in:processing,assigned,out_for_delivery,completed,cancelled',
            'items.*.item_name'         => 'nullable|string|max:255',
            'new_items.*.item_name'     => 'required_with:new_items.*|string|max:255',
            'new_images.*'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            // custom delivery charge: nullable numeric — empty string means "use global %"
            'custom_delivery_charge'    => 'nullable|numeric|min:0',
        ]);

        $groceryList = GroceryList::with(['items', 'image_records'])->findOrFail($id);

        DB::beginTransaction();
        try {
            // ── Basic fields ──────────────────────────────────────────────────
            $groceryList->update([
                'status'        => $request->status,
                'description'   => $request->description,
                'address'       => $request->address,
                'house'         => $request->house,
                'street_number' => $request->street_number,
                'floor'         => $request->floor,
            ]);

            // ── Pricing / custom delivery charge ─────────────────────────────
            //
            // Logic:
            //   - 'use_custom_delivery' toggle NOT checked  → custom_delivery_charge field is absent / empty
            //     → pass -1 sentinel to recalculateCharges to CLEAR any existing override
            //   - toggle checked + amount provided          → use that flat amount for this order
            //   - items_cost not provided                   → skip charge recalculation
            //
            $useCustom     = $request->has('use_custom_delivery') || $request->filled('custom_delivery_charge');
            $customDelivery = null; // null = keep whatever is stored (no recalc)

            if ($request->filled('items_cost') && !$groceryList->is_price_confirmed) {
                if ($useCustom && $request->filled('custom_delivery_charge')) {
                    // Admin set a specific flat delivery amount for this order
                    $customDelivery = (float) $request->input('custom_delivery_charge');
                } elseif (!$useCustom) {
                    // Toggle turned off → clear the stored custom charge, revert to global %
                    $customDelivery = -1.0; // sentinel to clear
                }
                // else: toggle on but no amount typed yet → keep stored value

                $groceryList->recalculateCharges(
                    (float) $request->items_cost,
                    $customDelivery
                );
            } elseif ($request->filled('items_cost') && $groceryList->is_price_confirmed) {
                // Price already confirmed — only allow custom delivery charge update
                // (items_cost itself won't change, but charge override can be set/cleared)
                if ($useCustom && $request->filled('custom_delivery_charge')) {
                    $customDelivery = (float) $request->input('custom_delivery_charge');
                    $groceryList->recalculateCharges((float) $groceryList->items_cost, $customDelivery);
                } elseif (!$useCustom && $groceryList->custom_delivery_charge !== null) {
                    // Clear override → revert to global %
                    $groceryList->recalculateCharges((float) $groceryList->items_cost, -1.0);
                }
            } else {
                // items_cost not filled → only persist the custom_delivery_charge toggle state
                // without running a full recalculation
                if (!$useCustom && $groceryList->custom_delivery_charge !== null) {
                    // Admin removed the override via toggle without providing items_cost
                    $groceryList->update(['custom_delivery_charge' => null]);
                } elseif ($useCustom && $request->filled('custom_delivery_charge')) {
                    $groceryList->update(['custom_delivery_charge' => (float) $request->input('custom_delivery_charge')]);
                }
            }

            // ── Delete items ──────────────────────────────────────────────────
            if ($request->filled('deleted_items')) {
                GroceryListItem::whereIn('id', array_map('intval', (array) $request->deleted_items))
                    ->where('grocery_list_id', $groceryList->id)->delete();
            }

            // ── Update existing items ─────────────────────────────────────────
            if ($request->has('items')) {
                foreach ($request->items as $itemId => $data) {
                    if (empty($data['item_name'])) continue;
                    GroceryListItem::where('id', $itemId)
                        ->where('grocery_list_id', $groceryList->id)
                        ->update([
                            'item_name' => $data['item_name'],
                            'quantity'  => $data['quantity'] ?? null,
                            'unit'      => $data['unit']     ?? null,
                            'count'     => $data['count']    ?? null,
                        ]);
                }
            }

            // ── Add new items ─────────────────────────────────────────────────
            if ($request->has('new_items')) {
                $maxSort = GroceryListItem::where('grocery_list_id', $groceryList->id)->max('sort_order') ?? 0;
                foreach ($request->new_items as $data) {
                    if (empty($data['item_name'])) continue;
                    $maxSort++;
                    GroceryListItem::create([
                        'grocery_list_id' => $groceryList->id,
                        'item_name'       => $data['item_name'],
                        'quantity'        => $data['quantity'] ?? null,
                        'unit'            => $data['unit']     ?? null,
                        'count'           => $data['count']    ?? null,
                        'sort_order'      => $maxSort,
                    ]);
                }
            }

            // ── Delete images ─────────────────────────────────────────────────
            if ($request->filled('deleted_images')) {
                $toDelete = GroceryImage::whereIn('id', array_map('intval', (array) $request->deleted_images))
                    ->where('grocery_list_id', $groceryList->id)->get();
                foreach ($toDelete as $img) {
                    if ($img->file_path && !str_starts_with($img->file_url, 'http')) {
                        @unlink(public_path($img->file_path));
                    }
                    $img->delete();
                }
            }

            // ── Upload new images ─────────────────────────────────────────────
            if ($request->hasFile('new_images')) {
                $maxImgSort = GroceryImage::where('grocery_list_id', $groceryList->id)->max('sort_order') ?? 0;
                foreach ($request->file('new_images') as $file) {
                    if (!$file->isValid()) continue;
                    $maxImgSort++;
                    $filename = 'grocery_' . $groceryList->id . '_' . time() . '_' . $maxImgSort . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/grocery_images'), $filename);
                    GroceryImage::create([
                        'grocery_list_id' => $groceryList->id,
                        'file_url'        => url('uploads/grocery_images/' . $filename),
                        'file_path'       => 'uploads/grocery_images/' . $filename,
                        'original_name'   => $file->getClientOriginalName(),
                        'sort_order'      => $maxImgSort,
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.grocery-list.show', $groceryList->id)
            ->with('success', 'Order #' . $groceryList->id . ' updated successfully.');
    }

    // ─── Export ────────────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $groceryLists = GroceryList::with(['user', 'items', 'image_records'])->get();

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=grocery-lists.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = ['ID', 'Customer Name', 'Email', 'Phone', 'Title', 'Description',
                    'Status', 'Items Cost', 'Delivery Charge', 'Custom Delivery?',
                    'Service Charge', 'GST', 'Total Amount', 'Items Count', 'Date'];

        $callback = function () use ($groceryLists, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($groceryLists as $list) {
                fputcsv($file, [
                    $list->id,
                    ($list->user->f_name ?? '') . ' ' . ($list->user->l_name ?? ''),
                    $list->user->email  ?? 'N/A',
                    $list->user->phone  ?? 'N/A',
                    $list->title        ?? 'N/A',
                    $list->description  ?? 'N/A',
                    ucfirst($list->status),
                    $list->items_cost,
                    $list->delivery_charge,
                    $list->custom_delivery_charge !== null ? 'Yes (₹' . (int)$list->custom_delivery_charge . ')' : 'No (global %)',
                    $list->service_charge,
                    $list->gst_amount,
                    $list->total_amount,
                    $list->items->count(),
                    $list->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─── Status Update ─────────────────────────────────────────────────────────

    public function updateStatus(Request $request)
    {
        $groceryList = GroceryList::find($request->id);
        if (!$groceryList) {
            return redirect()->back()->with('error', 'Grocery list not found.');
        }
        $groceryList->update(['status' => $request->status]);
        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    // ─── Update Charges ────────────────────────────────────────────────────────

   public function updateCharges(Request $request, $id)
{
    $groceryList = GroceryList::findOrFail($id);
    $settings    = BestieBuddySetting::current();

    $itemsCost = (float) $request->input('items_cost', 0);
    
    // empty string → null (revert to global), otherwise use the value
    $rawCustom     = $request->input('custom_delivery_charge');
    $customCharge  = ($rawCustom === null || $rawCustom === '') 
                        ? null 
                        : (float) $rawCustom;

    $charges = $settings->calculateCharges($itemsCost, $customCharge);

    $updateData = [
        'items_cost'              => $itemsCost,
        'custom_delivery_charge'  => $customCharge,
        'delivery_charge'         => $charges['deliveryCharge'],
        'service_charge'          => $charges['serviceCharge'],
        'gst_amount'              => $charges['gstAmount'],
        'total_amount'            => $charges['totalAmount'],
    ];

    // If "Confirm" button was clicked
    if ($request->input('confirm')) {
        $updateData['is_price_confirmed'] = true;
        $updateData['price_confirmed_at'] = now();
        $updateData['status']             = 'out_for_delivery';
        BestieOrderLogic::notifyCustomerPriceConfirmed($groceryList);
    }

    $groceryList->update($updateData);

    return response()->json([
        'items_cost'      => $itemsCost,
        'delivery_charge' => $charges['deliveryCharge'],
        'service_charge'  => $charges['serviceCharge'],
        'gst_amount'      => $charges['gstAmount'],
        'total_amount'    => $charges['totalAmount'],
    ]);
}

    // ─── Delivery Men ──────────────────────────────────────────────────────────

    public function availableDeliveryMen($id)
    {
        $groceryList = GroceryList::with('delivery_man')->find($id);

        $deliveryMen = \App\Models\DeliveryMan::where('status', 1)
            ->where('type', 'zone_wise')
            ->select('id', 'f_name', 'l_name', 'phone', 'email', 'image')
            ->withCount(['orders as current_orders' => function ($q) {
                $q->whereIn('order_status', ['pending', 'confirmed', 'processing', 'picked_up']);
            }])
            ->get()
            ->map(fn($dm) => [
                'id'             => $dm->id,
                'name'           => $dm->f_name . ' ' . $dm->l_name,
                'phone'          => $dm->phone,
                'email'          => $dm->email,
                'image'          => $dm->image,
                'current_orders' => $dm->current_orders,
            ]);

        $current = null;
        if ($groceryList && $groceryList->delivery_man) {
            $dm = $groceryList->delivery_man;
            $current = [
                'id'             => $dm->id,
                'name'           => $dm->f_name . ' ' . $dm->l_name,
                'image'          => $dm->image,
                'current_orders' => $dm->orders_count ?? 0,
            ];
            $deliveryMen = $deliveryMen->filter(fn($d) => $d['id'] != $dm->id)->values();
        }

        return response()->json(['current' => $current, 'delivery_men' => $deliveryMen]);
    }

    public function assignDeliveryMan($id, $dm_id)
    {
        $groceryList = GroceryList::with('delivery_man', 'user')->find($id);
        if (!$groceryList) return response()->json(['message' => 'Grocery list not found.'], 404);

        $deliveryMan = \App\Models\DeliveryMan::find($dm_id);
        if (!$deliveryMan) return response()->json(['message' => 'Delivery man not found.'], 404);

        if ($groceryList->delivery_man_id == $dm_id)
            return response()->json(['message' => 'Order already assigned to this delivery man.'], 400);

        if ($groceryList->delivery_man) {
            $oldDm = $groceryList->delivery_man;
            $oldDm->current_orders = $oldDm->current_orders > 1 ? $oldDm->current_orders - 1 : 0;
            $oldDm->save();

            if ($oldDm->fcm_token) {
                $data = [
                    'title'       => translate('messages.order_push_title'),
                    'description' => translate('messages.you_are_unassigned_from_a_order'),
                    'order_id'    => (string) $groceryList->id,
                    'image'       => '',
                    'type'        => 'bestie_buddy_assign',
                    'order_type'  => 'bestie_buddy',
                ];
                \App\CentralLogics\Helpers::send_push_notif_to_device($oldDm->fcm_token, $data);
                DB::table('user_notifications')->insert([
                    'data'            => json_encode($data),
                    'delivery_man_id' => $oldDm->id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        $groceryList->update(['delivery_man_id' => $dm_id, 'status' => 'assigned', 'assigned_at' => now()]);
        $deliveryMan->current_orders += 1;
        $deliveryMan->save();
        $deliveryMan->increment('assigned_order_count');

        if ($deliveryMan->fcm_token) {
            $dmData = [
                'title'       => translate('messages.order_push_title'),
                'description' => 'New Bestie Buddy grocery order #' . $groceryList->id . ' assigned to you.',
                'order_id'    => (string) $groceryList->id,
                'image'       => '',
                'type'        => 'bestie_buddy_assign',
                'order_type'  => 'bestie_buddy',
            ];
            \App\CentralLogics\Helpers::send_push_notif_to_device($deliveryMan->fcm_token, $dmData);
            DB::table('user_notifications')->insert([
                'data'            => json_encode($dmData),
                'delivery_man_id' => $deliveryMan->id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        if ($groceryList->user && $groceryList->user->cm_firebase_token) {
            $customerData = [
                'title'       => translate('messages.order_push_title'),
                'description' => 'Your Bestie Buddy order #' . $groceryList->id . ' has been assigned to a delivery man.',
                'order_id'    => (string) $groceryList->id,
                'image'       => '',
                'type'        => 'bestie_buddy_order_status',
                'order_type'  => 'bestie_buddy',
            ];
            \App\CentralLogics\Helpers::send_push_notif_to_device($groceryList->user->cm_firebase_token, $customerData);
            DB::table('user_notifications')->insert([
                'data'       => json_encode($customerData),
                'user_id'    => $groceryList->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message'      => 'Delivery man assigned successfully.',
            'delivery_man' => [
                'id'    => $deliveryMan->id,
                'name'  => $deliveryMan->f_name . ' ' . $deliveryMan->l_name,
                'phone' => $deliveryMan->phone,
            ],
        ]);
    }

    // ─── Settings ──────────────────────────────────────────────────────────────

    public function settings()
    {
        $settings = BestieBuddySetting::current();
        return view('admin-views.grocery-list.settings', compact('settings'));
    }

   public function updateSettings(Request $request)
    {
        $request->validate([
            'delivery_charge_type'       => 'required|in:percentage,fixed',
            'delivery_charge_percentage' => 'required_if:delivery_charge_type,percentage|nullable|numeric|min:0|max:100',
            'delivery_charge_amount'     => 'required_if:delivery_charge_type,fixed|nullable|numeric|min:0',
            'service_charge_percentage'  => 'required|numeric|min:0|max:100',
            'gst_percentage'             => 'required|numeric|min:0|max:100',
        ]);
 
        BestieBuddySetting::current()->update([
            'delivery_charge_type'       => $request->delivery_charge_type,
            'delivery_charge_percentage' => $request->delivery_charge_percentage ?? 0,
            'delivery_charge_amount'     => $request->delivery_charge_amount     ?? 0,
            'service_charge_percentage'  => $request->service_charge_percentage,
            'gst_percentage'             => $request->gst_percentage,
        ]);
 
        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}