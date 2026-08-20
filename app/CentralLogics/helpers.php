<?php
namespace App\CentralLogics;

use Exception;
use App\Models\Log;
use App\Models\User;
use App\Models\Zone;
use App\Models\AddOn;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Review;
use App\Models\Expense;
use App\Models\TimeLog;
use App\Models\Vehicle;
use App\Mail\PlaceOrder;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DMReview;
use App\Models\Restaurant;
use App\Models\VisitorLog;
use App\Models\DataSetting;
use App\Models\DeliveryMan;
use App\Models\Translation;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\BusinessSetting;
use App\Models\DeliveryManWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use App\Mail\OrderVerificationMail;
use App\Models\NotificationMessage;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\CentralLogics\RestaurantLogic;
use App\Models\RestaurantSubscription;
use Illuminate\Support\Facades\Config;
use App\Models\SubscriptionTransaction;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Models\GroceryList;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Laravelpkg\Laravelchk\Http\Controllers\LaravelchkController;
use App\CentralLogics\OrderLogic;
class Helpers
{
     
    public static function error_processor($validator)
    {
        $err_keeper = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            array_push($err_keeper, ['code' => $index, 'message' => $error[0]]);
        }
        return $err_keeper;
    }

    public static function error_formater($key, $mesage, $errors = [])
    {
        $errors[] = ['code' => $key, 'message' => $mesage];

        return $errors;
    }

    public static function schedule_order()
    {
        return (bool)BusinessSetting::where(['key' => 'schedule_order'])->first()?->value;
    }


    // public static function combinations($arrays)
    // {
    //     $result = [[]];
    //     foreach ($arrays as $property => $property_values) {
    //         $tmp = [];
    //         foreach ($result as $result_item) {
    //             foreach ($property_values as $property_value) {
    //                 $tmp[] = array_merge($result_item, [$property => $property_value]);
    //             }
    //         }
    //         $result = $tmp;
    //     }
    //     return $result;
    // }

    public static function variation_price($product, $variations)
    {
        $match = $variations;
        $result = 0;
            foreach($product as $product_variation){
                foreach($product_variation['values'] as $option){
                    foreach($match as $variation){
                        if($product_variation['name'] == $variation['name'] && isset($variation['values']) && in_array($option['label'], $variation['values']['label'])){
                            $result += $option['optionPrice'];
                        }
                    }
                }
            }

        return $result;
    }

    public static function cart_product_data_formatting($data, $selected_variation, $selected_addons, $selected_addon_quantity,$trans = false, $local = 'en')
    {

        $variations = [];
        $categories = [];
        $category_ids = gettype($data['category_ids']) == 'array' ? $data['category_ids'] : json_decode($data['category_ids'],true);
        foreach ($category_ids as $value) {
            $category_name = Category::where('id',$value['id'])->pluck('name');
            $categories[] = ['id' => (string)$value['id'], 'position' => $value['position'], 'name'=>data_get($category_name,'0','NA')];
        }
        $data['category_ids'] = $categories;

        $add_ons = gettype($data['add_ons']) == 'array' ? $data['add_ons'] : json_decode($data['add_ons'],true);
        $data_addons = self::addon_data_formatting(AddOn::whereIn('id', $add_ons)->active()->get(), true, $trans, $local);
        $selected_data = array_combine($selected_addons, $selected_addon_quantity);
        foreach ($data_addons as $addon) {
            $addon_id = $addon['id'];
            if (in_array($addon_id, $selected_addons)) {
                $addon['isChecked'] = true;
                $addon['quantity'] = $selected_data[$addon_id];
            } else {
                $addon['isChecked'] = false;
                $addon['quantity'] = 0;
            }
        }
        $data['addons'] = $data_addons;

        if ($data->title) {
            $data['name'] = $data->title;
            unset($data['title']);
        }
        if ($data->start_time) {
            $data['available_time_starts'] = $data->start_time->format('H:i');
            unset($data['start_time']);
        }
        if ($data->end_time) {
            $data['available_time_ends'] = $data->end_time->format('H:i');
            unset($data['end_time']);
        }
        if ($data->start_date) {
            $data['available_date_starts'] = $data->start_date->format('Y-m-d');
            unset($data['start_date']);
        }
        if ($data->end_date) {
            $data['available_date_ends'] = $data->end_date->format('Y-m-d');
            unset($data['end_date']);
        }
        $data_variation = $data['variations']?(gettype($data['variations']) == 'array' ? $data['variations'] : json_decode($data['variations'],true)):[];
        foreach ($selected_variation as $item1) {
            foreach ($data_variation as &$item2) {
                if ($item1["name"] === $item2["name"]) {
                    foreach ($item2["values"] as &$value) {
                        if (in_array($value["label"], $item1["values"]["label"])) {
                            $value["isSelected"] = true;
                        }else{
                            $value["isSelected"] = false;
                        }
                    }
                }
            }
        }
        

        $data['variations'] = $data_variation;
        $data['restaurant_name'] = $data->restaurant->name;
        $data['restaurant_status'] = (int) $data->restaurant->status;
        $data['restaurant_discount'] = self::get_restaurant_discount($data->restaurant) ? $data->restaurant->discount->discount : 0;
        $data['restaurant_opening_time'] = $data->restaurant->opening_time ? $data->restaurant->opening_time->format('H:i') : null;
        $data['restaurant_closing_time'] = $data->restaurant->closeing_time ? $data->restaurant->closeing_time->format('H:i') : null;
        $data['schedule_order'] = $data->restaurant->schedule_order;
        $data['rating_count'] = (int)($data->rating ? array_sum(json_decode($data->rating, true)) : 0);
        $data['avg_rating'] = (float)($data->avg_rating ? $data->avg_rating : 0);
        $data['recommended'] =(int) $data->recommended;


        $data['free_delivery'] =  (int) $data->restaurant->free_delivery ?? 0;
        $data['min_delivery_time'] =  (int) explode('-',$data->restaurant->delivery_time)[0] ?? 0;
        $data['max_delivery_time'] =  (int) explode('-',$data->restaurant->delivery_time)[1] ?? 0;
        $cuisine =[];
        $cui =$data->restaurant->load('cuisine');
        if(isset($cui->cuisine)){
            foreach($cui->cuisine as $cu){
                $cuisine[]= ['id' => (int) $cu->id, 'name' => $cu->name , 'image' => $cu->image];
            }
        }

        $data['cuisines'] =   $cuisine;

        unset($data['restaurant']);
        unset($data['rating']);


        return $data;
    }

    public static function product_data_formatting($data, $multi_data = false, $trans = false, $local = 'en')
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                $item = self::format_food_availability($item);
                $variations = [];
                if ($item->title) {
                    $item['name'] = $item->title;
                    unset($item['title']);
                }
                if ($item->start_time) {
                    $item['available_time_starts'] = $item->start_time->format('H:i');
                    unset($item['start_time']);
                }
                if ($item->end_time) {
                    $item['available_time_ends'] = $item->end_time->format('H:i');
                    unset($item['end_time']);
                }

                if ($item->start_date) {
                    $item['available_date_starts'] = $item->start_date->format('Y-m-d');
                    unset($item['start_date']);
                }
                if ($item->end_date) {
                    $item['available_date_ends'] = $item->end_date->format('Y-m-d');
                    unset($item['end_date']);
                }
                $item['recommended'] =(int) $item->recommended;
                $categories = [];
                foreach (json_decode($item?->category_ids) as $value) {
                    $categories[] = ['id' => (string)$value->id, 'position' => $value->position];
                }
                $item['category_ids'] = $categories;
                // $item['attributes'] = json_decode($item['attributes']);
                // $item['choice_options'] = json_decode($item['choice_options']);
                $item['add_ons'] = self::addon_data_formatting(AddOn::whereIn('id', json_decode($item['add_ons']))->active()->get(), true, $trans, $local);
                $item['tags'] = $item->tags;
                $item['variations'] = json_decode($item['variations'], true);
                $item['restaurant_name'] = $item->restaurant->name;
                $item['restaurant_status'] = (int) $item->restaurant->status;
                $item['restaurant_discount'] = self::get_restaurant_discount($item->restaurant) ? $item->restaurant->discount->discount : 0;
                $item['restaurant_opening_time'] = $item->restaurant->opening_time ? $item->restaurant->opening_time->format('H:i') : null;
                $item['restaurant_closing_time'] = $item->restaurant->closeing_time ? $item->restaurant->closeing_time->format('H:i') : null;
                $item['schedule_order'] = $item->restaurant->schedule_order;
                $item['tax'] = $item->restaurant->tax;
                try {
                    $reviewsInfo = $item->rating()->first();
                } catch (\Exception $e) {
                    $reviewsInfo = null;
                }
                $item['rating_count'] = $reviewsInfo?->rating_count ?? 0;
                $item['avg_rating'] = $reviewsInfo?->average ?? 0;
                $item['min_delivery_time'] =  (int) explode('-',$item->restaurant->delivery_time)[0] ?? 0;
                $item['max_delivery_time'] =  (int) explode('-',$item->restaurant->delivery_time)[1] ?? 0;


                if( $item->restaurant->restaurant_model == 'subscription'  && isset($item->restaurant->restaurant_sub)){
                    $item->restaurant['self_delivery_system'] = (int) $item->restaurant->restaurant_sub->self_delivery;
                }

                $item['free_delivery'] =  (int) $item->restaurant->free_delivery ?? 0;

               if(self::getDeliveryFee($item->restaurant)  ==  'free_delivery'){
                    $item['free_delivery'] =  (int)  1;
               }

                $cuisine =[];
                $cui =$item->restaurant->load('cuisine');
                if(isset($cui->cuisine)){
                    foreach($cui->cuisine as $cu){
                        $cuisine[]= ['id' => (int) $cu->id, 'name' => $cu->name , 'image' => $cu->image];
                    }
                }

                $item['cuisines'] =   $cuisine;


                unset($item['restaurant']);
                unset($item['rating']);
                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            $data = self::format_food_availability($data);
            $variations = [];
            $categories = [];
            foreach (json_decode($data?->category_ids) as $value) {
                $categories[] = ['id' => (string)$value->id, 'position' => $value->position];
            }
            $data['category_ids'] = $categories;

            $data['add_ons'] = self::addon_data_formatting(AddOn::whereIn('id', json_decode($data['add_ons']))->active()->get(), true, $trans, $local);
            if ($data->title) {
                $data['name'] = $data->title;
                unset($data['title']);
            }
            if ($data->start_time) {
                $data['available_time_starts'] = $data->start_time->format('H:i');
                unset($data['start_time']);
            }
            if ($data->end_time) {
                $data['available_time_ends'] = $data->end_time->format('H:i');
                unset($data['end_time']);
            }
            if ($data->start_date) {
                $data['available_date_starts'] = $data->start_date->format('Y-m-d');
                unset($data['start_date']);
            }
            if ($data->end_date) {
                $data['available_date_ends'] = $data->end_date->format('Y-m-d');
                unset($data['end_date']);
            }
            $data['variations'] = json_decode($data['variations'], true);
            $data['restaurant_name'] = $data->restaurant->name;
            $data['restaurant_status'] = (int) $data->restaurant->status;
            $data['restaurant_discount'] = self::get_restaurant_discount($data->restaurant) ? $data->restaurant->discount->discount : 0;
            $data['restaurant_opening_time'] = $data->restaurant->opening_time ? $data->restaurant->opening_time->format('H:i') : null;
            $data['restaurant_closing_time'] = $data->restaurant->closeing_time ? $data->restaurant->closeing_time->format('H:i') : null;
            $data['schedule_order'] = $data->restaurant->schedule_order;
                try {
                    $reviewsInfo = $data->rating()->first();
                } catch (\Exception $e) {
                    $reviewsInfo = null;
                }
                $data['rating_count'] = $reviewsInfo?->rating_count ?? 0;
                $data['avg_rating'] = $reviewsInfo?->average ?? 0;
            $data['recommended'] =(int) $data->recommended;



            if( $data->restaurant->restaurant_model == 'subscription'  && isset($data->restaurant->restaurant_sub)){
                $data->restaurant['self_delivery_system'] = (int) $data->restaurant->restaurant_sub->self_delivery;
            }

            $data['free_delivery'] =  (int) $data->restaurant->free_delivery ?? 0;

            if(self::getDeliveryFee($data->restaurant)  ==  'free_delivery'){
                $data['free_delivery'] =  (int)  1;
            }

            $data['min_delivery_time'] =  (int) explode('-',$data->restaurant->delivery_time)[0] ?? 0;
            $data['max_delivery_time'] =  (int) explode('-',$data->restaurant->delivery_time)[1] ?? 0;
            $cuisine =[];
            $cui =$data->restaurant->load('cuisine');
            if(isset($cui->cuisine)){
                foreach($cui->cuisine as $cu){
                    $cuisine[]= ['id' => (int) $cu->id, 'name' => $cu->name , 'image' => $cu->image];
                }
            }

            $data['cuisines'] =   $cuisine;

            unset($data['restaurant']);
            unset($data['rating']);
        }

        return $data;
    }

    public static function product_data_formatting_translate($data, $multi_data = false, $trans = false, $local = 'en')
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                $item = self::format_food_availability($item);
                $variations = [];
                if ($item->title) {
                    $item['name'] = $item->title;
                    unset($item['title']);
                }
                if ($item->start_time) {
                    $item['available_time_starts'] = $item->start_time->format('H:i');
                    unset($item['start_time']);
                }
                if ($item->end_time) {
                    $item['available_time_ends'] = $item->end_time->format('H:i');
                    unset($item['end_time']);
                }
                if ($item->start_date) {
                    $item['available_date_starts'] = $item->start_date->format('Y-m-d');
                    unset($item['start_date']);
                }
                if ($item->end_date) {
                    $item['available_date_ends'] = $item->end_date->format('Y-m-d');
                    unset($item['end_date']);
                }
                $item['recommended'] =(int) $item->recommended;
                $categories = [];
                foreach (json_decode($item['category_ids']) as $value) {
                    $categories[] = ['id' => (string)$value->id, 'position' => $value->position];
                }
                $item['category_ids'] = $categories;
                $item['attributes'] = json_decode($item['attributes']);
                $item['choice_options'] = json_decode($item['choice_options']);
                $item['add_ons'] = self::addon_data_formatting(AddOn::whereIn('id', json_decode($item['add_ons'], true))->active()->get(), true, $trans, $local);

                $item['variations'] = json_decode($item['variations'], true);
                $item['restaurant_name'] = $item->restaurant->name;
                $item['zone_id'] = $item->restaurant->zone_id;
                $item['restaurant_discount'] = self::get_restaurant_discount($item->restaurant) ? $item->restaurant->discount->discount : 0;
                $item['schedule_order'] = $item->restaurant->schedule_order;
                $item['tax'] = $item->restaurant->tax;
                try {
                    $reviewsInfo = $item->rating()->first();
                } catch (\Exception $e) {
                    $reviewsInfo = null;
                }
                $item['rating_count'] = $reviewsInfo?->rating_count ?? 0;
                $item['avg_rating'] = $reviewsInfo?->average ?? 0;
                $item['recommended'] =(int) $item->recommended;

                if ($trans) {
                    $item['translations'][] = [
                        'translationable_type' => 'App\Models\Food',
                        'translationable_id' => $item->id,
                        'locale' => 'en',
                        'key' => 'name',
                        'value' => $item->name
                    ];

                    $item['translations'][] = [
                        'translationable_type' => 'App\Models\Food',
                        'translationable_id' => $item->id,
                        'locale' => 'en',
                        'key' => 'description',
                        'value' => $item->description
                    ];
                }

                if (count($item['translations']) > 0) {
                    foreach ($item['translations'] as $translation) {
                        if ($translation['locale'] == $local) {
                            if ($translation['key'] == 'name') {
                                $item['name'] = $translation['value'];
                            }

                            if ($translation['key'] == 'title') {
                                $item['name'] = $translation['value'];
                            }

                            if ($translation['key'] == 'description') {
                                $item['description'] = $translation['value'];
                            }
                        }
                    }
                }
                if (!$trans) {
                    unset($item['translations']);
                }

                unset($item['restaurant']);
                unset($item['rating']);
                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            $data = self::format_food_availability($data);
            $variations = [];
            $categories = [];
            foreach (json_decode($data['category_ids']) as $value) {
                $categories[] = ['id' => (string)$value->id, 'position' => $value->position];
            }
            $data['category_ids'] = $categories;

            $data['attributes'] = json_decode($data['attributes']);
            $data['choice_options'] = json_decode($data['choice_options']);
            $data['add_ons'] = self::addon_data_formatting(AddOn::whereIn('id', json_decode($data['add_ons']))->active()->get(), true, $trans, $local);

            if ($data->title) {
                $data['name'] = $data->title;
                unset($data['title']);
            }
            if ($data->start_time) {
                $data['available_time_starts'] = $data->start_time->format('H:i');
                unset($data['start_time']);
            }
            if ($data->end_time) {
                $data['available_time_ends'] = $data->end_time->format('H:i');
                unset($data['end_time']);
            }
            if ($data->start_date) {
                $data['available_date_starts'] = $data->start_date->format('Y-m-d');
                unset($data['start_date']);
            }
            if ($data->end_date) {
                $data['available_date_ends'] = $data->end_date->format('Y-m-d');
                unset($data['end_date']);
            }
            $data['variations'] = json_decode($data['variations'], true);
            $data['restaurant_name'] = $data->restaurant->name;
            $data['zone_id'] = $data->restaurant->zone_id;
            $data['restaurant_discount'] = self::get_restaurant_discount($data->restaurant) ? $data->restaurant->discount->discount : 0;
            $data['schedule_order'] = $data->restaurant->schedule_order;
                try {
                    $reviewsInfo = $data->rating()->first();
                } catch (\Exception $e) {
                    $reviewsInfo = null;
                }
                $data['rating_count'] = $reviewsInfo?->rating_count ?? 0;
                $data['avg_rating'] = $reviewsInfo?->average ?? 0;

            if ($trans) {
                $data['translations'][] = [
                    'translationable_type' => 'App\Models\Foos',
                    'translationable_id' => $data->id,
                    'locale' => 'en',
                    'key' => 'name',
                    'value' => $data->name
                ];

                $data['translations'][] = [
                    'translationable_type' => 'App\Models\Food',
                    'translationable_id' => $data->id,
                    'locale' => 'en',
                    'key' => 'description',
                    'value' => $data->description
                ];
            }

            if (count($data['translations']) > 0) {
                foreach ($data['translations'] as $translation) {
                    if ($translation['locale'] == $local) {
                        if ($translation['key'] == 'name') {
                            $data['name'] = $translation['value'];
                        }

                        if ($translation['key'] == 'title') {
                            $item['name'] = $translation['value'];
                        }

                        if ($translation['key'] == 'description') {
                            $data['description'] = $translation['value'];
                        }
                    }
                }
            }
            if (!$trans) {
                unset($data['translations']);
            }

            unset($data['restaurant']);
            unset($data['rating']);
        }

        return $data;
    }
    public static function addon_data_formatting($data, $multi_data = false, $trans = false, $local = 'en')
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                if ($trans) {
                    $item['translations'][] = [
                        'translationable_type' => 'App\Models\AddOn',
                        'translationable_id' => $item->id,
                        'locale' => 'en',
                        'key' => 'name',
                        'value' => $item->name
                    ];
                }
                // if (count($item->translations) > 0) {
                //     foreach ($item['translations'] as $translation) {
                //         if ($translation['locale'] == $local && $translation['key'] == 'name') {
                //             $item['name'] = $translation['value'];
                //         }
                //     }
                // }

                // if (!$trans) {
                //     unset($item['translations']);
                // }

                $storage[] = $item;
            }
            $data = $storage;
        } else if (isset($data)) {
            if ($trans) {
                $data['translations'][] = [
                    'translationable_type' => 'App\Models\AddOn',
                    'translationable_id' => $data->id,
                    'locale' => 'en',
                    'key' => 'name',
                    'value' => $data->name
                ];
            }

            // if (count($data->translations) > 0) {
            //     foreach ($data['translations'] as $translation) {
            //         if ($translation['locale'] == $local && $translation['key'] == 'name') {
            //             $data['name'] = $translation['value'];
            //         }
            //     }
            // }

            // if (!$trans) {
            //     unset($data['translations']);
            // }
        }
        return $data;
    }

    public static function category_data_formatting($data, $multi_data = false, $trans = false)
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                // if (count($item->translations) > 0) {
                //     $item->name = $item->translations[0]['value'];
                // }

                // if (!$trans) {
                //     unset($item['translations']);
                // }

                if($item->relationLoaded('childes') && $item['childes']){
                    $item['products_count'] += $item['childes']->sum('products_count');
                    unset($item['childes']);
                }
                $storage[] = $item;
            }
            $data = $storage;
        } else if (isset($data)) {
            // if (count($data->translations) > 0) {
            //     $data->name = $data->translations[0]['value'];
            // }

            // if (!$trans) {
            //     unset($data['translations']);
            // }
            if($data->relationLoaded('childes') && $data['childes']){
                $data['products_count'] += $data['childes']->sum('products_count');
                unset($data['childes']);
            }
        }
        return $data;
    }

    public static function basic_campaign_data_formatting($data, $multi_data = false)
    {
        $storage = [];
        if ($multi_data == true) {
            foreach ($data as $item) {
                $variations = [];

                if ($item->start_date) {
                    $item['available_date_starts'] = $item->start_date->format('Y-m-d');
                    unset($item['start_date']);
                }
                if ($item->end_date) {
                    $item['available_date_ends'] = $item->end_date->format('Y-m-d');
                    unset($item['end_date']);
                }

                // if (count($item['translations']) > 0) {
                //     $translate = array_column($item['translations']->toArray(), 'value', 'key');
                //     $item['title'] = $translate['title'];
                //     $item['description'] = $translate['description'];
                // }
                if (count($item['restaurants']) > 0) {
                    $item['restaurants'] = self::restaurant_data_formatting($item['restaurants'], true);
                }

                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            if ($data->start_date) {
                $data['available_date_starts'] = $data->start_date->format('Y-m-d');
                unset($data['start_date']);
            }
            if ($data->end_date) {
                $data['available_date_ends'] = $data->end_date->format('Y-m-d');
                unset($data['end_date']);
            }

            // if (count($data['translations']) > 0) {
            //     $translate = array_column($data['translations']->toArray(), 'value', 'key');
            //     $data['title'] = $translate['title'];
            //     $data['description'] = $translate['description'];
            // }
            if (count($data['restaurants']) > 0) {
                $data['restaurants'] = self::restaurant_data_formatting($data['restaurants'], true);
            }
        }

        return $data;
    }
    public static function restaurant_data_formatting($data, $multi_data = false)
    {
        $storage = [];
        $cuisines=[];

        if ($multi_data == true) {
            foreach ($data as $item) {
                $item->load('cuisine');
                // $item['coupons'] = $item->coupon_valid;
                $restaurant_id= (string)$item->id;

                $item['coupons'] = Coupon::Where(function ($q) use ($restaurant_id) {
                    $q->Where('coupon_type', 'restaurant_wise')->whereJsonContains('data', [$restaurant_id])
                        ->where(function ($q1)  {
                            $q1->WhereJsonContains('customer_id', ['all']);
                        });
                })->orwhere('restaurant_id',$restaurant_id)
                ->active()
                ->valid()
                ->get();

                if( $item->restaurant_model == 'subscription'  && isset($item->restaurant_sub)){
                    $item['self_delivery_system'] = (int) $item->restaurant_sub->self_delivery;
                }

                $item['delivery_fee'] = self::getDeliveryFee($item);

                $item['restaurant_status'] = (int) $item->status;
                $item['cuisine'] = $item->cuisine;

                if ($item->opening_time) {
                    $item['available_time_starts'] = $item->opening_time->format('H:i');
                    unset($item['opening_time']);
                }
                if ($item->closeing_time) {
                    $item['available_time_ends'] = $item->closeing_time->format('H:i');
                    unset($item['closeing_time']);
                }

                $reviewsInfo = $item->reviews()
                ->selectRaw('avg(reviews.rating) as average_rating, count(reviews.id) as total_reviews, food.restaurant_id')
                ->groupBy('food.restaurant_id')
                ->first();

                $item['avg_rating'] = (float)  $reviewsInfo?->average_rating ?? 0;
                $item['rating_count'] = (int)   $reviewsInfo?->total_reviews ?? 0;

//                $ratings = RestaurantLogic::calculate_restaurant_rating($item['rating']);
                $positive_rating = RestaurantLogic::calculate_positive_rating($item['rating']);

                $item['positive_rating'] = (int) $positive_rating['rating'];
//                $item['avg_rating'] =   (int) $ratings['rating'];
//                $item['rating_count'] =   (int) $ratings['total'];

                $item['customer_order_date'] =   (int) $item?->restaurant_config?->customer_order_date;
                $item['customer_date_order_sratus'] =   (bool) $item?->restaurant_config?->customer_date_order_sratus;
                $item['instant_order'] =   (bool) $item?->restaurant_config?->instant_order;

                // unset($item['coupon_valid']);
                unset($item['campaigns']);
                unset($item['pivot']);
                unset($item['rating']);
                unset($item['restaurant_config']);
                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            if( $data->restaurant_model == 'subscription'  && isset($data->restaurant_sub)){
                $data['self_delivery_system'] = (int) $data->restaurant_sub->self_delivery;
            }
            $data['restaurant_status'] = (int) $data->status;
            if ($data->opening_time) {
                $data['available_time_starts'] = $data->opening_time->format('H:i');
                unset($data['opening_time']);
            }
            if ($data->closeing_time) {
                $data['available_time_ends'] = $data->closeing_time->format('H:i');
                unset($data['closeing_time']);
            }


            $restaurant_id= (string)$data->id;

            $data['coupons'] = Coupon::Where(function ($q) use ($restaurant_id) {
                $q->Where('coupon_type', 'restaurant_wise')->whereJsonContains('data', [$restaurant_id])
                    ->where(function ($q1)  {
                        $q1->WhereJsonContains('customer_id', ['all']);
                    });
            })->orwhere('restaurant_id',$restaurant_id)
            ->active()
            ->valid()
            ->get();

            $data->load(['cuisine']);
            $data['cuisine'] = $data->cuisine;

            $reviewsInfo = $data->reviews()
            ->selectRaw('avg(reviews.rating) as average_rating, count(reviews.id) as total_reviews, food.restaurant_id')
            ->groupBy('food.restaurant_id')
            ->first();

            $data['avg_rating'] = (float)  $reviewsInfo?->average_rating ?? 0;
            $data['rating_count'] = (int)   $reviewsInfo?->total_reviews ?? 0;

//            $ratings = RestaurantLogic::calculate_restaurant_rating($data['rating']);
            $positive_rating = RestaurantLogic::calculate_positive_rating($data['rating']);
            $data['positive_rating'] = (int) $positive_rating['rating'];
            // $data['coupons'] = $data->coupon_valid;
//            $data['avg_rating'] =   (int) $ratings['rating'];
//            $data['rating_count'] =  (int) $ratings['total'];



            $data['customer_order_date'] =   (int) $data?->restaurant_config?->customer_order_date;
            $data['customer_date_order_sratus'] =   (bool) $data?->restaurant_config?->customer_date_order_sratus;
            $data['instant_order'] =   (bool) $data?->restaurant_config?->instant_order;
            $data['delivery_fee'] = self::getDeliveryFee($data);

            // unset($data['coupon_valid']);
            // unset($data['translations']);
            unset($data['rating']);
            unset($data['campaigns']);
            unset($data['pivot']);
            unset($data['restaurant_config']);
        }

        return $data;
    }

    public static function wishlist_data_formatting($data, $multi_data = false)
    {
        $foods = [];
        $restaurants = [];
        if ($multi_data == true) {

            foreach ($data as $item) {
                if ($item->food) {
                    $foods[] = self::product_data_formatting($item->food, false, false, app()->getLocale());
                }
                if ($item->restaurant) {
                    $restaurants[] = self::restaurant_data_formatting($item->restaurant);
                }
            }
        } else {
            if ($data->food) {
                $foods[] = self::product_data_formatting($data->food, false, false, app()->getLocale());
            }
            if ($data->restaurant) {
                $restaurants[] = self::restaurant_data_formatting($data->restaurant);
            }
        }

        return ['food' => $foods, 'restaurant' => $restaurants];
    }

    public static function order_data_formatting($data, $multi_data = false)
    {
        $storage = [];
        if ($multi_data) {
            foreach ($data as $item) {
                if (isset($item['restaurant'])) {
                    $item['restaurant_name'] = $item['restaurant']['name'];
                    $item['restaurant_address'] = $item['restaurant']['address'];
                    $item['restaurant_phone'] = $item['restaurant']['phone'];
                    $item['restaurant_lat'] = $item['restaurant']['latitude'];
                    $item['restaurant_lng'] = $item['restaurant']['longitude'];
                    $item['restaurant_logo'] = $item['restaurant']['logo'];
                    $item['restaurant_delivery_time'] = $item['restaurant']['delivery_time'];
                    $item['vendor_id'] = $item['restaurant']['vendor_id'];
                    $item['chat_permission'] = $item['restaurant']['restaurant_sub']['chat'] ?? 0;
                    $item['restaurant_model'] = $item['restaurant']['restaurant_model'];
                    unset($item['restaurant']);
                } else {
                    $item['restaurant_name'] = null;
                    $item['restaurant_address'] = null;
                    $item['restaurant_phone'] = null;
                    $item['restaurant_lat'] = null;
                    $item['restaurant_lng'] = null;
                    $item['restaurant_logo'] = null;
                    $item['restaurant_delivery_time'] = null;
                    $item['restaurant_model'] = null;
                    $item['chat_permission'] = null;
                }
                $item['food_campaign'] = 0;
                foreach ($item->details as $d) {
                    if ($d->item_campaign_id != null) {
                        $item['food_campaign'] = 1;
                    }
                }

                $item['delivery_address'] = $item->delivery_address ? json_decode($item->delivery_address, true) : null;
                $item['details_count'] = (int)$item->details->count();
                unset($item['details']);
                array_push($storage, $item);
            }
            $data = $storage;
        } else {
            if (isset($data['restaurant'])) {
                $data['restaurant_name'] = $data['restaurant']['name'];
                $data['restaurant_address'] = $data['restaurant']['address'];
                $data['restaurant_phone'] = $data['restaurant']['phone'];
                $data['restaurant_lat'] = $data['restaurant']['latitude'];
                $data['restaurant_lng'] = $data['restaurant']['longitude'];
                $data['restaurant_logo'] = $data['restaurant']['logo'];
                $data['restaurant_delivery_time'] = $data['restaurant']['delivery_time'];
                $data['vendor_id'] = $data['restaurant']['vendor_id'];
                $data['chat_permission'] = $data['restaurant']['restaurant_sub']['chat'] ?? 0;
                $data['restaurant_model'] = $data['restaurant']['restaurant_model'];
                unset($data['restaurant']);
            } else {
                $data['restaurant_name'] = null;
                $data['restaurant_address'] = null;
                $data['restaurant_phone'] = null;
                $data['restaurant_lat'] = null;
                $data['restaurant_lng'] = null;
                $data['restaurant_logo'] = null;
                $data['restaurant_delivery_time'] = null;
                $data['chat_permission'] = null;
                $data['restaurant_model'] = null;
            }

            $data['food_campaign'] = 0;
            foreach ($data->details as $d) {
                if ($d->item_campaign_id != null) {
                    $data['food_campaign'] = 1;
                }
            }
            $data['delivery_address'] = $data->delivery_address ? json_decode($data->delivery_address, true) : null;
            $data['details_count'] = (int)$data->details->count();
            unset($data['details']);
        }
        return $data;
    }

    public static function order_details_data_formatting($data)
    {
        $storage = [];
        foreach ($data as $item) {
            $item['add_ons'] = json_decode($item['add_ons']);
            $item['variation'] = json_decode($item['variation']);
            $item['food_details'] = json_decode($item['food_details'], true);
            array_push($storage, $item);
        }
        $data = $storage;

        return $data;
    }

    public static function deliverymen_list_formatting($data , $restaurant_lat = null , $restaurant_lng = null , $single_data = false )
    {
        $storage = [];
        $map_api_key = BusinessSetting::where(['key' => 'map_api_key_server'])->first()?->value ?? null;

        if($single_data ==  true){
            $item=$data;
                if( $restaurant_lat &&  $restaurant_lng && $item->last_location){
//                    $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $restaurant_lat . ',' . $restaurant_lng . '&destinations=' . ($item->last_location ? $item->last_location->latitude : 0 ). ',' . ($item->last_location ? $item->last_location->longitude : 0) . '&key=' . $map_api_key . '&mode=walking');
//                    $distance=  $response->json();
//                    $distance= gettype($distance) == 'array' ? $distance: json_decode($distance,true);
//                    $distance = data_get($distance,'rows.0.elements.0.distance.text',' ');

                    $originCoordinates =[
                        $restaurant_lat,
                        $restaurant_lng
                    ];
                    $destinationCoordinates =[
                        $item->last_location->latitude,
                        $item->last_location->longitude
                    ];
                    $distance = self::get_distance($originCoordinates, $destinationCoordinates);

                    $distance =  round($distance,2).' KM';
                }




                $data = [
                    'id' => $item['id'],
                    'name' => $item['f_name'] . ' ' . $item['l_name'],
                    'image' => $item['image'],
                    'current_orders' => $item['current_orders'],
                    'lat' => $item->last_location ? $item->last_location->latitude : '0',
                    'lng' => $item->last_location ? $item->last_location->longitude : '0',
                    'location' => $item->last_location ? $item->last_location->location : '',
                    'distance' => $distance ?? '',
                ];;

                return $data;
        }

        foreach ($data as $item) {
        if( $restaurant_lat &&  $restaurant_lng && $item->last_location){
//            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $restaurant_lat . ',' . $restaurant_lng . '&destinations=' . ($item->last_location ? $item->last_location->latitude : 0 ). ',' . ($item->last_location ? $item->last_location->longitude : 0) . '&key=' . $map_api_key . '&mode=walking');
//            $distance=  $response->json();
//            $distance= gettype($distance) == 'array' ? $distance: json_decode($distance,true);
//            $distance = data_get($distance,'rows.0.elements.0.distance.text',' ');

            $originCoordinates =[
                $restaurant_lat,
                $restaurant_lng
            ];
            $destinationCoordinates =[
                $item->last_location->latitude,
                $item->last_location->longitude
            ];
            $distance = self::get_distance($originCoordinates, $destinationCoordinates);
            $distance =  round($distance,2).' KM';
        }

            $storage[] = [
                'id' => $item['id'],
                'name' => $item['f_name'] . ' ' . $item['l_name'],
                'image' => $item['image'],
                'current_orders' => $item['current_orders'],
                'lat' => $item->last_location ? $item->last_location->latitude : '0',
                'lng' => $item->last_location ? $item->last_location->longitude : '0',
                'location' => $item->last_location ? $item->last_location->location : '',
                'distance' => $distance ?? '',
            ];
        }

        $data = $storage;

        return $data;
    }

    public static function address_data_formatting($data)
    {
        foreach ($data as $key=>$item) {
            $data[$key]['zone_ids'] = array_column(Zone::query()->whereContains('coordinates', new Point($item->latitude, $item->longitude, POINT_SRID))->latest()->get(['id'])->toArray(), 'id');
        }
        return $data;
    }

    public static function deliverymen_data_formatting($data)
    {
        $storage = [];
        foreach ($data as $item) {
            $item['avg_rating'] = (float)(count($item->rating) ? (float)$item->rating[0]->average : 0);
            $item['rating_count'] = (int)(count($item->rating) ? $item->rating[0]->rating_count : 0);
            $item['lat'] = $item->last_location ? $item->last_location->latitude : null;
            $item['lng'] = $item->last_location ? $item->last_location->longitude : null;
            $item['location'] = $item->last_location ? $item->last_location->location : null;

            if ($item['rating']) {
                unset($item['rating']);
            }
            if ($item['last_location']) {
                unset($item['last_location']);
            }
            $storage[] = $item;
        }
        $data = $storage;

        return $data;
    }

    public static function get_business_settings($name, $json_decode = true)
    {
        $config = null;

        $paymentmethod = BusinessSetting::where('key', $name)->first();

        if ($paymentmethod) {
            $config = $json_decode ? json_decode($paymentmethod->value, true) : $paymentmethod->value;
        }

        return $config;
    }

    public static function currency_code()
    {
        // if(!request()->is('/api*') && !session()->has('currency_code')){
        //     $currency = BusinessSetting::where(['key' => 'currency'])->first()?->value;
        //     session()->put('currency_code',$currency);
        // }else{
        //     $currency = BusinessSetting::where(['key' => 'currency'])->first()?->value;
        // }

        // if(!request()->is('/api*')){
        //     $currency = session()->get('currency_code');
        // }


// dd( BusinessSetting::where(['key' => 'currency'])->first()?->value);

        if (!config('currency') ){
            // Session::forget('currency_symbol');
            // Session::forget('currency_code');
            // Session::forget('currency_symbol_position');
            // Session::forget('is_settings_changed');
            $currency = BusinessSetting::where(['key' => 'currency'])->first()?->value;
            Config::set('currency', $currency );

            // session()->put('currency_code',$currency);
        }
            else{
                $currency = config('currency');
            }

// dd($currency);

        return $currency;
    }

    public static function currency_symbol()
    {

        if (!config('currency_symbol') ){
            $currency_symbol = Currency::where(['currency_code' => Helpers::currency_code()])->first()?->currency_symbol;
            Config::set('currency_symbol', $currency_symbol );
        }
        else{
            $currency_symbol =config('currency_symbol');
        }
        // dd(session()->get('currency_symbol'));
        // if(!session()->has('currency_symbol')){
        //     $currency_symbol = Currency::where(['currency_code' => Helpers::currency_code()])->first()?->currency_symbol;
        //     session()->put('currency_symbol',$currency_symbol);
        // }
        // $currency_symbol = session()->get('currency_symbol');
        return $currency_symbol ;
    }


    public static function format_currency($value)
    {
        // if(!session()->has('currency_symbol_position')){
        //     $currency_symbol_position = BusinessSetting::where(['key' => 'currency_symbol_position'])->first()?->value;
        //     session()->put('currency_symbol_position',$currency_symbol_position);
        // }
        // $currency_symbol_position = session()->get('currency_symbol_position');

        if (!config('currency_symbol_position') ){
            $currency_symbol_position = BusinessSetting::where(['key' => 'currency_symbol_position'])->first()?->value;
            Config::set('currency_symbol_position', $currency_symbol_position );
        }
        else{
            $currency_symbol_position =config('currency_symbol_position');
        }

        return $currency_symbol_position == 'right' ? number_format($value, config('round_up_to_digit')) . ' ' . self::currency_symbol() : self::currency_symbol() . ' ' . number_format($value, config('round_up_to_digit'));
    }

    //private static $serviceAccountFile = 'bestie-food-firebase-adminsdk-f2aop-9c435621c6.json';

   public static function send_push_notif_to_device($fcm_token, $data, $web_push_link = null)
{
    try {
        $accessToken = self::getAccessToken();
 
        // getAccessToken now returns null on failure instead of die()
        if (!$accessToken) {
            \Illuminate\Support\Facades\Log::channel('single')->error('FCM: Could not obtain access token');
            return null;
        }
 
        $url = 'https://fcm.googleapis.com/v1/projects/bestie-food/messages:send';
 
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
 
        $dataPayload = [
            'title'           => (string) $data['title'],
            'body'            => (string) $data['description'],
            'image'           => (string) $data['image'],
            'order_id'        => (string) ($data['order_id'] ?? ''),
            'type'            => (string) ($data['type'] ?? ''),
            'conversation_id' => (string) ($data['conversation_id'] ?? ''),
            'sender_type'     => (string) ($data['sender_type'] ?? ''),
            'order_type'      => (string) ($data['order_type'] ?? ''),
            'is_read'         => '0',
        ];
 
        $click_action = $web_push_link ? ['click_action' => $web_push_link] : [];
 
        $payload = [
            'message' => [
                'token'        => $fcm_token,
                'data'         => $dataPayload,
                'notification' => array_merge([
                    'title' => $data['title'],
                    'body'  => $data['description'],
                    'image' => $data['image'],
                ], $click_action),
            ]
        ];
 
        $postdata = json_encode($payload);
 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);         // ← 10 sec timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);   // ← 5 sec connect timeout
 
        $response     = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            curl_close($ch);
            \Illuminate\Support\Facades\Log::channel('single')->error('FCM CURL Error: ' . $curlError);
            return null;  // ← was: die('CURL Error: ...')
        }
 
        curl_close($ch);
 
        // ✅ FIX: Removed echo statements that were polluting the API response
        // echo 'HTTP Status Code: ' . $responseCode . PHP_EOL;  ← REMOVED
        // echo 'Response: ' . $response . PHP_EOL;              ← REMOVED
 
        \Illuminate\Support\Facades\Log::channel('single')->info('FCM Response', [
            'status_code' => $responseCode,
            'response'    => $response,
        ]);
 
        return json_decode($response, true);
 
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::channel('single')->error('send_push_notif_to_device failed: ' . $e->getMessage());
        return null;
    }
}
 
 
    public static function getAccessToken()
{
    try {
        $serviceAccountFile = app_path('CentralLogics/bestie-food-firebase-adminsdk-f2aop-9c435621c6.json');
 
        // ✅ FIX: Load from storage_path (secure) instead of public URL
        if (!file_exists($serviceAccountFile)) {
            \Illuminate\Support\Facades\Log::channel('single')->error('FCM: Service account file not found at: ' . $serviceAccountFile);
            return null;
        }
 
        $credentials = json_decode(file_get_contents($serviceAccountFile), true);
 
        if (!$credentials || !isset($credentials['private_key'])) {
            \Illuminate\Support\Facades\Log::channel('single')->error('FCM: Invalid service account JSON');
            return null;
        }
 
        $jwtHeader = ['alg' => 'RS256', 'typ' => 'JWT'];
        $jwtClaim  = [
            'iss'   => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => $credentials['token_uri'],
            'exp'   => time() + 3600,
            'iat'   => time(),
        ];
 
        $jwtHeaderEncoded = rtrim(base64_encode(json_encode($jwtHeader)), '=');
        $jwtClaimEncoded  = rtrim(base64_encode(json_encode($jwtClaim)), '=');
        $dataToSign       = "$jwtHeaderEncoded.$jwtClaimEncoded";
 
        $signature = '';
        openssl_sign($dataToSign, $signature, $credentials['private_key'], 'sha256WithRSAEncryption');
        $jwtToken = "$dataToSign." . rtrim(base64_encode($signature), '=');
 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $credentials['token_uri']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwtToken,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
 
        $response = curl_exec($ch);
 
        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            curl_close($ch);
            \Illuminate\Support\Facades\Log::channel('single')->error('FCM getAccessToken CURL Error: ' . $curlError);
            return null;  // ← was: die('CURL Error: ...')
        }
 
        curl_close($ch);
 
        $response = json_decode($response, true);
 
        if (isset($response['access_token'])) {
            return $response['access_token'];
        }
 
        // ✅ FIX: Log the error instead of die() which killed the entire response
        \Illuminate\Support\Facades\Log::channel('single')->error('FCM: Failed to obtain access token', [
            'response' => $response,
        ]);
 
        return null;  // ← was: die('Failed to obtain access token: ...')
 
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::channel('single')->error('getAccessToken Exception: ' . $e->getMessage());
        return null;
    }
}
 public static function send_push_notif_to_topic($data, $topic, $type, $web_push_link = null)
{
    try {
        $accessToken = self::getAccessToken();

        if (!$accessToken) {
            \Illuminate\Support\Facades\Log::channel('single')->error('FCM Topic: Could not obtain access token');
            return null;
        }

        $url = "https://fcm.googleapis.com/v1/projects/bestie-food/messages:send";

        $headers = [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        ];

        $dataPayload = [
            "title"      => (string) $data['title'],
            "body"       => (string) $data['description'],
            "image"      => (string) ($data['image'] ?? ''),
            "order_id"   => (string) ($data['order_id'] ?? ''),
            "order_type" => (string) ($data['order_type'] ?? ''),
            "is_read"    => "0",
            "type"       => (string) $type
        ];

        $payload = [
            "message" => [
                "topic" => $topic,
                "notification" => [
                    "title" => (string) $data['title'],
                    "body"  => (string) $data['description'],
                    "image" => (string) ($data['image'] ?? ''),
                ],
                "data" => $dataPayload,
                "android" => [
                    "notification" => [
                        "sound"        => "notification.wav",
                        "channel_id"   => "stackfood",
                        "click_action" => $web_push_link ?? ''
                    ]
                ],
                "webpush" => [
                    "notification" => [
                        "icon"         => (string) ($data['image'] ?? ''),
                        "click_action" => $web_push_link ?? ''
                    ]
                ]
            ]
        ];

        $postdata = json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $response     = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $curlError = curl_error($ch);
            curl_close($ch);
            \Illuminate\Support\Facades\Log::channel('single')->error('FCM Topic CURL Error: ' . $curlError);
            return null;
        }

        curl_close($ch);

        \Illuminate\Support\Facades\Log::channel('single')->info('FCM Topic Response', [
            'topic'       => $topic,
            'status_code' => $responseCode,
            'response'    => $response,
        ]);

        return json_decode($response, true);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::channel('single')->error('send_push_notif_to_topic failed: ' . $e->getMessage());
        return null;
    }
}

    public static function rating_count($food_id, $rating)
    {
        return Review::where(['food_id' => $food_id, 'rating' => $rating])->count();
    }

    public static function dm_rating_count($deliveryman_id, $rating)
    {
        return DMReview::where(['delivery_man_id' => $deliveryman_id, 'rating' => $rating])->count();
    }

    public static function tax_calculate($food, $price)
    {
        if ($food['tax_type'] == 'percent') {
            $price_tax = ($price / 100) * $food['tax'];
        } else {
            $price_tax = $food['tax'];
        }
        return $price_tax;
    }

    public static function discount_calculate($product, $price)
    {
        if ($product['restaurant_discount']) {
            $price_discount = ($price / 100) * $product['restaurant_discount'];
        } else if ($product['discount_type'] == 'percent') {
            $price_discount = ($price / 100) * $product['discount'];
        } else {
            $price_discount = $product['discount'];
        }
        return $price_discount;
    }

    public static function get_product_discount($product)
    {
        $restaurant_discount = self::get_restaurant_discount($product->restaurant);
        if ($restaurant_discount) {
            $discount = $restaurant_discount['discount'] . ' %';
        } else if ($product['discount_type'] == 'percent') {
            $discount = $product['discount'] . ' %';
        } else {
            $discount = self::format_currency($product['discount']);
        }
        return $discount;
    }

    public static function product_discount_calculate($product, $price, $restaurant)
    {
        $restaurant_discount = self::get_restaurant_discount($restaurant);
        if (isset($restaurant_discount)) {
            $price_discount = ($price / 100) * $restaurant_discount['discount'];
        } else if ($product['discount_type'] == 'percent') {
            $price_discount = ($price / 100) * $product['discount'];
        } else {
            $price_discount = $product['discount'];
        }
        return $price_discount;
    }

    public static function get_price_range($product, $discount = false)
    {
        $lowest_price = $product->price;
        // $highest_price = $product->price;

            // foreach(json_decode($product->variations,true) as $variation){
            //     if(isset($variation["price"])){
            //         foreach (json_decode($product->variations) as $key => $variation) {
            //             if ($lowest_price > $variation->price) {
            //                 $lowest_price = round($variation->price, 2);
            //             }
            //             if ($highest_price < $variation->price) {
            //                 $highest_price = round($variation->price, 2);
            //             }
            //         }
            //         break;
            //     }
            //     else{
            //         foreach ($variation['values'] as $value){
            //             $value['optionPrice'];
            //         }
            //     }
            // }

        if ($discount) {
            $lowest_price -= self::product_discount_calculate($product, $lowest_price, $product->restaurant);
            // $highest_price -= self::product_discount_calculate($product, $highest_price, $product->restaurant);
        }
        $lowest_price = self::format_currency($lowest_price);
        // $highest_price = self::format_currency($highest_price);

        // if ($lowest_price == $highest_price) {
        //     return $lowest_price;
        // }
        // return $lowest_price . ' - ' . $highest_price;
        return $lowest_price;
    }

    public static function get_restaurant_discount($restaurant)
    {
        //dd($restaurant);
        if ($restaurant->discount) {
            if (date('Y-m-d', strtotime($restaurant->discount->start_date)) <= now()->format('Y-m-d') && date('Y-m-d', strtotime($restaurant->discount->end_date)) >= now()->format('Y-m-d') && date('H:i', strtotime($restaurant->discount->start_time)) <= now()->format('H:i') && date('H:i', strtotime($restaurant->discount->end_time)) >= now()->format('H:i')) {
                return [
                    'discount' => $restaurant->discount->discount,
                    'min_purchase' => $restaurant->discount->min_purchase,
                    'max_discount' => $restaurant->discount->max_discount
                ];
            }
        }
        return null;
    }

    public static function max_earning()
    {
        $data = Order::where(['order_status' => 'delivered'])->select('id', 'created_at', 'order_amount')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            });

        $max = 0;
        foreach ($data as $month) {
            $count = 0;
            foreach ($month as $order) {
                $count += $order['order_amount'];
            }
            if ($count > $max) {
                $max = $count;
            }
        }
        return $max;
    }

    public static function max_orders()
    {
        $data = Order::select('id', 'created_at')
            ->get()
            ->groupBy(function ($date) {
                return Carbon::parse($date->created_at)->format('m');
            });

        $max = 0;
        foreach ($data as $month) {
            $count = 0;
            foreach ($month as $order) {
                $count += 1;
            }
            if ($count > $max) {
                $max = $count;
            }
        }
        return $max;
    }






    public static function order_status_update_message($status, $lang='default')
    {
        if ($status == 'pending') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'order_pending_message')->first();
        } elseif ($status == 'confirmed') {
            $data =  NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'order_confirmation_msg')->first();
        } elseif ($status == 'processing') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'order_processing_message')->first();
        } elseif ($status == 'picked_up') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'out_for_delivery_message')->first();
        } elseif ($status == 'handover') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'order_handover_message')->first();
        } elseif ($status == 'delivered') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'order_delivered_message')->first();
        } elseif ($status == 'delivery_boy_delivered') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'delivery_boy_delivered_message')->first();
        } elseif ($status == 'accepted') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'delivery_boy_assign_message')->first();
        } elseif ($status == 'canceled') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'order_cancled_message')->first();
        } elseif ($status == 'refunded') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'order_refunded_message')->first();
        } elseif ($status == 'refund_request_canceled') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'refund_request_canceled')->first();
        } elseif ($status == 'offline_verified') {
        $data = NotificationMessage::with(['translations'=>function($query)use($lang){
            $query->where('locale', $lang);
        }])->where('key', 'offline_order_accept_message')->first();
        } elseif ($status == 'offline_denied') {
            $data = NotificationMessage::with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->where('key', 'offline_order_deny_message')->first();
        } else {
            $data = ["status"=>"0","message"=>"",'translations'=>[]];
        }

        if($data){
            if ($data['status'] == 0) {
                return 0;
            }
            return count($data->translations) > 0 ? $data->translations[0]->value : $data['message'];
        }else{
            return false;
        }
    }




    public static function send_order_notification($order)
    {
        $order= Order::where('id',$order->id)->with('zone:id,deliveryman_wise_topic','restaurant:id,name,restaurant_model,self_delivery_system,vendor_id','restaurant.restaurant_sub','customer:id,cm_firebase_token,email,f_name,l_name','restaurant.vendor:id,firebase_token','delivery_man:id,fcm_token','guest')->first();

        try {
            $status = ($order->order_status == 'delivered' && $order->delivery_man) ? 'delivery_boy_delivered' : $order->order_status;


            if($order->subscription_id == null &&  (in_array($order->payment_method, ['cash_on_delivery', 'offline_payment'])  && $order->order_status == 'pending' )||(!in_array($order->payment_method, ['cash_on_delivery', 'offline_payment']) && $order->order_status == 'confirmed' )){
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.new_order_push_description'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'new_order_admin',
                ];
                self::send_push_notif_to_topic($data, 'admin_message', 'order_request', url('/').'/admin/order/list/all');
            }

            if($order->is_guest){
                $customer_details = json_decode($order['delivery_address'],true);
                $value = self::order_status_update_message($status,'en');
                $value = self::text_variable_data_format(value:$value,restaurant_name:$order->restaurant?->name,order_id:$order->id,user_name:"{$customer_details['contact_person_name']}");
                $user_fcm = $order?->guest?->fcm_token;

            }else{

                $value = self::order_status_update_message($status,$order->customer?$order->customer->current_language_key:'en');
                $value = self::text_variable_data_format(value:$value,user_name:"{$order->customer?->f_name} {$order->customer?->l_name}",restaurant_name:$order->restaurant?->name,order_id:$order->id);
                $user_fcm = $order?->customer?->cm_firebase_token;
            }

            if ($value && $user_fcm) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => $value,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                self::send_push_notif_to_device($user_fcm, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'user_id' => $order->user_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            if($order?->customer && $order->order_status == 'refund_request_canceled'){
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.Your_refund_request_has_been_canceled'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                self::send_push_notif_to_device($order?->customer?->cm_firebase_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'user_id' => $order->user_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            if ($status == 'picked_up') {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => $value,
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status',
                ];
                self::send_push_notif_to_device($order->restaurant->vendor->firebase_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'vendor_id' => $order->restaurant->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            if ($order->order_type == 'delivery' && !$order->scheduled && $order->order_status == 'pending' && $order->payment_method == 'cash_on_delivery' && config('order_confirmation_model') == 'deliveryman' && $order->order_type != 'take_away') {
                // if ($order->restaurant->self_delivery_system)
                if (($order->restaurant->restaurant_model == 'commission' && $order->restaurant->self_delivery_system)
                || ($order->restaurant->restaurant_model == 'subscription' &&  isset($order->restaurant->restaurant_sub) && $order->restaurant->restaurant_sub->self_delivery)
                )
                {
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.new_order_push_description'),
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'new_order',
                    ];
                    self::send_push_notif_to_device($order->restaurant->vendor->firebase_token, $data);
                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'vendor_id' => $order->restaurant->vendor_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $web_push_link = url('/').'/restaurant-panel/order/list/all';
                    self::send_push_notif_to_topic($data, "restaurant_panel_{$order->restaurant_id}_message", 'new_order', $web_push_link);
                } else {
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.new_order_push_description'),
                        'order_id' => $order->id,
                        'image' => '',
                    ];

                    if($order->zone){
                        if($order->vehicle_id){
                            $topic = 'delivery_man_'.$order->zone_id.'_'.$order->vehicle_id;
                            self::send_push_notif_to_topic($data, $topic, 'order_request');
                        }
                        self::send_push_notif_to_topic($data, $order->zone->deliveryman_wise_topic, 'order_request');
                    }
                }
            }

            if ($order->order_type == 'delivery' && !$order->scheduled && $order->order_status == 'pending' && $order->payment_method == 'cash_on_delivery' && config('order_confirmation_model') == 'restaurant') {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.new_order_push_description'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'new_order',
                ];
                self::send_push_notif_to_device($order->restaurant->vendor->firebase_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'vendor_id' => $order->restaurant->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $web_push_link = url('/').'/restaurant-panel/order/list/all';
                self::send_push_notif_to_topic($data, "restaurant_panel_{$order->restaurant_id}_message", 'new_order', $web_push_link);
            }

            if (!$order->scheduled && (($order->order_type == 'take_away' && $order->order_status == 'pending') || ($order->payment_method != 'cash_on_delivery' && $order->order_status == 'confirmed'))) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.new_order_push_description'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'new_order',
                ];
                self::send_push_notif_to_device($order->restaurant->vendor->firebase_token, $data);

                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'vendor_id' => $order->restaurant->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $web_push_link = url('/').'/restaurant-panel/order/list/all';
                self::send_push_notif_to_topic($data, "restaurant_panel_{$order->restaurant->id}_message", 'new_order', $web_push_link);
            }

            if ($order->order_status == 'confirmed' && $order->order_type != 'take_away' && config('order_confirmation_model') == 'deliveryman' && $order->payment_method == 'cash_on_delivery') {
                if ($order->restaurant->restaurant_model == 'commission' && $order->restaurant->self_delivery_system
                || ($order->restaurant->restaurant_model == 'subscription' &&  isset($order->restaurant->restaurant_sub) && $order->restaurant->restaurant_sub->self_delivery)
                ) {
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.new_order_push_description'),
                        'order_id' => $order->id,
                        'image' => '',
                    ];

                    self::send_push_notif_to_topic($data, "restaurant_dm_" . $order->restaurant_id, 'new_order');
                } else {
                    $data = [
                        'title' => translate('messages.order_push_title'),
                        'description' => translate('messages.new_order_push_description'),
                        'order_id' => $order->id,
                        'image' => '',
                        'type' => 'new_order',
                    ];

                    self::send_push_notif_to_device($order->restaurant->vendor->firebase_token, $data);

                    DB::table('user_notifications')->insert([
                        'data' => json_encode($data),
                        'vendor_id' => $order->restaurant->vendor_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $web_push_link = url('/').'/restaurant-panel/order/list/all';
                    self::send_push_notif_to_topic($data, "restaurant_panel_{$order->restaurant_id}_message", 'new_order', $web_push_link);
                }
            }

            if ($order->order_type == 'delivery' && !$order->scheduled && $order->order_status == 'confirmed'  && ($order->payment_method != 'cash_on_delivery' || config('order_confirmation_model') == 'restaurant')) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => translate('messages.new_order_push_description'),
                    'order_id' => $order->id,
                    'image' => '',
                ];
                if (($order->restaurant->restaurant_model == 'commission' && $order->restaurant->self_delivery_system)
                || ($order->restaurant->restaurant_model == 'subscription' &&  isset($order->restaurant->restaurant_sub) && $order->restaurant->restaurant_sub->self_delivery)
                )
                {
                    self::send_push_notif_to_topic($data, "restaurant_dm_" . $order->restaurant_id, 'order_request');
                } else {
                    if($order->zone){
                        if($order->vehicle_id){
                            $topic = 'delivery_man_'.$order->zone_id.'_'.$order->vehicle_id;
                            self::send_push_notif_to_topic($data, $topic, 'order_request');
                        }
                        self::send_push_notif_to_topic($data, $order->zone->deliveryman_wise_topic, 'order_request');
                    }
                }
            }

            if (in_array($order->order_status, ['processing', 'handover']) && $order->delivery_man) {
                $data = [
                    'title' => translate('messages.order_push_title'),
                    'description' => $order->order_status == 'processing' ? translate('messages.Proceed_for_cooking') : translate('messages.ready_for_delivery'),
                    'order_id' => $order->id,
                    'image' => '',
                    'type' => 'order_status'
                ];
                self::send_push_notif_to_device($order->delivery_man->fcm_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'delivery_man_id' => $order->delivery_man->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $mail_status = Helpers::get_mail_status('place_order_mail_status_user');
            try {
                if ($order->order_status == 'confirmed' && $order->payment_method != 'cash_on_delivery' && config('mail.status') && $mail_status == '1' && $order->is_guest == 0) {
                        Mail::to($order->customer->email)->send(new PlaceOrder($order->id));
                }
                $order_verification_mail_status = Helpers::get_mail_status('order_verification_mail_status_user');
                if ($order->order_status == 'pending'  && config('order_delivery_verification') == 1 && $order_verification_mail_status == '1' && $order->is_guest == 0) {
                    Mail::to($order->customer->email)->send(new OrderVerificationMail($order->otp,$order->customer->f_name));
                }
            } catch (\Exception $e) {
                info(["line___{$e->getLine()}",$e->getMessage()]);
            }
            return true;
        } catch (\Exception $e) {
            info(["line___{$e->getLine()}",$e->getMessage()]);
        }
        return false;
    }

    public static function day_part()
    {
        $part = "";
        $morning_start = date("h:i:s", strtotime("5:00:00"));
        $afternoon_start = date("h:i:s", strtotime("12:01:00"));
        $evening_start = date("h:i:s", strtotime("17:01:00"));
        $evening_end = date("h:i:s", strtotime("21:00:00"));

        if (time() >= $morning_start && time() < $afternoon_start) {
            $part = "morning";
        } elseif (time() >= $afternoon_start && time() < $evening_start) {
            $part = "afternoon";
        } elseif (time() >= $evening_start && time() <= $evening_end) {
            $part = "evening";
        } else {
            $part = "night";
        }

        return $part;
    }

    public static function env_update($key, $value)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $key . '=' . env($key),
                $key . '=' . $value,
                file_get_contents($path)
            ));
        }
    }

    public static function env_key_replace($key_from, $key_to, $value)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                $key_from . '=' . env($key_from),
                $key_to . '=' . $value,
                file_get_contents($path)
            ));
        }
    }

    public static  function remove_dir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") Helpers::remove_dir($dir . "/" . $object);
                    else unlink($dir . "/" . $object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public static function get_restaurant_id()
    {
        if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user()->restaurant->id;
        }
        return auth('vendor')->user()->restaurants[0]->id;
    }

    public static function get_vendor_id()
    {
        if (auth('vendor')->check()) {
            return auth('vendor')->id();
        } else if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user()->vendor_id;
        }
        return 0;
    }

    public static function get_vendor_data()
    {
        if (auth('vendor')->check()) {
            return auth('vendor')->user();
        } else if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user()->vendor;
        }
        return 0;
    }

    public static function get_loggedin_user()
    {
        if (auth('vendor')->check()) {
            return auth('vendor')->user();
        } else if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user();
        }
        return 0;
    }

    public static function get_restaurant_data()
{
    if (auth('vendor_employee')->check()) {
        $employee = auth('vendor_employee')->user();
        if ($employee && $employee->restaurant) {
            return $employee->restaurant;
        }
        return null;
    }

    if (auth('vendor')->check()) {
        $vendor = auth('vendor')->user();
        if ($vendor && isset($vendor->restaurants[0])) {
            return $vendor->restaurants[0];
        }
        return null;
    }

    return null;
}

    public static function upload(string $dir, string $format, $image = null)
    {
        if ($image != null) {
            $imageName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . "." . $format;
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }
            Storage::disk('public')->put($dir . $imageName, file_get_contents($image));
            return $imageName;
        }
    }

    public static function update(string $dir, $old_image, string $format, $image = null)
    {
        if ($image == null) {
            return $old_image;
        }
        if (Storage::disk('public')->exists($dir . $old_image)) {
            Storage::disk('public')->delete($dir . $old_image);
        }
        $imageName = Helpers::upload($dir, $format, $image);
        return $imageName;
    }

    public static function format_coordiantes($coordinates)
    {
        $data = [];
        foreach ($coordinates as $coord) {
            $data[] = (object)['lat' => $coord[1], 'lng' => $coord[0]];
        }
        return $data;
    }

    public static function module_permission_check($mod_name)
    {

        if (!auth('admin')->user()->role) {
            return false;
        }

        if ($mod_name == 'zone' && auth('admin')->user()->zone_id) {
            return false;
        }

        $permission = auth('admin')->user()->role->modules;
        if (isset($permission) && in_array($mod_name, (array)json_decode($permission)) == true) {
            return true;
        }

        if (auth('admin')->user()->role_id == 1) {
            return true;
        }
        return false;
    }

    public static function employee_module_permission_check($mod_name)
    {

        if (auth('vendor')->check()) {
            if ($mod_name == 'reviews' ) {
                return auth('vendor')->user()->restaurants[0]->reviews_section ;
            } else if ($mod_name == 'deliveryman') {
                return auth('vendor')->user()->restaurants[0]->self_delivery_system;
            } else if ($mod_name == 'pos') {
                return auth('vendor')->user()->restaurants[0]->pos_system;
            }
            return true;
        } else if (auth('vendor_employee')->check()) {
            $permission = auth('vendor_employee')->user()->role->modules;
            if (isset($permission) && in_array($mod_name, (array)json_decode($permission)) == true) {
                if ($mod_name == 'reviews') {
                    return auth('vendor_employee')->user()->restaurant->reviews_section;
                } else if ($mod_name == 'deliveryman') {
                    return auth('vendor_employee')->user()->restaurant->self_delivery_system;
                } else if ($mod_name == 'pos') {
                    return auth('vendor_employee')->user()->restaurant->pos_system;
                }
                return true;
            }
        }
        return false;
    }


    public static function calculate_addon_price($addons, $add_on_qtys)
    {
        $add_ons_cost = 0;
        $data = [];
        if ($addons) {
            foreach ($addons as $key2 => $addon) {
                if ($add_on_qtys == null) {
                    $add_on_qty = 1;
                } else {
                    $add_on_qty = $add_on_qtys[$key2];
                }
                $data[] = ['id' => $addon->id, 'name' => $addon->name, 'price' => $addon->price, 'quantity' => $add_on_qty];
                $add_ons_cost += $addon['price'] * $add_on_qty;
            }
            return ['addons' => $data, 'total_add_on_price' => $add_ons_cost];
        }
        return null;
    }

    public static function get_settings($name)
    {
        $config = null;
        $data = BusinessSetting::where(['key' => $name])->first();
        if (isset($data)) {
            $config = json_decode($data['value'], true);
            if (is_null($config)) {
                $config = $data['value'];
            }
        }
        return $config;
    }

    public static function setEnvironmentValue($envKey, $envValue)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);
        $oldValue = env($envKey);
        if (strpos($str, $envKey) !== false) {
            $str = str_replace("{$envKey}={$oldValue}", "{$envKey}={$envValue}", $str);
        } else {
            $str .= "{$envKey}={$envValue}\n";
        }
        $fp = fopen($envFile, 'w');
        fwrite($fp, $str);
        fclose($fp);
        return $envValue;
    }


    public static function requestSender()
    {
        $class = new LaravelchkController();
        $response = $class->actch();
        return json_decode($response->getContent(), true);
    }


    public static function insert_business_settings_key($key, $value = null)
    {
        $data =  BusinessSetting::where('key', $key)->first();
        if (!$data) {
            DB::table('business_settings')->updateOrInsert(['key' => $key], [
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return true;
    }
    public static function insert_data_settings_key($key,$type, $value = null)
    {
        $data =  DataSetting::where('key', $key)->where('type', $type)->first();
        if (!$data) {
            DataSetting::updateOrCreate(['key' => $key,'type' => $type ], [
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return true;
    }

    public static function get_language_name($key)
    {
        $languages = array(
            "af" => "Afrikaans",
            "sq" => "Albanian - shqip",
            "am" => "Amharic - አማርኛ",
            "ar" => "Arabic - العربية",
            "an" => "Aragonese - aragonés",
            "hy" => "Armenian - հայերեն",
            "ast" => "Asturian - asturianu",
            "az" => "Azerbaijani - azərbaycan dili",
            "eu" => "Basque - euskara",
            "be" => "Belarusian - беларуская",
            "bn" => "Bengali - বাংলা",
            "bs" => "Bosnian - bosanski",
            "br" => "Breton - brezhoneg",
            "bg" => "Bulgarian - български",
            "ca" => "Catalan - català",
            "ckb" => "Central Kurdish - کوردی (دەستنوسی عەرەبی)",
            "zh" => "Chinese - 中文",
            "zh-HK" => "Chinese (Hong Kong) - 中文（香港）",
            "zh-CN" => "Chinese (Simplified) - 中文（简体）",
            "zh-TW" => "Chinese (Traditional) - 中文（繁體）",
            "co" => "Corsican",
            "hr" => "Croatian - hrvatski",
            "cs" => "Czech - čeština",
            "da" => "Danish - dansk",
            "nl" => "Dutch - Nederlands",
            "en" => "English",
            "en-AU" => "English (Australia)",
            "en-CA" => "English (Canada)",
            "en-IN" => "English (India)",
            "en-NZ" => "English (New Zealand)",
            "en-ZA" => "English (South Africa)",
            "en-GB" => "English (United Kingdom)",
            "en-US" => "English (United States)",
            "eo" => "Esperanto - esperanto",
            "et" => "Estonian - eesti",
            "fo" => "Faroese - føroyskt",
            "fil" => "Filipino",
            "fi" => "Finnish - suomi",
            "fr" => "French - français",
            "fr-CA" => "French (Canada) - français (Canada)",
            "fr-FR" => "French (France) - français (France)",
            "fr-CH" => "French (Switzerland) - français (Suisse)",
            "gl" => "Galician - galego",
            "ka" => "Georgian - ქართული",
            "de" => "German - Deutsch",
            "de-AT" => "German (Austria) - Deutsch (Österreich)",
            "de-DE" => "German (Germany) - Deutsch (Deutschland)",
            "de-LI" => "German (Liechtenstein) - Deutsch (Liechtenstein)",
            "de-CH" => "German (Switzerland) - Deutsch (Schweiz)",
            "el" => "Greek - Ελληνικά",
            "gn" => "Guarani",
            "gu" => "Gujarati - ગુજરાતી",
            "ha" => "Hausa",
            "haw" => "Hawaiian - ʻŌlelo Hawaiʻi",
            "he" => "Hebrew - עברית",
            "hi" => "Hindi - हिन्दी",
            "hu" => "Hungarian - magyar",
            "is" => "Icelandic - íslenska",
            "id" => "Indonesian - Indonesia",
            "ia" => "Interlingua",
            "ga" => "Irish - Gaeilge",
            "it" => "Italian - italiano",
            "it-IT" => "Italian (Italy) - italiano (Italia)",
            "it-CH" => "Italian (Switzerland) - italiano (Svizzera)",
            "ja" => "Japanese - 日本語",
            "kn" => "Kannada - ಕನ್ನಡ",
            "kk" => "Kazakh - қазақ тілі",
            "km" => "Khmer - ខ្មែរ",
            "ko" => "Korean - 한국어",
            "ku" => "Kurdish - Kurdî",
            "ky" => "Kyrgyz - кыргызча",
            "lo" => "Lao - ລາວ",
            "la" => "Latin",
            "lv" => "Latvian - latviešu",
            "ln" => "Lingala - lingála",
            "lt" => "Lithuanian - lietuvių",
            "mk" => "Macedonian - македонски",
            "ms" => "Malay - Bahasa Melayu",
            "ml" => "Malayalam - മലയാളം",
            "mt" => "Maltese - Malti",
            "mr" => "Marathi - मराठी",
            "mn" => "Mongolian - монгол",
            "ne" => "Nepali - नेपाली",
            "no" => "Norwegian - norsk",
            "nb" => "Norwegian Bokmål - norsk bokmål",
            "nn" => "Norwegian Nynorsk - nynorsk",
            "oc" => "Occitan",
            "or" => "Oriya - ଓଡ଼ିଆ",
            "om" => "Oromo - Oromoo",
            "ps" => "Pashto - پښتو",
            "fa" => "Persian - فارسی",
            "pl" => "Polish - polski",
            "pt" => "Portuguese - português",
            "pt-BR" => "Portuguese (Brazil) - português (Brasil)",
            "pt-PT" => "Portuguese (Portugal) - português (Portugal)",
            "pa" => "Punjabi - ਪੰਜਾਬੀ",
            "qu" => "Quechua",
            "ro" => "Romanian - română",
            "mo" => "Romanian (Moldova) - română (Moldova)",
            "rm" => "Romansh - rumantsch",
            "ru" => "Russian - русский",
            "gd" => "Scottish Gaelic",
            "sr" => "Serbian - српски",
            "sh" => "Serbo-Croatian - Srpskohrvatski",
            "sn" => "Shona - chiShona",
            "sd" => "Sindhi",
            "si" => "Sinhala - සිංහල",
            "sk" => "Slovak - slovenčina",
            "sl" => "Slovenian - slovenščina",
            "so" => "Somali - Soomaali",
            "st" => "Southern Sotho",
            "es" => "Spanish - español",
            "es-AR" => "Spanish (Argentina) - español (Argentina)",
            "es-419" => "Spanish (Latin America) - español (Latinoamérica)",
            "es-MX" => "Spanish (Mexico) - español (México)",
            "es-ES" => "Spanish (Spain) - español (España)",
            "es-US" => "Spanish (United States) - español (Estados Unidos)",
            "su" => "Sundanese",
            "sw" => "Swahili - Kiswahili",
            "sv" => "Swedish - svenska",
            "tg" => "Tajik - тоҷикӣ",
            "ta" => "Tamil - தமிழ்",
            "tt" => "Tatar",
            "te" => "Telugu - తెలుగు",
            "th" => "Thai - ไทย",
            "ti" => "Tigrinya - ትግርኛ",
            "to" => "Tongan - lea fakatonga",
            "tr" => "Turkish - Türkçe",
            "tk" => "Turkmen",
            "tw" => "Twi",
            "uk" => "Ukrainian - українська",
            "ur" => "Urdu - اردو",
            "ug" => "Uyghur",
            "uz" => "Uzbek - o‘zbek",
            "vi" => "Vietnamese - Tiếng Việt",
            "wa" => "Walloon - wa",
            "cy" => "Welsh - Cymraeg",
            "fy" => "Western Frisian",
            "xh" => "Xhosa",
            "yi" => "Yiddish",
            "yo" => "Yoruba - Èdè Yorùbá",
            "zu" => "Zulu - isiZulu",
        );
        return array_key_exists($key, $languages) ? $languages[$key] : $key;
    }

    public static function get_view_keys()
    {
        $keys = BusinessSetting::whereIn('key', ['toggle_veg_non_veg', 'toggle_dm_registration', 'toggle_restaurant_registration'])->get();
        $data = [];
        foreach ($keys as $key) {
            $data[$key->key] = (bool)$key->value ?? 0;
        }
        return $data;
    }

    // public static function default_lang()
    // {
    //     if (strpos(url()->current(), '/api')) {
    //         $lang = App::getLocale();
    //     } elseif ( auth('admin')?->check() && session()->has('local')) {
    //         $lang = session('local');
    //     }elseif ((auth('vendor_employee')?->check() || auth('vendor')?->check()) && session()->has('vendor_local')) {
    //         $lang = session('vendor_local');
    //     }
    //     elseif (session()->has('landing_local')) {
    //         $lang = session('landing_local');
    //     }
    //     elseif (session()->has('local')) {
    //         $lang = session('local');
    //     } else {
    //         $data = Helpers::get_business_settings('language');
    //         $code = 'en';
    //         $direction = 'ltr';
    //         foreach ($data as $ln) {
    //             if (is_array($ln) && array_key_exists('default', $ln) && $ln['default']) {
    //                 $code = $ln['code'];
    //                 if (array_key_exists('direction', $ln)) {
    //                     $direction = $ln['direction'];
    //                 }
    //             }
    //         }
    //         session()->put('local', $code);
    //         $lang = $code;
    //     }
    //     return $lang;
    // }


    public static function default_lang()
    {
        if (strpos(url()->current(), '/api')) {
            $lang = App::getLocale();
        } elseif ( request()->is('admin*') && auth('admin')?->check() && session()->has('local')) {
            $lang = session('local');
        }elseif (request()->is('restaurant-panel/*') && (auth('vendor_employee')?->check() || auth('vendor')?->check()) && session()->has('vendor_local')) {
            $lang = session('vendor_local');
        }
        elseif (session()->has('landing_local')) {
            $lang = session('landing_local');
        }
        elseif (session()->has('local')) {
            $lang = session('local');
        } else {
            $data = Helpers::get_business_settings('language');
            $code = 'en';
            $direction = 'ltr';
            foreach ($data as $ln) {
                if (is_array($ln) && array_key_exists('default', $ln) && $ln['default']) {
                    $code = $ln['code'];
                    if (array_key_exists('direction', $ln)) {
                        $direction = $ln['direction'];
                    }
                }
            }
            session()->put('local', $code);
            $lang = $code;
        }
        return $lang;
    }

    public static function system_default_language()
    {
        $languages = json_decode(\App\Models\BusinessSetting::where('key', 'system_language')->first()?->value);
        $lang = 'en';

        foreach ($languages as $key => $language) {
            if($language->default){
                $lang = $language->code;
            }
        }
        return $lang;
    }
    public static function system_default_direction()
    {
        $languages = json_decode(\App\Models\BusinessSetting::where('key', 'system_language')->first()?->value);
        $lang = 'en';

        foreach ($languages as $key => $language) {
            if($language->default){
                $lang = $language->direction;
            }
        }
        return $lang;
    }

    public static function generate_referer_code() {
        $ref_code = strtoupper(Str::random(10));
        if (self::referer_code_exists($ref_code)) {
            return self::generate_referer_code();
        }
        return $ref_code;
    }

    public static function referer_code_exists($ref_code) {
        return User::where('ref_code', '=', $ref_code)->exists();
    }


    public static function remove_invalid_charcaters($str)
    {
        return str_ireplace(['\'', '"', ',', ';', '<', '>', '?'], ' ', $str);
    }

    public static function set_time_log($user_id , $date, $online = null, $offline = null,$shift_id = null)
    {
        try {
            $time_log = TimeLog::where(['user_id'=>$user_id, 'date'=>$date ,'shift_id'  => $shift_id])->first();

            if($time_log && $time_log->online && $online) return true;

            if($time_log && $offline) {
                $time_log->offline = $offline;

                if($time_log->online){
                    $time_log->working_hour = (strtotime($offline) - strtotime($time_log->online))/60;
                }
                else{
                    $time_log->online =$offline;
                    $time_log->working_hour =  0;
                }

                $time_log->shift_id = $shift_id;
                $time_log->save();
                return true;
            }

            if(!$time_log){
                $time_log = new TimeLog;
                $time_log->date = $date;
                $time_log->user_id = $user_id;
                $time_log->offline = $offline;
                $time_log->online = $online ?? $offline ;
                $time_log->working_hour =0;
                $time_log->shift_id = $shift_id;
                $time_log->save();
            }
            return true;
        } catch(\Exception $e) {
            info(["line___{$e->getLine()}",$e->getMessage()]);
        }
        return false;
    }

    public static function push_notification_export_data($data){
        $format = [];
        foreach($data as $key=>$item){
            $format[] =[
                '#'=>$key+1,
                translate('title')=>$item['title'],
                translate('description')=>$item['description'],
                translate('zone')=>$item->zone ? $item->zone->name : translate('messages.all_zones'),
                translate('tergat')=>$item['tergat'],
                translate('status')=>$item['status']
            ];
        }
        return $format;
    }


    public static function export_restaurants($collection){
        $data = [];

        foreach($collection as $key=>$item){
            // $data[] = [
            //     'SL'=>$key+1,
            //     translate('messages.restaurant_name')=> $item['name'],
            //     translate('messages.owner_information') => $item->vendor->f_name.' '.$item->vendor->l_name,
            //     translate('messages.phone') => $item->vendor->phone,
            //     translate('messages.zone') => $item->zone->name,
            //     translate('messages.status') => $item['status'] ? translate('messages.active') : translate('messages.inactive'),
            // ];



            $data[] = [
                'id'=>$item->id,
                'ownerID'=>$item->vendor->id,
                'ownerFirstName'=>$item->vendor->f_name,
                'ownerLastName'=>$item->vendor->l_name,
                'restaurantName'=>$item->name,
                'CoverPhoto'=>$item->cover_photo,
                'logo'=>$item->logo,
                'phone'=>$item->vendor->phone,
                'email'=>$item->vendor->email,
                'latitude'=>$item->latitude,
                'longitude'=>$item->longitude,
                'zone_id'=>$item->zone_id,
                'Address'=>$item->address ?? null,
                'Slug'=> $item->slug  ?? null,
                'MinimumOrderAmount'=>$item->minimum_order,
                'Comission'=>$item->comission ?? 0,
                'Tax'=>$item->tax ?? 0,

                'DeliveryTime'=>$item->delivery_time ?? '20-30',
                'MinimumDeliveryFee'=>$item->minimum_shipping_charge ?? 0,
                'PerKmDeliveryFee'=>$item->per_km_shipping_charge ?? 0,
                'MaximumDeliveryFee'=>$item->maximum_shipping_charge ?? 0,
                // 'order_count'=>$item->order_count,
                // 'total_order'=>$item->total_order,
                'RestaurantModel'=>$item->restaurant_model,
                'ScheduleOrder'=> $item->schedule_order == 1 ? 'yes' : 'no',
                'FreeDelivery'=> $item->free_delivery == 1 ? 'yes' : 'no',
                'TakeAway'=> $item->take_away == 1 ? 'yes' : 'no',
                'Delivery'=> $item->delivery == 1 ? 'yes' : 'no',
                'Veg'=> $item->veg == 1 ? 'yes' : 'no',
                'NonVeg'=> $item->non_veg == 1 ? 'yes' : 'no',
                'OrderSubscription'=> $item->order_subscription_active == 1 ? 'yes' : 'no',
                'Status'=> $item->status == 1 ? 'active' : 'inactive',
                'FoodSection'=> $item->food_section == 1 ? 'active' : 'inactive',
                'ReviewsSection'=> $item->reviews_section == 1 ? 'active' : 'inactive',
                'SelfDeliverySystem'=> $item->self_delivery_system == 1 ? 'active' : 'inactive',
                'PosSystem'=> $item->pos_system == 1 ? 'active' : 'inactive',
                'RestaurantOpen'=> $item->active == 1 ? 'yes' : 'no',
                // 'gst'=>$item->restaurants[0]->gst ?? null,
            ];
        }

        return $data;
    }


    public static function export_attributes($collection){
        $data = [];
        foreach($collection as $key=>$item){
            $data[] = [
                'SL'=>$key+1,
                 translate('messages.id') => $item['id'],
                 translate('messages.name') => $item['name'],
            ];
        }

        return $data;
    }

    public static function get_varient(array $product_variations, array $variations)
    {
        $result = [];
        $variation_price = 0;

        foreach($variations as $k=> $variation){
            foreach($product_variations as  $product_variation){
                if( isset($variation['values']) && isset($product_variation['values']) && $product_variation['name'] == $variation['name']  ){
                    $result[$k] = $product_variation;
                    $result[$k]['values'] = [];
                    foreach($product_variation['values'] as $key=> $option){
                        if(in_array($option['label'], $variation['values']['label'])){
                            $result[$k]['values'][] = $option;
                            $variation_price += $option['optionPrice'];
                        }
                    }
                }
            }
        }

        return ['price'=>$variation_price,'variations'=>$result];
    }




    public Static function subscription_check()
    {
        $business_model= BusinessSetting::where('key', 'business_model')->first();
        if(!$business_model)
            {
                Helpers::insert_business_settings_key('refund_active_status', '1');
                Helpers::insert_business_settings_key('business_model',
                json_encode([
                    'commission'        =>  1,
                    'subscription'     =>  0,
                ]));
                $business_model = [
                    'commission'        =>  1,
                    'subscription'     =>  0,
                ];
            } else{
                $business_model = $business_model->value ? json_decode($business_model->value, true) : [
                    'commission'        =>  1,
                    'subscription'     =>  0,
                ];
            }

        if ($business_model['subscription'] == 1 ){
            return true;
        }
        return false;
    }

    public Static function commission_check()
    {
        $business_model= BusinessSetting::where('key', 'business_model')->first();
        if(!$business_model)
            {
                Helpers::insert_business_settings_key('business_model',
                json_encode([
                    'commission'        =>  1,
                    'subscription'     =>  0,
                ]));
                $business_model = [
                    'commission'        =>  1,
                    'subscription'     =>  0,
                ];
            } else{
                $business_model = $business_model->value ? json_decode($business_model->value, true) : [
                    'commission'        =>  1,
                    'subscription'     =>  0,
                ];
            }

        if ($business_model['commission'] == 1 ){
            return true;
        }
        return false;
    }

    public static function check_subscription_validity()
    {
        $current_date = date('Y-m-d');
        $check_subscription_validity_on= BusinessSetting::where('key', 'check_subscription_validity_on')->first();
        if(!$check_subscription_validity_on){
            Helpers::insert_business_settings_key('check_subscription_validity_on', date('Y-m-d'));
        }
        if($check_subscription_validity_on && $check_subscription_validity_on->value != $current_date){
            Restaurant::whereHas('restaurant_subs',function ($query)use($current_date){
                $query->where('status',1)->where('expiry_date', '<', $current_date);
            })->update(['status' => 0,
                        'pos_system'=>1,
                        'self_delivery_system'=>1,
                        'reviews_section'=>1,
                        'free_delivery'=>0,
                        'restaurant_model'=>'unsubscribed',
                        ]);
            RestaurantSubscription::where('status',1)->where('expiry_date', '<', $current_date)->update([
                'status' => 0
            ]);
            $check_subscription_validity_on->value=$current_date;
            $check_subscription_validity_on->save();
            Helpers::create_subscription_order_logs();
        }
        return false;
    }

    public static function subscription_plan_chosen($restaurant_id ,$package_id, $payment_method  ,$discount,$reference=null ,$type=null){
        $restaurant=Restaurant::findOrFail($restaurant_id);
        $package = SubscriptionPackage::withoutGlobalScope('translate')->findOrFail($package_id);
        $add_days=0;
        $add_orders=0;
        $total_food= $restaurant->foods()->withoutGlobalScope(\App\Scopes\RestaurantScope::class)->count();
        if ($package->max_product != 'unlimited' &&  $total_food >= $package->max_product  ){
            return 'downgrade_error';
        }
        try {
            $restaurant_subscription=$restaurant->restaurant_sub;
            if (isset($restaurant_subscription) && $type == 'renew') {
                $restaurant_subscription->total_package_renewed= $restaurant_subscription->total_package_renewed + 1;
                $day_left=$restaurant_subscription->expiry_date->format('Y-m-d');
                if (Carbon::now()->subDays(1)->diffInDays($day_left, false) > 0) {
                    $add_days= Carbon::now()->subDays(1)->diffInDays($day_left, false);
                }
                if ($restaurant_subscription->max_order != 'unlimited' && $restaurant_subscription->max_order > 0) {
                    $add_orders=$restaurant_subscription->max_order;
                }
            } else{
                RestaurantSubscription::where('restaurant_id',$restaurant->id)->update([
                    'status' => 0,
                ]);
                $restaurant_subscription =new RestaurantSubscription();
                $restaurant_subscription->total_package_renewed= 0;

            }

            $restaurant_subscription->package_id=$package->id;
            $restaurant_subscription->restaurant_id=$restaurant->id;
            if ($payment_method  == 'free_trial' ) {
                $free_trial_period_data = BusinessSetting::where(['key' => 'free_trial_period'])->first();
                if ($free_trial_period_data == false) {
                    $values= [
                        'data' => 7,
                        'status' => 1,
                    ];
                    Helpers::insert_business_settings_key('free_trial_period',  json_encode($values) );
                }
                $free_trial_period_data = json_decode(BusinessSetting::where(['key' => 'free_trial_period'])->first()->value,true);
                $free_trial_period= $free_trial_period_data['data'];
                $restaurant_subscription->expiry_date= Carbon::now()->addDays($free_trial_period)->format('Y-m-d');
            }
            else{
                $restaurant_subscription->expiry_date= Carbon::now()->addDays($package->validity+$add_days)->format('Y-m-d');
            }
            if($package->max_order != 'unlimited'){
                $restaurant_subscription->max_order=$package->max_order + $add_orders;
            } else{
                $restaurant_subscription->max_order=$package->max_order;
            }


            $restaurant_subscription->max_product=$package->max_product;
            $restaurant_subscription->pos=$package->pos;
            $restaurant_subscription->mobile_app=$package->mobile_app;
            $restaurant_subscription->chat=$package->chat;
            $restaurant_subscription->review=$package->review;
            $restaurant_subscription->self_delivery=$package->self_delivery;

            $restaurant->food_section= 1;
            $restaurant->pos_system= 1;
            if ($type == 'new_join' && $restaurant->vendor?->status == 0 ) {
                $restaurant->status= 0;
                $restaurant_subscription->status= 0;

            }else{
                $restaurant->status= 1;
                $restaurant_subscription->status= 1;

            }

            // For Restaurant Free Delivery
            if($restaurant->free_delivery == 1 && $package->self_delivery == 1){
                $restaurant->free_delivery = 1 ;
            } else{
                $restaurant->free_delivery = 0 ;
                $restaurant->coupon()->where('created_by','vendor')->where('coupon_type','free_delivery')->delete();
            }


            $restaurant->reviews_section= 1;
            $restaurant->self_delivery_system= 1;
            $restaurant->restaurant_model= 'subscription';

            $subscription_transaction= new SubscriptionTransaction();
            $subscription_transaction_ID= Str::uuid();
            $subscription_transaction->id=  $subscription_transaction_ID;
            $subscription_transaction->package_id=$package->id;
            $subscription_transaction->restaurant_id=$restaurant->id;
            $subscription_transaction->price=$package->price;

            $subscription_transaction->validity=$package->validity;
            $subscription_transaction->paid_amount= $package->price - (($package->price*$discount)/100);

            if ($payment_method  == 'free_trial') {
                $subscription_transaction->validity= $free_trial_period;
                $subscription_transaction->paid_amount= 0;
            }
            elseif($payment_method  == 'pay_now'){
                $subscription_transaction->payment_status ='on_hold';
                $subscription_transaction->transaction_status = 0;
                $restaurant_subscription->status= 0;
            }

            $subscription_transaction->payment_method=$payment_method;
            $subscription_transaction->reference=$reference ?? null;
            $subscription_transaction->discount=$discount ?? 0;
            if( $payment_method == 'manual_payment_admin'){
                $subscription_transaction->created_by= 'Admin';
            } else{
                $subscription_transaction->created_by= 'Restaurant';
            }

            $subscription_transaction->package_details=[
                'pos'=>$package->pos,
                'review'=>$package->review,
                'self_delivery'=>$package->self_delivery,
                'chat'=>$package->chat,
                'mobile_app'=>$package->mobile_app,
                'max_order'=>$package->max_order,
                'max_product'=>$package->max_product,
            ];

            DB::beginTransaction();
            $restaurant->save();
            $subscription_transaction->save();
            $restaurant_subscription->save();
            DB::commit();
        } catch(\Exception $e){
            DB::rollBack();
            info(["line___{$e->getLine()}",$e->getMessage()]);
            return false;
        }
        return  $subscription_transaction_ID;
    }
    public static function expenseCreate($amount,$type,$datetime,$created_by,$order_id=null,$restaurant_id=null,$user_id=null,$description='',$delivery_man_id=null)
    {
        $expense = new Expense();
        $expense->amount = $amount;
        $expense->type = $type;
        $expense->order_id = $order_id;
        $expense->created_by = $created_by;
        $expense->restaurant_id = $restaurant_id;
        $expense->delivery_man_id = $delivery_man_id;
        $expense->user_id = $user_id;
        $expense->description = $description;
        $expense->created_at = $datetime;
        $expense->updated_at = $datetime;
        return $expense->save();
    }
    public static function hex_to_rbg($color){
        list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
        $output = "$r, $g, $b";
        return $output;
    }

    public static function increment_order_count($data){
        $restaurant=$data;
        $rest_sub=$restaurant->restaurant_sub;
        if ( $restaurant->restaurant_model == 'subscription' && isset($rest_sub) && $rest_sub->max_order != "unlimited") {
            $rest_sub->increment('max_order', 1);
        }
        return true;
    }

    public static function react_activation_check($react_domain, $react_license_code){
        $scheme = str_contains($react_domain, 'localhost')?'http://':'https://';
        $url = empty(parse_url($react_domain)['scheme']) ? $scheme . ltrim($react_domain, '/') : $react_domain;
        $response = Http::post('https://store.6amtech.com/api/v1/customer/license-check', [
            'domain_name' => str_ireplace('www.', '', parse_url($url, PHP_URL_HOST)),
            'license_code' => $react_license_code
        ]);
        return ($response->successful() && isset($response->json('content')['is_active']) && $response->json('content')['is_active']);
    }

    public static function activation_submit($purchase_key)
    {
        $post = [
            'purchase_key' => $purchase_key
        ];
        $live = 'https://check.6amtech.com';
        $ch = curl_init($live . '/api/v1/software-check');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $response = curl_exec($ch);
        curl_close($ch);
        $response_body = json_decode($response, true);

        try {
            if ($response_body['is_valid'] && $response_body['result']['item']['id'] == env('REACT_APP_KEY')) {
                $previous_active = json_decode(BusinessSetting::where('key', 'app_activation')->first()->value ?? '[]');
                $found = 0;
                foreach ($previous_active as $key => $item) {
                    if ($item->software_id == env('REACT_APP_KEY')) {
                        $found = 1;
                    }
                }
                if (!$found) {
                    $previous_active[] = [
                        'software_id' => env('REACT_APP_KEY'),
                        'is_active' => 1
                    ];
                    DB::table('business_settings')->updateOrInsert(['key' => 'app_activation'], [
                        'value' => json_encode($previous_active)
                    ]);
                }
                return true;
            }

        } catch (\Exception $e) {
            info(["line___{$e->getLine()}",$e->getMessage()]);

            $previous_active[] = [
                'software_id' => env('REACT_APP_KEY'),
                'is_active' => 1
            ];
            DB::table('business_settings')->updateOrInsert(['key' => 'app_activation'], [
                'value' => json_encode($previous_active)
            ]);

            return true;
        }
        return false;
    }

    public static function react_domain_status_check(){
        $data = self::get_business_settings('react_setup');
        if($data && isset($data['react_domain']) && isset($data['react_license_code'])){
            if(isset($data['react_platform']) && $data['react_platform'] == 'codecanyon'){
                $data['status'] = (int)self::activation_submit($data['react_license_code']);
            }elseif(!self::react_activation_check($data['react_domain'], $data['react_license_code'])){
                $data['status']=0;
            }elseif($data['status'] != 1){
                $data['status']=1;
            }
            DB::table('business_settings')->updateOrInsert(['key' => 'react_setup'], [
                'value' => json_encode($data)
            ]);
        }
    }

    public static function number_format_short( $n ) {
        if ($n < 900) {
            // 0 - 900
            $n = $n;
            $suffix = '';
        } else if ($n < 900000) {
            // 0.9k-850k
            $n = $n / 1000;
            $suffix = 'K';
        } else if ($n < 900000000) {
            // 0.9m-850m
            $n = $n / 1000000;
            $suffix = 'M';
        } else if ($n < 900000000000) {
            // 0.9b-850b
            $n = $n / 1000000000;
            $suffix = 'B';
        } else {
            // 0.9t+
            $n = $n / 1000000000000;
            $suffix = 'T';
        }

        if(!session()->has('currency_symbol_position')){
            $currency_symbol_position = BusinessSetting::where(['key' => 'currency_symbol_position'])->first()->value;
            session()->put('currency_symbol_position',$currency_symbol_position);
        }
        $currency_symbol_position = session()->get('currency_symbol_position');

        return $currency_symbol_position == 'right' ? number_format($n, config('round_up_to_digit')).$suffix . ' ' . self::currency_symbol() : self::currency_symbol() . ' ' . number_format($n, config('round_up_to_digit')).$suffix;
    }


    public static function gen_mpdf($view, $file_prefix, $file_postfix)
    {
        $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../../storage/tmp','default_font' => 'FreeSerif', 'mode' => 'utf-8', 'format' => [190, 250]]);
        /* $mpdf->AddPage('XL', '', '', '', '', 10, 10, 10, '10', '270', '');*/
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $mpdf_view = $view;
        $mpdf_view = $mpdf_view->render();
        $mpdf->WriteHTML($mpdf_view);
        $mpdf->Output($file_prefix . $file_postfix . '.pdf', 'D');
    }


    public static function product_tax($price , $tax, $is_include=false){
        $price_tax = ($price * $tax) / (100 + ($is_include?$tax:0)) ;
        return $price_tax;
    }

    public static function dm_wallet_transaction($delivery_man_id, $amount, $referance = null, $type = 'dm_admin_bonus')
    {
        if (!$dmwallet = DeliveryManWallet::firstOrNew(['delivery_man_id' => $delivery_man_id])) return false;
        $wallet_transaction = new WalletTransaction();
        $wallet_transaction->transaction_id = Str::uuid();
        $wallet_transaction->reference = $referance;
        $wallet_transaction->transaction_type = $type;
        $wallet_transaction->admin_bonus = $amount;
        $wallet_transaction->credit = $amount;
        $wallet_transaction->debit = 0;
        $wallet_transaction->balance = $dmwallet->total_earning + $amount;
        $wallet_transaction->created_at = now();
        $wallet_transaction->updated_at = now();
        $wallet_transaction->delivery_man_id = $delivery_man_id;
        try {
            DB::beginTransaction();
            $wallet_transaction->save();
            $dmwallet->total_earning = $dmwallet->total_earning + $amount;
            $dmwallet->save();
            Helpers::expenseCreate(amount:$amount,type:$type,datetime:now(), created_by:'admin',delivery_man_id:$delivery_man_id);
            DB::commit();
            return true;
        } catch (Exception $ex) {
            DB::rollBack();
            info(['dm_wallet_transaction_error', 'code' => $ex->getLine(), 'message' => $ex->getMessage()]);
            return false;
        }
    }

    public static function get_subscription_schedules($type, $startDate, $endDate, $days)
    {
        $arrayOfDate = [];
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        $days = $type != 'daily' ? array_column($days, 'time', 'day') : $days;
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {

            if($type == 'weekly'){
                if(isset($days[$date->weekday()])){
                    $arrayOfDate[] = $date->format('Y-m-d ').$days[$date->weekday()];
                }
            }elseif($type == 'monthly'){
                if(isset($days[$date->day])){
                    $arrayOfDate[] = $date->format('Y-m-d ').$days[$date->day];
                }
            }else{
                $arrayOfDate[] = $date->format('Y-m-d ').$days[0]['itme'];
            }
        }
        return $arrayOfDate;
    }



    public static function visitor_log($model,$user_id,$visitor_log_id,$order_count=false){
            if( $model == 'restaurant' ){
                $visitor_log_type = 'App\Models\Restaurant';
            }
            else {
                $visitor_log_type = 'App\Models\Category';
            }
        VisitorLog::updateOrInsert(
            ['visitor_log_type' => $visitor_log_type,
                'user_id' => $user_id,
                'visitor_log_id' => $visitor_log_id,
            ],
            [
                'visit_count' => $order_count == false ? DB::raw('visit_count + 1') : DB::raw('visit_count'),
                'order_count' =>  $order_count == true ? DB::raw('order_count + 1') : DB::raw('order_count'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        return;
    }

    public static function getLanguageCode(string $country_code): string
    {
        $locales = array(
            'en-English(default)',
            'af-Afrikaans',
            'sq-Albanian - shqip',
            'am-Amharic - አማርኛ',
            'ar-Arabic - العربية',
            'an-Aragonese - aragonés',
            'hy-Armenian - հայերեն',
            'ast-Asturian - asturianu',
            'az-Azerbaijani - azərbaycan dili',
            'eu-Basque - euskara',
            'be-Belarusian - беларуская',
            'bn-Bengali - বাংলা',
            'bs-Bosnian - bosanski',
            'br-Breton - brezhoneg',
            'bg-Bulgarian - български',
            'ca-Catalan - català',
            'ckb-Central Kurdish - کوردی (دەستنوسی عەرەبی)',
            'zh-Chinese - 中文',
            'zh-HK-Chinese (Hong Kong) - 中文（香港）',
            'zh-CN-Chinese (Simplified) - 中文（简体）',
            'zh-TW-Chinese (Traditional) - 中文（繁體）',
            'co-Corsican',
            'hr-Croatian - hrvatski',
            'cs-Czech - čeština',
            'da-Danish - dansk',
            'nl-Dutch - Nederlands',
            'en-AU-English (Australia)',
            'en-CA-English (Canada)',
            'en-IN-English (India)',
            'en-NZ-English (New Zealand)',
            'en-ZA-English (South Africa)',
            'en-GB-English (United Kingdom)',
            'en-US-English (United States)',
            'eo-Esperanto - esperanto',
            'et-Estonian - eesti',
            'fo-Faroese - føroyskt',
            'fil-Filipino',
            'fi-Finnish - suomi',
            'fr-French - français',
            'fr-CA-French (Canada) - français (Canada)',
            'fr-FR-French (France) - français (France)',
            'fr-CH-French (Switzerland) - français (Suisse)',
            'gl-Galician - galego',
            'ka-Georgian - ქართული',
            'de-German - Deutsch',
            'de-AT-German (Austria) - Deutsch (Österreich)',
            'de-DE-German (Germany) - Deutsch (Deutschland)',
            'de-LI-German (Liechtenstein) - Deutsch (Liechtenstein)
            ',
            'de-CH-German (Switzerland) - Deutsch (Schweiz)',
            'el-Greek - Ελληνικά',
            'gn-Guarani',
            'gu-Gujarati - ગુજરાતી',
            'ha-Hausa',
            'haw-Hawaiian - ʻŌlelo Hawaiʻi',
            'he-Hebrew - עברית',
            'hi-Hindi - हिन्दी',
            'hu-Hungarian - magyar',
            'is-Icelandic - íslenska',
            'id-Indonesian - Indonesia',
            'ia-Interlingua',
            'ga-Irish - Gaeilge',
            'it-Italian - italiano',
            'it-IT-Italian (Italy) - italiano (Italia)',
            'it-CH-Italian (Switzerland) - italiano (Svizzera)',
            'ja-Japanese - 日本語',
            'kn-Kannada - ಕನ್ನಡ',
            'kk-Kazakh - қазақ тілі',
            'km-Khmer - ខ្មែរ',
            'ko-Korean - 한국어',
            'ku-Kurdish - Kurdî',
            'ky-Kyrgyz - кыргызча',
            'lo-Lao - ລາວ',
            'la-Latin',
            'lv-Latvian - latviešu',
            'ln-Lingala - lingála',
            'lt-Lithuanian - lietuvių',
            'mk-Macedonian - македонски',
            'ms-Malay - Bahasa Melayu',
            'ml-Malayalam - മലയാളം',
            'mt-Maltese - Malti',
            'mr-Marathi - मराठी',
            'mn-Mongolian - монгол',
            'ne-Nepali - नेपाली',
            'no-Norwegian - norsk',
            'nb-Norwegian Bokmål - norsk bokmål',
            'nn-Norwegian Nynorsk - nynorsk',
            'oc-Occitan',
            'or-Oriya - ଓଡ଼ିଆ',
            'om-Oromo - Oromoo',
            'ps-Pashto - پښتو',
            'fa-Persian - فارسی',
            'pl-Polish - polski',
            'pt-Portuguese - português',
            'pt-BR-Portuguese (Brazil) - português (Brasil)',
            'pt-PT-Portuguese (Portugal) - português (Portugal)',
            'pa-Punjabi - ਪੰਜਾਬੀ',
            'qu-Quechua',
            'ro-Romanian - română',
            'mo-Romanian (Moldova) - română (Moldova)',
            'rm-Romansh - rumantsch',
            'ru-Russian - русский',
            'gd-Scottish Gaelic',
            'sr-Serbian - српски',
            'sh-Serbo-Croatian - Srpskohrvatski',
            'sn-Shona - chiShona',
            'sd-Sindhi',
            'si-Sinhala - සිංහල',
            'sk-Slovak - slovenčina',
            'sl-Slovenian - slovenščina',
            'so-Somali - Soomaali',
            'st-Southern Sotho',
            'es-Spanish - español',
            'es-AR-Spanish (Argentina) - español (Argentina)',
            'es-419-Spanish (Latin America) - español (Latinoamérica)
            ',
            'es-MX-Spanish (Mexico) - español (México)',
            'es-ES-Spanish (Spain) - español (España)',
            'es-US-Spanish (United States) - español (Estados Unidos)
            ',
            'su-Sundanese',
            'sw-Swahili - Kiswahili',
            'sv-Swedish - svenska',
            'tg-Tajik - тоҷикӣ',
            'ta-Tamil - தமிழ்',
            'tt-Tatar',
            'te-Telugu - తెలుగు',
            'th-Thai - ไทย',
            'ti-Tigrinya - ትግርኛ',
            'to-Tongan - lea fakatonga',
            'tr-Turkish - Türkçe',
            'tk-Turkmen',
            'tw-Twi',
            'uk-Ukrainian - українська',
            'ur-Urdu - اردو',
            'ug-Uyghur',
            'uz-Uzbek - o‘zbek',
            'vi-Vietnamese - Tiếng Việt',
            'wa-Walloon - wa',
            'cy-Welsh - Cymraeg',
            'fy-Western Frisian',
            'xh-Xhosa',
            'yi-Yiddish',
            'yo-Yoruba - Èdè Yorùbá',
            'zu-Zulu - isiZulu',
        );

        foreach ($locales as $locale) {
            $locale_region = explode('-',$locale);
            if ($country_code == $locale_region[0]) {
                return $locale_region[0];
            }
        }

        return "en";
    }

    public static function auto_translator($q, $sl, $tl)
    {
        $res = file_get_contents("https://translate.googleapis.com/translate_a/single?client=gtx&ie=UTF-8&oe=UTF-8&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&dt=at&sl=" . $sl . "&tl=" . $tl . "&hl=hl&q=" . urlencode($q), $_SERVER['DOCUMENT_ROOT'] . "/transes.html");
        $res = json_decode($res);
        return str_replace('_',' ',$res[0][0][0]);
    }
    public static function language_load()
    {
        if (\session()->has('language_settings')) {
            $language = \session('language_settings');
        } else {
            $language = BusinessSetting::where('key', 'system_language')->first();
            \session()->put('language_settings', $language);
        }
        return $language;
    }
    public static function vendor_language_load()
    {
        if (\session()->has('vendor_language_settings')) {
            $language = \session('vendor_language_settings');
        } else {
            $language = BusinessSetting::where('key', 'system_language')->first();
            \session()->put('vendor_language_settings', $language);
        }
        return $language;
    }



    public static function create_subscription_order_logs()
    {
        $order_schedule_day=now()->dayOfWeek;
            $o=Order::HasSubscriptionTodayGet()->with(['restaurant.schedule_today','subscription.schedule_today'])->whereHas('restaurant.schedules',function ($q)use($order_schedule_day){
                $q->where('day',$order_schedule_day);
            })
            ->get();
            foreach($o as $order){
                foreach($order->restaurant->schedule_today as $rest_sh){
                    if(Carbon::parse($rest_sh->opening_time) <= Carbon::parse($order->subscription->schedule_today->time) && Carbon::parse($rest_sh->closing_time) >= Carbon::parse($order->subscription->schedule_today->time) ){
                    OrderLogic::create_subscription_log($order->id);
                    }
                }
            }
        return true;
    }


    public static function create_all_logs($object , $action_type, $model){
        $restaurant_id = null;
        if ((auth('vendor_employee')->check() || auth('vendor')->check() || request('vendor') || auth('admin')->check()) || (request()->token && DeliveryMan::where('auth_token' , request()->token)->exists()) ) {
            if (auth('vendor_employee')->check()) {
                $loable_type = 'App\Models\VendorEmployee';
                $logable_id = auth('vendor_employee')->id();
                $restaurant_id=auth('vendor_employee')->user() != null && isset(auth('vendor_employee')->user()->restaurant) ? auth('vendor_employee')->user()->restaurant->id : null;
            } elseif (auth('vendor')->check() || request('vendor')) {
                $restaurant_id=auth('vendor')->user() != null && isset(auth('vendor')->user()->restaurants[0]) ? auth('vendor')->user()->restaurants[0]->id : null;
                $loable_type = 'App\Models\Vendor';
                $logable_id = auth('vendor')->id();

                if(request('vendor')){
                    $logable_id =request('vendor')->id;
                    $restaurant_id= isset(request('vendor')->restaurants[0]) ? request('vendor')->restaurants[0]->id : null;
                }
            //    dd(request('vendor')->restaurants[0]->id);
            } elseif (auth('admin')->check()) {
                $loable_type = 'App\Models\Admin';
                $logable_id = auth('admin')->id();
            }elseif (request()->token && DeliveryMan::where('auth_token' , request()->token)->exists()) {
                $loable_type = 'App\Models\DeliveryMan';
                $dm =DeliveryMan::where('auth_token' , request()->token)->with('restaurant')->first();
                $logable_id = $dm->id;
                if($dm->type == 'restaurant_wise' && $dm->restaurant){
                    $restaurant_id= $dm->restaurant->id;
                }
            }

            $log = new Log();
            $log->logable_type = $loable_type;
            $log->logable_id = $logable_id;
            $log->action_type = $action_type;
            $log->model = $model;
            $log->restaurant_id = $restaurant_id;
            $log->model_id = $object->id;
            $log->ip_address = request()->ip();
            $log->before_state = json_encode($object->getOriginal());
            $log->after_state = json_encode($object->getDirty());
            $log->save();
        }
        return true;
    }

    public static function landing_language_load()
    {
        if (\session()->has('landing_language_settings')) {
            $language = \session('landing_language_settings');
        } else {
            $language = BusinessSetting::where('key', 'system_language')->first();
            \session()->put('landing_language_settings', $language);
        }
        return $language;
    }

    public static function generate_reset_password_code() {
        $code = strtoupper(Str::random(15));
        if (self::reset_password_code_exists($code)) {
            return self::generate_reset_password_code();
        }
        return $code;
    }

    public static function reset_password_code_exists($code) {
        return DB::table('password_resets')->where('token', '=', $code)->exists();
    }

    public static function Export_generator($datas) {
        foreach ($datas as $data) {
            yield $data;
        }
        return true;
    }

    public static function vehicle_extra_charge(float $distance_data) {
        $data =[];
        $vehicle = Vehicle::active()
        ->where(function ($query) use ($distance_data) {
            $query->where('starting_coverage_area', '<=', $distance_data)->where('maximum_coverage_area', '>=', $distance_data)
            ->orWhere(function ($query) use ($distance_data) {
                $query->where('starting_coverage_area', '>=', $distance_data);
            });
        })->orderBy('starting_coverage_area')->first();
        if(empty($vehicle)){
            $vehicle = Vehicle::active()->orderBy('maximum_coverage_area', 'desc')->first();
        }
        $data['extra_charge'] = $vehicle->extra_charges  ?? 0;
        $data['vehicle_id'] =  $vehicle->id  ?? null;
        return $data;
    }

    public static function react_services_formater($data)
    {
        $storage = [];
        foreach ($data as $item) {
            $storage[] = [
                'Id' => $item['id'],
                'Title' => $item['title'],
                'Sub_title' => $item['sub_title'],
                'Status' => $item['status'] == 1 ? 'active' : 'inactive',
            ];
        }
        $data = $storage;
        return $data;
    }
    public static function react_react_promotional_banner_formater($data)
    {
        $storage = [];
        foreach ($data as $item) {
            $storage[] = [
                'Id' => $item['id'],
                'Title' => $item['title'],
                'Description' => $item['description'],
                'Status' => $item['status'] == 1 ? 'active' : 'inactive',
            ];
        }
        $data = $storage;
        return $data;
    }

    public static function get_mail_status($name)
    {
        return BusinessSetting::where('key', $name)->first()?->value ?? 0;
    }

    public static function text_variable_data_format($value,$user_name=null,$restaurant_name=null,$delivery_man_name=null,$transaction_id=null,$order_id=null)
    {
        $data = $value;
        if ($value) {
            if($user_name){
                $data =  str_replace("{userName}", $user_name, $data);
            }

            if($restaurant_name){
                $data =  str_replace("{restaurantName}", $restaurant_name, $data);
            }

            if($delivery_man_name){
                $data =  str_replace("{deliveryManName}", $delivery_man_name, $data);
            }

            if($transaction_id){
                $data =  str_replace("{transactionId}", $transaction_id, $data);
            }

            if($order_id){
                $data =  str_replace("{orderId}", $order_id, $data);
            }
        }

        return $data;
    }

    public static function get_login_url($type){
        $data=DataSetting::whereIn('key',['restaurant_employee_login_url','restaurant_login_url','admin_employee_login_url','admin_login_url'
        ])->pluck('key','value')->toArray();

        return array_search($type,$data);
    }

    public static function time_date_format($data){
            $time=config('timeformat') ?? 'H:i';
        return  Carbon::parse($data)->locale(app()->getLocale())->translatedFormat('d M Y ' . $time);
    }
    public static function date_format($data){
        return  Carbon::parse($data)->locale(app()->getLocale())->translatedFormat('d M Y');
    }
    public static function time_format($data){
            $time=config('timeformat') ?? 'H:i';
        return  Carbon::parse($data)->locale(app()->getLocale())->translatedFormat($time);
    }


    public static function get_zones_name($zones){
        if(is_array($zones)){
            $data = Zone::whereIn('id',$zones)->pluck('name')->toArray();
        }else{
            $data = Zone::where('id',$zones)->pluck('name')->toArray();
        }
        $data = implode(', ', $data);
        return $data;
    }

    public static function get_restaurant_name($restaurant){
        if(is_array($restaurant)){
            $data = Restaurant::whereIn('id',$restaurant)->pluck('name')->toArray();
        }else{
            $data = Restaurant::where('id',$restaurant)->pluck('name')->toArray();
        }
        $data = implode(', ', $data);
        return $data;
    }

    public static function get_category_name($id){
        $id=Json_decode($id,true);
        $id=data_get($id,'0.id','NA');
        $data= Category::with('translations')->where('id',$id)->first()?->name ?? translate('messages.uncategorize');
        return $data;
    }
    public static function get_sub_category_name($id){
        $id=Json_decode($id,true);
        $id=data_get($id,'1.id','NA');
        return Category::where('id',$id)->first()?->name;
    }


    public static function get_food_variations($variations){
        try{
            $data=[];
            $data2=[];
            foreach((array)json_decode($variations,true) as $key => $choice){
                if(data_get($choice,'values',null)){
                    foreach( data_get($choice,'values',[]) as $k => $v){
                        $data2[$k] =  $v['label'];
                    // if(!next($choice['values'] )) {
                        //     $data2[$k] =  $v['label'].";";
                        // }
                        }
                        $data[$choice['name']] = $data2;
                    }
                }
            return str_ireplace(['\'', '"', '{','}', '[',']', '<', '>', '?'], ' ',json_encode($data));
            } catch (\Exception $ex) {
                info(["line___{$ex->getLine()}",$ex->getMessage()]);
                return 0;
            }

        }

        public static function get_customer_name($id){
            $user = User::where('id',$id)->first();

            return $user->f_name.' '.$user->l_name;
        }
        public static function get_addon_data($id){
            try{
                $data=[];
                $addon= AddOn::whereIn('id',json_decode($id, true))->get(['name','price'])->toArray();
                    foreach($addon as $key => $value){
                        $data[$key]= $value['name'] .' - ' .\App\CentralLogics\Helpers::format_currency($value['price']);
                    }
                return str_ireplace(['\'', '"', '{','}', '[',']', '<', '>', '?'], ' ',json_encode($data, JSON_UNESCAPED_UNICODE));
            } catch (\Exception $ex) {
                info(["line___{$ex->getLine()}",$ex->getMessage()]);
                return 0;
            }
        }
        public static function get_business_data($name)
        {
            $paymentmethod = BusinessSetting::where('key', $name)->first();
            return $paymentmethod?->value;
        }

        public static function add_or_update_translations($request, $key_data,$name_field ,$model_name, $data_id,$data_value ){
            try{
                $model = 'App\\Models\\'.$model_name;
                $default_lang = str_replace('_', '-', app()->getLocale());
                foreach ($request->lang as $index => $key) {
                    if ($default_lang == $key && !($request->{$name_field}[$index])) {
                        if ($key != 'default') {
                            Translation::updateorcreate(
                                [
                                    'translationable_type' =>  $model,
                                    'translationable_id' => $data_id,
                                    'locale' => $key,
                                    'key' => $key_data
                                ],
                                ['value' => $data_value]
                            );
                        }
                    } else {
                        if ($request->{$name_field}[$index] && $key != 'default') {
                            Translation::updateorcreate(
                                [
                                    'translationable_type' => $model,
                                    'translationable_id' => $data_id,
                                    'locale' => $key,
                                    'key' => $key_data
                                ],
                                ['value' => $request->{$name_field}[$index]]
                            );
                        }
                    }
                }
                return true;
        } catch(\Exception $e){
            info(["line___{$e->getLine()}",$e->getMessage()]);
            return false;
        }
    }

    public static function offline_payment_formater($user_data){
        $userInputs = [];

        $user_inputes=  json_decode($user_data->payment_info, true);
        $method_name= $user_inputes['method_name'];
        $method_id= $user_inputes['method_id'];

        foreach ($user_inputes as $key => $value) {
            if(!in_array($key,['method_name','method_id'])){
                $userInput = [
                'user_input' => $key,
                'user_data' => $value,
                ];
                $userInputs[] = $userInput;
            }
        }

        $data = [
        'status' => $user_data->status,
        'method_id' => $method_id,
        'method_name' => $method_name,
        'customer_note' => $user_data->customer_note,
        'admin_note' => $user_data->note,
        ];

        $result = [
        'input' => $userInputs,
        'data' => $data,
        'method_fields' =>json_decode($user_data->method_fields ,true),
        ];

        return $result;
    }

    public static function getDeliveryFee($restaurant): string
    {
        if(!request()->header('latitude') || !request()->header('longitude')){
            return 'out_of_range';
        }
            $zone = Zone::where('id', $restaurant->zone_id)->whereContains('coordinates', new Point(request()->header('latitude') && request()->header('longitude'), POINT_SRID))->first();
        if(!$zone) {
            return 'out_of_range';
        }

//        $map_api_key = BusinessSetting::where(['key' => 'map_api_key_server'])->first()?->value ?? null;
        if(isset($restaurant->distance) && $restaurant->distance > 0){
            $distance = $restaurant->distance / 1000;
            $distance=   round($distance,5);
        }
        elseif( $restaurant->latitude &&  $restaurant->longitude){
//            $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . $restaurant->latitude . ',' .  $restaurant->longitude . '&destinations=' . request()->header('latitude') . ',' . request()->header('longitude') . '&key=' . $map_api_key . '&mode=walking');
//            $distance=  $response->json();
//            $distance= gettype($distance) == 'array' ? $distance: json_decode($distance,true);
//            $distance = data_get($distance,'rows.0.elements.0.distance.value',0);
//            $distance = $distance / 1000;

        $originCoordinates =[
            $restaurant->latitude,
            $restaurant->longitude
        ];
        $destinationCoordinates =[
            request()->header('latitude') ,
            request()->header('longitude')
        ];
            $distance = self::get_distance($originCoordinates, $destinationCoordinates);
            $distance=   round($distance,5);
        } else {
            return 'out_of_range';
        }

        if($restaurant['self_delivery_system'] ==  1){

            if($restaurant->free_delivery == 1){
                return 'free_delivery';
            }
            if($restaurant->free_delivery_distance_status == 1 &&  $distance <= $restaurant->free_delivery_distance_value){
                return 'free_delivery';
            }

            $per_km_shipping_charge = $restaurant->per_km_shipping_charge ?? 0 ;
            $minimum_shipping_charge = $restaurant->minimum_shipping_charge ?? 0;
            $maximum_shipping_charge = $restaurant->maximum_shipping_charge ?? 0;
            $extra_charges= 0;
            $increased= 0;


        }
        else{
        $free_delivery_distance = BusinessSetting::where('key', 'free_delivery_distance')->first()?->value ?? 0;
            if($distance <= $free_delivery_distance){
                return 'free_delivery';
            }
            $per_km_shipping_charge = $zone->per_km_shipping_charge ?? 0;
            $minimum_shipping_charge = $zone->minimum_shipping_charge ?? 0;
            $maximum_shipping_charge = $zone->maximum_shipping_charge ?? 0;
            $increased= 0;
            if($zone->increased_delivery_fee_status == 1){
                $increased=$zone->increased_delivery_fee ?? 0;
            }
            $data = self::vehicle_extra_charge(distance_data:$distance);
            $extra_charges = (float) (isset($data) ? $data['extra_charge']  : 0);

        }

            $original_delivery_charge = ($distance * $per_km_shipping_charge > $minimum_shipping_charge) ? $distance * $per_km_shipping_charge + $extra_charges  : $minimum_shipping_charge + $extra_charges;
        if($increased > 0  && $original_delivery_charge > 0){
                $increased_fee = ($original_delivery_charge * $increased) / 100;
                $original_delivery_charge = $original_delivery_charge + $increased_fee;
        }
        return (string) $original_delivery_charge ;

    }




    public static function get_distance(array $originCoordinates,array $destinationCoordinates, $unit = 'K'): float
    {
        $lat1 = (float) $originCoordinates[0];
        $lat2 = (float) $destinationCoordinates[0];
        $lon1 = (float) $originCoordinates[1];
        $lon2 = (float) $destinationCoordinates[1];

        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
            return 0;
        } else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);
            if ($unit == "K") {
                return ($miles * 1.609344);
            } else if ($unit == "N") {
                return ($miles * 0.8684);
            } else {
                return $miles;
            }
        }
    }

public static function calculate_commission(float $price, $restaurant = null): float
{
    $percent = self::get_applicable_admin_charge_percent($restaurant);

    if ($percent <= 0) {
        return 0;
    }

    // ✅ $price already has the admin charge baked in (customer-facing price),
    // so back-calculate just the charge portion out of it
    return ($price * $percent) / (100 + $percent);
}
public static function apply_admin_charge_to_price($products, $is_array = true)
{
    // kept for backward compatibility, delegates to the priority-aware function above
    return self::inflate_price_with_admin_charge($products, $is_array);
}
public static function inflate_price_with_admin_charge($products, $multi_data = true)
{
    $global_logic = \App\Models\BusinessSetting::where('key', 'admin_commission_logic')->first()?->value ?? 'new';

    $global_status  = BusinessSetting::where('key', 'admin_charge_status')->first()?->value ?? 0;
    $global_percent = (float) (BusinessSetting::where('key', 'admin_charge_percent')->first()?->value ?? 0);

    $apply_to_single = function (&$product) use ($global_status, $global_percent, $global_logic) {
        if (!isset($product['price'])) {
            return;
        }

        // ✅ Read product's own restaurant (relation lazy/eager loaded both work)
        $restaurant = $product['restaurant'] ?? null;
        
        $restaurant_logic = null;
        if ($restaurant) {
            $restaurant_logic = is_array($restaurant) ? ($restaurant['admin_commission_logic'] ?? null) : ($restaurant->admin_commission_logic ?? null);
        }
        $logic = $restaurant_logic ?: $global_logic;
        if ($logic == 'old') {
            return;
        }

        $percent = 0.0;

        if ($restaurant && ($restaurant['restaurant_model'] ?? null) == 'commission' && (float) ($restaurant['comission'] ?? 0) > 0) {
            // ✅ Priority 1: this restaurant has its own Admin Commission turned ON
            $percent = (float) $restaurant['comission'];
        } elseif ($global_status == 1) {
            // ✅ Priority 2: fallback to global Business Setup Admin Charge
            $percent = $global_percent;
        }

        if ($percent > 0) {
            $multiplier = 1 + ($percent / 100);
            $product['price'] = round($product['price'] * $multiplier, config('round_up_to_digit'));
        }
    };

    if ($multi_data) {
        foreach ($products as &$product) {
            $apply_to_single($product);
        }
    } else {
        $apply_to_single($products);
    }

    return $products;
}
public static function get_applicable_admin_charge_percent($restaurant = null): float
{
    $global_logic = \App\Models\BusinessSetting::where('key', 'admin_commission_logic')->first()?->value ?? 'new';
    
    $restaurant_logic = null;
    if ($restaurant) {
        $restaurant_logic = is_array($restaurant) ? ($restaurant['admin_commission_logic'] ?? null) : ($restaurant->admin_commission_logic ?? null);
    }
    
    $logic = $restaurant_logic ?: $global_logic;
    if ($logic == 'old') {
        return 0.0;
    }

    if ($restaurant) {
        $restaurant_model = is_array($restaurant) ? ($restaurant['restaurant_model'] ?? null) : ($restaurant->restaurant_model ?? null);
        $comission = is_array($restaurant) ? ($restaurant['comission'] ?? 0) : ($restaurant->comission ?? 0);

        // ✅ Priority 1: Restaurant settings -> Admin Commission ON with a value
        if ($restaurant_model == 'commission' && (float) $comission > 0) {
            return (float) $comission;
        }
    }

    // ✅ Priority 2 (fallback): Business Setup -> Order Settings -> Admin Charges
    $admin_charge_status = BusinessSetting::where('key', 'admin_charge_status')->first()?->value ?? 0;
    if ($admin_charge_status == 1) {
        return (float) (BusinessSetting::where('key', 'admin_charge_percent')->first()?->value ?? 0);
    }

    return 0.0;
}

public static function format_food_availability($item)
{
    if (!$item || !isset($item->available_time_starts)) {
        return $item;
    }

    $current_time = now()->format('H:i:s');
    $time_slots = [];

    if ($item->available_time_starts && $item->available_time_ends) {
        $time_slots[] = [
            'start' => $item->available_time_starts,
            'end' => $item->available_time_ends
        ];
    }
    if ($item->available_time_starts2 && $item->available_time_ends2) {
        $time_slots[] = [
            'start' => $item->available_time_starts2,
            'end' => $item->available_time_ends2
        ];
    }
    if ($item->available_time_starts3 && $item->available_time_ends3) {
        $time_slots[] = [
            'start' => $item->available_time_starts3,
            'end' => $item->available_time_ends3
        ];
    }

    if (empty($time_slots)) {
        return $item;
    }

    $active_slot = null;
    $next_slot = null;

    foreach ($time_slots as $slot) {
        if ($current_time >= $slot['start'] && $current_time <= $slot['end']) {
            $active_slot = $slot;
            break;
        }
    }

    if ($active_slot) {
        $item->available_time_starts = substr($active_slot['start'], 0, 5);
        $item->available_time_ends = substr($active_slot['end'], 0, 5);
    } else {
        // Find the next slot starting today
        foreach ($time_slots as $slot) {
            if ($slot['start'] > $current_time) {
                if (!$next_slot || $slot['start'] < $next_slot['start']) {
                    $next_slot = $slot;
                }
            }
        }
        // If no next slot today, wrap around to the first slot of tomorrow (earliest start time)
        if (!$next_slot) {
            foreach ($time_slots as $slot) {
                if (!$next_slot || $slot['start'] < $next_slot['start']) {
                    $next_slot = $slot;
                }
            }
        }
        if ($next_slot) {
            $item->available_time_starts = substr($next_slot['start'], 0, 5);
            $item->available_time_ends = substr($next_slot['end'], 0, 5);
        }
    }

    return $item;
}

}
