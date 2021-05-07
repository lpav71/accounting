<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserIdColumnToProductExchangeProductExchangeState extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product_exchange_product_exchange_state', function (Blueprint $table){
            $table->integer('user_id')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_exchange_product_exchange_state', function (Blueprint $table) {
            $table->dropColumn([
                'user_id'
            ]);
        });
    }
}
