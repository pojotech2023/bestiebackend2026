<?php

namespace App\CentralLogics;

use App\Models\Banner;
use App\Models\Food;
use App\Models\Restaurant;
use App\CentralLogics\Helpers;

class BannerLogic
{
    public static function get_banners($zone_id)
    {
        $banners = Banner::active()->whereIn('zone_id', $zone_id)->get();
        $data = [];
        foreach($banners as $banner)
        {
            if($banner->type=='restaurant_wise')
            {
                $restaurant = Restaurant::where(['id'=> $banner->data,'status' => 1])->first();
                if($restaurant){
                    $data[]=[
                        'id'=>$banner->id,
                        'title'=>$banner->title,
                        'type'=>$banner->type,
                        'image'=>$banner->image,
                        'restaurant'=> $restaurant?Helpers::restaurant_data_formatting(data:$restaurant, multi_data:false):null,
                        'food'=>null
                    ];
                }
            }
            if($banner->type=='item_wise')
            {
                $food = Food::active()->where(['id'=> $banner->data])->first();
                if($food){
                    $data[]=[
                        'id'=>$banner->id,
                        'title'=>$banner->title,
                        'type'=>$banner->type,
                        'image'=>$banner->image,
                        'restaurant'=> null,
                        'food'=> $food?Helpers::product_data_formatting(data:$food, multi_data:false, trans:false, local:app()->getLocale()):null,
                    ];
                }
            }
        }
        return $data;
    }
}
