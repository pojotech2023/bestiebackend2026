<?php

namespace App\CentralLogics;

use App\Models\Category;
use App\Models\Food;
use App\Models\Restaurant;

class CategoryLogic
{
    public static function parents()
    {
        return Category::where('position', 0)->get();
    }

    public static function child($parent_id)
    {
        return Category::where(['parent_id' => $parent_id])->get();
    }

    public static function products(array $additional_data)
    {
        $paginator = Food::whereHas('restaurant', function($query)use($additional_data){
            return $query->whereIn('zone_id', $additional_data['zone_id']);
        })
        ->whereHas('category',function($q)use($additional_data){
            return $q->whereId($additional_data['category_id'])->orWhere('parent_id', $additional_data['category_id']);
        })

        ->when($additional_data['veg'] == 1 && $additional_data['non_veg'] == 0 , function($query) {
            $query->where('veg',1);
        })

        ->when($additional_data['non_veg'] == 1 && $additional_data['veg'] == 0  , function($query) {
            $query->where('veg',0);
        })
        ->when($additional_data['avg_rating'] > 0 , function($query) use($additional_data) {
            $query->where('avg_rating','>=' , $additional_data['avg_rating']);
        })

        ->when($additional_data['top_rated'] == 1 , function($query) {
            $query->where('avg_rating','>=' , 4);
        })

        ->when($additional_data['end_price'] > 0 , function($query)  use($additional_data){
            $query->whereBetween('price', [ $additional_data['start_price'] , $additional_data['end_price'] ]);
        })

        ->active()->type($additional_data['type'])->latest()->paginate($additional_data['limit'], ['*'], 'page', $additional_data['offset']);

        $maxPrice = Food::whereHas('category',function($q)use($additional_data){
            return $q->whereId($additional_data['category_id'])->orWhere('parent_id', $additional_data['category_id']);
        })->max('price');

        return [
            'total_size' => $paginator->total(),
            'limit' => $additional_data['limit'],
            'offset' => $additional_data['offset'],
            'products' => $paginator->items(),
            'max_price'=> (float) $maxPrice??0
        ];
    }


    public static function restaurants(array $additional_data)
    {

        $paginator = Restaurant::withOpen($additional_data['longitude'] , $additional_data['latitude'] )->with(['discount'=>function($q){
            return $q->validate();
        }])->whereIn('zone_id', $additional_data['zone_id'] )
        ->whereHas('foods.category', function($query)use($additional_data){
            return $query->whereId( $additional_data['category_id'])->orWhere('parent_id', $additional_data['category_id']);
        })

        ->when($additional_data['veg'] == 1  , function($query) {
            $query->where('veg',1);
        })
        ->when($additional_data['non_veg'] == 1  , function($query) {
            $query->where('non_veg',1);
        })

        ->when($additional_data['avg_rating'] > 0 , function($query) use($additional_data) {
            $query->selectSub(function ($query) use ($additional_data){
                $query->selectRaw('AVG(reviews.rating)')
                    ->from('reviews')
                    ->join('food', 'food.id', '=', 'reviews.food_id')
                    ->whereColumn('food.restaurant_id', 'restaurants.id')
                    ->groupBy('food.restaurant_id')
                    ->havingRaw('AVG(reviews.rating) >= ?', [$additional_data['avg_rating']]);
            }, 'avg_r')->having('avg_r', '>=', $additional_data['avg_rating']);
        })

        ->when($additional_data['top_rated'] == 1 , function($query){
                    $query->selectSub(function ($query) {
                        $query->selectRaw('AVG(reviews.rating)')
                            ->from('reviews')
                            ->join('food', 'food.id', '=', 'reviews.food_id')
                            ->whereColumn('food.restaurant_id', 'restaurants.id')
                            ->groupBy('food.restaurant_id')
                            ->havingRaw('AVG(reviews.rating) > ?', [4]);
                    }, 'avg_r')->having('avg_r', '>=', 4);
                })

        ->active()->type($additional_data['type'])->latest()->paginate($additional_data['limit'], ['*'], 'page', $additional_data['offset']);

        return [
            'total_size' => $paginator->total(),
            'limit' => $additional_data['limit'],
            'offset' => $additional_data['offset'],
            'restaurants' => $paginator->items()
        ];
    }


    public static function all_products($id)
    {
        $cate_ids=[];
        array_push($cate_ids,(int)$id);
        foreach (CategoryLogic::child($id) as $ch1){
            array_push($cate_ids,$ch1['id']);
            foreach (CategoryLogic::child($ch1['id']) as $ch2){
                array_push($cate_ids,$ch2['id']);
            }
        }
        return Food::active()->whereIn('category_id', $cate_ids)->get();
    }


    public static function export_categories($collection){
        $data = [];
        foreach($collection as $key=>$item){
            $data[] = [
                'Id'=>$item->id,
                'Name'=>$item->name,
                'Image'=>$item->image,
                'ParentId'=>$item->parent_id,
                'Position'=>$item->position,
                'Priority'=>$item->priority,
                'Status'=>$item->status == 1 ? 'active' : 'inactive',
            ];
        }
        return $data;
    }
}
