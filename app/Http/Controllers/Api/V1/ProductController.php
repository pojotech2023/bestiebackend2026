<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\CentralLogics\RestaurantLogic;
use App\Http\Controllers\Controller;
use App\Models\Food;
use App\Models\Restaurant;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function get_latest_products(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required',
            'category_id'   => 'required',
            'limit'         => 'required',
            'offset'        => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $type = $request->query('type', 'all');

        $products = ProductLogic::get_latest_products(
            limit:         $request['limit'],
            offset:        $request['offset'],
            restaurant_id: $request['restaurant_id'],
            category_id:   $request['category_id'],
            type:          $type
        );

        // ✅ Admin charge inflate — price-la % add agum
        $products['products'] = Helpers::inflate_price_with_admin_charge(
            $products['products'], true
        );

        $products['products'] = Helpers::product_data_formatting(
            data:       $products['products'],
            multi_data: true,
            trans:      false,
            local:      app()->getLocale()
        );

        return response()->json($products, 200);
    }

    public function get_searched_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            return response()->json([
                'errors' => [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]]
            ], 403);
        }

        $zone_id = json_decode($request->header('zoneId'), true);
        $key     = isset($request['name']) && $request['name'] != 'null'
                    ? explode(' ', $request['name'])
                    : null;

        $limit  = $request['limit']  ?? 10;
        $offset = $request['offset'] ?? 1;
        $type   = $request->query('type', 'all');

        $products = Food::active()->type($type)
            ->whereHas('restaurant', function ($q) use ($zone_id) {
                $q->whereIn('zone_id', $zone_id);
            })
            ->when($request->category_id, function ($query) use ($request) {
                $query->whereHas('category', function ($q) use ($request) {
                    return $q->whereId($request->category_id)
                             ->orWhere('parent_id', $request->category_id);
                });
            })
            ->when($request->restaurant_id, function ($query) use ($request) {
                return $query->where('restaurant_id', $request->restaurant_id);
            })
            ->when($request->new == 1, function ($query) {
                return $query->latest();
            })
            ->when($request->popular == 1, function ($query) {
                return $query->popular();
            })
            ->when($request->rating == 1, function ($query) {
                return $query->has('reviews')->withCount('reviews')->orderBy('reviews_count', 'desc');
            })
            ->when($request->discounted == 1, function ($query) {
                return $query->where(function ($q) {
                    $q->Has('restaurant.discount')->orwhere('discount', '>', 0);
                });
            })
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->orWhere('name', 'like', "%{$value}%");
                    }
                    $q->orWhereHas('translations', function ($query) use ($key) {
                        $query->where(function ($q) use ($key) {
                            foreach ($key as $value) {
                                $q->where('value', 'like', "%{$value}%");
                            }
                        });
                    });
                    $q->orWhereHas('tags', function ($query) use ($key) {
                        $query->where(function ($q1) use ($key) {
                            foreach ($key as $value) {
                                $q1->where('tag', 'like', "%{$value}%");
                            }
                        });
                    })
                    ->orWhereHas('restaurant.cuisine', function ($query) use ($key) {
                        $query->where(function ($q2) use ($key) {
                            foreach ($key as $value) {
                                $q2->where('name', 'like', "%{$value}%");
                            }
                        });
                    });
                });
            })
            ->when(isset($request->sort_by) && ($request->sort_by == 'asc' || $request->sort_by == 'desc'), function ($query) use ($request) {
                return $query->orderBy('name', $request->sort_by);
            })
            ->when(isset($request->sort_by) && $request->sort_by == 'low', function ($query) {
                return $query->orderBy('price', 'asc');
            })
            ->when(isset($request->sort_by) && $request->sort_by == 'high', function ($query) {
                return $query->orderBy('price', 'desc');
            })
            ->paginate($limit, ['*'], 'page', $offset);

        $data = [
            'total_size' => $products->total(),
            'limit'      => $limit,
            'offset'     => $offset,
            'products'   => $products->items(),
        ];

        // ✅ Admin charge inflate
        $data['products'] = Helpers::inflate_price_with_admin_charge($data['products'], true);

        $data['products'] = Helpers::product_data_formatting(
            data:       $data['products'],
            multi_data: true,
            trans:      false,
            local:      app()->getLocale()
        );

        return response()->json($data, 200);
    }

    public function get_popular_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            return response()->json([
                'errors' => [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]]
            ], 403);
        }

        $type     = $request->query('type', 'all');
        $zone_id  = json_decode($request->header('zoneId'), true);
        $products = ProductLogic::popular_products(
            zone_id: $zone_id,
            limit:   $request['limit'],
            offset:  $request['offset'],
            type:    $type
        );

        // ✅ Admin charge inflate
        $products['products'] = Helpers::inflate_price_with_admin_charge($products['products'], true);

        $products['products'] = Helpers::product_data_formatting(
            data:       $products['products'],
            multi_data: true,
            trans:      false,
            local:      app()->getLocale()
        );

        return response()->json($products, 200);
    }

    public function get_most_reviewed_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            return response()->json([
                'errors' => [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]]
            ], 403);
        }

        $type     = $request->query('type', 'all');
        $zone_id  = json_decode($request->header('zoneId'), true);
        $products = ProductLogic::most_reviewed_products(
            zone_id: $zone_id,
            limit:   $request['limit'],
            offset:  $request['offset'],
            type:    $type
        );

        // ✅ Admin charge inflate
        $products['products'] = Helpers::inflate_price_with_admin_charge($products['products'], true);

        $products['products'] = Helpers::product_data_formatting(
            data:       $products['products'],
            multi_data: true,
            trans:      false,
            local:      app()->getLocale()
        );

        return response()->json($products, 200);
    }

    public function get_product($id)
    {
        try {
            $product = ProductLogic::get_product($id);

            // ✅ Admin charge inflate — single product
            $product = Helpers::inflate_price_with_admin_charge($product, false);

            $product = Helpers::product_data_formatting(
                data:       $product,
                multi_data: false,
                trans:      false,
                local:      app()->getLocale()
            );

            return response()->json($product, 200);
        } catch (\Exception $e) {
            info($e->getMessage());
            return response()->json([
                'errors' => ['code' => 'product-001', 'message' => translate('messages.not_found')]
            ], 404);
        }
    }

    public function get_related_products($id)
    {
        if (Food::find($id)) {
            $products = ProductLogic::get_related_products(product_id: $id);

            // ✅ Admin charge inflate
            $products = Helpers::inflate_price_with_admin_charge($products, true);

            $products = Helpers::product_data_formatting(
                data:       $products,
                multi_data: true,
                trans:      false,
                local:      app()->getLocale()
            );

            return response()->json($products, 200);
        }
        return response()->json([
            'errors' => ['code' => 'product-001', 'message' => translate('messages.not_found')]
        ], 404);
    }

    public function get_set_menus()
    {
        try {
            $raw = Food::active()->with(['rating'])->where(['set_menu' => 1, 'status' => 1])->get();

            // ✅ Admin charge inflate
            $raw = Helpers::inflate_price_with_admin_charge($raw, true);

            $products = Helpers::product_data_formatting(
                data:       $raw,
                multi_data: true,
                trans:      false,
                local:      app()->getLocale()
            );

            return response()->json($products, 200);
        } catch (\Exception $e) {
            info($e->getMessage());
            return response()->json([
                'errors' => ['code' => 'product-001', 'message' => 'Set menu not found!']
            ], 404);
        }
    }

    public function get_product_reviews($food_id)
    {
        $reviews = Review::with(['customer', 'food'])->where(['food_id' => $food_id])->active()->get();

        $storage = [];
        foreach ($reviews as $item) {
            $item['attachment'] = json_decode($item['attachment']);
            $item['food_name']  = null;
            if ($item?->food) {
                $item['food_name'] = $item?->food?->name;
                if (count($item?->food?->translations) > 0) {
                    $translate        = array_column($item->food->translations->toArray(), 'value', 'key');
                    $item['food_name'] = $translate['name'];
                }
            }
            unset($item['food']);
            array_push($storage, $item);
        }
        return response()->json($storage, 200);
    }

    public function get_product_rating($id)
    {
        try {
            $product       = Food::find($id);
            $overallRating = ProductLogic::get_overall_rating(reviews: $product->reviews);
            return response()->json(floatval($overallRating[0]), 200);
        } catch (\Exception $e) {
            return response()->json(['errors' => $e->getMessage()], 403);
        }
    }

    public function submit_product_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'food_id'       => 'required',
            'order_id'      => 'required',
            'comment'       => 'required',
            'rating'        => 'required|numeric|max:5',
            'attachment.*'  => 'nullable|max:2048',
        ]);

        $product = Food::find($request->food_id);
        if (isset($product) == false) {
            $validator->errors()->add('food_id', translate('messages.food_not_found'));
        }

        $multi_review = Review::where([
            'food_id' => $request->food_id,
            'user_id' => $request?->user()?->id,
            'order_id' => $request->order_id,
        ])->first();

        if (isset($multi_review)) {
            return response()->json([
                'errors' => [['code' => 'review', 'message' => translate('messages.already_submitted')]]
            ], 403);
        } else {
            $review = new Review;
        }

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $image_array = [];
        if (!empty($request->file('attachment'))) {
            foreach ($request->file('attachment') as $image) {
                if ($image != null) {
                    if (!Storage::disk('public')->exists('review')) {
                        Storage::disk('public')->makeDirectory('review');
                    }
                    array_push($image_array, Storage::disk('public')->put('review', $image));
                }
            }
        }

        $review->user_id    = $request?->user()?->id;
        $review->food_id    = $request->food_id;
        $review->order_id   = $request->order_id;
        $review->comment    = $request->comment;
        $review->rating     = $request->rating;
        $review->attachment = json_encode($image_array);
        $review->save();

        if ($product->restaurant) {
            $restaurant_rating = RestaurantLogic::update_restaurant_rating(
                ratings:        $product?->restaurant?->rating,
                product_rating: $request->rating
            );
            $product->restaurant->rating = $restaurant_rating;
            $product?->restaurant?->save();
        }

        $product->rating     = ProductLogic::update_rating(ratings: $product->rating, product_rating: $request->rating);
        $product->avg_rating = ProductLogic::get_avg_rating(rating: json_decode($product->rating, true));
        $product?->save();
        $product->increment('rating_count');

        return response()->json(['message' => translate('messages.review_submited_successfully')], 200);
    }

    public function get_recommended(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            return response()->json([
                'errors' => [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $type    = $request->query('type', 'all');
        $key     = explode(' ', $request['name']);
        $zone_id = json_decode($request->header('zoneId'), true);

        $products = ProductLogic::recommended_products(
            zone_id:       $zone_id,
            restaurant_id: $request->restaurant_id,
            limit:         $request['limit'],
            offset:        $request['offset'],
            type:          $type,
            name:          $key
        );

        // ✅ Admin charge inflate
        $products['products'] = Helpers::inflate_price_with_admin_charge($products['products'], true);

        $products['products'] = Helpers::product_data_formatting(
            data:       $products['products'],
            multi_data: true,
            trans:      false,
            local:      app()->getLocale()
        );

        return response()->json($products, 200);
    }

    public function food_or_restaurant_search(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            return response()->json([
                'errors' => [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]]
            ], 403);
        }

        if (!$request->hasHeader('longitude') || !$request->hasHeader('latitude')) {
            return response()->json([
                'errors' => [['code' => 'longitude-latitude', 'message' => translate('messages.longitude-latitude_required')]]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $zone_id   = json_decode($request->header('zoneId'), true);
        $longitude = $request->header('longitude');
        $latitude  = $request->header('latitude');
        $key       = explode(' ', $request->name);

        $foods = Food::active()->whereHas('restaurant', function ($q) use ($zone_id) {
            $q->whereIn('zone_id', $zone_id)->Weekday();
        })
        ->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%");
            }
            $q->orWhereHas('translations', function ($query) use ($key) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('value', 'like', "%{$value}%");
                    }
                });
            });
            $q->orWhereHas('tags', function ($query) use ($key) {
                $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('tag', 'like', "%{$value}%");
                    }
                });
            });
        })
        ->limit(50)
        ->get(['id', 'name', 'image']);

        $restaurants = Restaurant::withOpen($longitude, $latitude)
            ->whereIn('zone_id', $zone_id)
            ->weekday()
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
                $q->orwhereHas('cuisine', function ($query) use ($key) {
                    $query->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->where('name', 'like', "%{$value}%");
                        }
                    });
                });
            })
            ->active()
            ->limit(50)
            ->select(['id', 'name', 'logo'])
            ->get();

        return [
            'foods'       => $foods,
            'restaurants' => $restaurants,
        ];
    }

    public function get_restaurant_popular_products(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            return response()->json([
                'errors' => [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $type    = $request->query('type', 'all');
        $key     = explode(' ', $request['name']);
        $zone_id = json_decode($request->header('zoneId'), true);

        $products = ProductLogic::get_restaurant_popular_products(
            zone_id:       $zone_id,
            restaurant_id: $request->restaurant_id,
            type:          $type,
            name:          $key
        );

        // ✅ Admin charge inflate
        $products = Helpers::inflate_price_with_admin_charge($products, true);

        $products = Helpers::product_data_formatting(
            data:       $products,
            multi_data: true,
            trans:      false,
            local:      app()->getLocale()
        );

        return response()->json($products, 200);
    }

    public function recommended_most_reviewed(Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            return response()->json([
                'errors' => [['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]]
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'restaurant_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $type    = $request->query('type', 'all');
        $key     = explode(' ', $request['name']);
        $zone_id = json_decode($request->header('zoneId'), true);

        $products = ProductLogic::recommended_most_reviewed(
            zone_id:       $zone_id,
            restaurant_id: $request->restaurant_id,
            type:          $type,
            name:          $key
        );

        // ✅ Admin charge inflate
        $products = Helpers::inflate_price_with_admin_charge($products, true);

        $products = Helpers::product_data_formatting(
            data:       $products,
            multi_data: true,
            trans:      false,
            local:      app()->getLocale()
        );

        return response()->json($products, 200);
    }
}