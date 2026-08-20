<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\CentralLogics\BestieOrderLogic;
use App\Models\BestieBuddySetting;
use App\Models\DeliveryMan;
use App\Models\GroceryImage;
use App\Models\GroceryList;
use App\Models\GroceryListItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class GroceryListController extends Controller
{
    // =========================================================================
    //  POST  /customer/grocery-list/store
    // =========================================================================
    public function store(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'errors' => [
                    ['code' => 'unauthorized', 'message' => 'Unauthorized. Please provide a valid customer token.']
                ]
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'title'         => 'nullable|string|max:255',
            'description'   => 'nullable|string|max:1000',
            'items'         => 'required|array|min:1',
            'items.*.name'  => 'required|string|max:255',
            'items.*.qty'   => 'required|numeric|min:0.01',
            'items.*.unit'  => 'required|string|max:50',
            'items.*.count' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string|max:500',
            'address_id'    => 'nullable|integer',
            'latitude'      => 'nullable|numeric|between:-90,90',
            'longitude'     => 'nullable|numeric|between:-180,180',
            'address'       => 'nullable|string|max:500',
            'street_number' => 'nullable|string|max:100',
            'house'         => 'nullable|string|max:100',
            'floor'         => 'nullable|string|max:50',
            'images.*'      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 422);
        }

        try {
            $groceryList = GroceryList::create([
                'user_id'       => $user->id,
                'zone_id'       => $user->zone_id,
                'title'         => $request->input('title') ?? 'Grocery List - ' . now()->format('d M Y'),
                'description'   => $request->input('description'),
                'status'        => 'processing',
                'address_id'    => $request->input('address_id'),
                'latitude'      => $request->input('latitude'),
                'longitude'     => $request->input('longitude'),
                'address'       => $request->input('address'),
                'street_number' => $request->input('street_number'),
                'house'         => $request->input('house'),
                'floor'         => $request->input('floor'),
            ]);

            if ($request->hasFile('images')) {
                $imageNames = [];
                foreach ($request->file('images') as $index => $image) {
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->storeAs('public/grocery-list', $imageName);
                    $fileUrl = asset('public/storage/grocery-list/' . $imageName);

                    GroceryImage::create([
                        'grocery_list_id' => $groceryList->id,
                        'original_name'   => $image->getClientOriginalName(),
                        'file_path'       => 'grocery-list/' . $imageName,
                        'file_url'        => $fileUrl,
                        'mime_type'       => $image->getClientMimeType(),
                        'file_size'       => $image->getSize(),
                        'sort_order'      => $index,
                    ]);

                    $imageNames[] = $imageName;
                }
                $groceryList->update(['images' => $imageNames]);
            }

            foreach ($request->input('items') as $index => $item) {
                GroceryListItem::create([
                    'grocery_list_id' => $groceryList->id,
                    'item_name'       => $item['name'],
                    'count'           => $item['count'] ?? null,
                    'quantity'        => (float) $item['qty'],
                    'unit'            => $item['unit'],
                    'rate'            => null,
                    'total_amount'    => null,
                    'notes'           => $item['notes'] ?? null,
                    'sort_order'      => $index,
                ]);
            }

            $groceryList = $groceryList->load(['items', 'image_records', 'delivery_man']);

            return response()->json([
                'key'     => 1,
                'message' => 'Grocery list created successfully. Admin will assign a delivery person.',
                'data'    => $this->formatList($groceryList),
                'ui_config' => [
                    'order_type'    => 'bestie_buddy',
                    'screen'        => 'bestie_order_detail',
                    'theme_color'   => '#FF6B35',
                    'icon_type'     => 'grocery',
                    'show_tracking' => false,
                    'status_label'  => 'Looking for Delivery Man',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'key'    => 0,
                'debug'  => $e->getMessage(),
                'file'   => $e->getFile(),
                'line'   => $e->getLine(),
                'errors' => [
                    ['code' => 'server_error', 'message' => 'An error occurred while creating the grocery list.']
                ]
            ], 500);
        }
    }

    // =========================================================================
    //  GET  /customer/grocery-list/index
    // =========================================================================
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->input('per_page', 10);
            $status  = $request->input('status');

            $query = GroceryList::with(['items', 'image_records', 'delivery_man'])
                ->orderBy('created_at', 'desc');

            if ($request->has('user_id')) {
                $query->where('user_id', $request->input('user_id'));
            }

            if ($status && in_array($status, ['processing', 'assigned', 'completed', 'cancelled'])) {
                $query->where('status', $status);
            }

            $lists = $query->paginate($perPage);

            return response()->json([
                'key'     => 1,
                'message' => 'Grocery lists fetched successfully.',
                'data'    => [
                    'lists'        => collect($lists->items())->map(fn($list) => $this->formatListSummary($list)),
                    'total'        => $lists->total(),
                    'per_page'     => $lists->perPage(),
                    'current_page' => $lists->currentPage(),
                    'last_page'    => $lists->lastPage(),
                    'has_more'     => $lists->hasMorePages(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'key'    => 0,
                'errors' => [['code' => 'server_error', 'message' => 'An error occurred while fetching grocery lists.']],
            ], 500);
        }
    }

    // =========================================================================
    //  GET  /customer/grocery-list/show/{id}
    // =========================================================================
    public function show(Request $request, $id)
    {
        try {
            $user = auth('api')->user();

            $groceryList = GroceryList::with(['items', 'image_records', 'delivery_man'])
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$groceryList) {
                return response()->json([
                    'key'    => 0,
                    'errors' => [['code' => 'not_found', 'message' => 'Grocery list not found.']],
                ], 404);
            }

            return response()->json([
                'key'     => 1,
                'message' => 'Grocery list details fetched successfully.',
                'data'    => $this->formatList($groceryList),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'key'    => 0,
                'errors' => [['code' => 'server_error', 'message' => 'An error occurred while fetching grocery list details.']],
            ], 500);
        }
    }

    // =========================================================================
    //  POST  /customer/grocery-list/{id}/update-items
    //
    //  Customer can:
    //    - Edit existing items      (items[])
    //    - Add new items            (new_items[])
    //    - Delete items             (deleted_item_ids[])
    //    - Add new images           (new_images[])
    //    - Delete existing images   (deleted_image_ids[])
    //
    //  Only allowed when status = 'processing' (not yet assigned)
    // =========================================================================
    public function updateItems(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'errors' => [['code' => 'unauthorized', 'message' => 'Unauthorized.']]
            ], 401);
        }

        $groceryList = GroceryList::with(['items', 'image_records'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$groceryList) {
            return response()->json([
                'key'    => 0,
                'errors' => [['code' => 'not_found', 'message' => 'Grocery list not found.']],
            ], 404);
        }

        // Only allow edit while still in processing
        if ($groceryList->status !== 'processing') {
            return response()->json([
                'key'    => 0,
                'errors' => [[
                    'code'    => 'not_allowed',
                    'message' => 'Order can only be edited while in processing status. Current status: ' . $groceryList->status,
                ]],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            // Existing item edits: items[{id}][name], items[{id}][qty], etc.
            'items'                      => 'nullable|array',
            'items.*.id'                 => 'required|integer',
            'items.*.name'               => 'required|string|max:255',
            'items.*.qty'                => 'nullable|numeric|min:0',
            'items.*.unit'               => 'nullable|string|max:50',
            'items.*.count'              => 'nullable|string|max:50',
            // New items to add
            'new_items'                  => 'nullable|array',
            'new_items.*.name'           => 'required|string|max:255',
            'new_items.*.qty'            => 'required|numeric|min:0.01',
            'new_items.*.unit'           => 'required|string|max:50',
            'new_items.*.count'          => 'nullable|string|max:50',
            // IDs to delete
            'deleted_item_ids'           => 'nullable|array',
            'deleted_item_ids.*'         => 'integer',
            // New images
            'new_images'                 => 'nullable|array',
            'new_images.*'               => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            // Image IDs to delete
            'deleted_image_ids'          => 'nullable|array',
            'deleted_image_ids.*'        => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => Helpers::error_processor($validator)
            ], 422);
        }

        DB::beginTransaction();
        try {
            // ── Delete items ──────────────────────────────────────────────────
            if ($request->filled('deleted_item_ids')) {
                GroceryListItem::whereIn('id', $request->input('deleted_item_ids'))
                    ->where('grocery_list_id', $groceryList->id)
                    ->delete();
            }

            // ── Edit existing items ───────────────────────────────────────────
            if ($request->filled('items')) {
                foreach ($request->input('items') as $itemData) {
                    GroceryListItem::where('id', $itemData['id'])
                        ->where('grocery_list_id', $groceryList->id)
                        ->update([
                            'item_name' => $itemData['name'],
                            'quantity'  => isset($itemData['qty'])   ? (float) $itemData['qty'] : null,
                            'unit'      => $itemData['unit']  ?? null,
                            'count'     => $itemData['count'] ?? null,
                        ]);
                }
            }

            // ── Add new items ─────────────────────────────────────────────────
            if ($request->filled('new_items')) {
                $maxSort = GroceryListItem::where('grocery_list_id', $groceryList->id)->max('sort_order') ?? 0;
                foreach ($request->input('new_items') as $itemData) {
                    if (empty($itemData['name'])) continue;
                    $maxSort++;
                    GroceryListItem::create([
                        'grocery_list_id' => $groceryList->id,
                        'item_name'       => $itemData['name'],
                        'quantity'        => (float) $itemData['qty'],
                        'unit'            => $itemData['unit']  ?? null,
                        'count'           => $itemData['count'] ?? null,
                        'sort_order'      => $maxSort,
                    ]);
                }
            }

            // ── Delete images ─────────────────────────────────────────────────
            if ($request->filled('deleted_image_ids')) {
                $toDelete = GroceryImage::whereIn('id', $request->input('deleted_image_ids'))
                    ->where('grocery_list_id', $groceryList->id)
                    ->get();
                foreach ($toDelete as $img) {
                    if ($img->file_path && !str_starts_with($img->file_url, 'http')) {
                        @unlink(public_path($img->file_path));
                    }
                    $img->delete();
                }
            }

            // ── Add new images ────────────────────────────────────────────────
            if ($request->hasFile('new_images')) {
                $maxImgSort = GroceryImage::where('grocery_list_id', $groceryList->id)->max('sort_order') ?? 0;
                foreach ($request->file('new_images') as $image) {
                    if (!$image->isValid()) continue;
                    $maxImgSort++;
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->storeAs('public/grocery-list', $imageName);
                    $fileUrl = asset('public/storage/grocery-list/' . $imageName);

                    GroceryImage::create([
                        'grocery_list_id' => $groceryList->id,
                        'original_name'   => $image->getClientOriginalName(),
                        'file_path'       => 'grocery-list/' . $imageName,
                        'file_url'        => $fileUrl,
                        'mime_type'       => $image->getClientMimeType(),
                        'file_size'       => $image->getSize(),
                        'sort_order'      => $maxImgSort,
                    ]);
                }
            }

            DB::commit();

            $groceryList = $groceryList->fresh(['items', 'image_records', 'delivery_man']);

            return response()->json([
                'key'     => 1,
                'message' => 'Grocery list updated successfully.',
                'data'    => $this->formatList($groceryList),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'key'    => 0,
                'debug'  => $e->getMessage(),
                'errors' => [['code' => 'server_error', 'message' => 'An error occurred while updating the grocery list.']],
            ], 500);
        }
    }

    // =========================================================================
    //  PUT  /delivery-man/grocery-list/{id}/confirm-price
    // =========================================================================
public function confirmPrice(Request $request, $id)
{
    $token = $request->input('token') ?? $request->bearerToken();
    $dm    = DeliveryMan::where('auth_token', $token)->first();

    if (!$dm) {
        return response()->json([
            'errors' => [
                ['code' => 'unauthorized', 'message' => 'Invalid delivery man token.']
            ]
        ], 401);
    }

    $validator = Validator::make($request->all(), [
        'items_cost'      => 'nullable|numeric|min:0.01',
        'delivery_charge' => 'nullable|numeric|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => Helpers::error_processor($validator)
        ], 422);
    }

    try {
        $groceryList = GroceryList::with(['items','image_records','delivery_man'])
            ->where('id', $id)
            ->first();

        if (!$groceryList) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Order not found']]
            ], 404);
        }

        if ((int)$groceryList->delivery_man_id !== (int)$dm->id) {
            return response()->json([
                'errors' => [['code' => 'forbidden', 'message' => 'Not your order']]
            ], 403);
        }

        if ($groceryList->is_price_confirmed) {
            return response()->json([
                'errors' => [['code' => 'already_confirmed', 'message' => 'Already confirmed']]
            ], 422);
        }

        // =========================
        // STEP 1: HANDLE DELIVERY CHARGE
        // =========================
        $rawCharge   = $request->input('delivery_charge');
        $customCharge = ($rawCharge === null || $rawCharge === '')
            ? null
            : (float)$rawCharge;

        if ($request->has('delivery_charge')) {
            $groceryList->update([
                'custom_delivery_charge' => $customCharge
            ]);
        }

        // =========================
        // STEP 2: HANDLE ITEMS COST + FINAL CALCULATION
        // =========================
        $itemsCost = $request->input('items_cost') !== null
            ? (float)$request->input('items_cost')
            : $groceryList->items_cost;

        $settings = BestieBuddySetting::current();

        $charges = null;

        if ($itemsCost !== null) {
            $charges = $settings->calculateCharges($itemsCost, $customCharge);

            $groceryList->update([
                'items_cost'      => $itemsCost,
                'delivery_charge' => $charges['deliveryCharge'],
                'service_charge'  => $charges['serviceCharge'],
                'gst_amount'      => $charges['gstAmount'],
                'total_amount'    => $charges['totalAmount'],
            ]);
        }

        // =========================
        // STEP 3: CONFIRM PRICE (optional)
        // =========================
        $confirmed = false;

        if ($request->has('items_cost')) {
            $groceryList->update([
                'is_price_confirmed' => true,
                'price_confirmed_at' => now(),
                'status'             => 'out_for_delivery',
            ]);

            BestieOrderLogic::notifyCustomerPriceConfirmed($groceryList);
            $confirmed = true;
        }

        $groceryList = $groceryList->fresh(['items','image_records','delivery_man']);

        return response()->json([
            'key'     => 1,
            'message' => $confirmed
                ? 'Price confirmed successfully'
                : 'Delivery charge updated successfully',

            'custom_delivery_charge' => $customCharge,

            'pricing_summary' => $charges ? [
                'items_cost'      => $itemsCost,
                'delivery_charge' => $charges['deliveryCharge'],
                'service_charge'  => $charges['serviceCharge'],
                'gst_amount'      => $charges['gstAmount'],
                'total_amount'    => $charges['totalAmount'],
                'delivery_mode'   => $customCharge !== null ? 'custom' : 'global',
            ] : null,

            'data' => $this->formatList($groceryList),
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'errors' => [
                ['code' => 'server_error', 'message' => $e->getMessage()]
            ]
        ], 500);
    }
}
    // =========================================================================
    //  POST  /delivery-man/grocery-list/{id}/update-delivery-charge
    //
    //  Delivery man sets custom delivery charge BEFORE confirming price.
    //  This stores it on the order so admin can see it, and it will be
    //  used when confirm-price is called.
    //
    //  Rules:
    //    - Only assigned DM can call this
    //    - Price must NOT be confirmed yet
    //    - amount replaces global % — it does NOT add on top
    // =========================================================================
   public function updateDeliveryCharge(Request $request, $id)
{
    $token = $request->input('token') ?? $request->bearerToken();
    $dm    = DeliveryMan::where('auth_token', $token)->first();
 
    if (!$dm) {
        return response()->json([
            'errors' => [['code' => 'unauthorized', 'message' => 'Unauthorized. Invalid delivery man token.']]
        ], 401);
    }
 
    // ── FIX #1: nullable instead of required ───────────────────────────────
    // This allows the DM app to send an empty value to clear the override
    // and revert back to the global delivery percentage.
    $validator = Validator::make($request->all(), [
        'delivery_charge' => 'nullable|numeric|min:0',
    ]);
 
    if ($validator->fails()) {
        return response()->json([
            'errors' => Helpers::error_processor($validator)
        ], 422);
    }
 
    try {
        $groceryList = GroceryList::where('id', $id)->first();
 
        if (!$groceryList) {
            return response()->json([
                'key'    => 0,
                'errors' => [['code' => 'not_found', 'message' => 'Grocery list not found.']],
            ], 404);
        }
 
        if ((int) $groceryList->delivery_man_id !== (int) $dm->id) {
            return response()->json([
                'key'    => 0,
                'errors' => [['code' => 'forbidden', 'message' => 'You are not assigned to this order.']],
            ], 403);
        }
 
        if ($groceryList->is_price_confirmed) {
            return response()->json([
                'key'    => 0,
                'errors' => [[
                    'code'    => 'already_confirmed',
                    'message' => 'Price is already confirmed. Delivery charge cannot be changed.',
                ]],
            ], 422);
        }
 
        // ── FIX #2: empty/null = remove override, NOT zero override ───────
        $rawCharge = $request->input('delivery_charge');
        $customCharge = ($rawCharge === null || $rawCharge === '')
            ? null
            : (float) $rawCharge;
 
        // Store the custom charge — this REPLACES global %, not adds to it.
        // null => order goes back to using the global percentage setting.
        $groceryList->update(['custom_delivery_charge' => $customCharge]);
 
        // ── FIX #3: persist recalculated charges immediately ───────────────
        // Previously this block only built a $preview array and returned it
        // in the response WITHOUT saving to the grocery_lists table. That
        // meant the admin "Order Summary" page (which reads straight from
        // delivery_charge / service_charge / gst_amount / total_amount
        // columns) kept showing stale numbers until confirm-price was
        // eventually called. Now we save it right away.
        $preview = null;
        if ($groceryList->items_cost !== null) {
            $settings  = BestieBuddySetting::current();
            $itemsCost = (float) $groceryList->items_cost;
            $charges   = $settings->calculateCharges($itemsCost, $customCharge);
 
            $groceryList->update([
                'delivery_charge' => $charges['deliveryCharge'],
                'service_charge'  => $charges['serviceCharge'],
                'gst_amount'      => $charges['gstAmount'],
                'total_amount'    => $charges['totalAmount'],
            ]);
 
            $preview = [
                'items_cost'      => $itemsCost,
                'delivery_charge' => $charges['deliveryCharge'],
                'service_charge'  => $charges['serviceCharge'],
                'gst_amount'      => $charges['gstAmount'],
                'total_amount'    => $charges['totalAmount'],
            ];
        }
 
        return response()->json([
            'key'     => 1,
            'message' => $customCharge !== null
                ? 'Custom delivery charge saved. It will be applied when you confirm the price.'
                : 'Custom delivery charge removed. Global delivery percentage will apply.',
            'custom_delivery_charge' => $customCharge,
            'preview_charges'        => $preview,
            'note'    => 'This flat amount replaces the global delivery % — it is not added on top.',
        ], 200);
 
    } catch (\Exception $e) {
        return response()->json([
            'key'    => 0,
            'debug'  => $e->getMessage(),
            'errors' => [['code' => 'server_error', 'message' => 'An error occurred.']],
        ], 500);
    }
}
    // =========================================================================
    //  PRIVATE HELPERS
    // =========================================================================

    private function formatList(GroceryList $list): array
    {
        $items = $list->items->map(fn($item) => [
            'id'           => $item->id,
            'item_name'    => $item->item_name,
            'count'        => $item->count,
            'quantity'     => (float) $item->quantity,
            'unit'         => $item->unit,
            'rate'         => $item->rate !== null ? (float) $item->rate : null,
            'total_amount' => $item->total_amount !== null ? (float) $item->total_amount : null,
            'notes'        => $item->notes,
            'sort_order'   => $item->sort_order,
        ]);

        $images = $list->image_records->map(fn($img) => [
            'id'            => $img->id,
            'original_name' => $img->original_name,
            'file_url'      => $img->file_url,
            'mime_type'     => $img->mime_type,
            'file_size'     => $img->file_size,
            'sort_order'    => $img->sort_order,
        ]);

        $deliveryMan = null;
        if ($list->delivery_man) {
            $dm          = $list->delivery_man;
            $deliveryMan = [
                'id'         => $dm->id,
                'name'       => trim($dm->f_name . ' ' . $dm->l_name),
                'phone'      => $dm->phone,
                'image'      => $dm->image ? asset('storage/' . $dm->image) : null,
                'avg_rating' => $dm->avg_rating ?? null,
                'vehicle'    => isset($dm->vehicle) ? [
                    'type'  => $dm->vehicle->type ?? null,
                    'model' => $dm->vehicle->model ?? null,
                ] : null,
            ];
        }

        $deliveryAddress = null;
        if ($list->address || $list->latitude) {
            $deliveryAddress = [
                'address_id'    => $list->address_id,
                'latitude'      => $list->latitude ? (float) $list->latitude : null,
                'longitude'     => $list->longitude ? (float) $list->longitude : null,
                'address'       => $list->address,
                'street_number' => $list->street_number,
                'house'         => $list->house,
                'floor'         => $list->floor,
            ];
        }

        // Pricing block
        if ($list->is_price_confirmed) {
            $pricing = [
                'status'             => 'confirmed',
                'status_message'     => 'Price has been confirmed by delivery man.',
                'is_price_confirmed' => true,
                'price_confirmed_at' => Carbon::parse($list->price_confirmed_at)->toIso8601String(),
                'items_cost'         => $list->items_cost !== null ? (float) $list->items_cost : null,
                'delivery_charge'    => $list->delivery_charge !== null ? (float) $list->delivery_charge : null,
                'service_charge'     => $list->service_charge !== null ? (float) $list->service_charge : null,
                'gst_amount'         => $list->gst_amount !== null ? (float) $list->gst_amount : null,
                'total_amount'       => $list->total_amount !== null ? (float) $list->total_amount : null,
                // Let customer know if admin/DM set a special delivery charge
                'delivery_mode'      => $list->custom_delivery_charge !== null ? 'custom' : 'standard',
            ];
        } else {
            $pricing = [
                'status'             => 'pending',
                'status_message'     => 'Price not updated yet. Waiting for delivery man confirmation.',
                'is_price_confirmed' => false,
                'price_confirmed_at' => null,
                'items_cost'         => null,
                'delivery_charge'    => null,
                'service_charge'     => null,
                'gst_amount'         => null,
                'total_amount'       => null,
                'delivery_mode'      => null,
            ];
        }

        return [
            'id'               => $list->id,
            'title'            => $list->title,
            'description'      => $list->description,
            'status'           => $list->status,
            'status_label'     => $this->statusLabel($list->status),
            // Customer can edit items/images only while processing
            'can_edit'         => $list->status === 'processing',
            'items'            => $items,
            'images'           => $images,
            'items_count'      => $list->items->count(),
            'delivery_address' => $deliveryAddress,
            'pricing'          => $pricing,
            'delivery_man'     => $deliveryMan,
            'assigned_at'      => $list->assigned_at
                ? Carbon::parse($list->assigned_at)->toIso8601String()
                : null,
            'completed_at'     => $list->completed_at
                ? Carbon::parse($list->completed_at)->toIso8601String()
                : null,
            'created_at'       => $list->created_at->toIso8601String(),
            'updated_at'       => $list->updated_at->toIso8601String(),
        ];
    }

    private function formatListSummary(GroceryList $list): array
    {
        $firstImage = $list->image_records->sortBy('sort_order')->first();

        return [
            'id'                 => $list->id,
            'title'              => $list->title,
            'description'        => $list->description,
            'status'             => $list->status,
            'status_label'       => $this->statusLabel($list->status),
            'can_edit'           => $list->status === 'processing',
            'items_count'        => $list->items->count(),
            'total_amount'       => $list->total_amount !== null ? (float) $list->total_amount : null,
            'is_price_confirmed' => (bool) $list->is_price_confirmed,
            'pricing_status'     => $list->is_price_confirmed ? 'confirmed' : 'pending',
            'thumb_image'        => $firstImage ? $firstImage->file_url : null,
            'address'            => $list->address,
            'delivery_man'       => $list->delivery_man ? [
                'id'    => $list->delivery_man->id,
                'name'  => trim($list->delivery_man->f_name . ' ' . $list->delivery_man->l_name),
                'phone' => $list->delivery_man->phone,
                'image' => $list->delivery_man->image
                    ? asset('storage/' . $list->delivery_man->image)
                    : null,
            ] : null,
            'created_at' => $list->created_at->toIso8601String(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'processing'       => 'Processing',
            'assigned'         => 'Delivery Man Assigned',
            'out_for_delivery' => 'Out for Delivery',
            'completed'        => 'Completed',
            'cancelled'        => 'Cancelled',
            default            => ucfirst($status),
        };
    }
    public function dmUpdateItems(Request $request, $id)
{
    // ── Auth: DM token ────────────────────────────────────────────────────
    $token = $request->input('token') ?? $request->bearerToken();
    $dm    = DeliveryMan::where('auth_token', $token)->first();

    if (!$dm) {
        return response()->json([
            'errors' => [['code' => 'unauthorized', 'message' => 'Unauthorized. Invalid delivery man token.']]
        ], 401);
    }

    // ── Find the grocery list ─────────────────────────────────────────────
    $groceryList = GroceryList::with(['items', 'image_records', 'delivery_man'])
        ->where('id', $id)
        ->first();

    if (!$groceryList) {
        return response()->json([
            'key'    => 0,
            'errors' => [['code' => 'not_found', 'message' => 'Grocery list not found.']],
        ], 404);
    }

    // ── Only the assigned DM can edit ─────────────────────────────────────
    if ((int) $groceryList->delivery_man_id !== (int) $dm->id) {
        return response()->json([
            'key'    => 0,
            'errors' => [['code' => 'forbidden', 'message' => 'You are not assigned to this order.']],
        ], 403);
    }

    // ── Only allowed before price is confirmed ────────────────────────────
    if ($groceryList->is_price_confirmed) {
        return response()->json([
            'key'    => 0,
            'errors' => [[
                'code'    => 'not_allowed',
                'message' => 'Items cannot be edited after price has been confirmed.',
            ]],
        ], 422);
    }

    // ── Only allowed when assigned or out_for_delivery ────────────────────
    $allowedStatuses = ['assigned', 'out_for_delivery'];
    if (!in_array($groceryList->status, $allowedStatuses)) {
        return response()->json([
            'key'    => 0,
            'errors' => [[
                'code'    => 'not_allowed',
                'message' => 'Items can only be edited when order is assigned or out for delivery. Current status: ' . $groceryList->status,
            ]],
        ], 422);
    }

    // ── Clean empty values from delete arrays BEFORE validation ───────────
    $data = $request->all();

    if (isset($data['deleted_item_ids'])) {
        $data['deleted_item_ids'] = array_values(
            array_filter($data['deleted_item_ids'], fn($v) => is_numeric($v) && (int) $v > 0)
        );
    }

    if (isset($data['deleted_image_ids'])) {
        $data['deleted_image_ids'] = array_values(
            array_filter($data['deleted_image_ids'], fn($v) => is_numeric($v) && (int) $v > 0)
        );
    }

    // ── Validate ──────────────────────────────────────────────────────────
    $validator = Validator::make($data, [
        // Edit existing items
        'items'               => 'nullable|array',
        'items.*.id'          => 'required|integer',
        'items.*.name'        => 'required|string|max:255',
        'items.*.qty'         => 'nullable|numeric|min:0',
        'items.*.unit'        => 'nullable|string|max:50',
        'items.*.count'       => 'nullable|string|max:50',
        'items.*.notes'       => 'nullable|string|max:500',
        // Add new items
        'new_items'           => 'nullable|array',
        'new_items.*.name'    => 'required|string|max:255',
        'new_items.*.qty'     => 'required|numeric|min:0.01',
        'new_items.*.unit'    => 'required|string|max:50',
        'new_items.*.count'   => 'nullable|string|max:50',
        'new_items.*.notes'   => 'nullable|string|max:500',
        // Delete items
        'deleted_item_ids'    => 'nullable|array',
        'deleted_item_ids.*'  => 'integer',
        // New images
        'new_images'          => 'nullable|array',
        'new_images.*'        => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        // Delete images
        'deleted_image_ids'   => 'nullable|array',
        'deleted_image_ids.*' => 'integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => Helpers::error_processor($validator)
        ], 422);
    }

    DB::beginTransaction();
    try {
        // ── Delete items ──────────────────────────────────────────────────
        if (!empty($data['deleted_item_ids'])) {
            GroceryListItem::whereIn('id', $data['deleted_item_ids'])
                ->where('grocery_list_id', $groceryList->id)
                ->delete();
        }

        // ── Edit existing items ───────────────────────────────────────────
        if (!empty($data['items'])) {
            foreach ($data['items'] as $itemData) {
                GroceryListItem::where('id', $itemData['id'])
                    ->where('grocery_list_id', $groceryList->id)
                    ->update([
                        'item_name' => $itemData['name'],
                        'quantity'  => isset($itemData['qty']) ? (float) $itemData['qty'] : null,
                        'unit'      => $itemData['unit']  ?? null,
                        'count'     => $itemData['count'] ?? null,
                        'notes'     => $itemData['notes'] ?? null,
                    ]);
            }
        }

        // ── Add new items (customer requested extras over call) ───────────
        if (!empty($data['new_items'])) {
            $maxSort = GroceryListItem::where('grocery_list_id', $groceryList->id)
                ->max('sort_order') ?? 0;

            foreach ($data['new_items'] as $itemData) {
                if (empty($itemData['name'])) continue;
                $maxSort++;
                GroceryListItem::create([
                    'grocery_list_id' => $groceryList->id,
                    'item_name'       => $itemData['name'],
                    'quantity'        => (float) $itemData['qty'],
                    'unit'            => $itemData['unit']  ?? null,
                    'count'           => $itemData['count'] ?? null,
                    'notes'           => $itemData['notes'] ?? null,
                    'rate'            => null,
                    'total_amount'    => null,
                    'sort_order'      => $maxSort,
                    'added_by'        => 'delivery_man',
                ]);
            }
        }

        // ── Delete images ─────────────────────────────────────────────────
        if (!empty($data['deleted_image_ids'])) {
            $toDelete = GroceryImage::whereIn('id', $data['deleted_image_ids'])
                ->where('grocery_list_id', $groceryList->id)
                ->get();
            foreach ($toDelete as $img) {
                if ($img->file_path && !str_starts_with($img->file_url, 'http')) {
                    @unlink(public_path($img->file_path));
                }
                $img->delete();
            }
        }

        // ── Add new images ────────────────────────────────────────────────
        if ($request->hasFile('new_images')) {
            $maxImgSort = GroceryImage::where('grocery_list_id', $groceryList->id)
                ->max('sort_order') ?? 0;

            foreach ($request->file('new_images') as $image) {
                if (!$image->isValid()) continue;
                $maxImgSort++;
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('public/grocery-list', $imageName);
                $fileUrl = asset('public/storage/grocery-list/' . $imageName);

                GroceryImage::create([
                    'grocery_list_id' => $groceryList->id,
                    'original_name'   => $image->getClientOriginalName(),
                    'file_path'       => 'grocery-list/' . $imageName,
                    'file_url'        => $fileUrl,
                    'mime_type'       => $image->getClientMimeType(),
                    'file_size'       => $image->getSize(),
                    'sort_order'      => $maxImgSort,
                    'added_by'        => 'delivery_man',
                ]);
            }
        }

        DB::commit();

        $groceryList = $groceryList->fresh(['items', 'image_records', 'delivery_man']);

        return response()->json([
            'key'     => 1,
            'message' => 'Items updated successfully.',
            'note'    => 'These changes were made by the delivery man based on customer request.',
            'data'    => $this->formatList($groceryList),
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'key'    => 0,
            'debug'  => $e->getMessage(),
            'errors' => [['code' => 'server_error', 'message' => 'An error occurred while updating items.']],
        ], 500);
    }
}

}