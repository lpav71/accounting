<?php


namespace App\Services\SalesService;


use App\OrderDetailState;
use App\Product;
use DB;
use Illuminate\Support\Carbon;

class SalesService
{

    /**
     * среднее потребление товаров на 3 недели
     *
     * @param Product $product
     * @return int
     */
    public static function getCurrentAveragePacked(Product $product, int $day, int $latest_sales_days):int
    {
        $puckedStates = OrderDetailState::where('is_shipped', 1)->pluck('id')->toArray();
        //orderDetails with need states
        $twoMonthsPacked =  DB::table('order_detail_order_detail_state')
            ->join('order_details', 'order_details.id', 'order_detail_order_detail_state.order_detail_id')
            ->whereIn('order_detail_order_detail_state.order_detail_state_id', $puckedStates)
            ->where('order_details.product_id', $product->id)
            ->where('order_detail_order_detail_state.created_at', '>', Carbon::now()->subDay($latest_sales_days))
            ->count();
        if($day != 0 && $latest_sales_days != 0){
            return ceil($twoMonthsPacked / $latest_sales_days * $day);
        }else{
            return 0;
        }    
    }
}