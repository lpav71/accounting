<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangeOldPriceToPriceDiscount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $old_rows = DB::table('presta_products')->get();
        Schema::table('presta_products', function (Blueprint $table) { 
            $table->float('price_discount')->nullable();
            $table->dropColumn(['old_price']);
        });
        foreach($old_rows as $row){
            if(null === $row->old_price){
                $row->old_price = $row->price;
                $row->price = null;
            }
            DB::table('presta_products')
                ->where('id', $row->id)
                ->update(['price' => $row->old_price, 'price_discount' => $row->price]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {   
        $old_rows = DB::table('presta_products')->get();
        Schema::table('presta_products', function (Blueprint $table) {
            $table->float('old_price')->nullable();
            $table->dropColumn(['price_discount']);
        });
        foreach($old_rows as $row){
            if(null === $row->price_discount){
                $row->price_discount = $row->price;
                $row->price = null;
            }
            DB::table('presta_products')
                ->where('id', $row->id)
                ->update(['price' => $row->price_discount, 'old_price' => $row->price]);
        }
    }
}
